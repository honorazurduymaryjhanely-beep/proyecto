<?php
/**
 * Plantilla de pie de página (footer)
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Cierra las etiquetas abiertas en header.php (.contenido-interno y
 * .contenido-principal), muestra el pie institucional y enlaza los
 * archivos JavaScript del sistema.
 *
 * Variable opcional antes de incluir:
 *   $scriptsPagina  array  Rutas relativas de scripts JS adicionales.
 */

$config = $config ?? require __DIR__ . '/../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$scriptsPagina = $scriptsPagina ?? [];
?>
        </div><!-- .contenido-interno -->

        <footer class="pie-pagina text-center text-muted py-3">
            <small>
                &copy; <?= date('Y') ?> <?= sanitizar($config['institucion']) ?>
                &middot; <?= sanitizar($config['nombre_sistema']) ?> v<?= sanitizar($config['version']) ?>
            </small>
        </footer>
    </div><!-- .contenido-principal -->

    <script src="<?= $urlBase ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $urlBase ?>/assets/js/validaciones.js?v=<?= sanitizar($config['version']) ?>"></script>
    <?php foreach ($scriptsPagina as $script): ?>
        <script src="<?= $urlBase ?>/<?= ltrim($script, '/') ?>?v=<?= sanitizar($config['version']) ?>"></script>
    <?php endforeach; ?>
    <script>
        // Alternar visibilidad del menú lateral en pantallas pequeñas.
        (function () {
            var btn = document.getElementById('btnToggleSidebar');
            var sidebar = document.getElementById('appSidebar');
            if (btn && sidebar) {
                btn.addEventListener('click', function () {
                    sidebar.classList.toggle('sidebar-visible');
                });
            }
        })();
    </script>
</body>
</html>
