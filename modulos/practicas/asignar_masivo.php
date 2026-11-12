<?php
/**
 * Asignación de Estudiantes a un Docente Supervisor
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Permite al Docente Encargado (y Administrador) asignar a uno o varios
 * estudiantes a la responsabilidad de un Docente Supervisor de su carrera.
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
$carreraActual = $restringirCarrera ?? trim($_GET['carrera'] ?? 'Sistemas Informáticos');

// Procesar asignación
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
        mostrarMensaje('error', 'Token de seguridad inválido.');
        redirigir('modulos/practicas/asignar_masivo.php');
    }

    $idDocente = (int) ($_POST['id_docente'] ?? 0);
    $practicasSeleccionadas = $_POST['practicas'] ?? [];
    $observaciones = trim($_POST['observaciones'] ?? '');

    if ($idDocente <= 0) {
        mostrarMensaje('error', 'Debe seleccionar un Docente Supervisor.');
        redirigir('modulos/practicas/asignar_masivo.php');
    }

    $docente = DocenteSupervisor::obtenerPorId($idDocente);
    if (!$docente || (int) $docente['estado'] !== 1) {
        mostrarMensaje('error', 'El docente seleccionado no está activo como supervisor.');
        redirigir('modulos/practicas/asignar_masivo.php');
    }

    if ($restringirCarrera !== null && normalizarCarrera($docente['carrera']) !== normalizarCarrera($restringirCarrera)) {
        mostrarMensaje('error', 'El docente supervisor no pertenece a su carrera.');
        redirigir('modulos/practicas/asignar_masivo.php');
    }

    if (empty($practicasSeleccionadas) || !is_array($practicasSeleccionadas)) {
        mostrarMensaje('error', 'Debe seleccionar al menos un estudiante / práctica para asignar.');
        redirigir('modulos/practicas/asignar_masivo.php?id_docente=' . $idDocente);
    }

    $asignados = 0;
    foreach ($practicasSeleccionadas as $idP) {
        $idPractica = (int) $idP;
        $p = Practica::obtenerPorId($idPractica);
        if ($p && normalizarCarrera($p['carrera']) === normalizarCarrera($docente['carrera'])) {
            if (Practica::asignarSupervisor($idPractica, $idDocente, $idUsuario, $observaciones)) {
                $asignados++;
                Auditoria::registrar($idUsuario, 'modificar', 'asignaciones_supervision', $idPractica, null, [
                    'id_docente' => $idDocente,
                    'supervisor' => $docente['nombre_completo'],
                    'tipo'       => 'asignacion_grupo'
                ]);
            }
        }
    }

    if ($asignados > 0) {
        mostrarMensaje('exito', "Se asignaron correctamente {$asignados} estudiante(s) bajo la responsabilidad del Docente Supervisor " . sanitizar($docente['nombre_completo']) . ".");
    } else {
        mostrarMensaje('error', 'No se pudieron asignar las prácticas seleccionadas.');
    }

    redirigir('modulos/practicas/supervisores.php');
}

// Cargar supervisores activos de la carrera
$docentes = DocenteSupervisor::obtenerActivos($carreraActual);
$idDocentePreseleccionado = (int) ($_GET['id_docente'] ?? 0);

// Cargar prácticas de estudiantes de esta carrera
$db = Database::obtenerInstancia();
$sqlPracticas = "SELECT p.id_practica, p.codigo_practica, p.estado, p.fecha_inicio, p.fecha_fin_prevista,
                        e.nombre_completo AS estudiante, e.codigo_estudiante, e.curso, e.paralelo,
                        i.nombre AS institucion,
                        (SELECT d.id_docente FROM asignaciones_supervision a 
                         INNER JOIN docentes_supervisores d ON d.id_docente = a.id_docente
                         WHERE a.id_practica = p.id_practica AND a.estado = 1 LIMIT 1) AS id_supervisor_actual,
                        (SELECT d.nombre_completo FROM asignaciones_supervision a 
                         INNER JOIN docentes_supervisores d ON d.id_docente = a.id_docente
                         WHERE a.id_practica = p.id_practica AND a.estado = 1 LIMIT 1) AS supervisor_actual
                 FROM practicas_laborales p
                 INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                 INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
                 WHERE (e.carrera = :carrera OR e.carrera LIKE :carrera_like)
                 ORDER BY supervisor_actual IS NULL DESC, e.nombre_completo ASC";

$stmtPrac = $db->prepare($sqlPracticas);
$norm = normalizarCarrera($carreraActual);
$primer = explode(' ', trim($norm ?: $carreraActual))[0] ?? $carreraActual;
$stmtPrac->execute([
    ':carrera'      => $norm,
    ':carrera_like' => '%' . $primer . '%',
]);
$practicas = $stmtPrac->fetchAll();

$tituloPagina = 'Asignar Estudiantes a Supervisor';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-0">Asignar Estudiantes a Docente Supervisor</h2>
        <small class="text-muted">Carrera: <strong><?= sanitizar($carreraActual) ?></strong></small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $urlBase ?>/modulos/practicas/supervisores.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-person-badge"></i> Ver Supervisores y Cargas
        </a>
        <a href="<?= $urlBase ?>/modulos/practicas/listar.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Listado de Prácticas
        </a>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold text-primary">
            <i class="bi bi-people-fill"></i> Asignación de Estudiantes a la Responsabilidad de un Docente Supervisor
        </h6>
        <p class="text-muted small mb-0 mt-1">
            Seleccione al Docente Supervisor y marque a los estudiantes que estarán a su cargo durante la gestión.
        </p>
    </div>
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/practicas/asignar_masivo.php" method="post" id="formAsignarMasivo">
            <?= campoCSRF() ?>

            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <label class="form-label fw-bold">1. Seleccione el Docente Supervisor *</label>
                    <select class="form-select form-select-lg" name="id_docente" id="sel_docente" required>
                        <option value="">-- Elija al docente supervisor de <?= sanitizar($carreraActual) ?> --</option>
                        <?php foreach ($docentes as $d): ?>
                            <option value="<?= (int) $d['id_docente'] ?>" <?= $idDocentePreseleccionado === (int) $d['id_docente'] ? 'selected' : '' ?>>
                                <?= sanitizar($d['nombre_completo']) ?> (CI: <?= sanitizar($d['cedula_identidad']) ?>)<?= !empty($d['telefono']) ? ' — Tel: ' . sanitizar($d['telefono']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($docentes)): ?>
                        <div class="text-danger small mt-1">
                            <i class="bi bi-exclamation-triangle"></i> No hay docentes supervisores activos en esta carrera.
                            El Administrador debe crear los docentes en <strong>Usuarios</strong>.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-bold">Observaciones / Detalle de la asignación</label>
                    <input type="text" class="form-control" name="observaciones"
                           placeholder="Ej: Asignación gestión 2026, grupo Mañana...">
                    <div class="form-text">Opcional. Se registrará en el historial de supervisión.</div>
                </div>
            </div>

            <div class="border-top pt-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label fw-bold mb-0">2. Marque los Estudiantes para Asignar a este Supervisor:</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnMarcarTodos">
                            <i class="bi bi-check2-all"></i> Marcar todos
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnDesmarcarTodos">
                            Desmarcar todos
                        </button>
                    </div>
                </div>

                <?php if (empty($practicas)): ?>
                    <div class="alert alert-warning mb-0">
                        No hay estudiantes con prácticas registradas en <?= sanitizar($carreraActual) ?>.
                    </div>
                <?php else: ?>
                    <div class="table-responsive border rounded">
                        <table class="table table-hover align-middle mb-0" id="tablaEstudiantes">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        <input type="checkbox" class="form-check-input" id="checkMaster">
                                    </th>
                                    <th>Estudiante</th>
                                    <th>Curso / Paralelo</th>
                                    <th>Institución Receptora</th>
                                    <th>Supervisor Actual</th>
                                    <th>Estado Práctica</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($practicas as $p): ?>
                                    <?php $sinSupervisor = empty($p['supervisor_actual']); ?>
                                    <tr class="<?= $sinSupervisor ? 'table-warning-subtle' : '' ?>">
                                        <td class="text-center">
                                            <input type="checkbox" name="practicas[]" value="<?= (int) $p['id_practica'] ?>" 
                                                   class="form-check-input check-practica">
                                        </td>
                                        <td>
                                            <span class="fw-semibold text-dark"><?= sanitizar($p['estudiante']) ?></span>
                                            <br><small class="text-muted"><?= sanitizar($p['codigo_estudiante']) ?> · Práctica <?= sanitizar($p['codigo_practica']) ?></small>
                                        </td>
                                        <td><?= sanitizar($p['curso']) ?> <?= sanitizar($p['paralelo']) ?></td>
                                        <td><?= sanitizar($p['institucion']) ?></td>
                                        <td>
                                            <?php if ($sinSupervisor): ?>
                                                <span class="badge bg-warning text-dark"><i class="bi bi-person-x"></i> Sin asignar</span>
                                            <?php else: ?>
                                                <span class="text-dark small"><i class="bi bi-person-check-fill text-success"></i> <?= sanitizar($p['supervisor_actual']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= sanitizar(ucfirst(str_replace('_', ' ', $p['estado']))) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4 d-flex justify-content-between align-items-center">
                <a href="<?= $urlBase ?>/modulos/practicas/supervisores.php" class="btn btn-outline-secondary">Cancelar</a>
                <button type="submit" class="btn btn-success btn-lg text-white" id="btnGuardar" <?= empty($docentes) || empty($practicas) ? 'disabled' : '' ?>>
                    <i class="bi bi-person-check-fill"></i> Asignar Estudiantes Seleccionados
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkMaster = document.getElementById('checkMaster');
    const checks = document.querySelectorAll('.check-practica');
    const btnMarcar = document.getElementById('btnMarcarTodos');
    const btnDesmarcar = document.getElementById('btnDesmarcarTodos');

    if (checkMaster) {
        checkMaster.addEventListener('change', function() {
            checks.forEach(c => c.checked = checkMaster.checked);
        });
    }

    if (btnMarcar) {
        btnMarcar.addEventListener('click', function() {
            checks.forEach(c => c.checked = true);
            if (checkMaster) checkMaster.checked = true;
        });
    }

    if (btnDesmarcar) {
        btnDesmarcar.addEventListener('click', function() {
            checks.forEach(c => c.checked = false);
            if (checkMaster) checkMaster.checked = false;
        });
    }
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
