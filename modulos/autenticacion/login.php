<?php
/**
 * Formulario de inicio de sesión
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra el formulario de acceso. Si ya existe una sesión activa,
 * redirige directamente al panel principal.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/helpers.php';

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

// Si ya hay sesión activa, ir al dashboard.
if (!empty($_SESSION['id_usuario'])) {
    redirigir('modulos/dashboard/index.php');
}

// Recuperar mensaje de error de credenciales/sesión (flash o expiración).
$mensajeFlash = mostrarMensaje();
$errorSesion = $_SESSION['error_sesion'] ?? null;
unset($_SESSION['error_sesion']);

// Generar token CSRF para el formulario.
$tokenCsrf = generarTokenCSRF();

// Declarar la codificación UTF-8 de la respuesta.
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Iniciar sesión · <?= sanitizar($config['siglas_institucion']) ?></title>
    <link rel="stylesheet" href="<?= $urlBase ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $urlBase ?>/assets/css/estilos.css">
    <link rel="stylesheet" href="<?= $urlBase ?>/assets/css/login.css">
</head>
<body class="login-body">
    <main class="login-card">
        <div class="login-card__header">
            <img src="<?= $urlBase ?>/assets/img/logo.png" alt="Logo <?= sanitizar($config['siglas_institucion']) ?>" class="login-card__logo">
            <h1 class="login-card__title"><?= sanitizar($config['siglas_institucion']) ?></h1>
            <p class="login-card__subtitle">Sistema de Gestión de Prácticas Laborales</p>
        </div>

        <div class="login-card__body">
            <?php if ($errorSesion): ?>
                <div class="alert alert-warning" role="alert"><?= sanitizar($errorSesion) ?></div>
            <?php endif; ?>

            <?php if ($mensajeFlash !== null): ?>
                <div class="alert alert-danger" role="alert"><?= sanitizar($mensajeFlash['texto']) ?></div>
            <?php endif; ?>

            <form action="<?= $urlBase ?>/modulos/autenticacion/procesar_login.php" method="post"
                  data-validar-form novalidate>
                <input type="hidden" name="token_csrf" value="<?= sanitizar($tokenCsrf) ?>">

                <div class="mb-3">
                    <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
                    <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario"
                           data-validar="requerido" autocomplete="username" autofocus required>
                </div>

                <div class="mb-4">
                    <label for="contrasena" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena"
                           data-validar="requerido" autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn-login">Ingresar</button>
            </form>
        </div>

        <div class="login-card__footer">
            <?= sanitizar($config['institucion']) ?><br>
            v<?= sanitizar($config['version']) ?>
        </div>
    </main>

    <script src="<?= $urlBase ?>/assets/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $urlBase ?>/assets/js/validaciones.js"></script>
</body>
</html>
