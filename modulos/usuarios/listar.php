<?php
/**
 * Listado de usuarios
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Usuario.php';

verificarPermiso('usuarios', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$porPagina = (int) ($config['registros_por_pagina'] ?? 15);

$pagina  = max(1, (int) ($_GET['pagina'] ?? 1));
$filtros = [
    'buscar' => trim($_GET['buscar'] ?? ''),
    'id_rol' => $_GET['id_rol'] ?? '',
    'estado' => $_GET['estado'] ?? '',
];

$total     = Usuario::contarTotal($filtros);
$paginacion = paginar($total, $pagina, $porPagina);
$usuarios  = Usuario::listar($paginacion['pagina'], $filtros, $porPagina);
$roles     = Usuario::obtenerRoles();

$puedeCrear  = tienePermiso('usuarios', 'crear');
$puedeEditar = tienePermiso('usuarios', 'editar');
$puedeEliminar = tienePermiso('usuarios', 'eliminar');

$tituloPagina = 'Gestión de Usuarios';
$moduloActivo = 'usuarios';
require __DIR__ . '/../../includes/header.php';

// Construir la query string base para conservar filtros en la paginación.
$qsFiltros = http_build_query(array_filter([
    'buscar' => $filtros['buscar'],
    'id_rol' => $filtros['id_rol'],
    'estado' => $filtros['estado'],
], fn($v) => $v !== ''));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Usuarios</h2>
    <?php if ($puedeCrear): ?>
        <a href="<?= $urlBase ?>/modulos/usuarios/crear.php" class="btn btn-primary btn-sm">Nuevo usuario</a>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-md-5">
        <input type="text" name="buscar" class="form-control form-control-sm"
               placeholder="Buscar por nombre o usuario" value="<?= sanitizar($filtros['buscar']) ?>">
    </div>
    <div class="col-md-3">
        <select name="id_rol" class="form-select form-select-sm">
            <option value="">Todos los roles</option>
            <?php foreach ($roles as $r): ?>
                <option value="<?= (int) $r['id_rol'] ?>" <?= (string) $filtros['id_rol'] === (string) $r['id_rol'] ? 'selected' : '' ?>>
                    <?= sanitizar(etiquetaRol($r['nombre_rol'])) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
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
                <th>Nombre completo</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estado</th>
                <th class="text-end">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($usuarios)): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No se encontraron usuarios.</td></tr>
            <?php else: ?>
                <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?= sanitizar($u['nombre_completo']) ?></td>
                        <td><?= sanitizar($u['nombre_usuario']) ?></td>
                        <td>
                            <span class="badge bg-light text-dark border"><?= sanitizar(etiquetaRol($u['nombre_rol'])) ?></span>
                            <?php if (!empty($u['carrera'])): ?>
                                <br><small class="text-primary fw-medium"><?= sanitizar($u['carrera']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($u['telefono'])): ?>
                                <br><small class="text-muted">Tel: <?= sanitizar($u['telefono']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int) $u['estado'] === 1): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end acciones-tabla">
                            <?php if ($puedeEditar): ?>
                                <a href="<?= $urlBase ?>/modulos/usuarios/editar.php?id=<?= (int) $u['id_usuario'] ?>"
                                   class="btn btn-outline-secondary btn-sm">Editar</a>
                                <form action="<?= $urlBase ?>/modulos/usuarios/procesar.php" method="post" class="d-inline"
                                      onsubmit="return confirm('¿Confirma cambiar el estado de este usuario?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="accion" value="cambiar_estado">
                                    <input type="hidden" name="id" value="<?= (int) $u['id_usuario'] ?>">
                                    <input type="hidden" name="estado" value="<?= (int) $u['estado'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-outline-<?= (int) $u['estado'] === 1 ? 'warning' : 'success' ?> btn-sm">
                                        <?= (int) $u['estado'] === 1 ? 'Deshabilitar' : 'Habilitar' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <?php if ($puedeEliminar && (int) $u['id_usuario'] !== (int) $_SESSION['id_usuario']): ?>
                                <form action="<?= $urlBase ?>/modulos/usuarios/procesar.php" method="post" class="d-inline"
                                      onsubmit="return confirm('¿Está seguro de ELIMINAR definitivamente al usuario <?= sanitizar($u['nombre_usuario']) ?>?\n\nEsta acción borrará el registro de la base de datos y no se puede deshacer.');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $u['id_usuario'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar definitivamente">
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

<?php if ($paginacion['total_paginas'] > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($i = 1; $i <= $paginacion['total_paginas']; $i++): ?>
            <li class="page-item <?= $i === $paginacion['pagina'] ? 'active' : '' ?>">
                <a class="page-link" href="?pagina=<?= $i ?><?= $qsFiltros ? '&' . $qsFiltros : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<p class="text-muted small mt-2">
    Mostrando <?= $paginacion['inicio'] ?>-<?= $paginacion['fin'] ?> de <?= $paginacion['total'] ?> usuarios.
</p>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
