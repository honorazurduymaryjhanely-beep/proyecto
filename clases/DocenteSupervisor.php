<?php
/**
 * Clase DocenteSupervisor - Gestión de docentes supervisores
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Encapsula el acceso a la tabla `docentes_supervisores`: alta, edición,
 * consulta, listado paginado con filtros por carrera, verificación de cédula
 * única y consulta de las prácticas asignadas al docente supervisor.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class DocenteSupervisor
{
    /**
     * Crear un docente supervisor asignándole el código DOC-XXXXXX.
     *
     * @param array $datos     Datos del docente (nombre_completo, cedula_identidad, carrera, telefono, correo_electronico)
     * @param int   $creadoPor ID del usuario que registra
     * @return int|null ID creado o null en caso de error
     */
    public static function crear(array $datos, int $creadoPor): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $db->beginTransaction();

            $sql = 'INSERT INTO docentes_supervisores
                        (codigo_docente, nombre_completo, cedula_identidad,
                         carrera, telefono, correo_electronico, estado, creado_por)
                    VALUES
                        (:codigo, :nombre, :cedula, :carrera, :telefono,
                         :correo, 1, :creado_por)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':codigo'     => 'TMP',
                ':nombre'     => trim($datos['nombre_completo']),
                ':cedula'     => trim($datos['cedula_identidad']),
                ':carrera'    => trim($datos['carrera']),
                ':telefono'   => trim($datos['telefono'] ?? '') ?: null,
                ':correo'     => trim($datos['correo_electronico'] ?? '') ?: null,
                ':creado_por' => $creadoPor,
            ]);
            $id = (int) $db->lastInsertId();

            $codigo = generarCodigo('DOC', $id);
            $upd = $db->prepare('UPDATE docentes_supervisores SET codigo_docente = :codigo WHERE id_docente = :id');
            $upd->execute([':codigo' => $codigo, ':id' => $id]);

            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al crear docente supervisor: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar los datos de un docente supervisor.
     */
    public static function actualizar(int $id, array $datos): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'UPDATE docentes_supervisores SET
                        nombre_completo = :nombre,
                        cedula_identidad = :cedula,
                        carrera = :carrera,
                        telefono = :telefono,
                        correo_electronico = :correo
                    WHERE id_docente = :id';
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':nombre'   => trim($datos['nombre_completo']),
                ':cedula'   => trim($datos['cedula_identidad']),
                ':carrera'  => trim($datos['carrera']),
                ':telefono' => trim($datos['telefono'] ?? '') ?: null,
                ':correo'   => trim($datos['correo_electronico'] ?? '') ?: null,
                ':id'       => $id,
            ]);
        } catch (Throwable $e) {
            error_log('Error al actualizar docente supervisor: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar el estado (activo/inactivo) de un docente supervisor.
     */
    public static function cambiarEstado(int $id, int $estado): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('UPDATE docentes_supervisores SET estado = :estado WHERE id_docente = :id');
            return $stmt->execute([':estado' => $estado ? 1 : 0, ':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al cambiar estado de docente supervisor: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener un docente supervisor por su ID.
     */
    public static function obtenerPorId(int $id): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('SELECT * FROM docentes_supervisores WHERE id_docente = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener docente supervisor: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verificar si el docente supervisor tiene asignaciones de supervisión registradas.
     *
     * @param int $id ID del docente
     * @return bool
     */
    public static function tieneAsignaciones(int $id): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('SELECT COUNT(*) FROM asignaciones_supervision WHERE id_docente = :id');
            $stmt->execute([':id' => $id]);
            return ((int) $stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            error_log('Error al verificar asignaciones del docente supervisor: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Eliminar físicamente un docente supervisor si no tiene asignaciones registradas.
     *
     * @param int $id ID del docente
     * @return array{0:bool, 1:string} [éxito, mensaje]
     */
    public static function eliminar(int $id): array
    {
        try {
            if (self::tieneAsignaciones($id)) {
                return [false, 'No se puede eliminar el docente supervisor porque tiene asignaciones de supervisión registradas. Si desea desactivarlo, use la opción "Desactivar".'];
            }

            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('DELETE FROM docentes_supervisores WHERE id_docente = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() > 0) {
                return [true, 'Docente supervisor eliminado definitivamente del sistema.'];
            }
            return [false, 'No se encontró el registro del docente para eliminar.'];
        } catch (Throwable $e) {
            error_log('Error al eliminar físicamente docente supervisor: ' . $e->getMessage());
            return [false, 'Error en el servidor al intentar eliminar el docente: ' . $e->getMessage()];
        }
    }

    /**
     * Construir cláusula WHERE y parámetros a partir de filtros.
     *
     * @param array $filtros Claves: buscar, carrera, estado
     * @return array{0:string,1:array}
     */
    private static function construirFiltros(array $filtros): array
    {
        $cond = [];
        $params = [];
        if (!empty($filtros['buscar'])) {
            $cond[] = '(nombre_completo LIKE :b1 OR cedula_identidad LIKE :b2 OR codigo_docente LIKE :b3 OR carrera LIKE :b4 OR telefono LIKE :b5)';
            $params[':b1'] = '%' . $filtros['buscar'] . '%';
            $params[':b2'] = '%' . $filtros['buscar'] . '%';
            $params[':b3'] = '%' . $filtros['buscar'] . '%';
            $params[':b4'] = '%' . $filtros['buscar'] . '%';
            $params[':b5'] = '%' . $filtros['buscar'] . '%';
        }
        if (!empty($filtros['carrera'])) {
            $norm = normalizarCarrera($filtros['carrera']);
            $primerPalabra = explode(' ', trim($norm ?: $filtros['carrera']))[0] ?? '';
            $cond[] = '(carrera = :carrera OR carrera = :carrera_norm OR carrera LIKE :carrera_like)';
            $params[':carrera'] = $filtros['carrera'];
            $params[':carrera_norm'] = $norm;
            $params[':carrera_like'] = '%' . $primerPalabra . '%';
        }
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $cond[] = 'estado = :estado';
            $params[':estado'] = (int) $filtros['estado'];
        }
        return [$cond ? 'WHERE ' . implode(' AND ', $cond) : '', $params];
    }

    /**
     * Listar docentes supervisores paginados con filtros.
     */
    public static function listar(int $pagina = 1, array $filtros = [], int $porPagina = 15): array
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $offset = max(0, ($pagina - 1) * $porPagina);
            $sql = "SELECT id_docente, codigo_docente, nombre_completo, cedula_identidad,
                           carrera, telefono, correo_electronico, estado
                    FROM docentes_supervisores
                    {$where}
                    ORDER BY nombre_completo ASC
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
            error_log('Error al listar docentes supervisores: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar docentes supervisores que cumplen los filtros.
     */
    public static function contarTotal(array $filtros = []): int
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $stmt = $db->prepare("SELECT COUNT(*) FROM docentes_supervisores {$where}");
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Error al contar docentes supervisores: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Buscar docentes supervisores por término libre y carrera opcional.
     */
    public static function buscar(string $termino, ?string $carrera = null, int $limite = 50): array
    {
        $filtros = ['buscar' => $termino];
        if ($carrera !== null && $carrera !== '') {
            $filtros['carrera'] = $carrera;
        }
        return self::listar(1, $filtros, $limite);
    }

    /**
     * Obtener los docentes supervisores activos (para selectores de asignación).
     * Permite filtrar opcionalmente por carrera.
     *
     * @param string|null $carrera Carrera del estudiante/docente encargado
     * @return array Filas con id_docente, nombre_completo, carrera, telefono, correo_electronico
     */
    public static function obtenerActivos(?string $carrera = null): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT id_docente, codigo_docente, nombre_completo, cedula_identidad, carrera, telefono, correo_electronico
                    FROM docentes_supervisores
                    WHERE estado = 1';
            $params = [];
            if ($carrera !== null && trim($carrera) !== '') {
                $norm = normalizarCarrera($carrera);
                $primerPalabra = explode(' ', trim($norm ?: $carrera))[0] ?? $carrera;
                $sql .= ' AND (carrera = :carrera OR carrera = :carrera_norm OR carrera LIKE :carrera_like)';
                $params[':carrera'] = $carrera;
                $params[':carrera_norm'] = $norm;
                $params[':carrera_like'] = '%' . $primerPalabra . '%';
            }
            $sql .= ' ORDER BY nombre_completo ASC';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener docentes supervisores activos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si una cédula está disponible, excluyendo un ID opcional.
     */
    public static function verificarCedulaUnica(string $cedula, ?int $idExcluir = null): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT COUNT(*) FROM docentes_supervisores WHERE cedula_identidad = :cedula';
            $params = [':cedula' => trim($cedula)];
            if ($idExcluir !== null && $idExcluir > 0) {
                $sql .= ' AND id_docente <> :id';
                $params[':id'] = $idExcluir;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return ((int) $stmt->fetchColumn()) === 0;
        } catch (Throwable $e) {
            error_log('Error al verificar cédula de docente supervisor: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener las prácticas asignadas a un docente supervisor.
     *
     * @param int $idDocente
     * @return array Prácticas (id_practica, codigo_practica, estado, estudiante, institucion, fecha_asignacion)
     */
    public static function obtenerAsignaciones(int $idDocente): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = "SELECT p.id_practica, p.codigo_practica, p.estado,
                           e.nombre_completo AS estudiante,
                           e.carrera,
                           i.nombre AS institucion,
                           a.fecha_asignacion, a.observaciones, a.estado AS asignacion_activa
                    FROM asignaciones_supervision a
                    INNER JOIN practicas_laborales p ON p.id_practica = a.id_practica
                    INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                    INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
                    WHERE a.id_docente = :id
                    ORDER BY a.estado DESC, a.fecha_asignacion DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $idDocente]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener asignaciones del docente supervisor: ' . $e->getMessage());
            return [];
        }
    }
}
