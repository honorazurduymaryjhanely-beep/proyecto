<?php
/**
 * Reporte de convenios y vigencia - enlace de generación
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

verificarPermiso('reportes', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$tituloPagina = 'Reporte de Convenios';
$moduloActivo = 'reportes';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Convenios y vigencia</h2>
    <a href="<?= $urlBase ?>/modulos/reportes/index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <p>Genera un reporte con todos los convenios registrados y su estado de vigencia calculado a la fecha actual.</p>
        <form action="<?= $urlBase ?>/modulos/reportes/generar_pdf.php" method="get" target="_blank">
            <input type="hidden" name="tipo" value="convenios">
            <div class="mb-3">
                <label class="form-label">Filtrar por estado</label>
                <select class="form-select" name="estado">
                    <option value="">Todos</option>
                    <option value="vigente">Vigentes</option>
                    <option value="expirado">Expirados</option>
                    <option value="cancelado">Cancelados</option>
                </select>
            </div>
            <button type="submit" class="btn btn-danger">Generar PDF</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
