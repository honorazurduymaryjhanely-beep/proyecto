<?php
/**
 * Gestión de Docentes Supervisores y Estudiantes Asignados
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Permite al Docente Encargado y Administrador:
 * 1. Ver los docentes de la carrera y habilitar/asignar quiénes actúan como "Docente Supervisor".
 * 2. Visualizar la lista de estudiantes asignados a cada Docente Supervisor (relación 1 a varios).
 * 3. Acceder rápidamente a la asignación de estudiantes para cada supervisor.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/DocenteSupervisor.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/Carrera.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarPermiso('practicas', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$idUsuario = (int) $_SESSION['id_usuario'];
$restringirCarrera = restriccionCarreraActual();
$carreraActual = $restringirCarrera ?? trim($_GET['carrera'] ?? 'Sistemas Informáticos');
$carreras = Carrera::obtenerActivas();

// Procesar habilitación/deshabilitación de un docente como Docente Supervisor
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['accion'])) {
    if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
        mostrarMensaje('error', 'Token de seguridad inválido.');
        redirigir('modulos/practicas/supervisores.php');
    }

    if ($_POST['accion'] === 'cambiar_estado_supervisor') {
        verificarPermiso('practicas', 'editar');
        $idDocente = (int) ($_POST['id_docente'] ?? 0);
        $doc = DocenteSupervisor::obtenerPorId($idDocente);

        if (!$doc) {
            mostrarMensaje('error', 'El docente no existe.');
            redirigir('modulos/practicas/supervisores.php');
        }

        if ($restringirCarrera !== null && normalizarCarrera($doc['carrera']) !== normalizarCarrera($restringirCarrera)) {
            mostrarMensaje('error', 'No tiene permisos para modificar docentes de otra carrera.');
            redirigir('modulos/practicas/supervisores.php');
        }

        $nuevoEstado = (int) $doc['estado'] === 1 ? 0 : 1;
        DocenteSupervisor::cambiarEstado($idDocente, $nuevoEstado);

        Auditoria::registrar($idUsuario, 'modificar', 'docentes_supervisores', $idDocente, [
            'estado' => $doc['estado']
        ], [
            'estado' => $nuevoEstado,
            'accion' => $nuevoEstado === 1 ? 'asignado_como_supervisor' : 'deshabilitado_de_supervision'
        ]);

        $msg = $nuevoEstado === 1 
            ? 'Docente asignado y habilitado como Docente Supervisor.'
            : 'Docente deshabilitado de la función de Docente Supervisor.';
        mostrarMensaje('exito', $msg);
        redirigir('modulos/practicas/supervisores.php' . ($restringirCarrera === null ? '?carrera=' . urlencode($carreraActual) : ''));
    }
}

// Obtener todos los docentes de la carrera (tanto activos como inactivos)
$db = Database::obtenerInstancia();
$sqlDocentes = "SELECT d.*, 
                       (SELECT COUNT(*) FROM asignaciones_supervision a 
                        INNER JOIN practicas_laborales p ON p.id_practica = a.id_practica
                        WHERE a.id_docente = d.id_docente AND a.estado = 1) AS total_estudiantes
                FROM docentes_supervisores d
                WHERE (d.carrera = :carrera OR d.carrera LIKE :carrera_like)
                ORDER BY d.estado DESC, d.nombre_completo ASC";

$stmtDoc = $db->prepare($sqlDocentes);
$norm = normalizarCarrera($carreraActual);
$primer = explode(' ', trim($norm ?: $carreraActual))[0] ?? $carreraActual;
$stmtDoc->execute([
    ':carrera'      => $norm,
    ':carrera_like' => '%' . $primer . '%',
]);
$docentes = $stmtDoc->fetchAll();

// Para cada docente, obtener sus estudiantes asignados
$supervisoresConEstudiantes = [];
foreach ($docentes as $doc) {
    $sqlEst = "SELECT p.id_practica, p.codigo_practica, p.estado AS estado_practica,
                      e.nombre_completo AS estudiante, e.codigo_estudiante,
                      i.nombre AS institucion, a.fecha_asignacion
               FROM asignaciones_supervision a
               INNER JOIN practicas_laborales p ON p.id_practica = a.id_practica
               INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
               INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
               WHERE a.id_docente = :id_docente AND a.estado = 1
               ORDER BY e.nombre_completo ASC";
    $stmtEst = $db->prepare($sqlEst);
    $stmtEst->execute([':id_docente' => (int) $doc['id_docente']]);
    $doc['estudiantes'] = $stmtEst->fetchAll();
    $supervisoresConEstudiantes[] = $doc;
}

$puedeEditar = tienePermiso('practicas', 'editar');
$tituloPagina = 'Docentes Supervisores y Carga';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-0">Docentes Supervisores y Estudiantes Asignados</h2>
        <small class="text-muted">
            Carrera: <strong><?= sanitizar($carreraActual) ?></strong>
            &nbsp;·&nbsp; Asignación y seguimiento de docentes supervisores
        </small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $urlBase ?>/modulos/practicas/listar.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Listado de Prácticas
        </a>
        <?php if ($puedeEditar): ?>
            <a href="<?= $urlBase ?>/modulos/practicas/asignar_masivo.php" class="btn btn-success btn-sm text-white">
                <i class="bi bi-people-fill"></i> Asignar Estudiantes a Supervisor
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($restringirCarrera === null): ?>
    <!-- Selector de carrera para el administrador -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 fw-medium small">Seleccionar Carrera:</label>
                </div>
                <div class="col-md-4">
                    <select name="carrera" class="form-select form-select-sm" onchange="this.form.submit()">
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= sanitizar($c) ?>" <?= normalizarCarrera($carreraActual) === normalizarCarrera($c) ? 'selected' : '' ?>>
                                <?= sanitizar($c) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="alert alert-light border mb-4">
    <div class="d-flex align-items-center">
        <i class="bi bi-info-circle-fill text-primary fs-4 me-3"></i>
        <div>
            <strong>Gestión del Docente Encargado:</strong>
            Un Docente Supervisor puede tener varios estudiantes asignados a su responsabilidad.
            Aquí puede <strong>asignar a un docente como "Docente Supervisor"</strong> (activándolo o deshabilitándolo) y ver detalladamente qué estudiantes tiene a su cargo.
        </div>
    </div>
</div>

<?php if (empty($supervisoresConEstudiantes)): ?>
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
            <h6>No hay docentes registrados en <?= sanitizar($carreraActual) ?></h6>
            <p class="small mb-0">El Administrador debe crear las cuentas de los docentes en el menú <strong>Usuarios</strong> indicando su carrera correspondiente.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($supervisoresConEstudiantes as $sup): ?>
            <?php $esActivo = (int) $sup['estado'] === 1; ?>
            <div class="col-12">
                <div class="card <?= $esActivo ? 'border-primary' : 'border-secondary' ?> shadow-sm">
                    <div class="card-header <?= $esActivo ? 'bg-primary text-white' : 'bg-light text-muted' ?> d-flex justify-content-between align-items-center py-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge <?= $esActivo ? 'bg-white text-primary' : 'bg-secondary' ?> fw-semibold">
                                <?= sanitizar($sup['codigo_docente']) ?>
                            </span>
                            <span class="fw-bold fs-6"><?= sanitizar($sup['nombre_completo']) ?></span>
                            <?php if ($esActivo): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Docente Supervisor Habilitado</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="bi bi-pause-circle"></i> No asignado como supervisor</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge <?= $esActivo ? 'bg-light text-dark' : 'bg-secondary' ?> px-3 py-2">
                                <i class="bi bi-people-fill"></i> <?= (int) $sup['total_estudiantes'] ?> <?= (int) $sup['total_estudiantes'] === 1 ? 'estudiante asignado' : 'estudiantes asignados' ?>
                            </span>

                            <?php if ($puedeEditar): ?>
                                <form action="<?= $urlBase ?>/modulos/practicas/supervisores.php" method="post" class="d-inline"
                                      onsubmit="return confirm('¿Confirma <?= $esActivo ? 'deshabilitar a este docente de la supervisión' : 'asignar a este docente como Docente Supervisor activo' ?>?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="accion" value="cambiar_estado_supervisor">
                                    <input type="hidden" name="id_docente" value="<?= (int) $sup['id_docente'] ?>">
                                    <button type="submit" class="btn btn-sm <?= $esActivo ? 'btn-outline-light' : 'btn-outline-primary' ?>"
                                            title="<?= $esActivo ? 'Quitar rol de supervisor' : 'Habilitar como Docente Supervisor' ?>">
                                        <?= $esActivo ? '<i class="bi bi-x-circle"></i> Deshabilitar Supervisor' : '<i class="bi bi-person-check-fill"></i> Asignar como Docente Supervisor' ?>
                                    </button>
                                </form>

                                <?php if ($esActivo): ?>
                                    <a href="<?= $urlBase ?>/modulos/practicas/asignar_masivo.php?id_docente=<?= (int) $sup['id_docente'] ?>" 
                                       class="btn btn-sm btn-light text-primary fw-semibold" title="Asignar estudiantes a este supervisor">
                                        <i class="bi bi-plus-circle-fill"></i> Asignar Estudiantes
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body py-2">
                        <div class="row g-2 mb-2 text-muted small">
                            <div class="col-md-4">
                                <i class="bi bi-card-text"></i> CI / Usuario: <strong><?= sanitizar($sup['cedula_identidad']) ?></strong>
                            </div>
                            <div class="col-md-4">
                                <i class="bi bi-telephone"></i> Teléfono: <strong><?= sanitizar($sup['telefono'] ?: 'No registrado') ?></strong>
                            </div>
                            <div class="col-md-4">
                                <i class="bi bi-envelope"></i> Correo: <strong><?= sanitizar($sup['correo_electronico'] ?: 'No registrado') ?></strong>
                            </div>
                        </div>

                        <?php if (empty($sup['estudiantes'])): ?>
                            <div class="bg-light p-3 text-center rounded text-muted small">
                                <em>Este docente no tiene estudiantes asignados bajo su supervisión actualmente.</em>
                                <?php if ($puedeEditar && $esActivo): ?>
                                    <br>
                                    <a href="<?= $urlBase ?>/modulos/practicas/asignar_masivo.php?id_docente=<?= (int) $sup['id_docente'] ?>" class="btn btn-link btn-sm mt-1">
                                        <i class="bi bi-person-plus-fill"></i> Asignar estudiantes ahora
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="small text-muted">
                                            <th>Práctica</th>
                                            <th>Estudiante</th>
                                            <th>Código Est.</th>
                                            <th>Institución Receptora</th>
                                            <th>Fecha Asignación</th>
                                            <th>Estado Práctica</th>
                                            <th class="text-end">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sup['estudiantes'] as $est): ?>
                                            <tr>
                                                <td><span class="badge bg-light text-dark border"><?= sanitizar($est['codigo_practica']) ?></span></td>
                                                <td class="fw-medium"><?= sanitizar($est['estudiante']) ?></td>
                                                <td><span class="text-muted small"><?= sanitizar($est['codigo_estudiante']) ?></span></td>
                                                <td><?= sanitizar($est['institucion']) ?></td>
                                                <td class="small text-muted"><?= sanitizar(formatearFecha($est['fecha_asignacion'])) ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= sanitizar(ucfirst(str_replace('_', ' ', $est['estado_practica']))) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="<?= $urlBase ?>/modulos/practicas/asignar_supervisor.php?id=<?= (int) $est['id_practica'] ?>" 
                                                       class="btn btn-sm btn-outline-success py-0 px-2 small" title="Reasignar a otro docente">
                                                        Reasignar
                                                    </a>
                                                    <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $est['id_practica'] ?>" 
                                                       class="btn btn-sm btn-outline-primary py-0 px-2 small">
                                                        Ver
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
