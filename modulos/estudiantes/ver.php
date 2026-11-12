<?php
/**
 * Detalle de estudiante
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Estudiante.php';
require_once __DIR__ . '/../../config/database.php';

verificarPermiso('estudiantes', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$id = (int) ($_GET['id'] ?? 0);
$e = Estudiante::obtenerPorId($id);
if ($e === null) {
    mostrarMensaje('error', 'El estudiante solicitado no existe.');
    redirigir('modulos/estudiantes/listar.php');
}

$restringirCarrera = restriccionCarreraActual();
if ($restringirCarrera !== null && $e['carrera'] !== $restringirCarrera) {
    mostrarMensaje('error', 'No tiene permisos para ver estudiantes de otra carrera.');
    redirigir('modulos/estudiantes/listar.php');
}

// Consultar prácticas asociadas al estudiante
$practicas = [];
try {
    $db = Database::obtenerInstancia();
    $sql = "SELECT p.id_practica, p.codigo_practica, p.estado, p.fecha_inicio, p.fecha_fin_prevista,
                   i.nombre AS institucion,
                   (SELECT d.nombre_completo FROM asignaciones_supervision a
                    INNER JOIN docentes_supervisores d ON d.id_docente = a.id_docente
                    WHERE a.id_practica = p.id_practica AND a.estado = 1 LIMIT 1) AS supervisor
            FROM practicas_laborales p
            INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
            WHERE p.id_estudiante = :id
            ORDER BY p.fecha_creacion DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    $practicas = $stmt->fetchAll();
} catch (Throwable $th) {
    error_log('Error al obtener prácticas del estudiante: ' . $th->getMessage());
}

$puedeEditar = tienePermiso('estudiantes', 'editar');
$puedeEliminar = tienePermiso('estudiantes', 'eliminar');

$tituloPagina = 'Detalle de Estudiante';
$moduloActivo = 'estudiantes';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-0">Estudiante <small class="text-muted"><?= sanitizar($e['codigo_estudiante']) ?></small></h2>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $urlBase ?>/modulos/estudiantes/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
        <?php if ($puedeEditar): ?>
            <a href="<?= $urlBase ?>/modulos/estudiantes/editar.php?id=<?= (int) $id ?>" class="btn btn-primary btn-sm">Editar</a>
            <form action="<?= $urlBase ?>/modulos/estudiantes/procesar.php" method="post" class="d-inline"
                  onsubmit="return confirm('¿Confirma cambiar el estado de este estudiante?');">
                <?= campoCSRF() ?>
                <input type="hidden" name="accion" value="cambiar_estado">
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <input type="hidden" name="estado" value="<?= (int) $e['estado'] === 1 ? 0 : 1 ?>">
                <button type="submit" class="btn btn-outline-<?= (int) $e['estado'] === 1 ? 'warning' : 'success' ?> btn-sm">
                    <?= (int) $e['estado'] === 1 ? 'Inactivar' : 'Activar' ?>
                </button>
            </form>
        <?php endif; ?>
        <?php if ($puedeEliminar): ?>
            <form action="<?= $urlBase ?>/modulos/estudiantes/procesar.php" method="post" class="d-inline"
                  onsubmit="return confirm('¿Está seguro de ELIMINAR definitivamente a este estudiante?\n\nEsta acción borrará el registro de la base de datos y no se puede deshacer.');">
                <?= campoCSRF() ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $id ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Datos personales y académicos</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Nombre completo</dt>
                    <dd class="col-sm-8 fw-medium"><?= sanitizar($e['nombre_completo']) ?></dd>

                    <dt class="col-sm-4">Cédula</dt>
                    <dd class="col-sm-8"><?= sanitizar($e['cedula_identidad']) ?></dd>

                    <dt class="col-sm-4">Fecha nacimiento</dt>
                    <dd class="col-sm-8"><?= sanitizar(formatearFecha($e['fecha_nacimiento'])) ?></dd>

                    <dt class="col-sm-4">Carrera</dt>
                    <dd class="col-sm-8"><?= sanitizar($e['carrera']) ?></dd>

                    <dt class="col-sm-4">Curso / Paralelo</dt>
                    <dd class="col-sm-8"><?= sanitizar($e['curso']) ?><?= !empty($e['paralelo']) ? ' — Paralelo ' . sanitizar($e['paralelo']) : '' ?></dd>

                    <dt class="col-sm-4">Teléfono</dt>
                    <dd class="col-sm-8">
                        <?php if (!empty($e['telefono'])): ?>
                            <a href="tel:<?= sanitizar($e['telefono']) ?>" class="fw-semibold text-decoration-none"><?= sanitizar($e['telefono']) ?></a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Correo</dt>
                    <dd class="col-sm-8"><?= sanitizar($e['correo_electronico'] ?: '—') ?></dd>

                    <dt class="col-sm-4">Dirección</dt>
                    <dd class="col-sm-8"><?= sanitizar($e['direccion'] ?: '—') ?></dd>

                    <dt class="col-sm-4">Estado</dt>
                    <dd class="col-sm-8">
                        <?php if ((int) $e['estado'] === 1): ?>
                            <span class="badge bg-success">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactivo</span>
                        <?php endif; ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Historial de prácticas laborales</span>
                <span class="badge bg-primary rounded-pill"><?= count($practicas) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Institución</th>
                                <th>Supervisor</th>
                                <th>Estado</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($practicas)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No tiene prácticas registradas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($practicas as $p): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $p['id_practica'] ?>" class="text-decoration-none fw-medium">
                                                <?= sanitizar($p['codigo_practica']) ?>
                                            </a>
                                        </td>
                                        <td><?= sanitizar($p['institucion']) ?></td>
                                        <td>
                                            <?php if (!empty($p['supervisor'])): ?>
                                                <?= sanitizar($p['supervisor']) ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Sin asignar</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-estado-<?= sanitizar($p['estado']) ?>">
                                                <?= sanitizar(ucfirst(str_replace('_', ' ', $p['estado']))) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= $urlBase ?>/modulos/practicas/asignar_supervisor.php?id=<?= (int) $p['id_practica'] ?>"
                                               class="btn btn-sm btn-outline-primary" title="Asignar o cambiar supervisor">
                                                <?= empty($p['supervisor']) ? 'Asignar' : 'Cambiar' ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
