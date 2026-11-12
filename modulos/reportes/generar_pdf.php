<?php
/**
 * Generador de reportes PDF
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Procesa la solicitud de reporte (parámetro `tipo`) y produce el PDF con la
 * clase Reporte, enviándolo al navegador para su visualización/descarga.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../clases/Reporte.php';

verificarPermiso('reportes', 'ver');

// Evitar que cualquier aviso de PHP se imprima antes del PDF (corrompería el
// flujo). Los errores se registran en el log, no se envían al navegador.
ini_set('display_errors', '0');

$db = Database::obtenerInstancia();
$usuario = $_SESSION['nombre_completo'] ?? '';
$tipo = $_GET['tipo'] ?? '';

switch ($tipo) {
    case 'practicas_periodo':
        reportePracticasPeriodo($db, $usuario);
        break;
    case 'horas_estudiante':
        reporteHorasEstudiante($db, $usuario);
        break;
    case 'convenios':
        reporteConvenios($db, $usuario);
        break;
    case 'practicas_institucion':
        reportePracticasInstitucion($db, $usuario);
        break;
    default:
        mostrarMensaje('error', 'Tipo de reporte no válido.');
        redirigir('modulos/reportes/index.php');
}

/**
 * Reporte de prácticas por período (rango de fecha de inicio).
 */
function reportePracticasPeriodo(PDO $db, string $usuario): void
{
    $desde = $_GET['fecha_desde'] ?? '';
    $hasta = $_GET['fecha_hasta'] ?? '';
    if (!validarFecha($desde) || !validarFecha($hasta)) {
        mostrarMensaje('error', 'Debe indicar un rango de fechas válido.');
        redirigir('modulos/reportes/practicas_periodo.php');
    }

    $sql = "SELECT p.codigo_practica, p.estado, p.fecha_inicio, p.fecha_fin_prevista,
                   e.nombre_completo AS estudiante, i.nombre AS institucion
            FROM practicas_laborales p
            INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
            INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
            WHERE p.fecha_inicio BETWEEN :desde AND :hasta
            ORDER BY p.fecha_inicio";
    $stmt = $db->prepare($sql);
    $stmt->execute([':desde' => $desde, ':hasta' => $hasta]);
    $filas = $stmt->fetchAll();

    $pdf = new Reporte('Prácticas por Período', $usuario, 'L');
    $pdf->AddPage();
    $pdf->parrafo('Período: ' . date('d/m/Y', strtotime($desde)) . ' a ' . date('d/m/Y', strtotime($hasta)), 9);
    $pdf->Ln(2);
    $pdf->cabeceraTabla([['Código', 30], ['Estudiante', 70], ['Institución', 70], ['Inicio', 30], ['Fin prev.', 30], ['Estado', 35]]);
    if (empty($filas)) {
        $pdf->filaTabla([['Sin prácticas en el período seleccionado.', 265, 'C']]);
    } else {
        foreach ($filas as $f) {
            $pdf->filaTabla([
                [$f['codigo_practica'], 30],
                [$f['estudiante'], 70],
                [$f['institucion'], 70],
                [date('d/m/Y', strtotime($f['fecha_inicio'])), 30, 'C'],
                [date('d/m/Y', strtotime($f['fecha_fin_prevista'])), 30, 'C'],
                [ucfirst(str_replace('_', ' ', $f['estado'])), 35, 'C'],
            ]);
        }
    }
    $pdf->Output('I', 'reporte_practicas_periodo.pdf');
    exit;
}

/**
 * Reporte de horas cumplidas por estudiante (detalle por práctica).
 */
function reporteHorasEstudiante(PDO $db, string $usuario): void
{
    $idEstudiante = (int) ($_GET['id_estudiante'] ?? 0);
    $est = $db->prepare('SELECT nombre_completo, codigo_estudiante, carrera FROM estudiantes WHERE id_estudiante = :id');
    $est->execute([':id' => $idEstudiante]);
    $estudiante = $est->fetch();
    if (!$estudiante) {
        mostrarMensaje('error', 'Estudiante no encontrado.');
        redirigir('modulos/reportes/horas_estudiante.php');
    }

    $sql = "SELECT p.codigo_practica, p.total_horas_requeridas, i.nombre AS institucion,
                   (SELECT COALESCE(SUM(h.cantidad_horas),0) FROM horas_cumplidas h WHERE h.id_practica = p.id_practica) AS horas
            FROM practicas_laborales p
            INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
            WHERE p.id_estudiante = :id
            ORDER BY p.fecha_creacion";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $idEstudiante]);
    $filas = $stmt->fetchAll();

    $pdf = new Reporte('Horas por Estudiante', $usuario, 'P');
    $pdf->AddPage();
    $pdf->parrafo('Estudiante: ' . $estudiante['nombre_completo'] . ' (' . $estudiante['codigo_estudiante'] . ')', 10, 'B');
    $pdf->parrafo('Carrera: ' . $estudiante['carrera'], 9);
    $pdf->Ln(2);
    $pdf->cabeceraTabla([['Práctica', 35], ['Institución', 80], ['Cumplidas', 33], ['Requeridas', 33], ['%', 15]]);
    if (empty($filas)) {
        $pdf->filaTabla([['El estudiante no tiene prácticas registradas.', 196, 'C']]);
    } else {
        foreach ($filas as $f) {
            $req = (int) $f['total_horas_requeridas'];
            $pct = $req > 0 ? round(min(100, ($f['horas'] / $req) * 100)) : 0;
            $pdf->filaTabla([
                [$f['codigo_practica'], 35],
                [$f['institucion'], 80],
                [number_format((float) $f['horas'], 1), 33, 'C'],
                [$req, 33, 'C'],
                [$pct . '%', 15, 'C'],
            ]);
        }
    }
    $pdf->Output('I', 'reporte_horas_estudiante.pdf');
    exit;
}

