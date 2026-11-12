<?php
/**
 * Listado de prácticas laborales
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/Institucion.php';
require_once __DIR__ . '/../../clases/DocenteSupervisor.php';
require_once __DIR__ . '/../../clases/Carrera.php';

verificarPermiso('practicas', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$porPagina = (int) ($config['registros_por_pagina'] ?? 15);

$restringirCarrera = restriccionCarreraActual();
$carreras = Carrera::obtenerActivas();

$pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
$filtros = [
    'buscar'         => trim($_GET['buscar'] ?? ''),
    'estado'         => $_GET['estado'] ?? '',
    'id_institucion' => $_GET['id_institucion'] ?? '',
    'id_docente'     => $_GET['id_docente'] ?? '',
    'carrera'        => $restringirCarrera !== null ? $restringirCarrera : trim($_GET['carrera'] ?? ''),
];

$total      = Practica::contarTotal($filtros);
$paginacion = paginar($total, $pagina, $porPagina);
$practicas  = Practica::listar($paginacion['pagina'], $filtros, $porPagina);
$instituciones = Institucion::obtenerActivas();
$docentes   = DocenteSupervisor::obtenerActivos($filtros['carrera'] ?: $restringirCarrera);

$puedeCrear = tienePermiso('practicas', 'crear');
$hoy = date('Y-m-d');

$qsFiltros = http_build_query(array_filter([
    'buscar'         => $filtros['buscar'],
    'estado'         => $filtros['estado'],
    'id_institucion' => $filtros['id_institucion'],
    'id_docente'     => $filtros['id_docente'],
    'carrera'        => $filtros['carrera'],
], fn($v) => $v !== ''));

$badges = [
    'pendiente'  => 'badge-estado-pendiente',
    'en_curso'   => 'badge-estado-en-curso',
    'completada' => 'badge-estado-completada',
    'suspendida' => 'badge-estado-suspendida',
];

$etiquetaRegistros = 'prácticas';
$tituloPagina = 'Prácticas Laborales';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-0">Prácticas laborales</h2>
        <?php if ($restringirCarrera !== null): ?>
            <small class="text-muted">Carrera: <strong><?= sanitizar($restringirCarrera) ?></strong></small>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $urlBase ?>/modulos/practicas/supervisores.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-person-badge"></i> Docentes Supervisores y Cargas
        </a>
        <?php if (tienePermiso('practicas', 'editar')): ?>
            <a href="<?= $urlBase ?>/modulos/practicas/asignar_masivo.php" class="btn btn-outline-success btn-sm">
                <i class="bi bi-people-fill"></i> Asignar Estudiantes a Supervisor
            </a>
        <?php endif; ?>
        <?php if ($puedeCrear): ?>
            <a href="<?= $urlBase ?>/modulos/practicas/crear.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Registrar práctica
            </a>
        <?php endif; ?>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="<?= $restringirCarrera === null ? 'col-md-2' : 'col-md-3' ?>">
        <input type="text" name="buscar" class="form-control form-control-sm"
               placeholder="Código o estudiante..." value="<?= sanitizar($filtros['buscar']) ?>">
    </div>
    <?php if ($restringirCarrera === null): ?>
        <div class="col-md-2">
            <select name="carrera" class="form-select form-select-sm">
                <option value="">Todas las carreras</option>
                <?php foreach ($carreras as $c): ?>
                    <option value="<?= sanitizar($c) ?>" <?= $filtros['carrera'] === $c ? 'selected' : '' ?>><?= sanitizar($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else: ?>
        <input type="hidden" name="carrera" value="<?= sanitizar($restringirCarrera) ?>">
    <?php endif; ?>
    <div class="col-md-2">
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            <option value="pendiente" <?= $filtros['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="en_curso" <?= $filtros['estado'] === 'en_curso' ? 'selected' : '' ?>>En curso</option>
            <option value="completada" <?= $filtros['estado'] === 'completada' ? 'selected' : '' ?>>Completada</option>
            <option value="suspendida" <?= $filtros['estado'] === 'suspendida' ? 'selected' : '' ?>>Suspendida</option>
        </select>
    </div>
    <div class="<?= $restringirCarrera === null ? 'col-md-2' : 'col-md-3' ?>">
        <select name="id_institucion" class="form-select form-select-sm">
            <option value="">Todas las instituciones</option>
            <?php foreach ($instituciones as $i): ?>
                <option value="<?= (int) $i['id_institucion'] ?>" <?= (string) $filtros['id_institucion'] === (string) $i['id_institucion'] ? 'selected' : '' ?>>
                    <?= sanitizar($i['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="id_docente" class="form-select form-select-sm">
            <option value="">Todos los supervisores</option>
            <?php foreach ($docentes as $d): ?>
                <option value="<?= (int) $d['id_docente'] ?>" <?= (string) $filtros['id_docente'] === (string) $d['id_docente'] ? 'selected' : '' ?>>
                    <?= sanitizar($d['nombre_completo']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filtrar</button>
        <a href="<?= $urlBase ?>/modulos/practicas/listar.php" class="btn btn-outline-secondary btn-sm" title="Limpiar">×</a>
    </div>
</form>

<div class="tabla-personalizada">
    <table class="table mb-0 align-middle">
        <thead>
            <tr>
                <th>Código</th>
                <th>Estudiante</th>
                <th>Institución</th>
                <th>Supervisor</th>
                <th>Tel. Supervisor</th>
                <th>Fin prevista</th>
                <th>% Horas</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($practicas)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">
                        <p class="mb-2">No se encontraron prácticas registradas<?= $restringirCarrera ? ' para la carrera ' . sanitizar($restringirCarrera) : '' ?>.</p>
                        <?php if ($puedeCrear): ?>
                            <a href="<?= $urlBase ?>/modulos/practicas/crear.php" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle"></i> Registrar primera práctica
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($practicas as $p): ?>
                    <?php
                        $requeridas = (int) $p['total_horas_requeridas'];
                        $pct = $requeridas > 0 ? min(100, round(((float) $p['horas_cumplidas'] / $requeridas) * 100)) : 0;
                        $fueraPlazo = $p['estado'] === 'en_curso' && $p['fecha_fin_prevista'] < $hoy;
                    ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?= sanitizar($p['codigo_practica']) ?></span></td>
                        <td class="fw-medium">
                            <div><?= sanitizar($p['estudiante']) ?></div>
                            <small class="badge bg-secondary"><?= sanitizar($p['carrera'] ?? '') ?></small>
                        </td>
                        <td><?= sanitizar($p['institucion']) ?></td>
                        <td>
                            <?php if (!empty($p['supervisor'])): ?>
                                <span class="fw-medium text-dark"><?= sanitizar($p['supervisor']) ?></span>
                            <?php else: ?>
                                <a href="<?= $urlBase ?>/modulos/practicas/asignar_supervisor.php?id=<?= (int) $p['id_practica'] ?>"
                                   class="badge bg-warning text-dark text-decoration-none py-1 px-2" title="Asignar docente supervisor ahora">
                                    <i class="bi bi-person-x-fill"></i> Sin asignar (Asignar Sup.)
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($p['supervisor_telefono'])): ?>
                                <a href="tel:<?= sanitizar($p['supervisor_telefono']) ?>" class="text-decoration-none">
                                    <?= sanitizar($p['supervisor_telefono']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= sanitizar(formatearFecha($p['fecha_fin_prevista'])) ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar" style="width: <?= $pct ?>%"></div>
                                </div>
                                <small class="text-muted"><?= $pct ?>%</small>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= sanitizar($badges[$p['estado']] ?? 'bg-secondary') ?>"><?= sanitizar(ucfirst(str_replace('_', ' ', $p['estado']))) ?></span>
                            <?php if ($fueraPlazo): ?>
                                <span class="badge badge-estado-fuera-plazo">Fuera de plazo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end acciones-tabla">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $p['id_practica'] ?>"
                                   class="btn btn-outline-primary btn-sm">Ver</a>
                                <?php if (tienePermiso('practicas', 'editar')): ?>
                                    <a href="<?= $urlBase ?>/modulos/practicas/asignar_supervisor.php?id=<?= (int) $p['id_practica'] ?>"
                                       class="btn btn-success btn-sm text-white" title="Asignar o cambiar el docente supervisor del estudiante">
                                        <i class="bi bi-person-check-fill"></i> Asignar Sup.
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/paginacion.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
