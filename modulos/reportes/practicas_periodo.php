<?php
/**
 * Reporte de prácticas por período - formulario de selección
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

verificarPermiso('reportes', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$tituloPagina = 'Reporte de Prácticas por Período';
$moduloActivo = 'reportes';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Prácticas por período</h2>
    <a href="<?= $urlBase ?>/modulos/reportes/index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/reportes/generar_pdf.php" method="get" target="_blank" data-validar-form novalidate>
            <input type="hidden" name="tipo" value="practicas_periodo">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Fecha desde *</label>
                    <input type="date" class="form-control" name="fecha_desde" data-validar="requerido" data-fecha-inicio required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha hasta *</label>
                    <input type="date" class="form-control" name="fecha_hasta" data-validar="requerido" data-fecha-fin required>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-danger">Generar PDF</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
