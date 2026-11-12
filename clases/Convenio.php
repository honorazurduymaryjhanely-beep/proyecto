<?php
/**
 * Clase Convenio - Gestión de convenios institucionales
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Encapsula el acceso a la tabla `convenios`: alta, edición, consulta,
 * listado paginado, cálculo del estado de vigencia y obtención de convenios
 * vigentes por institución.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Convenio
{
    /**
     * Calcular el estado de vigencia según las fechas.
     *
     * Un convenio "cancelado" conserva ese estado. En caso contrario, se
     * calcula: "vigente" si la fecha actual está dentro del rango de vigencia,
     * "expirado" si ya pasó la fecha de fin.
     *
     * @param string $fechaInicio   Fecha de inicio de vigencia (Y-m-d)
     * @param string $fechaFin       Fecha de fin de vigencia (Y-m-d)
     * @param string $estadoActual   Estado almacenado (para respetar 'cancelado')
     * @return string vigente|expirado|cancelado
     */
    public static function calcularEstado(string $fechaInicio, string $fechaFin, string $estadoActual = 'vigente'): string
    {
        if ($estadoActual === 'cancelado') {
            return 'cancelado';
        }
        $hoy = date('Y-m-d');
        if ($hoy > $fechaFin) {
            return 'expirado';
        }
        return 'vigente';
    }

    /**
     * Crear un convenio asignándole el código CON-XXXXXX y su estado calculado.
     *
     * @param array $datos     Datos del convenio
     * @param int   $creadoPor ID del usuario que registra
     * @return int|null
     */
    public static function crear(array $datos, int $creadoPor): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $db->beginTransaction();

            $estado = self::calcularEstado($datos['fecha_inicio_vigencia'], $datos['fecha_fin_vigencia']);

            $sql = 'INSERT INTO convenios
                        (codigo_convenio, titulo, descripcion, id_institucion,
                         fecha_firma, fecha_inicio_vigencia, fecha_fin_vigencia,
                         documento_adjunto, estado, creado_por)
                    VALUES
                        (:codigo, :titulo, :descripcion, :id_institucion,
                         :firma, :inicio, :fin, :documento, :estado, :creado_por)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':codigo'         => 'TMP',
                ':titulo'         => $datos['titulo'],
                ':descripcion'    => $datos['descripcion'] ?: null,
                ':id_institucion' => (int) $datos['id_institucion'],
                ':firma'          => $datos['fecha_firma'],
                ':inicio'         => $datos['fecha_inicio_vigencia'],
                ':fin'            => $datos['fecha_fin_vigencia'],
                ':documento'      => $datos['documento_adjunto'] ?: null,
                ':estado'         => $estado,
                ':creado_por'     => $creadoPor,
            ]);
            $id = (int) $db->lastInsertId();

            $codigo = generarCodigo('CON', $id);
            $upd = $db->prepare('UPDATE convenios SET codigo_convenio = :codigo WHERE id_convenio = :id');
            $upd->execute([':codigo' => $codigo, ':id' => $id]);

            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al crear convenio: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar un convenio y recalcular su estado.
     *
     * @param int   $id
     * @param array $datos
     * @return bool
     */
    public static function actualizar(int $id, array $datos): bool
    {
        try {
            $db = Database::obtenerInstancia();

            $actual = self::obtenerPorId($id);
            $estadoActual = $actual['estado'] ?? 'vigente';
            $estado = self::calcularEstado($datos['fecha_inicio_vigencia'], $datos['fecha_fin_vigencia'], $estadoActual);

            $campos = [
                'titulo = :titulo',
                'descripcion = :descripcion',
                'id_institucion = :id_institucion',
                'fecha_firma = :firma',
                'fecha_inicio_vigencia = :inicio',
                'fecha_fin_vigencia = :fin',
                'estado = :estado',
            ];
            $params = [
                ':titulo'         => $datos['titulo'],
                ':descripcion'    => $datos['descripcion'] ?: null,
                ':id_institucion' => (int) $datos['id_institucion'],
                ':firma'          => $datos['fecha_firma'],
                ':inicio'         => $datos['fecha_inicio_vigencia'],
                ':fin'            => $datos['fecha_fin_vigencia'],
                ':estado'         => $estado,
                ':id'             => $id,
            ];

            // Solo actualizar el documento si se subió uno nuevo.
            if (!empty($datos['documento_adjunto'])) {
                $campos[] = 'documento_adjunto = :documento';
                $params[':documento'] = $datos['documento_adjunto'];
            }

            $sql = 'UPDATE convenios SET ' . implode(', ', $campos) . ' WHERE id_convenio = :id';
            $stmt = $db->prepare($sql);
            return $stmt->execute($params);
        } catch (Throwable $e) {
            error_log('Error al actualizar convenio: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener un convenio por su ID (incluye nombre de la institución).
     */
    public static function obtenerPorId(int $id): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT c.*, i.nombre AS institucion_nombre, i.estado AS institucion_estado
                    FROM convenios c
                    INNER JOIN instituciones_receptoras i ON i.id_institucion = c.id_institucion
                    WHERE c.id_convenio = :id LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener convenio: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Construir cláusula WHERE y parámetros a partir de filtros.
     *
     * @param array $filtros Claves: buscar, id_institucion, estado
     * @return array{0:string,1:array}
     */
    private static function construirFiltros(array $filtros): array
    {
        $cond = [];
        $params = [];
        if (!empty($filtros['buscar'])) {
            $cond[] = '(c.titulo LIKE :b1 OR c.codigo_convenio LIKE :b2)';
            $params[':b1'] = '%' . $filtros['buscar'] . '%';
            $params[':b2'] = '%' . $filtros['buscar'] . '%';
        }
        if (!empty($filtros['id_institucion'])) {
            $cond[] = 'c.id_institucion = :id_institucion';
            $params[':id_institucion'] = (int) $filtros['id_institucion'];
        }
        if (!empty($filtros['estado'])) {
            $cond[] = 'c.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        return [$cond ? 'WHERE ' . implode(' AND ', $cond) : '', $params];
    }

    /**
     * Listar convenios paginados con filtros (incluye institución).
     */
    public static function listar(int $pagina = 1, array $filtros = [], int $porPagina = 15): array
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $offset = max(0, ($pagina - 1) * $porPagina);
            $sql = "SELECT c.id_convenio, c.codigo_convenio, c.titulo,
                           c.fecha_inicio_vigencia, c.fecha_fin_vigencia, c.estado,
                           i.nombre AS institucion_nombre
                    FROM convenios c
                    INNER JOIN instituciones_receptoras i ON i.id_institucion = c.id_institucion
                    {$where}
                    ORDER BY c.fecha_fin_vigencia DESC
                    LIMIT :limite OFFSET :offset";
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al listar convenios: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar convenios que cumplen los filtros.
     */
    public static function contarTotal(array $filtros = []): int
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $stmt = $db->prepare("SELECT COUNT(*) FROM convenios c {$where}");
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Error al contar convenios: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener los convenios vigentes de una institución a la fecha actual.
     *
     * @param int $idInstitucion
     * @return array
     */
    public static function obtenerVigentesPorInstitucion(int $idInstitucion): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = "SELECT id_convenio, codigo_convenio, titulo,
                           fecha_inicio_vigencia, fecha_fin_vigencia
                    FROM convenios
                    WHERE id_institucion = :id
                      AND estado = 'vigente'
                      AND CURDATE() BETWEEN fecha_inicio_vigencia AND fecha_fin_vigencia
                    ORDER BY fecha_fin_vigencia DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $idInstitucion]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener convenios vigentes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un convenio está vigente a la fecha actual.
     *
     * @param int $idConvenio
     * @return bool
     */
    public static function verificarVigencia(int $idConvenio): bool
    {
        $c = self::obtenerPorId($idConvenio);
        if ($c === null) {
            return false;
        }
        $hoy = date('Y-m-d');
        return $c['estado'] !== 'cancelado'
            && $hoy >= $c['fecha_inicio_vigencia']
            && $hoy <= $c['fecha_fin_vigencia'];
    }
}
