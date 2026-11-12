<?php
/**
 * Respaldos de la base de datos
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Permite generar un respaldo manual y muestra el historial de respaldos.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Respaldo.php';

verificarPermiso('respaldos', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$respaldos = Respaldo::listarRespaldos();
$puedeCrear = tienePermiso('respaldos', 'crear');

/**
 * Formatear un tamaño en bytes a una unidad legible.
 */
function formatearTamano(?int $bytes): string
{
    $bytes = (int) $bytes;
    if ($bytes < 1024) {
        return $bytes . ' B';
    }
    $unidades = ['KB', 'MB', 'GB'];
    $valor = $bytes / 1024;
    $i = 0;
    while ($valor >= 1024 && $i < count($unidades) - 1) {
        $valor /= 1024;
        $i++;
    }
    return number_format($valor, 2) . ' ' . $unidades[$i];
}

$tituloPagina = 'Respaldos';
$moduloActivo = 'respaldos';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Respaldos de la base de datos</h2>
    <?php if ($puedeCrear): ?>
        <form action="<?= $urlBase ?>/modulos/respaldos/procesar.php" method="post"
              onsubmit="return confirm('¿Generar un respaldo de la base de datos ahora?');">
            <?= campoCSRF() ?>
            <button type="submit" class="btn btn-primary btn-sm">Generar respaldo</button>
        </form>
    <?php endif; ?>
</div>

<div class="tabla-personalizada">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Tamaño</th>
                <th>Fecha</th>
                <th>Generado por</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($respaldos)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No hay respaldos generados.</td></tr>
            <?php else: ?>
                <?php foreach ($respaldos as $r): ?>
                    <tr>
                        <td><?= sanitizar($r['nombre_archivo']) ?></td>
                        <td><?= sanitizar(formatearTamano($r['tamano_bytes'])) ?></td>
                        <td><?= sanitizar(formatearFecha($r['fecha_creacion'], true)) ?></td>
                        <td><?= sanitizar($r['usuario']) ?></td>
                        <td class="text-end acciones-tabla">
                            <a href="<?= $urlBase ?>/modulos/respaldos/descargar.php?archivo=<?= rawurlencode($r['nombre_archivo']) ?>"
                               class="btn btn-outline-primary">Descargar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
