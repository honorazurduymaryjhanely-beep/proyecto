<?php
/**
 * Clase Busqueda - Búsqueda general transversal
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Realiza búsquedas transversales sobre las entidades principales
 * (estudiantes, instituciones, docentes supervisores, convenios y prácticas),
 * devolviendo resultados agrupados por categoría (máximo 10 por categoría).
 * Si el usuario es Docente Encargado, restringe todos los resultados a su carrera.
 */

require_once __DIR__ . '/../config/database.php';

class Busqueda
{
    private const MAX_POR_CATEGORIA = 10;

    /**
     * Ejecutar la búsqueda general en todas las entidades.
     *
     * @param string      $termino Término ingresado por el usuario
     * @param string|null $carrera Carrera para restringir (si es docente encargado)
     * @return array Categorías con sus respectivos resultados
     */
    public static function general(string $termino, ?string $carrera = null): array
    {
        $termino = trim($termino);
        if ($termino === '') {
            return [];
        }

        return [
            'estudiantes'   => self::buscarEstudiantes($termino, $carrera),
            'instituciones' => self::buscarInstituciones($termino, $carrera),
            'supervisores'  => self::buscarDocentes($termino, $carrera),
            'convenios'     => self::buscarConvenios($termino, $carrera),
            'practicas'     => self::buscarPracticas($termino, $carrera),
        ];
    }

    public static function buscarGlobal(string $termino, ?string $carrera = null): array
    {
        return self::general($termino, $carrera);
    }

