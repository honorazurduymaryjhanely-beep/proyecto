<?php
/**
 * Listado de estudiantes
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Estudiante.php';

verificarPermiso('estudiantes', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$porPagina = (int) ($config['registros_por_pagina'] ?? 15);

$pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
$filtros = [
    'buscar'  => trim($_GET['buscar'] ?? ''),
    'carrera' => trim($_GET['carrera'] ?? ''),
    'estado'  => $_GET['estado'] ?? '',
];

// Restricción de alcance por carrera (docente encargado solo ve su carrera).
$restringirCarrera = restriccionCarreraActual();
if ($restringirCarrera !== null) {
    $filtros['restringir_carrera'] = $restringirCarrera;
}

$total      = Estudiante::contarTotal($filtros);
$paginacion = paginar($total, $pagina, $porPagina);
$estudiantes = Estudiante::listar($paginacion['pagina'], $filtros, $porPagina);
$carreras   = Estudiante::obtenerCarreras();

$puedeCrear    = tienePermiso('estudiantes', 'crear');
$puedeEditar   = tienePermiso('estudiantes', 'editar');
$puedeEliminar = tienePermiso('estudiantes', 'eliminar');

$qsFiltros = http_build_query(array_filter([
    'buscar'  => $filtros['buscar'],
    'carrera' => $filtros['carrera'],
    'estado'  => $filtros['estado'],
], fn($v) => $v !== ''));

$etiquetaRegistros = 'estudiantes';
$tituloPagina = 'Gestión de Estudiantes';
$moduloActivo = 'estudiantes';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-0">Estudiantes</h2>
        <?php if ($restringirCarrera !== null): ?>
            <small class="text-muted">Carrera: <strong><?= sanitizar($restringirCarrera) ?></strong></small>
        <?php endif; ?>
    </div>
    <?php if ($puedeCrear): ?>
        <div class="d-flex gap-2">
            <a href="<?= $urlBase ?>/modulos/estudiantes/importar.php" class="btn btn-outline-success btn-sm">Importar Excel</a>
            <a href="<?= $urlBase ?>/modulos/estudiantes/crear.php" class="btn btn-primary btn-sm">Nuevo estudiante</a>
        </div>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="<?= $restringirCarrera === null ? 'col-md-5' : 'col-md-8' ?>">
        <input type="text" name="buscar" class="form-control form-control-sm"
               placeholder="Buscar por nombre, cédula o código" value="<?= sanitizar($filtros['buscar']) ?>">
    </div>
    <?php if ($restringirCarrera === null): ?>
        <div class="col-md-3">
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
            <option value="">Todos</option>
            <option value="1" <?= $filtros['estado'] === '1' ? 'selected' : '' ?>>Activos</option>
            <option value="0" <?= $filtros['estado'] === '0' ? 'selected' : '' ?>>Inactivos</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filtrar</button>
    </div>
</form>

<div class="tabla-personalizada">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nombre</th>
                <th>Cédula</th>
                <th>Teléfono</th>
                <th>Carrera</th>
                <th>Curso</th>
                <th>Paralelo</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($estudiantes)): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">No se encontraron estudiantes.</td></tr>
            <?php else: ?>
                <?php foreach ($estudiantes as $e): ?>
                    <tr>
                        <td><?= sanitizar($e['codigo_estudiante']) ?></td>
                        <td><?= sanitizar($e['nombre_completo']) ?></td>
                        <td><?= sanitizar($e['cedula_identidad']) ?></td>
                        <td><?= sanitizar($e['telefono'] ?: '—') ?></td>
                        <td><?= sanitizar($e['carrera']) ?></td>
                        <td><?= sanitizar($e['curso']) ?></td>
                        <td><?= sanitizar($e['paralelo'] ?: '—') ?></td>
                        <td>
                            <?php if ((int) $e['estado'] === 1): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end acciones-tabla">
                            <a href="<?= $urlBase ?>/modulos/estudiantes/ver.php?id=<?= (int) $e['id_estudiante'] ?>"
                               class="btn btn-outline-primary btn-sm">Ver</a>
                            <?php if ($puedeEditar): ?>
                                <a href="<?= $urlBase ?>/modulos/estudiantes/editar.php?id=<?= (int) $e['id_estudiante'] ?>"
                                   class="btn btn-outline-secondary btn-sm">Editar</a>
                                <form action="<?= $urlBase ?>/modulos/estudiantes/procesar.php" method="post" class="d-inline"
                                      onsubmit="return confirm('¿Confirma cambiar el estado de este estudiante?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="accion" value="cambiar_estado">
                                    <input type="hidden" name="id" value="<?= (int) $e['id_estudiante'] ?>">
                                    <input type="hidden" name="estado" value="<?= (int) $e['estado'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-outline-<?= (int) $e['estado'] === 1 ? 'warning' : 'success' ?> btn-sm">
                                        <?= (int) $e['estado'] === 1 ? 'Inactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($puedeEliminar): ?>
                                <form action="<?= $urlBase ?>/modulos/estudiantes/procesar.php" method="post" class="d-inline"
                                      onsubmit="return confirm('¿Está seguro de ELIMINAR definitivamente al estudiante <?= sanitizar($e['nombre_completo']) ?>?\n\nEsta acción borrará el registro de la base de datos y no se puede deshacer.');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $e['id_estudiante'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar registro">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/paginacion.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
