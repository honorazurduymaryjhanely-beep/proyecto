<?php
/**
 * Asignación de docente supervisor a una práctica
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra el formulario de asignación (docentes activos de la carrera)
 * y procesa la asignación/reasignación conservando el historial.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/DocenteSupervisor.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarPermiso('practicas', 'editar');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$idUsuario = (int) $_SESSION['id_usuario'];
$restringirCarrera = restriccionCarreraActual();

// Procesar el envío del formulario.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
        mostrarMensaje('error', 'Token de seguridad inválido.');
        redirigir('modulos/practicas/listar.php');
    }
    $idPractica = (int) ($_POST['id_practica'] ?? 0);
    $idDocente = (int) ($_POST['id_docente'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');

    $practica = Practica::obtenerPorId($idPractica);

    if ($practica === null) {
        mostrarMensaje('error', 'La práctica no existe.');
        redirigir('modulos/practicas/listar.php');
    }

    if ($restringirCarrera !== null && normalizarCarrera($practica['carrera']) !== normalizarCarrera($restringirCarrera)) {
        mostrarMensaje('error', 'No tiene permisos para modificar prácticas de otra carrera.');
        redirigir('modulos/practicas/listar.php');
    }

    // Validar docente activo
    $docente = DocenteSupervisor::obtenerPorId($idDocente);
    if ($docente === null || (int) $docente['estado'] !== 1) {
        mostrarMensaje('error', 'Debe seleccionar un docente supervisor activo.');
        redirigir('modulos/practicas/asignar_supervisor.php?id=' . $idPractica);
    }

    // Validar que el docente pertenezca a la misma carrera de la práctica (discriminación estricta por carrera)
    if (normalizarCarrera($docente['carrera']) !== normalizarCarrera($practica['carrera'])) {
        mostrarMensaje('error', 'El docente seleccionado (' . sanitizar($docente['carrera']) . ') no pertenece a la carrera de la práctica (' . sanitizar($practica['carrera']) . ').');
        redirigir('modulos/practicas/asignar_supervisor.php?id=' . $idPractica);
    }

    if (!Practica::asignarSupervisor($idPractica, $idDocente, $idUsuario, $observaciones)) {
        mostrarMensaje('error', 'No se pudo asignar el supervisor.');
        redirigir('modulos/practicas/asignar_supervisor.php?id=' . $idPractica);
    }

    Auditoria::registrar($idUsuario, 'modificar', 'asignaciones_supervision', $idPractica, null, [
        'id_docente' => $idDocente,
        'nombre_docente' => $docente['nombre_completo'],
    ]);
    mostrarMensaje('exito', 'Supervisor asignado correctamente.');
    redirigir('modulos/practicas/ver.php?id=' . $idPractica);
}

// Mostrar el formulario.
$id = (int) ($_GET['id'] ?? 0);
$practica = Practica::obtenerPorId($id);
if ($practica === null) {
    mostrarMensaje('error', 'La práctica solicitada no existe.');
    redirigir('modulos/practicas/listar.php');
}

if ($restringirCarrera !== null && normalizarCarrera($practica['carrera']) !== normalizarCarrera($restringirCarrera)) {
    mostrarMensaje('error', 'No tiene permisos para acceder a prácticas de otra carrera.');
    redirigir('modulos/practicas/listar.php');
}

// Filtrar docentes supervisores de forma estricta según la carrera del estudiante de la práctica
$docentes = DocenteSupervisor::obtenerActivos($practica['carrera']);
$supervisorActual = Practica::obtenerSupervisorActual($id);

$tituloPagina = 'Asignar Supervisor';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Asignar supervisor · <?= sanitizar($practica['codigo_practica']) ?></h2>
    <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $id ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-header bg-white py-3">
        <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0 fw-bold">Designación de Docente Supervisor de Práctica</h6>
            <span class="badge bg-primary"><?= sanitizar($practica['codigo_practica']) ?></span>
        </div>
        <p class="text-muted small mb-0 mt-1">
            Como <strong>Docente Encargado</strong> o Administrador, seleccione al docente que realizará las visitas de supervisión y seguimiento del estudiante en la institución receptora.
        </p>
    </div>
    <div class="card-body">
        <div class="alert alert-light border mb-4">
            <div class="row g-2">
                <div class="col-md-6">
                    <span class="text-muted small">Estudiante:</span><br>
                    <strong><?= sanitizar($practica['estudiante']) ?></strong>
                    <span class="badge bg-light text-dark border"><?= sanitizar($practica['codigo_estudiante']) ?></span>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small">Carrera:</span><br>
                    <span class="badge bg-secondary"><?= sanitizar($practica['carrera']) ?></span>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small">Institución receptora:</span><br>
                    <strong><?= sanitizar($practica['institucion']) ?></strong>
                </div>
                <div class="col-md-6">
                    <span class="text-muted small">Supervisor actual:</span><br>
                    <?php if ($supervisorActual): ?>
                        <span class="text-success fw-medium">
                            <i class="bi bi-person-check-fill"></i> <?= sanitizar($supervisorActual['nombre_completo']) ?>
                            (<?= sanitizar($supervisorActual['telefono'] ?: 'Sin tel.') ?>)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark"><i class="bi bi-person-x"></i> Sin supervisor asignado aún</span>
                    <?php endif; ?>
                </div>
                <div class="col-12 mt-2 pt-2 border-top">
                    <span class="text-muted small">Asignado por:</span>
                    <strong><?= sanitizar($_SESSION['nombre_completo'] ?? 'Usuario') ?></strong> (<?= sanitizar(etiquetaRol($_SESSION['rol'] ?? '')) ?>)
                </div>
            </div>
        </div>

        <form action="<?= $urlBase ?>/modulos/practicas/asignar_supervisor.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="id_practica" value="<?= (int) $id ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-medium">Docente Supervisor de <?= sanitizar($practica['carrera']) ?> *</label>
                    <select class="form-select" name="id_docente" data-validar="requerido" required <?= empty($docentes) ? 'disabled' : '' ?>>
                        <option value="">-- Seleccione el docente supervisor --</option>
                        <?php foreach ($docentes as $d): ?>
                            <option value="<?= (int) $d['id_docente'] ?>" <?= $supervisorActual && (int) $supervisorActual['id_docente'] === (int) $d['id_docente'] ? 'selected' : '' ?>>
                                <?= sanitizar($d['nombre_completo']) ?> (<?= sanitizar($d['carrera']) ?>)<?= !empty($d['telefono']) ? ' — Tel: ' . sanitizar($d['telefono']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($docentes)): ?>
                        <div class="form-text text-danger fw-semibold">
                            <i class="bi bi-exclamation-triangle"></i> No hay docentes supervisores registrados en <?= sanitizar($practica['carrera']) ?>. El Administrador debe registrar docentes para esta carrera.
                        </div>
                    <?php else: ?>
                        <div class="form-text">Solo se listan docentes de la carrera <strong><?= sanitizar($practica['carrera']) ?></strong>.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-medium">Observaciones de la asignación</label>
                    <input type="text" class="form-control" name="observaciones"
                           placeholder="Motivo o detalle de la designación...">
                    <div class="form-text">Opcional. Se registrará en el historial de asignaciones.</div>
                </div>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $id ?>" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">Guardar Asignación</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
