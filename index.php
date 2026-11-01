<?php
/**
 * Sitio web en PHP: seleccion de una localidad de Bogota y listado de los
 * paraderos zonales del SITP, tomados de Datos Abiertos Bogota.
 *
 * @author Steven Diaz Morales
 */
require __DIR__ . '/src/SitpClient.php';
$config = require __DIR__ . '/config.php';

$cliente     = new SitpClient($config);
$localidades = [];
$paraderos   = [];
$seleccion   = '';
$error       = '';
$consultado  = false;

function h($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

try {
    $localidades = $cliente->localidades();

    if (isset($_GET['localidad']) && trim($_GET['localidad']) !== '') {
        $resuelta = $cliente->resolverLocalidad($_GET['localidad']);
        if ($resuelta === null) {
            $error = 'La localidad seleccionada no es valida.';
        } else {
            $seleccion  = $resuelta;
            $paraderos  = $cliente->paraderosPorLocalidad($seleccion);
            $consultado = true;
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Paraderos SITP por localidad - Bogota</title>
    <link rel="stylesheet" href="assets/estilo.css">
</head>
<body>
<header>
    <h1>Paraderos zonales del SITP</h1>
    <p>Seleccione una localidad de Bogota para ver sus paraderos.</p>
</header>

<main>
    <form method="get" action="index.php">
        <label for="localidad">Localidad</label>
        <select name="localidad" id="localidad" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($localidades as $loc): ?>
                <option value="<?= h($loc) ?>"<?= $loc === $seleccion ? ' selected' : '' ?>><?= h($loc) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Mostrar paraderos</button>
    </form>

    <?php if ($error !== ''): ?>
        <p class="error"><?= h($error) ?></p>
    <?php endif; ?>

    <?php if ($consultado): ?>
        <h2>Localidad: <?= h($seleccion) ?></h2>
        <p class="resumen">
            <strong><?= count($paraderos) ?></strong> paraderos encontrados.
            <a href="api/paraderos.php?localidad=<?= urlencode($seleccion) ?>" target="_blank">Ver respuesta JSON de la API</a>
        </p>

        <?php if (count($paraderos) > 0): ?>
            <div class="tabla-contenedor">
                <table>
                    <thead>
                    <tr>
                        <th>Codigo</th><th>Nombre</th><th>Via</th><th>Direccion</th>
                        <th>Latitud</th><th>Longitud</th><th>Mapa</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($paraderos as $p): ?>
                        <tr>
                            <td><?= h($p['codigo']) ?></td>
                            <td><?= h($p['nombre']) ?></td>
                            <td><?= h($p['via']) ?></td>
                            <td><?= h($p['direccion']) ?></td>
                            <td><?= h($p['latitud']) ?></td>
                            <td><?= h($p['longitud']) ?></td>
                            <td>
                                <?php if ($p['latitud'] !== null && $p['longitud'] !== null): ?>
                                    <a target="_blank" href="https://www.google.com/maps?q=<?= h($p['latitud']) ?>,<?= h($p['longitud']) ?>">Ver</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p>No se encontraron paraderos para esta localidad.</p>
        <?php endif; ?>
    <?php endif; ?>
</main>

<footer>
    <p>Fuente: <?= h($cliente->fuenteUsada !== '' ? $cliente->fuenteUsada : 'Datos Abiertos Bogota') ?>.
       Proyecto AA5-EV03 - Steven Diaz Morales.</p>
</footer>
</body>
</html>
