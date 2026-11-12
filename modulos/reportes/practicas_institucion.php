<?php
/**
 * Reporte de prácticas por institución - formulario de selección
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Institucion.php';

verificarPermiso('reportes', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$instituciones = Institucion::obtenerActivas();

$tituloPagina = 'Reporte de Prácticas por Institución';
$moduloActivo = 'reportes';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Prácticas por institución</h2>
    <a href="<?= $urlBase ?>/modulos/reportes/index.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/reportes/generar_pdf.php" method="get" target="_blank" data-validar-form novalidate>
            <input type="hidden" name="tipo" value="practicas_institucion">
            <div class="mb-3">
                <label class="form-label">Institución *</label>
                <select class="form-select" name="id_institucion" data-validar="requerido" required>
                    <option value="">Seleccione...</option>
                    <?php foreach ($instituciones as $i): ?>
                        <option value="<?= (int) $i['id_institucion'] ?>"><?= sanitizar($i['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-danger">Generar PDF</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
