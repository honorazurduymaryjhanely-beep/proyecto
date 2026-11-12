<?php
/**
 * Listado de instituciones receptoras
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Institucion.php';

verificarPermiso('instituciones', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$porPagina = (int) ($config['registros_por_pagina'] ?? 15);

$pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
$filtros = [
    'buscar' => trim($_GET['buscar'] ?? ''),
    'estado' => $_GET['estado'] ?? '',
];

$total      = Institucion::contarTotal($filtros);
$paginacion = paginar($total, $pagina, $porPagina);
$instituciones = Institucion::listar($paginacion['pagina'], $filtros, $porPagina);

$puedeCrear  = tienePermiso('instituciones', 'crear');
$puedeEditar = tienePermiso('instituciones', 'editar');

$qsFiltros = http_build_query(array_filter([
    'buscar' => $filtros['buscar'],
    'estado' => $filtros['estado'],
], fn($v) => $v !== ''));

$etiquetaRegistros = 'instituciones';
$tituloPagina = 'Gestión de Instituciones';
$moduloActivo = 'instituciones';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Instituciones receptoras</h2>
    <?php if ($puedeCrear): ?>
        <a href="<?= $urlBase ?>/modulos/instituciones/crear.php" class="btn btn-primary btn-sm">Nueva institución</a>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-7">
        <input type="text" name="buscar" class="form-control form-control-sm"
               placeholder="Buscar por nombre, NIT o código" value="<?= sanitizar($filtros['buscar']) ?>">
    </div>
    <div class="col-md-3">
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todas</option>
            <option value="1" <?= $filtros['estado'] === '1' ? 'selected' : '' ?>>Activas</option>
            <option value="0" <?= $filtros['estado'] === '0' ? 'selected' : '' ?>>Inactivas</option>
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
                <th>NIT</th>
                <th>Teléfono</th>
                <th>Contacto</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($instituciones)): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No se encontraron instituciones.</td></tr>
            <?php else: ?>
                <?php foreach ($instituciones as $i): ?>
                    <tr>
                        <td><?= sanitizar($i['codigo_institucion']) ?></td>
                        <td><?= sanitizar($i['nombre']) ?></td>
                        <td><?= sanitizar($i['nit']) ?></td>
                        <td><?= sanitizar($i['telefono']) ?></td>
                        <td><?= sanitizar($i['nombre_contacto']) ?></td>
                        <td>
                            <?php if ((int) $i['estado'] === 1): ?>
                                <span class="badge bg-success">Activa</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end acciones-tabla">
                            <a href="<?= $urlBase ?>/modulos/instituciones/ver.php?id=<?= (int) $i['id_institucion'] ?>"
                               class="btn btn-outline-primary">Ver</a>
                            <?php if ($puedeEditar): ?>
                                <a href="<?= $urlBase ?>/modulos/instituciones/editar.php?id=<?= (int) $i['id_institucion'] ?>"
                                   class="btn btn-outline-secondary">Editar</a>
                                <form action="<?= $urlBase ?>/modulos/instituciones/procesar.php" method="post" class="d-inline"
                                      onsubmit="return confirm('¿Confirma cambiar el estado de esta institución?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="accion" value="cambiar_estado">
                                    <input type="hidden" name="id" value="<?= (int) $i['id_institucion'] ?>">
                                    <input type="hidden" name="estado" value="<?= (int) $i['estado'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-outline-<?= (int) $i['estado'] === 1 ? 'danger' : 'success' ?>">
                                        <?= (int) $i['estado'] === 1 ? 'Inactivar' : 'Activar' ?>
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
