<?php
/**
 * Procesador de acciones del módulo de estudiantes
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Maneja crear, actualizar y cambiar_estado con validación CSRF,
 * sanitización, validación de cédula única y auditoría.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Estudiante.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('modulos/estudiantes/listar.php');
}
if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
    mostrarMensaje('error', 'Token de seguridad inválido. Intente nuevamente.');
    redirigir('modulos/estudiantes/listar.php');
}

$accion = $_POST['accion'] ?? '';
$idUsuario = (int) $_SESSION['id_usuario'];
$restringirCarrera = restriccionCarreraActual();

/**
 * Recoger y sanear los datos del formulario de estudiante.
 */
function recogerDatosEstudiante(): array
{
    return [
        'nombre_completo'    => trim($_POST['nombre_completo'] ?? ''),
        'cedula_identidad'   => trim($_POST['cedula_identidad'] ?? ''),
        'fecha_nacimiento'   => trim($_POST['fecha_nacimiento'] ?? ''),
        'carrera'            => trim($_POST['carrera'] ?? ''),
        'curso'              => trim($_POST['curso'] ?? ''),
        'paralelo'           => trim($_POST['paralelo'] ?? ''),
        'telefono'           => trim($_POST['telefono'] ?? ''),
        'correo_electronico' => trim($_POST['correo_electronico'] ?? ''),
        'direccion'          => trim($_POST['direccion'] ?? ''),
    ];
}

/**
 * Validar los datos de un estudiante.
 *
 * @return array Lista de errores (vacía si válido)
 */
function validarDatosEstudiante(array $d, ?int $idExcluir): array
{
    $errores = [];
    if ($d['nombre_completo'] === '') {
        $errores[] = 'El nombre completo es obligatorio.';
    }
    if (!validarCedula($d['cedula_identidad'])) {
        $errores[] = 'La cédula debe tener entre 5 y 10 dígitos.';
    } elseif (!Estudiante::verificarCedulaUnica($d['cedula_identidad'], $idExcluir)) {
        $errores[] = 'La cédula ya está registrada para otro estudiante.';
    }
    if (!validarFecha($d['fecha_nacimiento'])) {
        $errores[] = 'La fecha de nacimiento no es válida.';
    }
    if ($d['carrera'] === '') {
        $errores[] = 'La carrera es obligatoria.';
    }
    if ($d['curso'] === '') {
        $errores[] = 'El curso es obligatorio.';
    }
    if ($d['correo_electronico'] !== '' && !validarEmail($d['correo_electronico'])) {
        $errores[] = 'El correo electrónico no es válido.';
    }
    return $errores;
}

switch ($accion) {
    case 'crear':
        verificarPermiso('estudiantes', 'crear');
        $d = recogerDatosEstudiante();
        if ($restringirCarrera !== null) {
            $d['carrera'] = $restringirCarrera;
        }
        $errores = validarDatosEstudiante($d, null);
        if ($errores) {
            $_SESSION['form_estudiante'] = $d;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/estudiantes/crear.php');
        }
        $nuevoId = Estudiante::crear($d, $idUsuario);
        if ($nuevoId === null) {
            $_SESSION['form_estudiante'] = $d;
            mostrarMensaje('error', 'No se pudo registrar el estudiante.');
            redirigir('modulos/estudiantes/crear.php');
        }
        Auditoria::registrar($idUsuario, 'crear', 'estudiantes', $nuevoId, null, ['cedula' => $d['cedula_identidad']]);
        mostrarMensaje('exito', 'Estudiante registrado correctamente.');
        redirigir('modulos/estudiantes/ver.php?id=' . $nuevoId);
        break;

    case 'actualizar':
        verificarPermiso('estudiantes', 'editar');
        $id = (int) ($_POST['id'] ?? 0);
        $previo = Estudiante::obtenerPorId($id);
        if ($previo === null) {
            mostrarMensaje('error', 'El estudiante no existe.');
            redirigir('modulos/estudiantes/listar.php');
        }
        if ($restringirCarrera !== null && normalizarCarrera($previo['carrera']) !== normalizarCarrera($restringirCarrera)) {
            mostrarMensaje('error', 'No tiene permisos para modificar estudiantes de otra carrera.');
            redirigir('modulos/estudiantes/listar.php');
        }
        $d = recogerDatosEstudiante();
        if ($restringirCarrera !== null) {
            $d['carrera'] = $restringirCarrera;
        }
        $errores = validarDatosEstudiante($d, $id);
        if ($errores) {
            $_SESSION['form_estudiante'] = $d;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/estudiantes/editar.php?id=' . $id);
        }
        if (!Estudiante::actualizar($id, $d)) {
            mostrarMensaje('error', 'No se pudo actualizar el estudiante.');
            redirigir('modulos/estudiantes/editar.php?id=' . $id);
        }
        Auditoria::registrar($idUsuario, 'modificar', 'estudiantes', $id,
            ['nombre' => $previo['nombre_completo']],
            ['nombre' => $d['nombre_completo']]
        );
        mostrarMensaje('exito', 'Estudiante actualizado correctamente.');
        redirigir('modulos/estudiantes/ver.php?id=' . $id);
        break;

    case 'cambiar_estado':
        verificarPermiso('estudiantes', 'editar');
        $id = (int) ($_POST['id'] ?? 0);
        $estado = (int) ($_POST['estado'] ?? 1);
        $previo = Estudiante::obtenerPorId($id);
        if ($previo && $restringirCarrera !== null && normalizarCarrera($previo['carrera']) !== normalizarCarrera($restringirCarrera)) {
            mostrarMensaje('error', 'No tiene permisos para modificar estudiantes de otra carrera.');
            redirigir('modulos/estudiantes/listar.php');
        }
        if (!Estudiante::cambiarEstado($id, $estado)) {
            mostrarMensaje('error', 'No se pudo cambiar el estado.');
            redirigir('modulos/estudiantes/listar.php');
        }
        Auditoria::registrar($idUsuario, 'modificar', 'estudiantes', $id, null, ['estado' => $estado]);
        mostrarMensaje('exito', 'Estado del estudiante actualizado.');
        redirigir('modulos/estudiantes/listar.php');
        break;

    case 'eliminar':
        verificarPermiso('estudiantes', 'eliminar');
        $id = (int) ($_POST['id'] ?? 0);
        $estudiante = Estudiante::obtenerPorId($id);
        if (!$estudiante) {
            mostrarMensaje('error', 'El estudiante solicitado no existe.');
            redirigir('modulos/estudiantes/listar.php');
        }
        if ($restringirCarrera !== null && normalizarCarrera($estudiante['carrera']) !== normalizarCarrera($restringirCarrera)) {
            mostrarMensaje('error', 'No tiene permisos para eliminar estudiantes de otra carrera.');
            redirigir('modulos/estudiantes/listar.php');
        }
        [$exito, $msg] = Estudiante::eliminar($id);
        if ($exito) {
            Auditoria::registrar($idUsuario, 'eliminar', 'estudiantes', $id, [
                'nombre' => $estudiante['nombre_completo'],
                'ci'     => $estudiante['cedula_identidad'],
                'codigo' => $estudiante['codigo_estudiante'],
            ], null);
            mostrarMensaje('exito', $msg);
        } else {
            mostrarMensaje('error', $msg);
        }
        redirigir('modulos/estudiantes/listar.php');
        break;

    default:
        redirigir('modulos/estudiantes/listar.php');
}
