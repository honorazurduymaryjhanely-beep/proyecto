<?php
/**
 * Detalle de convenio
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Convenio.php';

verificarPermiso('convenios', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$id = (int) ($_GET['id'] ?? 0);
$c = Convenio::obtenerPorId($id);
if ($c === null) {
    mostrarMensaje('error', 'El convenio solicitado no existe.');
    redirigir('modulos/convenios/listar.php');
}

// Estado calculado a la fecha actual (por si difiere del almacenado).
$estadoCalculado = Convenio::calcularEstado($c['fecha_inicio_vigencia'], $c['fecha_fin_vigencia'], $c['estado']);
$badges = ['vigente' => 'badge-vigente', 'expirado' => 'badge-expirado', 'cancelado' => 'bg-secondary'];

$tituloPagina = 'Detalle de Convenio';
$moduloActivo = 'convenios';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Convenio <small class="text-muted"><?= sanitizar($c['codigo_convenio']) ?></small></h2>
    <a href="<?= $urlBase ?>/modulos/convenios/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Título</dt>
            <dd class="col-sm-9"><?= sanitizar($c['titulo']) ?></dd>
            <dt class="col-sm-3">Institución</dt>
            <dd class="col-sm-9"><?= sanitizar($c['institucion_nombre']) ?></dd>
            <dt class="col-sm-3">Descripción</dt>
            <dd class="col-sm-9"><?= nl2br(sanitizar($c['descripcion'] ?: '—')) ?></dd>
            <dt class="col-sm-3">Fecha de firma</dt>
            <dd class="col-sm-9"><?= sanitizar(formatearFecha($c['fecha_firma'])) ?></dd>
            <dt class="col-sm-3">Vigencia</dt>
            <dd class="col-sm-9"><?= sanitizar(formatearFecha($c['fecha_inicio_vigencia'])) ?> — <?= sanitizar(formatearFecha($c['fecha_fin_vigencia'])) ?></dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><span class="badge <?= sanitizar($badges[$estadoCalculado] ?? 'bg-secondary') ?>"><?= sanitizar(ucfirst($estadoCalculado)) ?></span></dd>
            <dt class="col-sm-3">Documento adjunto</dt>
            <dd class="col-sm-9">
                <?php if (!empty($c['documento_adjunto'])): ?>
                    <a href="<?= $urlBase ?>/uploads/convenios/<?= rawurlencode($c['documento_adjunto']) ?>" target="_blank">
                        Descargar documento
                    </a>
                <?php else: ?>
                    <span class="text-muted">Sin documento adjunto.</span>
                <?php endif; ?>
            </dd>
        </dl>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
