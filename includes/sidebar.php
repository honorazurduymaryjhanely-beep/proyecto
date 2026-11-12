<?php
/**
 * Menú lateral de navegación (sidebar)
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra las opciones de navegación según los permisos del rol del usuario
 * autenticado. Cada opción se muestra solo si el rol tiene permiso de "ver"
 * sobre el módulo correspondiente. Se incluye desde header.php.
 *
 * Variable esperada:
 *   $moduloActivo  string  Clave del módulo actual para resaltarlo.
 */

require_once __DIR__ . '/auth.php';

$config = $config ?? require __DIR__ . '/../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$moduloActivo = $moduloActivo ?? '';

/**
 * Definición del menú: clave de módulo => [etiqueta, ruta].
 * El orden define la presentación en pantalla.
 */
$menu = [
    'dashboard'     => ['Panel Principal', 'modulos/dashboard/index.php'],
    'estudiantes'   => ['Estudiantes', 'modulos/estudiantes/listar.php'],
    'instituciones' => ['Instituciones', 'modulos/instituciones/listar.php'],
    'convenios'     => ['Convenios', 'modulos/convenios/listar.php'],
    'practicas'     => ['Prácticas Laborales', 'modulos/practicas/listar.php'],
    'busqueda'      => ['Búsqueda General', 'modulos/busqueda/general.php'],
    'reportes'      => ['Reportes', 'modulos/reportes/index.php'],
    'usuarios'      => ['Usuarios', 'modulos/usuarios/listar.php'],
    'respaldos'     => ['Respaldos', 'modulos/respaldos/index.php'],
];

// Elemento adicional de administración que reutiliza el permiso de usuarios.
$menuAdmin = [
    'carreras'  => ['Carreras', 'modulos/carreras/index.php', 'usuarios'],
    'auditoria' => ['Auditoría', 'modulos/auditoria/index.php', 'usuarios'],
];
?>
<nav class="sidebar" id="appSidebar">
    <div class="sidebar-header">
        <img src="<?= $urlBase ?>/assets/img/logo.png" alt="Logo <?= sanitizar($config['siglas_institucion']) ?>" class="sidebar-logo">
        <h5><?= sanitizar($config['siglas_institucion']) ?><br>Prácticas Laborales</h5>
    </div>
    <ul class="nav-menu">
        <?php foreach ($menu as $clave => $item): ?>
            <?php if (tienePermiso($clave, 'ver')): ?>
                <li>
                    <a class="<?= $moduloActivo === $clave ? 'active' : '' ?>"
                       href="<?= $urlBase ?>/<?= $item[1] ?>">
                        <?= sanitizar($item[0]) ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php foreach ($menuAdmin as $clave => $item): ?>
            <?php if (tienePermiso($item[2], 'ver')): ?>
                <li>
                    <a class="<?= $moduloActivo === $clave ? 'active' : '' ?>"
                       href="<?= $urlBase ?>/<?= $item[1] ?>">
                        <?= sanitizar($item[0]) ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>
