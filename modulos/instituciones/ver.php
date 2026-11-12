<?php
/**
 * Detalle de institución receptora
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra los datos completos de la institución y la lista de convenios
 * vinculados a ella.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Institucion.php';
require_once __DIR__ . '/../../config/database.php';

verificarPermiso('instituciones', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$id = (int) ($_GET['id'] ?? 0);
$i = Institucion::obtenerPorId($id);
if ($i === null) {
    mostrarMensaje('error', 'La institución solicitada no existe.');
    redirigir('modulos/instituciones/listar.php');
}

// Convenios vinculados a la institución.
$convenios = [];
try {
    $db = Database::obtenerInstancia();
    $stmt = $db->prepare('SELECT codigo_convenio, titulo, fecha_inicio_vigencia,
                                 fecha_fin_vigencia, estado
                          FROM convenios
                          WHERE id_institucion = :id
                          ORDER BY fecha_fin_vigencia DESC');
    $stmt->execute([':id' => $id]);
    $convenios = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Error al obtener convenios de institución: ' . $e->getMessage());
}

$tituloPagina = 'Detalle de Institución';
$moduloActivo = 'instituciones';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Institución <small class="text-muted"><?= sanitizar($i['codigo_institucion']) ?></small></h2>
    <a href="<?= $urlBase ?>/modulos/instituciones/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Nombre</dt>
            <dd class="col-sm-9"><?= sanitizar($i['nombre']) ?></dd>
            <dt class="col-sm-3">NIT</dt>
            <dd class="col-sm-9"><?= sanitizar($i['nit']) ?></dd>
            <dt class="col-sm-3">Dirección</dt>
            <dd class="col-sm-9"><?= sanitizar($i['direccion']) ?></dd>
            <dt class="col-sm-3">Teléfono institución</dt>
            <dd class="col-sm-9"><?= sanitizar($i['telefono']) ?></dd>
            <dt class="col-sm-3">Correo electrónico</dt>
            <dd class="col-sm-9"><?= sanitizar($i['correo_electronico'] ?: '—') ?></dd>
            <dt class="col-sm-3">MAE</dt>
            <dd class="col-sm-9"><?= sanitizar($i['nombre_mae'] ?: '—') ?><?= !empty($i['telefono_mae']) ? ' · Tel: ' . sanitizar($i['telefono_mae']) : '' ?></dd>
            <dt class="col-sm-3">Persona de contacto</dt>
            <dd class="col-sm-9"><?= sanitizar($i['nombre_contacto']) ?> (<?= sanitizar($i['cargo_contacto']) ?>)<?= !empty($i['telefono_contacto']) ? ' · Tel: ' . sanitizar($i['telefono_contacto']) : '' ?></dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9">
                <?php if ((int) $i['estado'] === 1): ?>
                    <span class="badge bg-success">Activa</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Inactiva</span>
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Convenios vinculados</div>
    <div class="card-body p-0">
        <div class="tabla-personalizada">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Título</th>
                        <th>Vigencia</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($convenios)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Sin convenios vinculados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($convenios as $c): ?>
                            <tr>
                                <td><?= sanitizar($c['codigo_convenio']) ?></td>
                                <td><?= sanitizar($c['titulo']) ?></td>
                                <td><?= sanitizar(formatearFecha($c['fecha_inicio_vigencia'])) ?> — <?= sanitizar(formatearFecha($c['fecha_fin_vigencia'])) ?></td>
                                <td><span class="badge badge-<?= sanitizar($c['estado']) ?>"><?= sanitizar(ucfirst($c['estado'])) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
