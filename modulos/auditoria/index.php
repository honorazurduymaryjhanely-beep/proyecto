<?php
/**
 * Log de auditoría del sistema
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra el registro cronológico de eventos y acciones realizadas en el
 * sistema con filtros por usuario, acción, tabla y fecha.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

verificarPermiso('usuarios', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$porPagina = (int) ($config['registros_por_pagina'] ?? 20);

$pagina = max(1, (int) ($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

$filtros = [
    'usuario' => trim($_GET['usuario'] ?? ''),
    'accion'  => trim($_GET['accion'] ?? ''),
    'tabla'   => trim($_GET['tabla'] ?? ''),
    'desde'   => trim($_GET['desde'] ?? ''),
    'hasta'   => trim($_GET['hasta'] ?? ''),
];

$db = Database::obtenerInstancia();

// Construcción de condiciones WHERE
$cond = [];
$params = [];
if (!empty($filtros['usuario'])) {
    $cond[] = '(u.nombre_completo LIKE :usuario OR u.nombre_usuario LIKE :usuario)';
    $params[':usuario'] = '%' . $filtros['usuario'] . '%';
}
if (!empty($filtros['accion'])) {
    $cond[] = 'a.accion = :accion';
    $params[':accion'] = $filtros['accion'];
}
if (!empty($filtros['tabla'])) {
    $cond[] = 'a.tabla_afectada = :tabla';
    $params[':tabla'] = $filtros['tabla'];
}
if (!empty($filtros['desde'])) {
    $cond[] = 'DATE(a.fecha_hora) >= :desde';
    $params[':desde'] = $filtros['desde'];
}
if (!empty($filtros['hasta'])) {
    $cond[] = 'DATE(a.fecha_hora) <= :hasta';
    $params[':hasta'] = $filtros['hasta'];
}
$whereSql = !empty($cond) ? 'WHERE ' . implode(' AND ', $cond) : '';

// Conteo total
$stmtCount = $db->prepare("SELECT COUNT(*) FROM auditoria a LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario {$whereSql}");
$stmtCount->execute($params);
$total = (int) $stmtCount->fetchColumn();

$paginacion = paginar($total, $pagina, $porPagina);

// Consulta paginada
$sql = "SELECT a.id_auditoria, a.accion, a.tabla_afectada, a.id_registro,
               a.datos_anteriores, a.datos_nuevos, a.ip_direccion, a.fecha_hora,
               u.nombre_completo, u.nombre_usuario
        FROM auditoria a
        LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
        {$whereSql}
        ORDER BY a.fecha_hora DESC
        LIMIT :limite OFFSET :offset";
$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$registros = $stmt->fetchAll();

// Listado de tablas para selector
$tablas = $db->query('SELECT DISTINCT tabla_afectada FROM auditoria ORDER BY tabla_afectada')->fetchAll(PDO::FETCH_COLUMN);

$badgesAccion = [
    'crear'           => 'badge bg-success',
    'modificar'       => 'badge bg-primary',
    'eliminar'        => 'badge bg-danger',
    'login'           => 'badge bg-info text-dark',
    'logout'          => 'badge bg-secondary',
    'acceso_denegado' => 'badge bg-warning text-dark',
];

$tituloPagina = 'Log de Auditoría';
$moduloActivo = 'auditoria';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Log de auditoría del sistema</h2>
    <span class="badge bg-secondary"><?= number_format($total) ?> evento(s)</span>
</div>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/auditoria/index.php" method="get" class="row g-2">
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" name="usuario"
                       placeholder="Usuario o nombre..." value="<?= sanitizar($filtros['usuario']) ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="accion">
                    <option value="">Todas las acciones</option>
                    <option value="crear" <?= $filtros['accion'] === 'crear' ? 'selected' : '' ?>>Crear</option>
                    <option value="modificar" <?= $filtros['accion'] === 'modificar' ? 'selected' : '' ?>>Modificar</option>
                    <option value="eliminar" <?= $filtros['accion'] === 'eliminar' ? 'selected' : '' ?>>Eliminar</option>
                    <option value="login" <?= $filtros['accion'] === 'login' ? 'selected' : '' ?>>Login</option>
                    <option value="logout" <?= $filtros['accion'] === 'logout' ? 'selected' : '' ?>>Logout</option>
                    <option value="acceso_denegado" <?= $filtros['accion'] === 'acceso_denegado' ? 'selected' : '' ?>>Acceso denegado</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="tabla">
                    <option value="">Todas las tablas</option>
                    <?php foreach ($tablas as $t): ?>
                        <option value="<?= sanitizar($t) ?>" <?= $filtros['tabla'] === $t ? 'selected' : '' ?>><?= sanitizar($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" name="desde"
                       value="<?= sanitizar($filtros['desde']) ?>" title="Desde fecha">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" name="hasta"
                       value="<?= sanitizar($filtros['hasta']) ?>" title="Hasta fecha">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-outline-primary btn-sm w-100">Filtrar</button>
                <a href="<?= $urlBase ?>/modulos/auditoria/index.php" class="btn btn-outline-secondary btn-sm" title="Limpiar">×</a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Tabla / Módulo</th>
                        <th>ID Reg.</th>
                        <th>Dirección IP</th>
                        <th class="text-end">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registros)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron registros de auditoría.</td></tr>
                    <?php else: ?>
                        <?php foreach ($registros as $idx => $r): ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d/m/Y H:i:s', strtotime($r['fecha_hora'])) ?></td>
                                <td>
                                    <?php if ($r['nombre_completo']): ?>
                                        <span class="fw-medium"><?= sanitizar($r['nombre_completo']) ?></span>
                                        <small class="text-muted">(<?= sanitizar($r['nombre_usuario']) ?>)</small>
                                    <?php else: ?>
                                        <span class="text-muted">Sistema</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="<?= $badgesAccion[$r['accion']] ?? 'badge bg-secondary' ?>">
                                        <?= sanitizar(ucfirst(str_replace('_', ' ', $r['accion']))) ?>
                                    </span>
                                </td>
                                <td><code><?= sanitizar($r['tabla_afectada']) ?></code></td>
                                <td><?= $r['id_registro'] ? (int) $r['id_registro'] : '—' ?></td>
                                <td class="text-muted"><?= sanitizar($r['ip_direccion'] ?: '—') ?></td>
                                <td class="text-end">
                                    <?php if (!empty($r['datos_anteriores']) || !empty($r['datos_nuevos'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
                                                data-bs-toggle="collapse" data-bs-target="#detalle-<?= $idx ?>">
                                            Ver datos
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($r['datos_anteriores']) || !empty($r['datos_nuevos'])): ?>
                                <tr class="collapse bg-light" id="detalle-<?= $idx ?>">
                                    <td colspan="7" class="p-3">
                                        <div class="row g-2">
                                            <?php if (!empty($r['datos_anteriores'])): ?>
                                                <div class="col-md-6">
                                                    <strong>Datos anteriores:</strong>
                                                    <pre class="bg-white p-2 border rounded mb-0 mt-1 small" style="max-height: 200px; overflow: auto;"><?= sanitizar(json_encode(json_decode($r['datos_anteriores']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($r['datos_nuevos'])): ?>
                                                <div class="col-md-6">
                                                    <strong>Datos nuevos:</strong>
                                                    <pre class="bg-white p-2 border rounded mb-0 mt-1 small" style="max-height: 200px; overflow: auto;"><?= sanitizar(json_encode(json_decode($r['datos_nuevos']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($paginacion['total_paginas'] > 1): ?>
        <div class="card-footer bg-white py-2">
            <?= renderizarPaginacion($paginacion, $urlBase . '/modulos/auditoria/index.php', $filtros) ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
