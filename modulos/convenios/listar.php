<?php
/**
 * Listado de convenios institucionales
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Convenio.php';
require_once __DIR__ . '/../../clases/Institucion.php';

verificarPermiso('convenios', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$porPagina = (int) ($config['registros_por_pagina'] ?? 15);

$pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
$filtros = [
    'buscar'         => trim($_GET['buscar'] ?? ''),
    'id_institucion' => $_GET['id_institucion'] ?? '',
    'estado'         => $_GET['estado'] ?? '',
];

$total      = Convenio::contarTotal($filtros);
$paginacion = paginar($total, $pagina, $porPagina);
$convenios  = Convenio::listar($paginacion['pagina'], $filtros, $porPagina);
$instituciones = Institucion::obtenerActivas();

$puedeCrear  = tienePermiso('convenios', 'crear');
$puedeEditar = tienePermiso('convenios', 'editar');

$qsFiltros = http_build_query(array_filter([
    'buscar'         => $filtros['buscar'],
    'id_institucion' => $filtros['id_institucion'],
    'estado'         => $filtros['estado'],
], fn($v) => $v !== ''));

$badges = ['vigente' => 'badge-vigente', 'expirado' => 'badge-expirado', 'cancelado' => 'bg-secondary'];

$etiquetaRegistros = 'convenios';
$tituloPagina = 'Gestión de Convenios';
$moduloActivo = 'convenios';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Convenios institucionales</h2>
    <?php if ($puedeCrear): ?>
        <a href="<?= $urlBase ?>/modulos/convenios/crear.php" class="btn btn-primary btn-sm">Nuevo convenio</a>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-4">
        <input type="text" name="buscar" class="form-control form-control-sm"
               placeholder="Buscar por título o código" value="<?= sanitizar($filtros['buscar']) ?>">
    </div>
    <div class="col-md-4">
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
        <select name="estado" class="form-select form-select-sm">
            <option value="">Todos</option>
            <option value="vigente" <?= $filtros['estado'] === 'vigente' ? 'selected' : '' ?>>Vigentes</option>
            <option value="expirado" <?= $filtros['estado'] === 'expirado' ? 'selected' : '' ?>>Expirados</option>
            <option value="cancelado" <?= $filtros['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelados</option>
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
                <th>Título</th>
                <th>Institución</th>
                <th>Vigencia</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($convenios)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No se encontraron convenios.</td></tr>
            <?php else: ?>
                <?php foreach ($convenios as $c): ?>
                    <tr>
                        <td><?= sanitizar($c['codigo_convenio']) ?></td>
                        <td><?= sanitizar($c['titulo']) ?></td>
                        <td><?= sanitizar($c['institucion_nombre']) ?></td>
                        <td><?= sanitizar(formatearFecha($c['fecha_inicio_vigencia'])) ?> — <?= sanitizar(formatearFecha($c['fecha_fin_vigencia'])) ?></td>
                        <td><span class="badge <?= sanitizar($badges[$c['estado']] ?? 'bg-secondary') ?>"><?= sanitizar(ucfirst($c['estado'])) ?></span></td>
                        <td class="text-end acciones-tabla">
                            <a href="<?= $urlBase ?>/modulos/convenios/ver.php?id=<?= (int) $c['id_convenio'] ?>"
                               class="btn btn-outline-primary">Ver</a>
                            <?php if ($puedeEditar): ?>
                                <a href="<?= $urlBase ?>/modulos/convenios/editar.php?id=<?= (int) $c['id_convenio'] ?>"
                                   class="btn btn-outline-secondary">Editar</a>
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
