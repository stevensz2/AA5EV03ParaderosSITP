<?php
/**
 * Cliente que consume el servicio REST de Datos Abiertos Bogota con los
 * paraderos zonales del SITP y los entrega listos para la API y el sitio web.
 *
 * @author Steven Diaz Morales
 */
class SitpClient
{
    private $fuentes;
    private $timeout;
    private $cacheTtl;
    private $pageSize;
    private $verificarSsl;

    /** Nombre de la fuente que respondio en la ultima consulta. */
    public $fuenteUsada = '';

    /** Atributos sin procesar del primer paradero (sirve para depurar nombres de campo). */
    public $ejemploCrudo = [];

    public function __construct(array $config)
    {
        $this->fuentes      = $config['fuentes'];
        $this->timeout      = isset($config['timeout']) ? (int) $config['timeout'] : 20;
        $this->cacheTtl     = isset($config['cache_ttl']) ? (int) $config['cache_ttl'] : 3600;
        $this->pageSize     = isset($config['page_size']) ? (int) $config['page_size'] : 1000;
        $this->verificarSsl = isset($config['verificar_ssl']) ? (bool) $config['verificar_ssl'] : true;
    }

    /** Hace una peticion GET y devuelve el cuerpo como texto. */
    private function httpGet($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, 'AA5-EV03-ParaderosSITP/1.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verificarSsl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verificarSsl ? 2 : 0);
            $cuerpo = curl_exec($ch);
            $error  = curl_error($ch);
            $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($cuerpo === false) {
                throw new Exception('Error de conexion: ' . $error);
            }
            if ($codigo >= 400) {
                throw new Exception('El servicio respondio con HTTP ' . $codigo);
            }
            return $cuerpo;
        }

