<?php
/**
 * Clase Reporte - Generación de reportes PDF
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Extiende FPDF con un encabezado institucional (nombre de la institución,
 * título del reporte, fecha de generación y usuario) y un pie de página con
 * la numeración. Provee utilidades para dibujar tablas.
 */

require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

class Reporte extends FPDF
{
    /** @var string Título del reporte. */
    private $tituloReporte;

    /** @var string Nombre de la institución. */
    private $institucion;

    /** @var string Siglas de la institución. */
    private $siglas;

    /** @var string Usuario que genera el reporte. */
    private $usuario;

    /**
     * @param string $tituloReporte Título específico del reporte
     * @param string $usuario       Nombre del usuario que lo genera
     * @param string $orientacion   'P' vertical, 'L' horizontal
     */
    public function __construct(string $tituloReporte, string $usuario = '', string $orientacion = 'P')
    {
        parent::__construct($orientacion, 'mm', 'A4');
        $config = require __DIR__ . '/../config/app.php';
        $this->tituloReporte = $tituloReporte;
        $this->institucion   = $config['institucion'];
        $this->siglas        = $config['siglas_institucion'];
        $this->usuario       = $usuario;
        $this->SetMargins(12, 12, 12);
        $this->AliasNbPages();
    }

    /**
     * Convertir texto UTF-8 a ISO-8859-1 (codificación esperada por FPDF).
     */
    private function txt(?string $texto): string
    {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', (string) $texto);
    }

    /**
     * Encabezado institucional (se dibuja en cada página).
     */
    public function Header(): void
    {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(27, 58, 107);
        $this->Cell(0, 7, $this->txt($this->siglas), 0, 1, 'C');

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(80, 80, 80);
        $this->Cell(0, 5, $this->txt($this->institucion), 0, 1, 'C');

        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 8, $this->txt($this->tituloReporte), 0, 1, 'C');

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(120, 120, 120);
        $meta = 'Generado el ' . date('d/m/Y H:i');
        if ($this->usuario !== '') {
            $meta .= ' por ' . $this->usuario;
        }
        $this->Cell(0, 5, $this->txt($meta), 0, 1, 'C');

        // Línea separadora.
        $this->SetDrawColor(27, 58, 107);
        $this->Line($this->GetX(), $this->GetY() + 1, $this->GetPageWidth() - 12, $this->GetY() + 1);
        $this->Ln(5);
        $this->SetTextColor(0, 0, 0);
    }

    /**
     * Pie de página con la numeración.
     */
    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 10, $this->txt('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'C');
    }

    /**
     * Dibujar la fila de cabecera de una tabla.
     *
     * @param array $columnas Lista de [texto, ancho]
     */
    public function cabeceraTabla(array $columnas): void
    {
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(27, 58, 107);
        $this->SetTextColor(255, 255, 255);
        foreach ($columnas as $c) {
            $this->Cell($c[1], 7, $this->txt($c[0]), 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 9);
    }

    /**
     * Dibujar una fila de datos.
     *
     * @param array $celdas Lista de [texto, ancho, align?]
     */
    public function filaTabla(array $celdas): void
    {
        foreach ($celdas as $c) {
            $align = $c[2] ?? 'L';
            $this->Cell($c[1], 6, $this->txt((string) $c[0]), 1, 0, $align);
        }
        $this->Ln();
    }

    /**
     * Escribir una línea de texto simple.
     */
    public function parrafo(string $texto, int $tam = 10, string $estilo = ''): void
    {
        $this->SetFont('Arial', $estilo, $tam);
        $this->MultiCell(0, 5, $this->txt($texto), 0, 'L');
    }
}
