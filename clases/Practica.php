<?php
/**
 * Clase Practica - Gestión de prácticas laborales
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Encapsula el acceso a la tabla `practicas_laborales`: registro, edición,
 * consulta, listado paginado con filtros, verificación de práctica activa,
 * asignación de supervisores, cambio de estado con transiciones válidas e
 * historial de estados.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Practica
{
    /**
     * Transiciones de estado permitidas.
     * Clave = estado actual, valor = lista de estados destino permitidos.
     */
    public const TRANSICIONES = [
        'pendiente'  => ['en_curso', 'suspendida'],
        'en_curso'   => ['completada', 'suspendida'],
        'suspendida' => ['en_curso'],
        'completada' => [],
    ];

    /**
     * Crear una práctica laboral con estado inicial "pendiente" y código PRA-XXXXXX.
     *
     * @param array $datos     Datos de la práctica
     * @param int   $creadoPor ID del usuario que registra
     * @return int|null
     */
    public static function crear(array $datos, int $creadoPor): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $db->beginTransaction();

            $sql = 'INSERT INTO practicas_laborales
                        (codigo_practica, id_estudiante, id_institucion, id_convenio,
                         descripcion_actividades, area_departamento, fecha_inicio,
                         fecha_fin_prevista, total_horas_requeridas, estado, creado_por)
                    VALUES
                        (:codigo, :id_estudiante, :id_institucion, :id_convenio,
                         :descripcion, :area, :inicio, :fin_prevista,
                         :horas, :estado, :creado_por)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':codigo'         => 'TMP',
                ':id_estudiante'  => (int) $datos['id_estudiante'],
                ':id_institucion' => (int) $datos['id_institucion'],
                ':id_convenio'    => (int) $datos['id_convenio'],
                ':descripcion'    => $datos['descripcion_actividades'] ?: null,
                ':area'           => $datos['area_departamento'] ?: null,
                ':inicio'         => $datos['fecha_inicio'],
                ':fin_prevista'   => $datos['fecha_fin_prevista'],
                ':horas'          => (int) $datos['total_horas_requeridas'],
                ':estado'         => 'pendiente',
                ':creado_por'     => $creadoPor,
            ]);
            $id = (int) $db->lastInsertId();

            $codigo = generarCodigo('PRA', $id);
            $upd = $db->prepare('UPDATE practicas_laborales SET codigo_practica = :codigo WHERE id_practica = :id');
            $upd->execute([':codigo' => $codigo, ':id' => $id]);

            // Registrar el estado inicial en el historial.
            $hist = $db->prepare('INSERT INTO practicas_historial_estado
                    (id_practica, estado_anterior, estado_nuevo, id_usuario, observaciones)
                    VALUES (:id, :ant, :nuevo, :usuario, :obs)');
            $hist->execute([
                ':id'      => $id,
                ':ant'     => 'pendiente',
                ':nuevo'   => 'pendiente',
                ':usuario' => $creadoPor,
                ':obs'     => 'Registro inicial de la práctica.',
            ]);

            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al crear práctica: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar los datos generales de una práctica.
     */
    public static function actualizar(int $id, array $datos): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'UPDATE practicas_laborales SET
                        descripcion_actividades = :descripcion,
                        area_departamento = :area,
                        fecha_inicio = :inicio,
                        fecha_fin_prevista = :fin_prevista,
                        total_horas_requeridas = :horas
                    WHERE id_practica = :id';
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':descripcion'  => $datos['descripcion_actividades'] ?: null,
                ':area'         => $datos['area_departamento'] ?: null,
                ':inicio'       => $datos['fecha_inicio'],
                ':fin_prevista' => $datos['fecha_fin_prevista'],
                ':horas'        => (int) $datos['total_horas_requeridas'],
                ':id'           => $id,
            ]);
        } catch (Throwable $e) {
            error_log('Error al actualizar práctica: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener una práctica por su ID con datos relacionados.
     */
    public static function obtenerPorId(int $id): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = "SELECT p.*,
                           e.nombre_completo AS estudiante, e.codigo_estudiante, e.carrera,
                           i.nombre AS institucion, i.codigo_institucion,
                           c.titulo AS convenio_titulo, c.codigo_convenio,
                           c.fecha_inicio_vigencia, c.fecha_fin_vigencia
                    FROM practicas_laborales p
                    INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                    INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
                    INNER JOIN convenios c ON c.id_convenio = p.id_convenio
                    WHERE p.id_practica = :id LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener práctica: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Construir cláusula WHERE y parámetros a partir de filtros.
     *
     * @param array $filtros Claves: estado, id_institucion, id_docente, buscar
     * @return array{0:string,1:array}
     */
    private static function construirFiltros(array $filtros): array
    {
        $cond = [];
        $params = [];
        if (!empty($filtros['estado'])) {
            $cond[] = 'p.estado = :estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['id_institucion'])) {
            $cond[] = 'p.id_institucion = :id_institucion';
            $params[':id_institucion'] = (int) $filtros['id_institucion'];
        }
        if (!empty($filtros['buscar'])) {
            $cond[] = '(p.codigo_practica LIKE :b1 OR e.nombre_completo LIKE :b2)';
            $params[':b1'] = '%' . $filtros['buscar'] . '%';
            $params[':b2'] = '%' . $filtros['buscar'] . '%';
        }
        if (!empty($filtros['id_docente'])) {
            $cond[] = 'EXISTS (SELECT 1 FROM asignaciones_supervision a
                               WHERE a.id_practica = p.id_practica
                                 AND a.id_docente = :id_docente AND a.estado = 1)';
            $params[':id_docente'] = (int) $filtros['id_docente'];
        }
        if (!empty($filtros['carrera'])) {
            $carr = normalizarCarrera($filtros['carrera']);
            $cond[] = '(e.carrera = :carrera OR e.carrera LIKE :carrera_like)';
            $params[':carrera'] = $carr;
            $primer = explode(' ', trim($carr))[0] ?? $carr;
            $params[':carrera_like'] = '%' . $primer . '%';
        }
        return [$cond ? 'WHERE ' . implode(' AND ', $cond) : '', $params];
    }

    /**
     * Listar prácticas paginadas con filtros y datos relacionados,
     * incluyendo el porcentaje de horas cumplidas y el supervisor actual.
     */
    public static function listar(int $pagina = 1, array $filtros = [], int $porPagina = 15): array
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $offset = max(0, ($pagina - 1) * $porPagina);
            $sql = "SELECT p.id_practica, p.codigo_practica, p.estado,
                           p.fecha_inicio, p.fecha_fin_prevista, p.total_horas_requeridas,
                           e.nombre_completo AS estudiante, e.carrera,
                           i.nombre AS institucion,
                           (SELECT COALESCE(SUM(h.cantidad_horas), 0)
                            FROM horas_cumplidas h WHERE h.id_practica = p.id_practica) AS horas_cumplidas,
                           (SELECT d.nombre_completo
                             FROM asignaciones_supervision a
                             INNER JOIN docentes_supervisores d ON d.id_docente = a.id_docente
                             WHERE a.id_practica = p.id_practica AND a.estado = 1
                             LIMIT 1) AS supervisor,
                            (SELECT d.telefono
                             FROM asignaciones_supervision a
                             INNER JOIN docentes_supervisores d ON d.id_docente = a.id_docente
                             WHERE a.id_practica = p.id_practica AND a.estado = 1
                             LIMIT 1) AS supervisor_telefono
                    FROM practicas_laborales p
                    INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                    INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
                    {$where}
                    ORDER BY p.fecha_creacion DESC
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
            error_log('Error al listar prácticas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar prácticas que cumplen los filtros.
     */
    public static function contarTotal(array $filtros = []): int
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $sql = "SELECT COUNT(*)
                    FROM practicas_laborales p
                    INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                    {$where}";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Error al contar prácticas: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar si un estudiante tiene una práctica en curso.
     *
     * @param int $idEstudiante
     * @return bool true si tiene una práctica en estado "en_curso"
     */
    public static function verificarPracticaActiva(int $idEstudiante): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare("SELECT COUNT(*) FROM practicas_laborales
                                  WHERE id_estudiante = :id AND estado = 'en_curso'");
            $stmt->execute([':id' => $idEstudiante]);
            return ((int) $stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            error_log('Error al verificar práctica activa: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar el estado de una práctica validando la transición y registrando
     * el cambio en el historial. Al completar, registra la fecha real.
     *
     * @param int    $id
     * @param string $nuevoEstado
     * @param int    $idUsuario
     * @param string $observaciones
     * @return array{0:bool,1:string} [éxito, mensaje]
     */
    public static function cambiarEstado(int $id, string $nuevoEstado, int $idUsuario, string $observaciones = ''): array
    {
        try {
            $practica = self::obtenerPorId($id);
            if ($practica === null) {
                return [false, 'La práctica no existe.'];
            }
            $estadoActual = $practica['estado'];

            $permitidos = self::TRANSICIONES[$estadoActual] ?? [];
            if (!in_array($nuevoEstado, $permitidos, true)) {
                return [false, "No se puede cambiar de \"{$estadoActual}\" a \"{$nuevoEstado}\"."];
            }

            $db = Database::obtenerInstancia();
            $db->beginTransaction();

            if ($nuevoEstado === 'completada') {
                $upd = $db->prepare("UPDATE practicas_laborales
                                     SET estado = :estado, fecha_finalizacion_real = CURDATE()
                                     WHERE id_practica = :id");
                $upd->execute([':estado' => $nuevoEstado, ':id' => $id]);
            } else {
                $upd = $db->prepare('UPDATE practicas_laborales SET estado = :estado WHERE id_practica = :id');
                $upd->execute([':estado' => $nuevoEstado, ':id' => $id]);
            }

            $hist = $db->prepare('INSERT INTO practicas_historial_estado
                    (id_practica, estado_anterior, estado_nuevo, id_usuario, observaciones)
                    VALUES (:id, :ant, :nuevo, :usuario, :obs)');
            $hist->execute([
                ':id'      => $id,
                ':ant'     => $estadoActual,
                ':nuevo'   => $nuevoEstado,
                ':usuario' => $idUsuario,
                ':obs'     => $observaciones ?: null,
            ]);

            $db->commit();
            return [true, 'Estado de la práctica actualizado correctamente.'];
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al cambiar estado de práctica: ' . $e->getMessage());
            return [false, 'Ocurrió un error al cambiar el estado.'];
        }
    }

    /**
     * Obtener el historial de cambios de estado de una práctica.
     */
    public static function obtenerHistorialEstados(int $id): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT h.estado_anterior, h.estado_nuevo, h.fecha_cambio,
                           h.observaciones, u.nombre_completo AS usuario
                    FROM practicas_historial_estado h
                    INNER JOIN usuarios u ON u.id_usuario = h.id_usuario
                    WHERE h.id_practica = :id
                    ORDER BY h.fecha_cambio ASC';
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener historial de estados: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Construir WHERE y parámetros para el historial de prácticas completadas.
     *
     * @param array $filtros Claves: fecha_desde, fecha_hasta, carrera, id_institucion
     * @return array{0:string,1:array}
     */
    private static function construirFiltrosHistorial(array $filtros): array
    {
        $cond = ["p.estado = 'completada'"];
        $params = [];
        if (!empty($filtros['fecha_desde'])) {
            $cond[] = 'p.fecha_finalizacion_real >= :desde';
            $params[':desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $cond[] = 'p.fecha_finalizacion_real <= :hasta';
            $params[':hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['carrera'])) {
            $carr = normalizarCarrera($filtros['carrera']);
            $cond[] = '(e.carrera = :carrera OR e.carrera LIKE :carrera_like)';
            $params[':carrera'] = $carr;
            $primer = explode(' ', trim($carr))[0] ?? $carr;
            $params[':carrera_like'] = '%' . $primer . '%';
        }
        if (!empty($filtros['id_institucion'])) {
            $cond[] = 'p.id_institucion = :id_institucion';
            $params[':id_institucion'] = (int) $filtros['id_institucion'];
        }
        return ['WHERE ' . implode(' AND ', $cond), $params];
    }

    /**
     * Listar prácticas completadas con filtros (para historial y exportación).
     *
     * @param array $filtros   Filtros del historial
     * @param int   $pagina    Página (1-indexada); usar 0 para traer todo (export)
     * @param int   $porPagina Registros por página
     * @return array
     */
    public static function listarCompletadas(array $filtros = [], int $pagina = 1, int $porPagina = 15): array
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltrosHistorial($filtros);
            $limite = '';
            if ($pagina > 0) {
                $offset = max(0, ($pagina - 1) * $porPagina);
                $limite = " LIMIT {$porPagina} OFFSET {$offset}";
            }
            $sql = "SELECT p.id_practica, p.codigo_practica, p.fecha_inicio,
                           p.fecha_finalizacion_real, p.total_horas_requeridas,
                           e.nombre_completo AS estudiante, e.carrera,
                           i.nombre AS institucion,
                           (SELECT COALESCE(SUM(h.cantidad_horas), 0)
                            FROM horas_cumplidas h WHERE h.id_practica = p.id_practica) AS horas_cumplidas,
                           (SELECT d.nombre_completo
                             FROM asignaciones_supervision a
                             INNER JOIN docentes_supervisores d ON d.id_docente = a.id_docente
                             WHERE a.id_practica = p.id_practica AND a.estado = 1 LIMIT 1) AS supervisor
                    FROM practicas_laborales p
                    INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                    INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
                    {$where}
                    ORDER BY p.fecha_finalizacion_real DESC{$limite}";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al listar prácticas completadas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar prácticas completadas que cumplen los filtros del historial.
     */
    public static function contarCompletadas(array $filtros = []): int
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltrosHistorial($filtros);
            $sql = "SELECT COUNT(*)
                    FROM practicas_laborales p
                    INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
                    {$where}";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Error al contar prácticas completadas: ' . $e->getMessage());
            return 0;
        }
    }

    // =====================================================================
    // Supervisión (asignación de docentes)
    // =====================================================================

    /**
     * Asignar un docente supervisor a la práctica. Si ya hay uno activo,
     * lo marca como reasignado (conserva el historial).
     *
     * @param int    $idPractica
     * @param int    $idDocente
     * @param int    $asignadoPor
     * @param string $observaciones
     * @return bool
     */
    public static function asignarSupervisor(int $idPractica, int $idDocente, int $asignadoPor, string $observaciones = ''): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $db->beginTransaction();

            // Desactivar la asignación activa anterior (si existe).
            $db->prepare('UPDATE asignaciones_supervision SET estado = 0
                          WHERE id_practica = :id AND estado = 1')
               ->execute([':id' => $idPractica]);

            // Crear la nueva asignación activa.
            $ins = $db->prepare('INSERT INTO asignaciones_supervision
                    (id_practica, id_docente, fecha_asignacion, estado, observaciones, asignado_por)
                    VALUES (:practica, :docente, CURDATE(), 1, :obs, :por)');
            $ins->execute([
                ':practica' => $idPractica,
                ':docente'  => $idDocente,
                ':obs'      => $observaciones ?: null,
                ':por'      => $asignadoPor,
            ]);

            $db->commit();
            return true;
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al asignar supervisor: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener el supervisor actual (asignación activa) de una práctica.
     */
    public static function obtenerSupervisorActual(int $idPractica): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT d.id_docente, d.nombre_completo, d.carrera, d.telefono, d.correo_electronico, a.fecha_asignacion, a.observaciones
                    FROM asignaciones_supervision a
                    INNER JOIN docentes_supervisores d ON d.id_docente = a.id_docente
                    WHERE a.id_practica = :id AND a.estado = 1
                    LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $idPractica]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener supervisor actual: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener el historial completo de supervisores de una práctica.
     */
    public static function obtenerHistorialSupervisores(int $idPractica): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT d.nombre_completo, d.carrera, d.telefono, a.fecha_asignacion, a.estado, a.observaciones
                    FROM asignaciones_supervision a
                    INNER JOIN docentes_supervisores d ON d.id_docente = a.id_docente
                    WHERE a.id_practica = :id
                    ORDER BY a.fecha_creacion DESC';
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $idPractica]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener historial de supervisores: ' . $e->getMessage());
            return [];
        }
    }
}
