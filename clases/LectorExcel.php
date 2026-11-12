<?php
/**
 * Clase LectorExcel - Lectura mínima de archivos .xlsx
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Lee la primera hoja de un archivo .xlsx (Open XML) usando ZipArchive y
 * SimpleXML, sin dependencias externas. Devuelve una matriz de filas/columnas
 * con los valores como texto. Soporta cadenas compartidas (sharedStrings) y
 * valores en línea. Pensado para importaciones sencillas (sin estilos/fórmulas).
 */

class LectorExcel
{
    /**
     * Leer todas las filas de la primera hoja de un .xlsx.
     *
     * @param string $rutaArchivo Ruta al archivo .xlsx
     * @return array Matriz de filas; cada fila es un arreglo indexado por columna (0..n)
     * @throws RuntimeException si el archivo no se puede abrir o no es un xlsx válido
     */
    public static function leer(string $rutaArchivo): array
    {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('La extensión ZIP de PHP no está disponible.');
        }
        $zip = new ZipArchive();
        if ($zip->open($rutaArchivo) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        // Cadenas compartidas (opcional).
        $sharedStrings = [];
        $ssXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($ssXml !== false) {
            $sxml = simplexml_load_string($ssXml);
            if ($sxml !== false) {
                foreach ($sxml->si as $si) {
                    $sharedStrings[] = self::textoDeSi($si);
                }
            }
        }

        // Localizar la primera hoja. Normalmente xl/worksheets/sheet1.xml.
        $hojaXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($hojaXml === false) {
            // Buscar la primera hoja disponible.
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nombre = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $nombre)) {
                    $hojaXml = $zip->getFromName($nombre);
                    break;
                }
            }
        }
        $zip->close();

        if ($hojaXml === false || $hojaXml === null) {
            throw new RuntimeException('El archivo Excel no contiene hojas legibles.');
        }

        $xml = simplexml_load_string($hojaXml);
        if ($xml === false) {
            throw new RuntimeException('No se pudo leer el contenido de la hoja.');
        }

        $filas = [];
        foreach ($xml->sheetData->row as $row) {
            $fila = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];               // ej: "B3"
                $colIndice = self::indiceColumna($ref); // 0-based
                $tipo = (string) $c['t'];
                $valor = '';

                if ($tipo === 's') {
                    // Índice a sharedStrings.
                    $idx = (int) $c->v;
                    $valor = $sharedStrings[$idx] ?? '';
                } elseif ($tipo === 'inlineStr') {
                    $valor = self::textoDeSi($c->is);
                } else {
                    $valor = isset($c->v) ? (string) $c->v : '';
                }

                $fila[$colIndice] = trim($valor);
            }
            // Normalizar la fila a índices consecutivos 0..max.
            if (!empty($fila)) {
                $max = max(array_keys($fila));
                $filaNormal = [];
                for ($i = 0; $i <= $max; $i++) {
                    $filaNormal[$i] = $fila[$i] ?? '';
                }
                $filas[] = $filaNormal;
            } else {
                $filas[] = [];
            }
        }

        return $filas;
    }

    /**
     * Extraer el texto de un nodo <si> (shared string) o <is> (inline),
     * concatenando posibles fragmentos <r><t>.
     */
    private static function textoDeSi($si): string
    {
        if ($si === null) {
            return '';
        }
        // Texto simple.
        if (isset($si->t) && count($si->r ?? []) === 0) {
            return (string) $si->t;
        }
        // Texto con fragmentos <r><t>.
        $texto = '';
        if (isset($si->r)) {
            foreach ($si->r as $r) {
                $texto .= (string) $r->t;
            }
        }
        if ($texto === '' && isset($si->t)) {
            $texto = (string) $si->t;
        }
        return $texto;
    }

    /**
     * Convertir una referencia de celda (ej. "AB12") al índice de columna 0-based.
     */
    private static function indiceColumna(string $ref): int
    {
        if (!preg_match('/^([A-Z]+)/', $ref, $m)) {
            return 0;
        }
        $letras = $m[1];
        $indice = 0;
        $len = strlen($letras);
        for ($i = 0; $i < $len; $i++) {
            $indice = $indice * 26 + (ord($letras[$i]) - ord('A') + 1);
        }
        return $indice - 1;
    }
}
