<?php
/**
 * Plantilla de encabezado (header)
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Renderiza el inicio del documento HTML, metadatos, hojas de estilo,
 * cabeceras de seguridad HTTP, el menú lateral y la barra superior.
 * La estructura de clases se corresponde con assets/css/estilos.css.
 *
 * Variables opcionales antes de incluir:
 *   $tituloPagina  string  Título específico de la página.
 *   $moduloActivo  string  Módulo actual (para resaltar el menú lateral).
 */

require_once __DIR__ . '/auth.php';

$config = require __DIR__ . '/../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

// Cabeceras HTTP (solo si aún no se envió salida).
if (!headers_sent()) {
    // Declarar explícitamente la codificación UTF-8 de la respuesta.
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self';");
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

$usuarioSesion = usuarioActual();
$usuario = $usuario ?? $usuarioSesion;
$tituloPagina = $tituloPagina ?? 'Panel';
$moduloActivo = $moduloActivo ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= sanitizar($tituloPagina) ?> · <?= sanitizar($config['siglas_institucion']) ?></title>
    <link rel="stylesheet" href="<?= $urlBase ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $urlBase ?>/assets/css/estilos.css">
</head>
<body>
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="contenido-principal">
        <header class="header-superior">
            <div class="d-flex align-items-center gap-2">
                <button class="btn-toggle-sidebar" type="button" id="btnToggleSidebar" aria-label="Alternar menú">
                    &#9776;
                </button>
                <h1 class="header-titulo"><?= sanitizar($tituloPagina) ?></h1>
            </div>

            <?php if ($usuarioSesion !== null): ?>
            <div class="header-usuario dropdown">
                <span class="nombre-usuario"><?= sanitizar($usuarioSesion['nombre']) ?></span>
                <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <?= sanitizar(etiquetaRol($usuarioSesion['rol'])) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted"><?= sanitizar($usuarioSesion['usuario']) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?= $urlBase ?>/modulos/usuarios/perfil.php">
                            Mi perfil y contraseña
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item text-danger btn-cerrar-sesion"
                           href="<?= $urlBase ?>/modulos/autenticacion/logout.php">
                            Cerrar sesión
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </header>

        <div class="contenido-interno">
            <?= renderizarMensajeFlash() ?>
