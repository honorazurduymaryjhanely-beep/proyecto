<?php
/**
 * Procesar la generación de un respaldo
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Respaldo.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarPermiso('respaldos', 'crear');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('modulos/respaldos/index.php');
}
if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
    mostrarMensaje('error', 'Token de seguridad inválido.');
    redirigir('modulos/respaldos/index.php');
}

$idUsuario = (int) $_SESSION['id_usuario'];
[$ok, $resultado] = Respaldo::generar($idUsuario);

if ($ok) {
    Auditoria::registrar($idUsuario, 'crear', 'respaldos', null, null, ['archivo' => $resultado]);
    mostrarMensaje('exito', 'Respaldo generado correctamente: ' . $resultado);
} else {
    mostrarMensaje('error', $resultado);
}
redirigir('modulos/respaldos/index.php');
