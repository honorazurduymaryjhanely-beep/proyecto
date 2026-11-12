<?php
/**
 * Procesador de registro de práctica laboral
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Valida las reglas de negocio del registro de prácticas:
 *  - estudiante activo, institución activa
 *  - convenio vigente vinculado a la institución seleccionada
 *  - el estudiante no debe tener otra práctica "en_curso"
 *  - fechas: inicio <= fin prevista; inicio >= inicio de vigencia del convenio
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/Estudiante.php';
require_once __DIR__ . '/../../clases/Institucion.php';
require_once __DIR__ . '/../../clases/Convenio.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarAutenticacion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('modulos/practicas/listar.php');
}
if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
    mostrarMensaje('error', 'Token de seguridad inválido. Intente nuevamente.');
    redirigir('modulos/practicas/listar.php');
}

$accion = $_POST['accion'] ?? '';
$idUsuario = (int) $_SESSION['id_usuario'];

function recogerDatosPractica(): array
{
    return [
        'id_estudiante'           => (int) ($_POST['id_estudiante'] ?? 0),
        'id_institucion'          => (int) ($_POST['id_institucion'] ?? 0),
        'id_convenio'             => (int) ($_POST['id_convenio'] ?? 0),
        'area_departamento'       => trim($_POST['area_departamento'] ?? ''),
        'total_horas_requeridas'  => (int) ($_POST['total_horas_requeridas'] ?? 0),
        'fecha_inicio'            => trim($_POST['fecha_inicio'] ?? ''),
        'fecha_fin_prevista'      => trim($_POST['fecha_fin_prevista'] ?? ''),
        'descripcion_actividades' => trim($_POST['descripcion_actividades'] ?? ''),
    ];
}

/**
 * Validar las reglas de negocio de una práctica al crearla.
 *
 * @return array Lista de errores
 */
function validarDatosPractica(array $d): array
{
    $errores = [];

    // Estudiante activo.
    $estudiante = Estudiante::obtenerPorId($d['id_estudiante']);
    if ($estudiante === null) {
        $errores[] = 'Debe seleccionar un estudiante válido.';
    } elseif ((int) $estudiante['estado'] !== 1) {
        $errores[] = 'El estudiante seleccionado está inactivo.';
    }

    // Institución activa.
    if (!Institucion::estaActiva($d['id_institucion'])) {
        $errores[] = 'La institución seleccionada no existe o está inactiva.';
    }

    // Convenio vigente y vinculado a la institución.
    $convenio = Convenio::obtenerPorId($d['id_convenio']);
    if ($convenio === null) {
        $errores[] = 'Debe seleccionar un convenio válido.';
    } else {
        if ((int) $convenio['id_institucion'] !== $d['id_institucion']) {
            $errores[] = 'El convenio no pertenece a la institución seleccionada.';
        }
        if (!Convenio::verificarVigencia($d['id_convenio'])) {
            $errores[] = 'El convenio seleccionado no está vigente.';
        }
    }

    // El estudiante no debe tener otra práctica en curso.
    if ($d['id_estudiante'] > 0 && Practica::verificarPracticaActiva($d['id_estudiante'])) {
        $errores[] = 'El estudiante ya tiene una práctica en curso.';
    }

    // Horas requeridas.
    if ($d['total_horas_requeridas'] <= 0) {
        $errores[] = 'El total de horas requeridas debe ser mayor a cero.';
    }

    // Fechas.
    if (!validarFecha($d['fecha_inicio']) || !validarFecha($d['fecha_fin_prevista'])) {
        $errores[] = 'Las fechas de la práctica no son válidas.';
    } else {
        if ($d['fecha_inicio'] > $d['fecha_fin_prevista']) {
            $errores[] = 'La fecha de inicio no puede ser posterior a la fecha de fin prevista.';
        }
        if (isset($convenio) && $convenio !== null && $d['fecha_inicio'] < $convenio['fecha_inicio_vigencia']) {
            $errores[] = 'La fecha de inicio no puede ser anterior al inicio de vigencia del convenio.';
        }
    }

    return $errores;
}

switch ($accion) {
    case 'crear':
        verificarPermiso('practicas', 'crear');
        $d = recogerDatosPractica();
        $errores = validarDatosPractica($d);
        if ($errores) {
            $_SESSION['form_practica'] = $d;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/practicas/crear.php');
        }
        $nuevoId = Practica::crear($d, $idUsuario);
        if ($nuevoId === null) {
            $_SESSION['form_practica'] = $d;
            mostrarMensaje('error', 'No se pudo registrar la práctica.');
            redirigir('modulos/practicas/crear.php');
        }
        Auditoria::registrar($idUsuario, 'crear', 'practicas_laborales', $nuevoId, null, [
            'id_estudiante' => $d['id_estudiante'],
            'id_convenio'   => $d['id_convenio'],
        ]);
        $idSupervisor = (int) ($_POST['id_docente_supervisor'] ?? 0);
        if ($idSupervisor > 0) {
            Practica::asignarSupervisor($nuevoId, $idSupervisor, $idUsuario, 'Asignación inicial al registrar la práctica');
        }
        mostrarMensaje('exito', 'Práctica registrada correctamente en estado pendiente.');
        redirigir('modulos/practicas/ver.php?id=' . $nuevoId);
        break;

    case 'actualizar':
        verificarPermiso('practicas', 'editar');
        $id = (int) ($_POST['id'] ?? 0);
        $previo = Practica::obtenerPorId($id);
        if ($previo === null) {
            mostrarMensaje('error', 'La práctica no existe.');
            redirigir('modulos/practicas/listar.php');
        }
        $d = recogerDatosPractica();
        // En edición solo se editan datos generales; se validan fechas y horas.
        $errores = [];
        if ($d['total_horas_requeridas'] <= 0) {
            $errores[] = 'El total de horas requeridas debe ser mayor a cero.';
        }
        if (!validarFecha($d['fecha_inicio']) || !validarFecha($d['fecha_fin_prevista'])) {
            $errores[] = 'Las fechas de la práctica no son válidas.';
        } elseif ($d['fecha_inicio'] > $d['fecha_fin_prevista']) {
            $errores[] = 'La fecha de inicio no puede ser posterior a la fecha de fin prevista.';
        }
        if ($errores) {
            $_SESSION['form_practica'] = $d;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/practicas/editar.php?id=' . $id);
        }
        if (!Practica::actualizar($id, $d)) {
            mostrarMensaje('error', 'No se pudo actualizar la práctica.');
            redirigir('modulos/practicas/editar.php?id=' . $id);
        }
        Auditoria::registrar($idUsuario, 'modificar', 'practicas_laborales', $id, null, [
            'total_horas' => $d['total_horas_requeridas'],
        ]);
        mostrarMensaje('exito', 'Práctica actualizada correctamente.');
        redirigir('modulos/practicas/ver.php?id=' . $id);
        break;

    default:
        redirigir('modulos/practicas/listar.php');
}
