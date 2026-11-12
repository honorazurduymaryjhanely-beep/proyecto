<?php
/**
 * Procesador de acciones del módulo de usuarios
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Maneja las acciones: crear, actualizar y cambiar_estado, con validación
 * CSRF, sanitización, validación de unicidad y registro de auditoría.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Usuario.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('modulos/usuarios/listar.php');
}

if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
    mostrarMensaje('error', 'Token de seguridad inválido. Intente nuevamente.');
    redirigir('modulos/usuarios/listar.php');
}

$accion = $_POST['accion'] ?? '';
$idUsuarioActual = (int) $_SESSION['id_usuario'];

switch ($accion) {
    case 'crear':
        verificarPermiso('usuarios', 'crear');

        $datos = [
            'nombre_completo'        => trim($_POST['nombre_completo'] ?? ''),
            'nombre_usuario'         => trim($_POST['nombre_usuario'] ?? ''),
            'correo_electronico'     => trim($_POST['correo_electronico'] ?? ''),
            'id_rol'                 => (int) ($_POST['id_rol'] ?? 0),
            'contrasena'             => $_POST['contrasena'] ?? '',
            'carrera'                => trim($_POST['carrera'] ?? ''),
            'telefono'               => trim($_POST['telefono'] ?? ''),
        ];
        $contrasena2 = $_POST['contrasena2'] ?? '';

        $errores = validarDatosUsuario($datos, $contrasena2, true, null);
        if ($errores) {
            $_SESSION['form_usuario'] = $datos;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/usuarios/crear.php');
        }

        $nuevoId = Usuario::crear($datos);
        if ($nuevoId === null) {
            $_SESSION['form_usuario'] = $datos;
            mostrarMensaje('error', 'No se pudo crear el usuario. Intente nuevamente.');
            redirigir('modulos/usuarios/crear.php');
        }

        Auditoria::registrar($idUsuarioActual, 'crear', 'usuarios', $nuevoId, null, [
            'nombre_usuario' => $datos['nombre_usuario'],
            'id_rol'         => $datos['id_rol'],
        ]);
        mostrarMensaje('exito', 'Usuario creado correctamente.');
        redirigir('modulos/usuarios/listar.php');
        break;

    case 'actualizar':
        verificarPermiso('usuarios', 'editar');

        $id = (int) ($_POST['id'] ?? 0);
        $usuarioPrevio = Usuario::obtenerPorId($id);
        if ($usuarioPrevio === null) {
            mostrarMensaje('error', 'El usuario no existe.');
            redirigir('modulos/usuarios/listar.php');
        }

        $datos = [
            'nombre_completo'        => trim($_POST['nombre_completo'] ?? ''),
            'correo_electronico'     => trim($_POST['correo_electronico'] ?? ''),
            'id_rol'                 => (int) ($_POST['id_rol'] ?? 0),
            'contrasena'             => $_POST['contrasena'] ?? '',
            'carrera'                => trim($_POST['carrera'] ?? ''),
            'telefono'               => trim($_POST['telefono'] ?? ''),
        ];
        $contrasena2 = $_POST['contrasena2'] ?? '';

        $errores = validarDatosUsuario($datos, $contrasena2, false, $id);
        if ($errores) {
            $_SESSION['form_usuario'] = $datos;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/usuarios/editar.php?id=' . $id);
        }

        if (!Usuario::actualizar($id, $datos)) {
            mostrarMensaje('error', 'No se pudo actualizar el usuario.');
            redirigir('modulos/usuarios/editar.php?id=' . $id);
        }

        Auditoria::registrar($idUsuarioActual, 'modificar', 'usuarios', $id,
            ['nombre_completo' => $usuarioPrevio['nombre_completo'], 'id_rol' => $usuarioPrevio['id_rol']],
            ['nombre_completo' => $datos['nombre_completo'], 'id_rol' => $datos['id_rol']]
        );
        mostrarMensaje('exito', 'Usuario actualizado correctamente.');
        redirigir('modulos/usuarios/listar.php');
        break;

    case 'cambiar_estado':
        verificarPermiso('usuarios', 'editar');

        $id = (int) ($_POST['id'] ?? 0);
        $estado = (int) ($_POST['estado'] ?? 1);

        // No permitir que un usuario se deshabilite a sí mismo.
        if ($id === $idUsuarioActual && $estado === 0) {
            mostrarMensaje('error', 'No puede deshabilitar su propia cuenta.');
            redirigir('modulos/usuarios/listar.php');
        }

        if (!Usuario::cambiarEstado($id, $estado)) {
            mostrarMensaje('error', 'No se pudo cambiar el estado del usuario.');
            redirigir('modulos/usuarios/listar.php');
        }

        Auditoria::registrar($idUsuarioActual, 'modificar', 'usuarios', $id, null, ['estado' => $estado]);
        mostrarMensaje('exito', 'Estado del usuario actualizado.');
        redirigir('modulos/usuarios/listar.php');
        break;

    case 'eliminar':
        verificarPermiso('usuarios', 'eliminar');

        $id = (int) ($_POST['id'] ?? 0);
        $usuario = Usuario::obtenerPorId($id);
        if (!$usuario) {
            mostrarMensaje('error', 'El usuario solicitado no existe.');
            redirigir('modulos/usuarios/listar.php');
        }

        [$exito, $msg] = Usuario::eliminar($id, $idUsuarioActual);
        if ($exito) {
            Auditoria::registrar($idUsuarioActual, 'eliminar', 'usuarios', $id, [
                'usuario' => $usuario['nombre_usuario'],
                'nombre'  => $usuario['nombre_completo'],
            ], null);
            mostrarMensaje('exito', $msg);
        } else {
            mostrarMensaje('error', $msg);
        }
        redirigir('modulos/usuarios/listar.php');
        break;

    default:
        redirigir('modulos/usuarios/listar.php');
}

/**
 * Validar los datos de un usuario y devolver un arreglo de errores.
 *
 * @param array    $datos        Datos del usuario
 * @param string   $contrasena2  Confirmación de contraseña
 * @param bool     $esNuevo      true si es creación (contraseña obligatoria)
 * @param int|null $idExcluir    ID a excluir en las validaciones de unicidad
 * @return array Lista de mensajes de error (vacía si es válido)
 */
function validarDatosUsuario(array $datos, string $contrasena2, bool $esNuevo, ?int $idExcluir): array
{
    $errores = [];

    if ($datos['nombre_completo'] === '') {
        $errores[] = 'El nombre completo es obligatorio.';
    }
    if (empty($datos['correo_electronico']) || !validarEmail($datos['correo_electronico'])) {
        $errores[] = 'El correo electrónico no es válido.';
    } elseif (!Usuario::correoDisponible($datos['correo_electronico'], $idExcluir)) {
        $errores[] = 'El correo electrónico ya está registrado.';
    }
    if ((int) $datos['id_rol'] <= 0) {
        $errores[] = 'Debe seleccionar un rol.';
    }

    if ($esNuevo) {
        if (empty($datos['nombre_usuario'])) {
            $errores[] = 'El nombre de usuario es obligatorio.';
        } elseif (!Usuario::nombreUsuarioDisponible($datos['nombre_usuario'], $idExcluir)) {
            $errores[] = 'El nombre de usuario ya está en uso.';
        }
        if (strlen($datos['contrasena']) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        }
    } elseif ($datos['contrasena'] !== '' && strlen($datos['contrasena']) < 6) {
        $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
    }

    if ($datos['contrasena'] !== '' && $datos['contrasena'] !== $contrasena2) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    return $errores;
}
