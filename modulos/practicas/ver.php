<?php
/**
 * Detalle completo de una práctica laboral
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra datos generales, actividades, historial de horas con porcentaje,
 * supervisor actual e historial de supervisores, e historial de estados.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/Actividad.php';
require_once __DIR__ . '/../../clases/HorasCumplidas.php';

verificarPermiso('practicas', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$id = (int) ($_GET['id'] ?? 0);
$p = Practica::obtenerPorId($id);
if ($p === null) {
    mostrarMensaje('error', 'La práctica solicitada no existe.');
    redirigir('modulos/practicas/listar.php');
}

$actividades = Actividad::listarPorPractica($id);
$registrosHoras = HorasCumplidas::listarPorPractica($id);
$totalHoras = HorasCumplidas::calcularTotalHoras($id);
$porcentaje = HorasCumplidas::calcularPorcentajeAvance($id);
$supervisor = Practica::obtenerSupervisorActual($id);
$histSupervisores = Practica::obtenerHistorialSupervisores($id);
$histEstados = Practica::obtenerHistorialEstados($id);

$hoy = date('Y-m-d');
$fueraPlazo = $p['estado'] === 'en_curso' && $p['fecha_fin_prevista'] < $hoy;

$badges = [
    'pendiente'  => 'badge-estado-pendiente',
    'en_curso'   => 'badge-estado-en-curso',
    'completada' => 'badge-estado-completada',
    'suspendida' => 'badge-estado-suspendida',
];

$puedeEditar = tienePermiso('practicas', 'editar');

$tituloPagina = 'Detalle de Práctica';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Práctica <small class="text-muted"><?= sanitizar($p['codigo_practica']) ?></small></h2>
    <div class="d-flex gap-2">
        <a href="<?= $urlBase ?>/modulos/practicas/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
        <?php if ($puedeEditar): ?>
            <a href="<?= $urlBase ?>/modulos/practicas/editar.php?id=<?= (int) $id ?>" class="btn btn-outline-secondary btn-sm">Editar</a>
            <a href="<?= $urlBase ?>/modulos/practicas/estado.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Cambiar estado</a>
            <a href="<?= $urlBase ?>/modulos/practicas/asignar_supervisor.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Supervisor</a>
            <a href="<?= $urlBase ?>/modulos/practicas/actividades.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Actividades</a>
            <a href="<?= $urlBase ?>/modulos/practicas/horas.php?id=<?= (int) $id ?>" class="btn btn-outline-primary btn-sm">Horas</a>
        <?php endif; ?>
    </div>
</div>

<!-- Datos generales -->
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Datos generales</div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Estudiante</dt>
            <dd class="col-sm-9"><?= sanitizar($p['estudiante']) ?> (<?= sanitizar($p['codigo_estudiante']) ?>) — <?= sanitizar($p['carrera']) ?></dd>
            <dt class="col-sm-3">Institución</dt>
            <dd class="col-sm-9"><?= sanitizar($p['institucion']) ?></dd>
            <dt class="col-sm-3">Convenio</dt>
            <dd class="col-sm-9"><?= sanitizar($p['codigo_convenio']) ?> - <?= sanitizar($p['convenio_titulo']) ?></dd>
            <dt class="col-sm-3">Área / departamento</dt>
            <dd class="col-sm-9"><?= sanitizar($p['area_departamento'] ?: '—') ?></dd>
            <dt class="col-sm-3">Período</dt>
            <dd class="col-sm-9"><?= sanitizar(formatearFecha($p['fecha_inicio'])) ?> — <?= sanitizar(formatearFecha($p['fecha_fin_prevista'])) ?>
                <?php if ($p['fecha_finalizacion_real']): ?>
                    (finalizó el <?= sanitizar(formatearFecha($p['fecha_finalizacion_real'])) ?>)
                <?php endif; ?>
            </dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9">
                <span class="badge <?= sanitizar($badges[$p['estado']] ?? 'bg-secondary') ?>"><?= sanitizar(ucfirst(str_replace('_', ' ', $p['estado']))) ?></span>
                <?php if ($fueraPlazo): ?><span class="badge badge-estado-fuera-plazo">Fuera de plazo</span><?php endif; ?>
            </dd>
            <dt class="col-sm-3">Descripción</dt>
            <dd class="col-sm-9"><?= nl2br(sanitizar($p['descripcion_actividades'] ?: '—')) ?></dd>
        </dl>
    </div>
</div>

<div class="row g-3">
    <!-- Supervisor -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Docente Supervisor</span>
                <?php if ($puedeEditar): ?>
                    <a href="<?= $urlBase ?>/modulos/practicas/asignar_supervisor.php?id=<?= (int) $id ?>" class="btn btn-sm btn-success text-white">
                        <i class="bi bi-person-check-fill"></i> Asignar Sup.
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <p><strong>Actual:</strong>
                    <?php if ($supervisor): ?>
                        <span class="fw-medium"><?= sanitizar($supervisor['nombre_completo']) ?></span>
                        <span class="text-muted">(<?= sanitizar($supervisor['carrera']) ?>)</span>
                        <?php if (!empty($supervisor['telefono'])): ?>
                            <br><small class="text-muted">Teléfono:</small> <a href="tel:<?= sanitizar($supervisor['telefono']) ?>" class="fw-semibold text-decoration-none"><?= sanitizar($supervisor['telefono']) ?></a>
                        <?php endif; ?>
                        <?php if (!empty($supervisor['correo_electronico'])): ?>
                            <br><small class="text-muted">Correo:</small> <a href="mailto:<?= sanitizar($supervisor['correo_electronico']) ?>"><?= sanitizar($supervisor['correo_electronico']) ?></a>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-muted">Sin asignar</span>
                    <?php endif; ?>
                </p>
                <h6 class="small text-muted">Historial</h6>
                <ul class="list-group list-group-flush">
                    <?php if (empty($histSupervisores)): ?>
                        <li class="list-group-item px-0 text-muted">Sin asignaciones.</li>
                    <?php else: ?>
                        <?php foreach ($histSupervisores as $h): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span><?= sanitizar($h['nombre_completo']) ?></span>
                                <small class="text-muted"><?= sanitizar(formatearFecha($h['fecha_asignacion'])) ?> <?= (int) $h['estado'] === 1 ? '(activo)' : '' ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Horas -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Horas cumplidas</div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-1">
                    <span><?= number_format($totalHoras, 2) ?> / <?= (int) $p['total_horas_requeridas'] ?> h</span>
                    <span><?= $porcentaje ?>%</span>
                </div>
                <div class="progress mb-3" role="progressbar" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar" style="width: <?= $porcentaje ?>%"></div>
                </div>
                <h6 class="small text-muted">Últimos registros</h6>
                <ul class="list-group list-group-flush">
                    <?php if (empty($registrosHoras)): ?>
                        <li class="list-group-item px-0 text-muted">Sin registros de horas.</li>
                    <?php else: ?>
                        <?php foreach (array_slice($registrosHoras, 0, 5) as $r): ?>
                            <li class="list-group-item px-0 d-flex justify-content-between">
                                <span><?= sanitizar(formatearFecha($r['fecha'])) ?></span>
                                <span><?= number_format((float) $r['cantidad_horas'], 2) ?> h</span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Actividades -->
<div class="card mt-3">
    <div class="card-header bg-white fw-semibold">Actividades</div>
    <div class="card-body p-0">
        <div class="tabla-personalizada">
            <table class="table mb-0">
                <thead><tr><th>Fecha</th><th>Actividad</th><th>Descripción</th></tr></thead>
                <tbody>
                    <?php if (empty($actividades)): ?>
                        <tr><td colspan="3" class="text-center text-muted py-3">Sin actividades registradas.</td></tr>
                    <?php else: ?>
                        <?php foreach ($actividades as $a): ?>
                            <tr>
                                <td><?= sanitizar(formatearFecha($a['fecha_registro'])) ?></td>
                                <td><?= sanitizar($a['nombre_actividad']) ?></td>
                                <td><?= sanitizar($a['descripcion'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Historial de estados -->
<div class="card mt-3">
    <div class="card-header bg-white fw-semibold">Historial de estados</div>
    <div class="card-body p-0">
        <div class="tabla-personalizada">
            <table class="table mb-0">
                <thead><tr><th>Fecha</th><th>Cambio</th><th>Usuario</th><th>Observaciones</th></tr></thead>
                <tbody>
                    <?php if (empty($histEstados)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sin historial.</td></tr>
                    <?php else: ?>
                        <?php foreach ($histEstados as $h): ?>
                            <tr>
                                <td><?= sanitizar(formatearFecha($h['fecha_cambio'], true)) ?></td>
                                <td><?= sanitizar(ucfirst(str_replace('_', ' ', $h['estado_anterior']))) ?> &rarr; <?= sanitizar(ucfirst(str_replace('_', ' ', $h['estado_nuevo']))) ?></td>
                                <td><?= sanitizar($h['usuario']) ?></td>
                                <td><?= sanitizar($h['observaciones'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
