<?php
/**
 * Servicio 1: GET /api/localidades.php
 * Devuelve la lista de localidades de Bogota que tienen paraderos zonales del SITP.
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

try {
    $cliente     = new SitpClient($config);
    $localidades = $cliente->localidades();
    echo json_encode([
        'exito'       => true,
        'total'       => count($localidades),
        'fuente'      => $cliente->fuenteUsada,
        'localidades' => $localidades,
    ], $opciones);
} catch (Exception $e) {
    http_response_code(502);
    echo json_encode(['exito' => false, 'mensaje' => $e->getMessage()], $opciones);
}
