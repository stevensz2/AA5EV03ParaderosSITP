# Documentacion de la API - Paraderos SITP por localidad

**Evidencia:** GA7-220501096-AA5-EV03. Diseno y desarrollo de servicios web - proyecto
**Aprendiz:** Steven Diaz Morales

## 1. Descripcion

La API consulta el conjunto de datos **"Paraderos Zonales del SITP"** de
[Datos Abiertos Bogota](https://datosabiertos.bogota.gov.co/en/dataset/paraderos-zonales-del-sitp)
(publicado por Transmilenio S.A.) y entrega los paraderos de una localidad especifica en formato JSON.
El sitio web en PHP (`index.php`) permite elegir cualquier localidad y muestra sus paraderos.

## 2. Origen de los datos

| Orden | Fuente | URL del servicio REST |
|---|---|---|
| 1 | Datos Abiertos Bogota - Paraderos Zonales del SITP | https://gis.transmilenio.gov.co/arcgis/rest/services/Zonal/consulta_paraderos_zonales/MapServer/0 |
| 2 (respaldo) | Mapa de Referencia Bogota - Paraderos Zonales SITP | https://serviciosgis.catastrobogota.gov.co/arcgis/rest/services/Mapa_Referencia/Mapa_Referencia/MapServer/8 |

Si la fuente 1 no responde, el sistema usa automaticamente la fuente 2. Las consultas se hacen
con la operacion `query` del servicio ArcGIS REST (filtro `where` por localidad y paginacion con
`resultOffset` / `resultRecordCount`).

## 3. Servicios

Base local (ejemplo con servidor de PHP): `http://localhost:8000`

### 3.1 Listar localidades

| Dato | Detalle |
|---|---|
| Metodo | GET |
| Ruta | `/api/localidades.php` |
| Parametros | Ninguno |

**Respuesta exitosa (200):**

```json
{
  "exito": true,
  "total": 19,
  "fuente": "Datos Abiertos Bogota - Paraderos Zonales del SITP (Transmilenio S.A.)",
  "localidades": ["Usaquen", "Chapinero", "..."]
}
```

(Los nombres se devuelven tal como vienen en los datos oficiales; el ejemplo es ilustrativo.)

### 3.2 Paraderos por localidad

| Dato | Detalle |
|---|---|
| Metodo | GET |
| Ruta | `/api/paraderos.php` |
| Parametro | `localidad` (obligatorio): nombre de la localidad, tal como lo devuelve el servicio 3.1. No distingue mayusculas |

**Ejemplo:** `GET /api/paraderos.php?localidad=Usaquen`

**Respuesta exitosa (200):**

```json
{
  "exito": true,
  "localidad": "Usaquen",
  "total": 1,
  "fuente": "Datos Abiertos Bogota - Paraderos Zonales del SITP (Transmilenio S.A.)",
  "paraderos": [
    {
      "codigo": "001A01",
      "nombre": "AC 100 - KR 13",
      "via": "AC 100",
      "direccion": "Avenida Calle 100 Carrera 13",
      "localidad": "Usaquen",
      "latitud": 4.684,
      "longitud": -74.046
    }
  ]
}
```

(Los valores del ejemplo son ilustrativos; la respuesta real trae todos los paraderos de la localidad.)

**Campos de cada paradero**

| Campo | Descripcion |
|---|---|
| codigo | Codigo del paradero |
| nombre | Nombre del paradero |
| via | Via donde se ubica |
| direccion | Direccion del paradero |
| localidad | Localidad de Bogota |
| latitud / longitud | Coordenadas en WGS84 (EPSG:4326) |

## 4. Codigos de respuesta

| Codigo | Cuando ocurre | Ejemplo de cuerpo |
|---|---|---|
| 200 | Consulta correcta | `{"exito": true, ...}` |
| 400 | Falta el parametro `localidad` | `{"exito": false, "mensaje": "Debe enviar el parametro localidad"}` |
| 404 | La localidad no existe | `{"exito": false, "mensaje": "La localidad no existe. Consulte /api/localidades.php..."}` |
| 405 | Se uso un metodo distinto de GET | `{"exito": false, "mensaje": "Metodo no permitido. Use GET"}` |
| 502 | Las fuentes de Datos Abiertos no respondieron | `{"exito": false, "mensaje": "No fue posible consultar..."}` |

## 5. Estructura del proyecto

```
AA5_EV03_ParaderosSITP/
  index.php            Sitio web: selecciona localidad y muestra paraderos
  config.php           Fuentes de datos y parametros
  src/SitpClient.php   Cliente que consume Datos Abiertos Bogota
  api/localidades.php  Servicio 1
  api/paraderos.php    Servicio 2
  assets/estilo.css    Estilos
  docs/                Esta documentacion
```
