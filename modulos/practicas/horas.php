<?php
/**
 * Registro de horas cumplidas de una práctica
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Permite registrar horas por día (1-12) y muestra el historial con el total
 * acumulado y el porcentaje de avance respecto a las horas requeridas.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/HorasCumplidas.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarPermiso('practicas', 'editar');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$idUsuario = (int) $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
        mostrarMensaje('error', 'Token de seguridad inválido.');
        redirigir('modulos/practicas/listar.php');
    }
    $idPractica = (int) ($_POST['id_practica'] ?? 0);
    $fecha = trim($_POST['fecha'] ?? '');
    $cantidad = (float) ($_POST['cantidad_horas'] ?? 0);
    $descripcion = trim($_POST['descripcion_actividades'] ?? '');

    $practica = Practica::obtenerPorId($idPractica);
    if ($practica === null) {
        mostrarMensaje('error', 'La práctica no existe.');
        redirigir('modulos/practicas/listar.php');
    }

    $errores = [];
    if (!validarFecha($fecha)) {
        $errores[] = 'La fecha no es válida.';
    }
    if ($cantidad <= 0 || $cantidad > 12) {
        $errores[] = 'La cantidad de horas debe ser mayor a 0 y hasta 12.';
    }
    if ($descripcion === '') {
        $errores[] = 'La descripción de actividades es obligatoria.';
    }

    if ($errores) {
        mostrarMensaje('error', implode(' ', $errores));
        redirigir('modulos/practicas/horas.php?id=' . $idPractica);
    }

    $nuevo = HorasCumplidas::registrar($idPractica, $fecha, $cantidad, $descripcion, $idUsuario);
    if ($nuevo === null) {
        mostrarMensaje('error', 'No se pudieron registrar las horas.');
        redirigir('modulos/practicas/horas.php?id=' . $idPractica);
    }

    Auditoria::registrar($idUsuario, 'crear', 'horas_cumplidas', $nuevo, null, ['id_practica' => $idPractica, 'horas' => $cantidad]);

    // Notificar si se alcanzó o superó el total requerido.
    $total = HorasCumplidas::calcularTotalHoras($idPractica);
    if ($total >= (float) $practica['total_horas_requeridas']) {
        mostrarMensaje('exito', 'Horas registradas. El estudiante ha alcanzado el total de horas requeridas.');
    } else {
        mostrarMensaje('exito', 'Horas registradas correctamente.');
    }
    redirigir('modulos/practicas/horas.php?id=' . $idPractica);
}

$id = (int) ($_GET['id'] ?? 0);
$practica = Practica::obtenerPorId($id);
if ($practica === null) {
    mostrarMensaje('error', 'La práctica solicitada no existe.');
    redirigir('modulos/practicas/listar.php');
}
$registros = HorasCumplidas::listarPorPractica($id);
$total = HorasCumplidas::calcularTotalHoras($id);
$porcentaje = HorasCumplidas::calcularPorcentajeAvance($id);
$puedeEditar = tienePermiso('practicas', 'editar');

$tituloPagina = 'Horas Cumplidas';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Horas cumplidas · <?= sanitizar($practica['codigo_practica']) ?></h2>
    <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $id ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between mb-1">
            <span><strong><?= number_format($total, 2) ?></strong> de <?= (int) $practica['total_horas_requeridas'] ?> horas requeridas</span>
            <span><?= $porcentaje ?>%</span>
        </div>
        <div class="progress" role="progressbar" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: <?= $porcentaje ?>%"><?= $porcentaje ?>%</div>
        </div>
    </div>
</div>

<?php if ($puedeEditar): ?>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Registrar horas</div>
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/practicas/horas.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="id_practica" value="<?= (int) $id ?>">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Fecha *</label>
                    <input type="date" class="form-control" name="fecha" data-validar="requerido" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Horas *</label>
                    <input type="number" class="form-control" name="cantidad_horas" min="0.5" max="12" step="0.5" data-validar="requerido" required>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Descripción de actividades *</label>
                    <input type="text" class="form-control" name="descripcion_actividades" data-validar="requerido" required>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm">Registrar horas</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="tabla-personalizada">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Horas</th>
                <th>Descripción</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">Sin registros de horas.</td></tr>
            <?php else: ?>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= sanitizar(formatearFecha($r['fecha'])) ?></td>
                        <td><?= number_format((float) $r['cantidad_horas'], 2) ?></td>
                        <td><?= sanitizar($r['descripcion_actividades']) ?></td>
                        <td><?= sanitizar($r['registrado_por']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
