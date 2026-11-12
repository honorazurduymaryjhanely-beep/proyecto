<?php
/**
 * Configuración de Sesiones PHP
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 * 
 * Este archivo configura los parámetros de sesión con medidas de seguridad:
 * - Cookie httponly para prevenir acceso desde JavaScript
 * - SameSite Lax para protección CSRF y navegación fluida local
 * - Tiempo de inactividad de 30 minutos
 * - Recolección de basura (garbage collection)
 * - Cookie path dinámico adaptado a la subcarpeta del proyecto (ej: /pasantias2/)
 * 
 * Debe incluirse al inicio de cada página que requiera acceso a sesión.
 */

// ============================================================
// Configuración dinámica de parámetros de cookie de sesión
// ============================================================
$configApp = require __DIR__ . '/app.php';
$cookiePath = '/' . trim($configApp['url_base'] ?? 'pasantias2', '/');

$parametros_cookie = [
    'lifetime' => 0,             // La cookie expira al cerrar el navegador
    'path'     => $cookiePath,   // Ruta del cookie vinculada a la URL base del proyecto
    'domain'   => '',            // Dominio vacío para localhost
    'secure'   => false,         // false porque XAMPP local no usa HTTPS
    'httponly'  => true,          // Prevenir acceso desde JavaScript (protección XSS)
    'samesite' => 'Lax'          // Protección contra CSRF permitiendo navegación estándar
];

// ============================================================
// Nombre personalizado de la sesión
// ============================================================
// Usar un nombre distinto al predeterminado (PHPSESSID) para mayor seguridad
session_name('ITSEC_SESION');

// ============================================================
// Aplicar parámetros de cookie de sesión
// ============================================================
session_set_cookie_params($parametros_cookie);

// ============================================================
// Configuración del tiempo de vida y recolección de basura
// ============================================================
// Tiempo máximo de vida de los datos de sesión en el servidor (30 minutos)
ini_set('session.gc_maxlifetime', 1800);

// Probabilidad de que se ejecute el garbage collector (1/100 = 1% por cada request)
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// ============================================================
// Configuración adicional de seguridad de sesión
// ============================================================
// Usar solo cookies para el identificador de sesión (no pasar por URL)
ini_set('session.use_only_cookies', 1);

// No aceptar IDs de sesión no inicializados por el servidor
ini_set('session.use_strict_mode', 1);

// ============================================================
// Iniciar la sesión si no está activa
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Control de tiempo de inactividad (30 minutos = 1800 segundos)
// ============================================================
$tiempo_inactividad = 1800;

if (isset($_SESSION['ultima_actividad'])) {
    $tiempo_transcurrido = time() - $_SESSION['ultima_actividad'];

    if ($tiempo_transcurrido > $tiempo_inactividad) {
        // La sesión ha expirado por inactividad
        session_unset();
        session_destroy();

        // Reiniciar la sesión para poder establecer un mensaje de error
        session_name('ITSEC_SESION');
        session_set_cookie_params($parametros_cookie);
        session_start();

        $_SESSION['error_sesion'] = 'Su sesión ha expirado por inactividad. Por favor, inicie sesión nuevamente.';
        header('Location: ' . rtrim($cookiePath, '/') . '/modulos/autenticacion/login.php?error=sesion_expirada');
        exit;
    }
}

// Actualizar la marca de tiempo de última actividad
$_SESSION['ultima_actividad'] = time();
