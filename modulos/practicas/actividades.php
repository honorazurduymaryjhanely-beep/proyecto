<?php
/**
 * Actividades de una práctica
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Permite registrar actividades y muestra la lista de actividades existentes.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/Actividad.php';
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
    $nombre = trim($_POST['nombre_actividad'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (Practica::obtenerPorId($idPractica) === null) {
        mostrarMensaje('error', 'La práctica no existe.');
        redirigir('modulos/practicas/listar.php');
    }
    if ($nombre === '') {
        mostrarMensaje('error', 'El nombre de la actividad es obligatorio.');
        redirigir('modulos/practicas/actividades.php?id=' . $idPractica);
    }

    $nuevo = Actividad::crear($idPractica, $nombre, $descripcion, $idUsuario);
    if ($nuevo === null) {
        mostrarMensaje('error', 'No se pudo registrar la actividad.');
    } else {
        Auditoria::registrar($idUsuario, 'crear', 'actividades_practica', $nuevo, null, ['id_practica' => $idPractica]);
        mostrarMensaje('exito', 'Actividad registrada correctamente.');
    }
    redirigir('modulos/practicas/actividades.php?id=' . $idPractica);
}

$id = (int) ($_GET['id'] ?? 0);
$practica = Practica::obtenerPorId($id);
if ($practica === null) {
    mostrarMensaje('error', 'La práctica solicitada no existe.');
    redirigir('modulos/practicas/listar.php');
}
$actividades = Actividad::listarPorPractica($id);
$puedeEditar = tienePermiso('practicas', 'editar');

$tituloPagina = 'Actividades de la Práctica';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Actividades · <?= sanitizar($practica['codigo_practica']) ?></h2>
    <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $id ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<?php if ($puedeEditar): ?>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Registrar actividad</div>
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/practicas/actividades.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="id_practica" value="<?= (int) $id ?>">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Nombre de la actividad *</label>
                    <input type="text" class="form-control" name="nombre_actividad" data-validar="requerido" required>
                </div>
                <div class="col-md-7">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" name="descripcion">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-sm">Agregar actividad</button>
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
                <th>Actividad</th>
                <th>Descripción</th>
                <th>Registrado por</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($actividades)): ?>
                <tr><td colspan="4" class="text-center text-muted py-3">Sin actividades registradas.</td></tr>
            <?php else: ?>
                <?php foreach ($actividades as $a): ?>
                    <tr>
                        <td><?= sanitizar(formatearFecha($a['fecha_registro'])) ?></td>
                        <td><?= sanitizar($a['nombre_actividad']) ?></td>
                        <td><?= sanitizar($a['descripcion'] ?: '—') ?></td>
                        <td><?= sanitizar($a['registrado_por']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
