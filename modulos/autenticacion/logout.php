<?php
/**
 * Cierre de sesión
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Registra la auditoría de cierre, destruye la sesión y las cookies
 * asociadas, y redirige al formulario de inicio de sesión.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

// Registrar la auditoría de cierre de sesión antes de destruirla.
if (!empty($_SESSION['id_usuario'])) {
    Auditoria::registrar(
        (int) $_SESSION['id_usuario'],
        'logout',
        'usuarios',
        (int) $_SESSION['id_usuario']
    );
}

// Vaciar los datos de sesión.
$_SESSION = [];

// Eliminar la cookie de sesión del navegador.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destruir la sesión en el servidor.
session_destroy();

// Redirigir al login con un mensaje informativo.
$config = require __DIR__ . '/../../config/app.php';
$base = rtrim($config['url_base'], '/');
header('Location: ' . $base . '/modulos/autenticacion/login.php');
exit;
