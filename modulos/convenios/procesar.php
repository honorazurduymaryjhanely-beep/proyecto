<?php
/**
 * Procesador de acciones del módulo de convenios
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Maneja crear y actualizar con validación de fechas (inicio <= fin),
 * subida de documento PDF a uploads/convenios/, cálculo de estado,
 * advertencia si la institución está inactiva y auditoría.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Convenio.php';
require_once __DIR__ . '/../../clases/Institucion.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarAutenticacion();

$config = require __DIR__ . '/../../config/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('modulos/convenios/listar.php');
}
if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
    mostrarMensaje('error', 'Token de seguridad inválido. Intente nuevamente.');
    redirigir('modulos/convenios/listar.php');
}

$accion = $_POST['accion'] ?? '';
$idUsuario = (int) $_SESSION['id_usuario'];

/**
 * Recoger y sanear los datos del formulario de convenio.
 */
function recogerDatosConvenio(): array
{
    return [
        'titulo'                => trim($_POST['titulo'] ?? ''),
        'descripcion'           => trim($_POST['descripcion'] ?? ''),
        'id_institucion'        => (int) ($_POST['id_institucion'] ?? 0),
        'fecha_firma'           => trim($_POST['fecha_firma'] ?? ''),
        'fecha_inicio_vigencia' => trim($_POST['fecha_inicio_vigencia'] ?? ''),
        'fecha_fin_vigencia'    => trim($_POST['fecha_fin_vigencia'] ?? ''),
        'documento_adjunto'     => '',
    ];
}

/**
 * Validar los datos del convenio (sin considerar el documento).
 *
 * @return array Lista de errores
 */
function validarDatosConvenio(array $d): array
{
    $errores = [];
    if ($d['titulo'] === '') {
        $errores[] = 'El título es obligatorio.';
    }
    if ($d['id_institucion'] <= 0) {
        $errores[] = 'Debe seleccionar una institución.';
    }
    foreach (['fecha_firma' => 'firma', 'fecha_inicio_vigencia' => 'inicio de vigencia', 'fecha_fin_vigencia' => 'fin de vigencia'] as $campo => $etiqueta) {
        if (!validarFecha($d[$campo])) {
            $errores[] = "La fecha de {$etiqueta} no es válida.";
        }
    }
    if (empty($errores) && $d['fecha_inicio_vigencia'] > $d['fecha_fin_vigencia']) {
        $errores[] = 'La fecha de inicio de vigencia no puede ser posterior a la de fin.';
    }
    return $errores;
}

/**
 * Procesar la subida del documento PDF adjunto.
 *
 * @param array  $config Configuración de la aplicación
 * @param string $error  Referencia para mensaje de error
 * @return string|null Nombre del archivo guardado, '' si no hay archivo, null si error
 */
function procesarDocumento(array $config, ?string &$error): ?string
{
    if (empty($_FILES['documento']['name']) || $_FILES['documento']['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el documento adjunto.';
        return null;
    }
    if ($_FILES['documento']['size'] > 5 * 1024 * 1024) {
        $error = 'El documento no debe superar los 5 MB.';
        return null;
    }

    // Validar el tipo real del archivo (debe ser PDF).
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $tipo = $finfo->file($_FILES['documento']['tmp_name']);
    if ($tipo !== 'application/pdf') {
        $error = 'El documento adjunto debe ser un archivo PDF.';
        return null;
    }

    $destino = $config['ruta_convenios'];
    if (!is_dir($destino)) {
        mkdir($destino, 0755, true);
    }
    $nombreArchivo = 'convenio_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    if (!move_uploaded_file($_FILES['documento']['tmp_name'], $destino . $nombreArchivo)) {
        $error = 'No se pudo guardar el documento adjunto.';
        return null;
    }
    return $nombreArchivo;
}

switch ($accion) {
    case 'crear':
        verificarPermiso('convenios', 'crear');
        $d = recogerDatosConvenio();
        $errores = validarDatosConvenio($d);
        if ($errores) {
            $_SESSION['form_convenio'] = $d;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/convenios/crear.php');
        }

        $errorDoc = null;
        $documento = procesarDocumento($config, $errorDoc);
        if ($documento === null) {
            $_SESSION['form_convenio'] = $d;
            mostrarMensaje('error', $errorDoc);
            redirigir('modulos/convenios/crear.php');
        }
        $d['documento_adjunto'] = $documento;

        // Advertencia si la institución está inactiva (no bloquea la creación).
        $advertencia = '';
        if (!Institucion::estaActiva($d['id_institucion'])) {
            $advertencia = ' Advertencia: la institución seleccionada está inactiva.';
        }

        $nuevoId = Convenio::crear($d, $idUsuario);
        if ($nuevoId === null) {
            $_SESSION['form_convenio'] = $d;
            mostrarMensaje('error', 'No se pudo registrar el convenio.');
            redirigir('modulos/convenios/crear.php');
        }
        Auditoria::registrar($idUsuario, 'crear', 'convenios', $nuevoId, null, ['titulo' => $d['titulo']]);
        mostrarMensaje('exito', 'Convenio registrado correctamente.' . $advertencia);
        redirigir('modulos/convenios/ver.php?id=' . $nuevoId);
        break;

    case 'actualizar':
        verificarPermiso('convenios', 'editar');
        $id = (int) ($_POST['id'] ?? 0);
        $previo = Convenio::obtenerPorId($id);
        if ($previo === null) {
            mostrarMensaje('error', 'El convenio no existe.');
            redirigir('modulos/convenios/listar.php');
        }
        $d = recogerDatosConvenio();
        $errores = validarDatosConvenio($d);
        if ($errores) {
            $_SESSION['form_convenio'] = $d;
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/convenios/editar.php?id=' . $id);
        }

        $errorDoc = null;
        $documento = procesarDocumento($config, $errorDoc);
        if ($documento === null) {
            $_SESSION['form_convenio'] = $d;
            mostrarMensaje('error', $errorDoc);
            redirigir('modulos/convenios/editar.php?id=' . $id);
        }
        $d['documento_adjunto'] = $documento; // '' conserva el actual

        if (!Convenio::actualizar($id, $d)) {
            mostrarMensaje('error', 'No se pudo actualizar el convenio.');
            redirigir('modulos/convenios/editar.php?id=' . $id);
        }
        Auditoria::registrar($idUsuario, 'modificar', 'convenios', $id,
            ['titulo' => $previo['titulo']], ['titulo' => $d['titulo']]);
        mostrarMensaje('exito', 'Convenio actualizado correctamente.');
        redirigir('modulos/convenios/ver.php?id=' . $id);
        break;

    default:
        redirigir('modulos/convenios/listar.php');
}