    /**
     * Ejecutar una consulta de búsqueda parametrizada y mapear los resultados.
     */
    private static function ejecutar(string $sql, array $params, callable $mapear): array
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return array_map($mapear, $stmt->fetchAll());
        } catch (Throwable $e) {
            error_log('Error en búsqueda: ' . $e->getMessage());
            return [];
        }
    }

    private static function buscarEstudiantes(string $t, ?string $carrera): array
    {
        $like = '%' . $t . '%';
        $params = [$like, $like, $like];
        $filtroCarrera = '';

        if ($carrera !== null && $carrera !== '') {
            $filtroCarrera = 'AND carrera = ? ';
            $params[] = $carrera;
        }

        $sql = "SELECT id_estudiante, codigo_estudiante, nombre_completo, cedula_identidad, carrera
                FROM estudiantes
                WHERE (nombre_completo LIKE ? OR cedula_identidad LIKE ? OR codigo_estudiante LIKE ?)
                {$filtroCarrera}
                ORDER BY nombre_completo LIMIT " . self::MAX_POR_CATEGORIA;

        $items = self::ejecutar(
            $sql,
            $params,
            fn($r) => [
                'id'        => (int) $r['id_estudiante'],
                'titulo'    => $r['nombre_completo'],
                'subtitulo' => $r['codigo_estudiante'] . ' · CI ' . $r['cedula_identidad'] . ' · ' . $r['carrera'],
            ]
        );
        return ['etiqueta' => 'Estudiantes', 'ruta' => 'modulos/estudiantes/ver.php', 'items' => $items];
    }

    private static function buscarInstituciones(string $t, ?string $carrera): array
    {
        $like = '%' . $t . '%';
        $params = [$like, $like, $like];
        $filtroCarrera = '';

        if ($carrera !== null && $carrera !== '') {
            $filtroCarrera = 'AND EXISTS (SELECT 1 FROM practicas_laborales pl
                                         INNER JOIN estudiantes est ON est.id_estudiante = pl.id_estudiante
                                         WHERE pl.id_institucion = i.id_institucion AND est.carrera = ?) ';
            $params[] = $carrera;
        }

        $sql = "SELECT i.id_institucion, i.codigo_institucion, i.nombre, i.nit
                FROM instituciones_receptoras i
                WHERE (i.nombre LIKE ? OR i.nit LIKE ? OR i.codigo_institucion LIKE ?)
                {$filtroCarrera}
                ORDER BY i.nombre LIMIT " . self::MAX_POR_CATEGORIA;

        $items = self::ejecutar(
            $sql,
            $params,
            fn($r) => [
                'id'        => (int) $r['id_institucion'],
                'titulo'    => $r['nombre'],
                'subtitulo' => $r['codigo_institucion'] . ' · NIT ' . $r['nit'],
            ]
        );
        return ['etiqueta' => 'Instituciones', 'ruta' => 'modulos/instituciones/ver.php', 'items' => $items];
    }

    private static function buscarDocentes(string $t, ?string $carrera): array
    {
        $like = '%' . $t . '%';
        $params = [$like, $like, $like, $like];
        $filtroCarrera = '';

        if ($carrera !== null && $carrera !== '') {
            $filtroCarrera = 'AND carrera = ? ';
            $params[] = $carrera;
        }

        $sql = "SELECT id_docente, codigo_docente, nombre_completo, carrera, telefono
                FROM docentes_supervisores
                WHERE (nombre_completo LIKE ? OR cedula_identidad LIKE ? OR codigo_docente LIKE ? OR telefono LIKE ?)
                {$filtroCarrera}
                ORDER BY nombre_completo LIMIT " . self::MAX_POR_CATEGORIA;

        $items = self::ejecutar(
            $sql,
            $params,
            fn($r) => [
                'id'        => (int) $r['id_docente'],
                'titulo'    => $r['nombre_completo'],
                'subtitulo' => $r['codigo_docente'] . ' · ' . $r['carrera'] . ($r['telefono'] ? ' · Tel: ' . $r['telefono'] : ''),
            ]
        );
        return ['etiqueta' => 'Docentes supervisores', 'ruta' => 'modulos/practicas/supervisores.php', 'items' => $items];
    }

    private static function buscarConvenios(string $t, ?string $carrera): array
    {
        $like = '%' . $t . '%';
        $params = [$like, $like];
        $filtroCarrera = '';

        if ($carrera !== null && $carrera !== '') {
            $filtroCarrera = 'AND EXISTS (SELECT 1 FROM practicas_laborales pl
                                         INNER JOIN estudiantes est ON est.id_estudiante = pl.id_estudiante
                                         WHERE pl.id_convenio = c.id_convenio AND est.carrera = ?) ';
            $params[] = $carrera;
        }

        $sql = "SELECT c.id_convenio, c.codigo_convenio, c.titulo, i.nombre AS institucion
                FROM convenios c
                INNER JOIN instituciones_receptoras i ON i.id_institucion = c.id_institucion
                WHERE (c.titulo LIKE ? OR c.codigo_convenio LIKE ?)
                {$filtroCarrera}
                ORDER BY c.titulo LIMIT " . self::MAX_POR_CATEGORIA;

        $items = self::ejecutar(
            $sql,
            $params,
            fn($r) => [
                'id'        => (int) $r['id_convenio'],
                'titulo'    => $r['titulo'],
                'subtitulo' => $r['codigo_convenio'] . ' · ' . $r['institucion'],
            ]
        );
        return ['etiqueta' => 'Convenios', 'ruta' => 'modulos/convenios/ver.php', 'items' => $items];
    }

    private static function buscarPracticas(string $t, ?string $carrera): array
    {
        $like = '%' . $t . '%';
        $params = [$like, $like];
        $filtroCarrera = '';

        if ($carrera !== null && $carrera !== '') {
            $filtroCarrera = 'AND e.carrera = ? ';
            $params[] = $carrera;
        }

        $sql = "SELECT p.id_practica, p.codigo_practica, p.estado, e.nombre_completo AS estudiante, e.carrera
                FROM practicas_laborales p
                INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                WHERE (p.codigo_practica LIKE ? OR e.nombre_completo LIKE ?)
                {$filtroCarrera}
                ORDER BY p.codigo_practica LIMIT " . self::MAX_POR_CATEGORIA;

        $items = self::ejecutar(
            $sql,
            $params,
            fn($r) => [
                'id'        => (int) $r['id_practica'],
                'titulo'    => $r['codigo_practica'] . ' - ' . $r['estudiante'],
                'subtitulo' => $r['carrera'] . ' · Estado: ' . ucfirst(str_replace('_', ' ', $r['estado'])),
            ]
        );
        return ['etiqueta' => 'Prácticas', 'ruta' => 'modulos/practicas/ver.php', 'items' => $items];
    }
}