        $contexto = stream_context_create([
            'http' => ['timeout' => $this->timeout, 'header' => "User-Agent: AA5-EV03-ParaderosSITP/1.0\r\n"],
        ]);
        $cuerpo = @file_get_contents($url, false, $contexto);
        if ($cuerpo === false) {
            throw new Exception('No se pudo conectar con el servicio de datos');
        }
        return $cuerpo;
    }

    /** Ejecuta una consulta (query) sobre una capa de ArcGIS y devuelve el JSON como arreglo. */
    private function consultar($urlCapa, array $parametros)
    {
        $parametros['f'] = 'json';
        $url  = $urlCapa . '/query?' . http_build_query($parametros);
        $data = json_decode($this->httpGet($url), true);
        if (!is_array($data)) {
            throw new Exception('Respuesta no valida del servicio');
        }
        if (isset($data['error'])) {
            $msg = isset($data['error']['message']) ? $data['error']['message'] : 'error desconocido';
            throw new Exception('El servicio reporto: ' . $msg);
        }
        return $data;
    }

    /** Descubre cual es el nombre real del campo de localidad en la capa. */
    private function campoLocalidad($urlCapa)
    {
        $data = json_decode($this->httpGet($urlCapa . '?f=json'), true);
        if (is_array($data) && isset($data['fields'])) {
            // Primero coincidencia exacta, luego cualquier campo que contenga "localidad"
            foreach ($data['fields'] as $campo) {
                if (isset($campo['name']) && strcasecmp($campo['name'], 'localidad') === 0) {
                    return $campo['name'];
                }
            }
            foreach ($data['fields'] as $campo) {
                if (isset($campo['name']) && stripos($campo['name'], 'localidad') !== false) {
                    return $campo['name'];
                }
            }
        }
        return 'LOCALIDAD';
    }

    /** Devuelve la lista de localidades que existen en los datos. */
    public function localidades()
    {
        $cache = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sitp_localidades_' . md5(serialize($this->fuentes)) . '.json';
        if (is_file($cache) && (time() - filemtime($cache)) < $this->cacheTtl) {
            $guardado = json_decode(@file_get_contents($cache), true);
            if (isset($guardado['localidades']) && count($guardado['localidades']) > 0) {
                $this->fuenteUsada = isset($guardado['fuente']) ? $guardado['fuente'] : '';
                return $guardado['localidades'];
            }
        }

        $errores = [];
        foreach ($this->fuentes as $fuente) {
            try {
                $campo = $this->campoLocalidad($fuente['url']);
                $data  = $this->consultar($fuente['url'], [
                    'where'                => '1=1',
                    'outFields'            => $campo,
                    'returnGeometry'       => 'false',
                    'returnDistinctValues' => 'true',
                ]);
                $lista = [];
                if (isset($data['features'])) {
                    foreach ($data['features'] as $f) {
                        if (isset($f['attributes'][$campo])) {
                            $valor = trim((string) $f['attributes'][$campo]);
                            if ($valor !== '') {
                                $lista[$valor] = true;
                            }
                        }
                    }
                }
                $lista = array_map('strval', array_keys($lista));
                if (count($lista) === 0) {
                    throw new Exception('La fuente no devolvio localidades');
                }
                sort($lista);
                $this->fuenteUsada = $fuente['nombre'];
                @file_put_contents($cache, json_encode(['fuente' => $fuente['nombre'], 'localidades' => $lista]));
                return $lista;
            } catch (Exception $e) {
                $errores[] = $fuente['nombre'] . ': ' . $e->getMessage();
            }
        }
        throw new Exception('No fue posible consultar los datos. ' . implode(' | ', $errores));
    }

    /** Busca la localidad (sin importar mayusculas) en la lista oficial. Devuelve null si no existe. */
    public function resolverLocalidad($texto)
    {
        $texto = trim((string) $texto);
        if ($texto === '' || strlen($texto) > 60) {
            return null;
        }
        foreach ($this->localidades() as $localidad) {
            if (strcasecmp($localidad, $texto) === 0) {
                return $localidad;
            }
        }
        return null;
    }

    /** Devuelve todos los paraderos de una localidad (ya validada con resolverLocalidad). */
    public function paraderosPorLocalidad($localidad)
    {
        $errores = [];
        $vacio   = null;

        foreach ($this->fuentes as $fuente) {
            try {
                $campo = $this->campoLocalidad($fuente['url']);
                $where = $campo . " = '" . str_replace("'", "''", $localidad) . "'";
                $lista = [];
                $desde = 0;

                for ($i = 0; $i < 30; $i++) {
                    $data = $this->consultar($fuente['url'], [
                        'where'             => $where,
                        'outFields'         => '*',
                        'returnGeometry'    => 'true',
                        'outSR'             => '4326',
                        'resultOffset'      => $desde,
                        'resultRecordCount' => $this->pageSize,
                    ]);
                    $features = isset($data['features']) ? $data['features'] : [];
                    if (count($features) === 0) {
                        break;
                    }
                    if (empty($this->ejemploCrudo) && isset($features[0]['attributes'])) {
                        $this->ejemploCrudo = $features[0]['attributes'];
                    }
                    foreach ($features as $f) {
                        $geom    = isset($f['geometry']) ? $f['geometry'] : null;
                        $lista[] = $this->normalizar($f['attributes'], $geom, $campo);
                    }
                    $desde += count($features);
                    $hayMas = !empty($data['exceededTransferLimit']) || count($features) >= $this->pageSize;
                    if (!$hayMas) {
                        break;
                    }
                }

                if (count($lista) > 0) {
                    $this->fuenteUsada = $fuente['nombre'];
                    return $lista;
                }
                $vacio = $fuente['nombre'];
            } catch (Exception $e) {
                $errores[] = $fuente['nombre'] . ': ' . $e->getMessage();
            }
        }

        if ($vacio !== null) {
            $this->fuenteUsada = $vacio;
            return [];
        }
        throw new Exception('No fue posible consultar los paraderos. ' . implode(' | ', $errores));
    }

    /** Convierte un registro del servicio en un arreglo con nombres de campo propios y estables. */
    private function normalizar(array $atributos, $geometria, $campoLocalidad)
    {
        $m = [];
        foreach ($atributos as $clave => $valor) {
            $m[strtoupper($clave)] = $valor;
        }
        $tomar = function (array $candidatos) use ($m) {
            foreach ($candidatos as $c) {
                if (isset($m[$c]) && trim((string) $m[$c]) !== '') {
                    return trim((string) $m[$c]);
                }
            }
            return '';
        };

        $lat = $tomar(['LATITUD', 'LAT']);
        $lon = $tomar(['LONGITUD', 'LON', 'LONG']);
        if (($lat === '' || $lon === '') && is_array($geometria) && isset($geometria['x'], $geometria['y'])) {
            $lon = (string) $geometria['x'];
            $lat = (string) $geometria['y'];
        }

        $direccion = $tomar(['DIRECCION_', 'DIRECCION', 'DIRECCIÓN']);
        if ($direccion === '') {
            foreach ($m as $clave => $valor) {
                if (stripos($clave, 'DIREC') !== false && trim((string) $valor) !== '') {
                    $direccion = trim((string) $valor);
                    break;
                }
            }
        }

        return [
            'codigo'    => $tomar(['CENEFA', 'CODIGO', 'COD_PARADERO', 'CODIGO_PARADERO']),
            'nombre'    => $tomar(['NOMBRE', 'NOMBRE_PARADERO']),
            'via'       => $tomar(['VIA', 'VÍA']),
            'direccion' => $direccion,
            'localidad' => $tomar([strtoupper($campoLocalidad), 'LOCALIDAD']),
            'latitud'   => is_numeric($lat) ? round((float) $lat, 6) : null,
            'longitud'  => is_numeric($lon) ? round((float) $lon, 6) : null,
        ];
    }
}
