<?php
/**
 * Procesador del catálogo de carreras
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Carrera.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('modulos/carreras/index.php');
}
if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
    mostrarMensaje('error', 'Token de seguridad inválido.');
    redirigir('modulos/carreras/index.php');
}

// La gestión del catálogo requiere permiso administrativo.
verificarPermiso('usuarios', 'crear');

$accion = $_POST['accion'] ?? '';
$idUsuario = (int) $_SESSION['id_usuario'];

switch ($accion) {
    case 'crear':
        $nombre = trim($_POST['nombre'] ?? '');
        if ($nombre === '') {
            mostrarMensaje('error', 'El nombre de la carrera es obligatorio.');
            redirigir('modulos/carreras/index.php');
        }
        if (!Carrera::nombreDisponible($nombre)) {
            mostrarMensaje('error', 'Ya existe una carrera con ese nombre.');
            redirigir('modulos/carreras/index.php');
        }
        $id = Carrera::crear($nombre);
        if ($id === null) {
            mostrarMensaje('error', 'No se pudo agregar la carrera.');
            redirigir('modulos/carreras/index.php');
        }
        Auditoria::registrar($idUsuario, 'crear', 'carreras', $id, null, ['nombre' => $nombre]);
        mostrarMensaje('exito', 'Carrera agregada correctamente.');
        redirigir('modulos/carreras/index.php');
        break;

    case 'cambiar_estado':
        $id = (int) ($_POST['id'] ?? 0);
        $estado = (int) ($_POST['estado'] ?? 1);
        if (!Carrera::cambiarEstado($id, $estado)) {
            mostrarMensaje('error', 'No se pudo cambiar el estado de la carrera.');
            redirigir('modulos/carreras/index.php');
        }
        Auditoria::registrar($idUsuario, 'modificar', 'carreras', $id, null, ['estado' => $estado]);
        mostrarMensaje('exito', 'Estado de la carrera actualizado.');
        redirigir('modulos/carreras/index.php');
        break;

    default:
        redirigir('modulos/carreras/index.php');
}
