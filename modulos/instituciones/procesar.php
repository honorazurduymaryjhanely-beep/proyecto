<?php
/**
 * Procesador de acciones del módulo de instituciones
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Institucion.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('modulos/instituciones/listar.php');
}
if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
    mostrarMensaje('error', 'Token de seguridad inválido. Intente nuevamente.');
    redirigir('modulos/instituciones/listar.php');
}

$accion = $_POST['accion'] ?? '';
$idUsuario = (int) $_SESSION['id_usuario'];

function recogerDatosInstitucion(): array
{
    return [
        'nombre'             => trim($_POST['nombre'] ?? ''),
        'nit'                => trim($_POST['nit'] ?? ''),
        'direccion'          => trim($_POST['direccion'] ?? ''),
        'telefono'           => trim($_POST['telefono'] ?? ''),
        'correo_electronico' => trim($_POST['correo_electronico'] ?? ''),
        'nombre_mae'         => trim($_POST['nombre_mae'] ?? ''),
        'telefono_mae'       => trim($_POST['telefono_mae'] ?? ''),
        'nombre_contacto'    => trim($_POST['nombre_contacto'] ?? ''),
        'cargo_contacto'     => trim($_POST['cargo_contacto'] ?? ''),
        'telefono_contacto'  => trim($_POST['telefono_contacto'] ?? ''),
    ];
}

function validarDatosInstitucion(array $d, ?int $idExcluir): array
{
    $errores = [];
    if ($d['nombre'] === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if (!validarNit($d['nit'])) {
        $errores[] = 'El NIT no tiene un formato válido.';
    } elseif (!Institucion::verificarNitUnico($d['nit'], $idExcluir)) {
        $errores[] = 'El NIT ya está registrado para otra institución.';
    }
    if ($d['direccion'] === '') {
        $errores[] = 'La dirección es obligatoria.';
    }
    if ($d['telefono'] === '') {
        $errores[] = 'El teléfono es obligatorio.';
    }
    if ($d['correo_electronico'] !== '' && !validarEmail($d['correo_electronico'])) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    if ($d['nombre_contacto'] === '') {
        $errores[] = 'La persona de contacto es obligatoria.';
    }
    if ($d['cargo_contacto'] === '') {
        $errores[] = 'El cargo del contacto es obligatorio.';
    }
    return $errores;
}

switch ($accion) {
    case 'crear':
        verificarPermiso('instituciones', 'crear');
        $d = recogerDatosInstitucion();
        $errores = validarDatosInstitucion($d, null);
        if ($errores) {
            $_SESSION['form_institucion'] = $d;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/instituciones/crear.php');
        }
        $nuevoId = Institucion::crear($d, $idUsuario);
        if ($nuevoId === null) {
            $_SESSION['form_institucion'] = $d;
            mostrarMensaje('error', 'No se pudo registrar la institución.');
            redirigir('modulos/instituciones/crear.php');
        }
        Auditoria::registrar($idUsuario, 'crear', 'instituciones_receptoras', $nuevoId, null, ['nit' => $d['nit']]);
        mostrarMensaje('exito', 'Institución registrada correctamente.');
        redirigir('modulos/instituciones/ver.php?id=' . $nuevoId);
        break;

    case 'actualizar':
        verificarPermiso('instituciones', 'editar');
        $id = (int) ($_POST['id'] ?? 0);
        $previo = Institucion::obtenerPorId($id);
        if ($previo === null) {
            mostrarMensaje('error', 'La institución no existe.');
            redirigir('modulos/instituciones/listar.php');
        }
        $d = recogerDatosInstitucion();
        $errores = validarDatosInstitucion($d, $id);
        if ($errores) {
            $_SESSION['form_institucion'] = $d;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/instituciones/editar.php?id=' . $id);
        }
        if (!Institucion::actualizar($id, $d)) {
            mostrarMensaje('error', 'No se pudo actualizar la institución.');
            redirigir('modulos/instituciones/editar.php?id=' . $id);
        }
        Auditoria::registrar($idUsuario, 'modificar', 'instituciones_receptoras', $id,
            ['nombre' => $previo['nombre']], ['nombre' => $d['nombre']]);
        mostrarMensaje('exito', 'Institución actualizada correctamente.');
        redirigir('modulos/instituciones/ver.php?id=' . $id);
        break;

    case 'cambiar_estado':
        verificarPermiso('instituciones', 'editar');
        $id = (int) ($_POST['id'] ?? 0);
        $estado = (int) ($_POST['estado'] ?? 1);
        if (!Institucion::cambiarEstado($id, $estado)) {
            mostrarMensaje('error', 'No se pudo cambiar el estado.');
            redirigir('modulos/instituciones/listar.php');
        }
        Auditoria::registrar($idUsuario, 'modificar', 'instituciones_receptoras', $id, null, ['estado' => $estado]);
        $mensaje = $estado === 0
            ? 'Institución inactivada. No se podrán registrar nuevas prácticas con ella.'
            : 'Institución activada correctamente.';
        mostrarMensaje('exito', $mensaje);
        redirigir('modulos/instituciones/listar.php');
        break;

    default:
        redirigir('modulos/instituciones/listar.php');
}
