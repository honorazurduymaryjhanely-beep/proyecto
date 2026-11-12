<?php
/**
 * Búsqueda general del sistema
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Campo de búsqueda global con resultados agrupados por categoría. Los
 * resultados se cargan de forma asíncrona mientras el usuario escribe.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

verificarPermiso('busqueda', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$tituloPagina = 'Búsqueda General';
$moduloActivo = 'busqueda';
$scriptsPagina = ['assets/js/busqueda.js'];
require __DIR__ . '/../../includes/header.php';
?>

<h2 class="h5 mb-3">Búsqueda general</h2>

<div class="card mb-3">
    <div class="card-body">
        <label for="busqueda_global" class="form-label">Buscar en todo el sistema</label>
        <input type="text" id="busqueda_global" class="form-control"
               data-url="<?= $urlBase ?>/modulos/busqueda/buscar_ajax.php"
               data-base="<?= $urlBase ?>"
               placeholder="Nombre, cédula, NIT, código o título..." autocomplete="off" autofocus>
        <div class="form-text">Los resultados aparecen mientras escribes (mínimo 2 caracteres).</div>
    </div>
</div>

<div id="resultados_busqueda">
    <p class="text-muted">Escribe un término para comenzar la búsqueda.</p>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
