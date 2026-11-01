<?php
/**
 * Servicio 2: GET /api/paraderos.php?localidad=NOMBRE
 * Devuelve los paraderos zonales del SITP de una localidad especifica.
 *
 * @author Steven Diaz Morales
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require __DIR__ . '/../src/SitpClient.php';
$config = require __DIR__ . '/../config.php';
$opciones = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['exito' => false, 'mensaje' => 'Metodo no permitido. Use GET'], $opciones);
    exit;
}

$texto = isset($_GET['localidad']) ? trim($_GET['localidad']) : '';
if ($texto === '') {
    http_response_code(400);
    echo json_encode(['exito' => false, 'mensaje' => 'Debe enviar el parametro localidad'], $opciones);
    exit;
}

try {
    $cliente   = new SitpClient($config);
    $localidad = $cliente->resolverLocalidad($texto);
    if ($localidad === null) {
        http_response_code(404);
        echo json_encode([
            'exito'   => false,
            'mensaje' => 'La localidad no existe. Consulte /api/localidades.php para ver las disponibles',
        ], $opciones);
        exit;
    }

    $paraderos = $cliente->paraderosPorLocalidad($localidad);
    $respuesta = [
        'exito'     => true,
        'localidad' => $localidad,
        'total'     => count($paraderos),
        'fuente'    => $cliente->fuenteUsada,
        'paraderos' => $paraderos,
    ];
    // Con &debug=1 se muestran los campos originales del servicio (util para revisar nombres de campo)
    if (isset($_GET['debug'])) {
        $respuesta['campos_originales'] = $cliente->ejemploCrudo;
    }
    echo json_encode($respuesta, $opciones);
} catch (Exception $e) {
    http_response_code(502);
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()], $opciones);
}
