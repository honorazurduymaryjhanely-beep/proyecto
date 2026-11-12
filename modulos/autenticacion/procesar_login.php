<?php
/**
 * Procesamiento del inicio de sesión
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Valida el token CSRF, aplica rate limiting por IP (3 intentos fallidos
 * bloquean 5 minutos), verifica las credenciales, crea la sesión con
 * regeneración de ID (anti session fixation) y registra la auditoría.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../clases/Usuario.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

$config = require __DIR__ . '/../../config/app.php';

// Solo se acepta el método POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('modulos/autenticacion/login.php');
}

// --- Validación del token CSRF ---------------------------------------------
$token = $_POST['token_csrf'] ?? '';
if (!validarTokenCSRF($token)) {
    mostrarMensaje('error', 'La sesión del formulario ha expirado. Intente nuevamente.');
    redirigir('modulos/autenticacion/login.php');
}

// --- Rate limiting por IP ---------------------------------------------------
$ip = obtenerIP();
$maxIntentos = (int) ($config['intentos_login_max'] ?? 3);
$bloqueoSegundos = ((int) ($config['bloqueo_minutos'] ?? 5)) * 60;
$ahora = time();

if (!isset($_SESSION['intentos_login']) || !is_array($_SESSION['intentos_login'])) {
    $_SESSION['intentos_login'] = [];
}
if (!isset($_SESSION['intentos_login'][$ip])) {
    $_SESSION['intentos_login'][$ip] = ['contador' => 0, 'bloqueo_hasta' => 0];
}
$registroIp = &$_SESSION['intentos_login'][$ip];

// Si la IP está bloqueada y el bloqueo no ha expirado, denegar.
if ($registroIp['bloqueo_hasta'] > $ahora) {
    $restante = (int) ceil(($registroIp['bloqueo_hasta'] - $ahora) / 60);
    mostrarMensaje('error', "Demasiados intentos fallidos. Intente nuevamente en {$restante} minuto(s).");
    redirigir('modulos/autenticacion/login.php');
}

// Si el bloqueo ya expiró, reiniciar el contador.
if ($registroIp['bloqueo_hasta'] !== 0 && $registroIp['bloqueo_hasta'] <= $ahora) {
    $registroIp = ['contador' => 0, 'bloqueo_hasta' => 0];
}

// --- Verificación de credenciales -------------------------------------------
$nombreUsuario = trim($_POST['nombre_usuario'] ?? '');
$contrasena = $_POST['contrasena'] ?? '';

if ($nombreUsuario === '' || $contrasena === '') {
    mostrarMensaje('error', 'Debe ingresar el nombre de usuario y la contraseña.');
    redirigir('modulos/autenticacion/login.php');
}

$usuario = Usuario::verificarCredenciales($nombreUsuario, $contrasena);

if ($usuario === null) {
    // Incrementar el contador de intentos fallidos.
    $registroIp['contador']++;
    if ($registroIp['contador'] >= $maxIntentos) {
        $registroIp['bloqueo_hasta'] = $ahora + $bloqueoSegundos;
    }
    mostrarMensaje('error', 'Nombre de usuario o contraseña incorrectos.');
    redirigir('modulos/autenticacion/login.php');
}

// --- Inicio de sesión exitoso -----------------------------------------------
// Reiniciar los intentos de esta IP.
$registroIp = ['contador' => 0, 'bloqueo_hasta' => 0];

// Prevenir session fixation regenerando el ID de sesión.
session_regenerate_id(true);

// Cargar los datos del usuario en la sesión.
$_SESSION['id_usuario']      = (int) $usuario['id_usuario'];
$_SESSION['nombre_completo'] = $usuario['nombre_completo'];
$_SESSION['nombre_usuario']  = $usuario['nombre_usuario'];
$_SESSION['rol']             = $usuario['nombre_rol'];
$_SESSION['carrera']         = $usuario['carrera'] ?? '';
$_SESSION['permisos']        = json_decode($usuario['permisos'] ?? '[]', true) ?: [];
$_SESSION['ultima_actividad'] = time();

// Registrar el último acceso y la auditoría de login.
Usuario::registrarUltimoAcceso((int) $usuario['id_usuario']);
Auditoria::registrar(
    (int) $usuario['id_usuario'],
    'login',
    'usuarios',
    (int) $usuario['id_usuario']
);

redirigir('modulos/dashboard/index.php');
