<?php
/**
 * Menú de reportes disponibles
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

verificarPermiso('reportes', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$reportes = [
    ['practicas_periodo.php', 'Prácticas por período', 'Listado de prácticas en un rango de fechas.'],
    ['horas_estudiante.php', 'Horas por estudiante', 'Detalle de horas cumplidas por un estudiante.'],
    ['convenios.php', 'Convenios y vigencia', 'Estado de vigencia de todos los convenios.'],
    ['practicas_institucion.php', 'Prácticas por institución', 'Prácticas asociadas a una institución.'],
];

$tituloPagina = 'Reportes';
$moduloActivo = 'reportes';
require __DIR__ . '/../../includes/header.php';
?>

<h2 class="h5 mb-3">Reportes disponibles</h2>

<div class="row g-3">
    <?php foreach ($reportes as $r): ?>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <h3 class="h6"><?= sanitizar($r[1]) ?></h3>
                    <p class="text-muted small flex-grow-1"><?= sanitizar($r[2]) ?></p>
                    <a href="<?= $urlBase ?>/modulos/reportes/<?= $r[0] ?>" class="btn btn-outline-primary btn-sm">Generar</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