/**
 * Reporte de convenios con estado de vigencia.
 */
function reporteConvenios(PDO $db, string $usuario): void
{
    $estado = $_GET['estado'] ?? '';
    $sql = "SELECT c.codigo_convenio, c.titulo, c.fecha_inicio_vigencia, c.fecha_fin_vigencia,
                   c.estado, i.nombre AS institucion
            FROM convenios c
            INNER JOIN instituciones_receptoras i ON i.id_institucion = c.id_institucion";
    $params = [];
    if (in_array($estado, ['vigente', 'expirado', 'cancelado'], true)) {
        $sql .= ' WHERE c.estado = :estado';
        $params[':estado'] = $estado;
    }
    $sql .= ' ORDER BY c.fecha_fin_vigencia DESC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $filas = $stmt->fetchAll();

    $pdf = new Reporte('Convenios y Vigencia', $usuario, 'L');
    $pdf->AddPage();
    $pdf->cabeceraTabla([['Código', 28], ['Título', 75], ['Institución', 70], ['Inicio', 27], ['Fin', 27], ['Estado', 38]]);
    if (empty($filas)) {
        $pdf->filaTabla([['Sin convenios registrados.', 265, 'C']]);
    } else {
        foreach ($filas as $f) {
            $pdf->filaTabla([
                [$f['codigo_convenio'], 28],
                [$f['titulo'], 75],
                [$f['institucion'], 70],
                [date('d/m/Y', strtotime($f['fecha_inicio_vigencia'])), 27, 'C'],
                [date('d/m/Y', strtotime($f['fecha_fin_vigencia'])), 27, 'C'],
                [ucfirst($f['estado']), 38, 'C'],
            ]);
        }
    }
    $pdf->Output('I', 'reporte_convenios.pdf');
    exit;
}

/**
 * Reporte de prácticas por institución.
 */
function reportePracticasInstitucion(PDO $db, string $usuario): void
{
    $idInstitucion = (int) ($_GET['id_institucion'] ?? 0);
    $ins = $db->prepare('SELECT nombre, codigo_institucion FROM instituciones_receptoras WHERE id_institucion = :id');
    $ins->execute([':id' => $idInstitucion]);
    $institucion = $ins->fetch();
    if (!$institucion) {
        mostrarMensaje('error', 'Institución no encontrada.');
        redirigir('modulos/reportes/practicas_institucion.php');
    }

    $sql = "SELECT p.codigo_practica, p.estado, p.fecha_inicio, p.fecha_fin_prevista,
                   e.nombre_completo AS estudiante, e.carrera
            FROM practicas_laborales p
            INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
            WHERE p.id_institucion = :id
            ORDER BY p.fecha_creacion";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $idInstitucion]);
    $filas = $stmt->fetchAll();

    $pdf = new Reporte('Prácticas por Institución', $usuario, 'L');
    $pdf->AddPage();
    $pdf->parrafo('Institución: ' . $institucion['nombre'] . ' (' . $institucion['codigo_institucion'] . ')', 10, 'B');
    $pdf->Ln(2);
    $pdf->cabeceraTabla([['Código', 30], ['Estudiante', 75], ['Carrera', 55], ['Inicio', 30], ['Fin prev.', 30], ['Estado', 35]]);
    if (empty($filas)) {
        $pdf->filaTabla([['Sin prácticas para esta institución.', 255, 'C']]);
    } else {
        foreach ($filas as $f) {
            $pdf->filaTabla([
                [$f['codigo_practica'], 30],
                [$f['estudiante'], 75],
                [$f['carrera'], 55],
                [date('d/m/Y', strtotime($f['fecha_inicio'])), 30, 'C'],
                [date('d/m/Y', strtotime($f['fecha_fin_prevista'])), 30, 'C'],
                [ucfirst(str_replace('_', ' ', $f['estado'])), 35, 'C'],
            ]);
        }
    }
    $pdf->Output('I', 'reporte_practicas_institucion.pdf');
    exit;
}
