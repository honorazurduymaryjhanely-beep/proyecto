<?php
/**
 * Exportación del historial de prácticas completadas (CSV / PDF)
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Genera el historial filtrado en formato CSV o PDF (FPDF) para descarga.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';

verificarPermiso('practicas', 'ver');

// Evitar que avisos de PHP se impriman antes del archivo (corromperían CSV/PDF).
ini_set('display_errors', '0');

$config = require __DIR__ . '/../../config/app.php';

$formato = $_GET['formato'] ?? 'csv';
$filtros = [
    'fecha_desde'    => trim($_GET['fecha_desde'] ?? ''),
    'fecha_hasta'    => trim($_GET['fecha_hasta'] ?? ''),
    'carrera'        => trim($_GET['carrera'] ?? ''),
    'id_institucion' => $_GET['id_institucion'] ?? '',
];

// pagina = 0 => sin límite (todos los registros filtrados).
$practicas = Practica::listarCompletadas($filtros, 0);

if ($formato === 'pdf') {
    exportarPDF($practicas, $config);
} else {
    exportarCSV($practicas);
}

/**
 * Exportar el historial en formato CSV.
 */
function exportarCSV(array $practicas): void
{
    $nombre = 'historial_practicas_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');

    $salida = fopen('php://output', 'w');
    // BOM para que Excel reconozca UTF-8.
    fprintf($salida, "\xEF\xBB\xBF");

    fputcsv($salida, ['Código', 'Estudiante', 'Carrera', 'Institución', 'Supervisor',
                      'Inicio', 'Finalización', 'Horas cumplidas', 'Horas requeridas']);

    foreach ($practicas as $p) {
        fputcsv($salida, [
            $p['codigo_practica'],
            $p['estudiante'],
            $p['carrera'],
            $p['institucion'],
            $p['supervisor'] ?: '',
            $p['fecha_inicio'],
            $p['fecha_finalizacion_real'],
            number_format((float) $p['horas_cumplidas'], 0),
            (int) $p['total_horas_requeridas'],
        ]);
    }
    fclose($salida);
    exit;
}

/**
 * Exportar el historial en formato PDF usando FPDF.
 */
function exportarPDF(array $practicas, array $config): void
{
    require_once __DIR__ . '/../../vendor/fpdf/fpdf.php';

    $pdf = new FPDF('L', 'mm', 'A4'); // Horizontal para más columnas.
    $pdf->AddPage();

    // Encabezado institucional.
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, utf8ADecodePdf($config['siglas_institucion']), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, utf8ADecodePdf('Historial de Prácticas Completadas'), 0, 1, 'C');
    $pdf->Cell(0, 6, utf8ADecodePdf('Generado el ' . date('d/m/Y H:i')), 0, 1, 'C');
    $pdf->Ln(3);

    // Cabecera de la tabla.
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(27, 58, 107);
    $pdf->SetTextColor(255, 255, 255);
    $cols = [
        ['Código', 25], ['Estudiante', 55], ['Carrera', 40], ['Institución', 55],
        ['Supervisor', 45], ['Inicio', 22], ['Fin', 22], ['Horas', 16],
    ];
    foreach ($cols as $c) {
        $pdf->Cell($c[1], 7, utf8ADecodePdf($c[0]), 1, 0, 'C', true);
    }
    $pdf->Ln();

    // Filas.
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    if (empty($practicas)) {
        $pdf->Cell(280, 7, utf8ADecodePdf('No hay prácticas completadas con los filtros seleccionados.'), 1, 1, 'C');
    } else {
        foreach ($practicas as $p) {
            $horas = number_format((float) $p['horas_cumplidas'], 0) . '/' . (int) $p['total_horas_requeridas'];
            $pdf->Cell(25, 6, utf8ADecodePdf($p['codigo_practica']), 1);
            $pdf->Cell(55, 6, utf8ADecodePdf(recortarPdf($p['estudiante'], 32)), 1);
            $pdf->Cell(40, 6, utf8ADecodePdf(recortarPdf($p['carrera'], 22)), 1);
            $pdf->Cell(55, 6, utf8ADecodePdf(recortarPdf($p['institucion'], 32)), 1);
            $pdf->Cell(45, 6, utf8ADecodePdf(recortarPdf($p['supervisor'] ?: '-', 26)), 1);
            $pdf->Cell(22, 6, date('d/m/Y', strtotime($p['fecha_inicio'])), 1, 0, 'C');
            $pdf->Cell(22, 6, date('d/m/Y', strtotime($p['fecha_finalizacion_real'])), 1, 0, 'C');
            $pdf->Cell(16, 6, $horas, 1, 0, 'C');
            $pdf->Ln();
        }
    }

    $nombre = 'historial_practicas_' . date('Ymd_His') . '.pdf';
    $pdf->Output('D', $nombre);
    exit;
}

/**
 * Convertir texto UTF-8 a la codificación que espera FPDF (ISO-8859-1).
 */
function utf8ADecodePdf(?string $texto): string
{
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $texto);
}

/**
 * Recortar un texto a una longitud máxima para que quepa en la celda.
 */
function recortarPdf(string $texto, int $max): string
{
    return mb_strlen($texto) > $max ? mb_substr($texto, 0, $max - 1) . '…' : $texto;
}
