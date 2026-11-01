<?php
/**
 * Configuracion del proyecto AA5-EV03 - Paraderos SITP por localidad.
 *
 * Se declaran las fuentes de datos (servicios REST de ArcGIS publicados en
 * Datos Abiertos Bogota). Si la primera no responde, se usa la siguiente.
 *
 * @author Steven Diaz Morales
 */
return [
    'fuentes' => [
        [
            // Recurso "Servicio REST" del conjunto de datos
            // "Paraderos Zonales del SITP" en datosabiertos.bogota.gov.co
            'nombre' => 'Datos Abiertos Bogota - Paraderos Zonales del SITP (Transmilenio S.A.)',
            'url'    => 'https://gis.transmilenio.gov.co/arcgis/rest/services/Zonal/consulta_paraderos_zonales/MapServer/0',
        ],
        [
            // Misma informacion de Transmilenio publicada en el Mapa de Referencia de Bogota
            'nombre' => 'Mapa de Referencia Bogota - Paraderos Zonales SITP (Transmilenio S.A.)',
            'url'    => 'https://serviciosgis.catastrobogota.gov.co/arcgis/rest/services/Mapa_Referencia/Mapa_Referencia/MapServer/8',
        ],
    ],
    'timeout'       => 20,    // segundos de espera por consulta
    'cache_ttl'     => 3600,  // segundos que se guarda la lista de localidades
    'page_size'     => 1000,  // registros pedidos por pagina al servicio
    'verificar_ssl' => true,  // poner false SOLO si XAMPP/Windows falla con el certificado
];
