<?php
/**
 * Historial de prácticas completadas
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Tabla paginada de prácticas completadas con filtros por rango de fechas de
 * finalización, carrera del estudiante e institución, y enlaces de exportación.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/Estudiante.php';
require_once __DIR__ . '/../../clases/Institucion.php';

verificarPermiso('practicas', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$porPagina = (int) ($config['registros_por_pagina'] ?? 15);

$pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
$filtros = [
    'fecha_desde'    => trim($_GET['fecha_desde'] ?? ''),
    'fecha_hasta'    => trim($_GET['fecha_hasta'] ?? ''),
    'carrera'        => trim($_GET['carrera'] ?? ''),
    'id_institucion' => $_GET['id_institucion'] ?? '',
];

$total      = Practica::contarCompletadas($filtros);
$paginacion = paginar($total, $pagina, $porPagina);
$practicas  = Practica::listarCompletadas($filtros, $paginacion['pagina'], $porPagina);
$carreras   = Estudiante::obtenerCarreras();
$instituciones = Institucion::obtenerActivas();

$qsFiltros = http_build_query(array_filter([
    'fecha_desde'    => $filtros['fecha_desde'],
    'fecha_hasta'    => $filtros['fecha_hasta'],
    'carrera'        => $filtros['carrera'],
    'id_institucion' => $filtros['id_institucion'],
], fn($v) => $v !== ''));

$etiquetaRegistros = 'prácticas completadas';
$tituloPagina = 'Historial de Prácticas';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Historial de prácticas completadas</h2>
    <div class="d-flex gap-2">
        <a href="<?= $urlBase ?>/modulos/practicas/exportar.php?formato=csv<?= $qsFiltros ? '&' . $qsFiltros : '' ?>" class="btn btn-outline-success btn-sm">Exportar CSV</a>
        <a href="<?= $urlBase ?>/modulos/practicas/exportar.php?formato=pdf<?= $qsFiltros ? '&' . $qsFiltros : '' ?>" class="btn btn-outline-danger btn-sm">Exportar PDF</a>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-3">
        <label class="form-label small mb-0">Finalización desde</label>
        <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= sanitizar($filtros['fecha_desde']) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-0">Finalización hasta</label>
        <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= sanitizar($filtros['fecha_hasta']) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-0">Carrera</label>
        <select name="carrera" class="form-select form-select-sm">
            <option value="">Todas</option>
            <?php foreach ($carreras as $c): ?>
                <option value="<?= sanitizar($c) ?>" <?= $filtros['carrera'] === $c ? 'selected' : '' ?>><?= sanitizar($c) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small mb-0">Institución</label>
        <select name="id_institucion" class="form-select form-select-sm">
            <option value="">Todas</option>
            <?php foreach ($instituciones as $i): ?>
                <option value="<?= (int) $i['id_institucion'] ?>" <?= (string) $filtros['id_institucion'] === (string) $i['id_institucion'] ? 'selected' : '' ?>><?= sanitizar($i['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-outline-primary btn-sm">Filtrar</button>
        <a href="<?= $urlBase ?>/modulos/practicas/historial.php" class="btn btn-link btn-sm">Limpiar</a>
    </div>
</form>

<div class="tabla-personalizada">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Código</th>
                <th>Estudiante</th>
                <th>Carrera</th>
                <th>Institución</th>
                <th>Supervisor</th>
                <th>Inicio</th>
                <th>Finalización</th>
                <th>Horas</th>
                <th class="text-end">Detalle</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($practicas)): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">No hay prácticas completadas con estos filtros.</td></tr>
            <?php else: ?>
                <?php foreach ($practicas as $p): ?>
                    <tr>
                        <td><?= sanitizar($p['codigo_practica']) ?></td>
                        <td><?= sanitizar($p['estudiante']) ?></td>
                        <td><?= sanitizar($p['carrera']) ?></td>
                        <td><?= sanitizar($p['institucion']) ?></td>
                        <td><?= sanitizar($p['supervisor'] ?: '—') ?></td>
                        <td><?= sanitizar(formatearFecha($p['fecha_inicio'])) ?></td>
                        <td><?= sanitizar(formatearFecha($p['fecha_finalizacion_real'])) ?></td>
                        <td><?= number_format((float) $p['horas_cumplidas'], 0) ?>/<?= (int) $p['total_horas_requeridas'] ?></td>
                        <td class="text-end acciones-tabla">
                            <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $p['id_practica'] ?>" class="btn btn-outline-primary">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/paginacion.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
