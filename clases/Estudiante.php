<?php
/**
 * Clase Estudiante - Gestión de estudiantes
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Encapsula el acceso a la tabla `estudiantes`: alta, edición, consulta,
 * listado paginado con filtros y verificación de cédula única. Utiliza
 * eliminación lógica (cambio de estado) en lugar de borrado físico.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Estudiante
{
    /**
     * Crear un nuevo estudiante y asignarle su código EST-XXXXXX.
     *
     * @param array $datos       Datos del estudiante
     * @param int   $creadoPor   ID del usuario que registra
     * @return int|null ID del estudiante creado o null en caso de error
     */
    public static function crear(array $datos, int $creadoPor): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $db->beginTransaction();

            $sql = 'INSERT INTO estudiantes
                        (codigo_estudiante, nombre_completo, cedula_identidad,
                         fecha_nacimiento, carrera, curso, paralelo, telefono,
                         correo_electronico, direccion, estado, creado_por)
                    VALUES
                        (:codigo, :nombre, :cedula, :fecha_nac, :carrera,
                         :curso, :paralelo, :telefono, :correo, :direccion, 1, :creado_por)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':codigo'      => 'TMP',
                ':nombre'      => $datos['nombre_completo'],
                ':cedula'      => $datos['cedula_identidad'],
                ':fecha_nac'   => $datos['fecha_nacimiento'],
                ':carrera'     => $datos['carrera'],
                ':curso'       => $datos['curso'],
                ':paralelo'    => ($datos['paralelo'] ?? '') ?: null,
                ':telefono'    => $datos['telefono'] ?: null,
                ':correo'      => $datos['correo_electronico'] ?: null,
                ':direccion'   => $datos['direccion'] ?: null,
                ':creado_por'  => $creadoPor,
            ]);
            $id = (int) $db->lastInsertId();

            // Asignar el código definitivo basado en el ID autogenerado.
            $codigo = generarCodigo('EST', $id);
            $upd = $db->prepare('UPDATE estudiantes SET codigo_estudiante = :codigo WHERE id_estudiante = :id');
            $upd->execute([':codigo' => $codigo, ':id' => $id]);

            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al crear estudiante: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar los datos de un estudiante.
     *
     * @param int   $id    ID del estudiante
     * @param array $datos Datos a actualizar
     * @return bool true si la actualización fue exitosa
     */
    public static function actualizar(int $id, array $datos): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'UPDATE estudiantes SET
                        nombre_completo = :nombre,
                        cedula_identidad = :cedula,
                        fecha_nacimiento = :fecha_nac,
                        carrera = :carrera,
                        curso = :curso,
                        paralelo = :paralelo,
                        telefono = :telefono,
                        correo_electronico = :correo,
                        direccion = :direccion
                    WHERE id_estudiante = :id';
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':nombre'    => $datos['nombre_completo'],
                ':cedula'    => $datos['cedula_identidad'],
                ':fecha_nac' => $datos['fecha_nacimiento'],
                ':carrera'   => $datos['carrera'],
                ':curso'     => $datos['curso'],
                ':paralelo'  => ($datos['paralelo'] ?? '') ?: null,
                ':telefono'  => $datos['telefono'] ?: null,
                ':correo'    => $datos['correo_electronico'] ?: null,
                ':direccion' => $datos['direccion'] ?: null,
                ':id'        => $id,
            ]);
        } catch (Throwable $e) {
            error_log('Error al actualizar estudiante: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar el estado (activo/inactivo) de un estudiante.
     *
     * @param int $id     ID del estudiante
     * @param int $estado 1=activo, 0=inactivo
     * @return bool
     */
    public static function cambiarEstado(int $id, int $estado): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('UPDATE estudiantes SET estado = :estado WHERE id_estudiante = :id');
            return $stmt->execute([':estado' => $estado ? 1 : 0, ':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al cambiar estado de estudiante: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el estudiante tiene prácticas laborales registradas.
     *
     * @param int $id ID del estudiante
     * @return bool
     */
    public static function tienePracticas(int $id): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('SELECT COUNT(*) FROM practicas_laborales WHERE id_estudiante = :id');
            $stmt->execute([':id' => $id]);
            return ((int) $stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            error_log('Error al verificar prácticas del estudiante: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Eliminar físicamente un estudiante si no tiene prácticas laborales asociadas.
     *
     * @param int $id ID del estudiante
     * @return array{0:bool, 1:string} [éxito, mensaje]
     */
    public static function eliminar(int $id): array
    {
        try {
            if (self::tienePracticas($id)) {
                return [false, 'No se puede eliminar el estudiante porque tiene prácticas laborales registradas. Si desea desactivarlo, use la opción "Inactivar".'];
            }

            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('DELETE FROM estudiantes WHERE id_estudiante = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() > 0) {
                return [true, 'Estudiante eliminado definitivamente del sistema.'];
            }
            return [false, 'No se encontró el registro del estudiante para eliminar.'];
        } catch (Throwable $e) {
            error_log('Error al eliminar físicamente estudiante: ' . $e->getMessage());
            return [false, 'Error en el servidor al intentar eliminar el estudiante: ' . $e->getMessage()];
        }
    }

    /**
     * Obtener un estudiante por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function obtenerPorId(int $id): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('SELECT * FROM estudiantes WHERE id_estudiante = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener estudiante: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Construir la cláusula WHERE y parámetros a partir de filtros.
     *
     * @param array $filtros Claves: buscar, carrera, estado
     * @return array{0:string,1:array}
     */
    private static function construirFiltros(array $filtros): array
    {
        $cond = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $cond[] = '(nombre_completo LIKE :b1 OR cedula_identidad LIKE :b2 OR codigo_estudiante LIKE :b3)';
            $params[':b1'] = '%' . $filtros['buscar'] . '%';
            $params[':b2'] = '%' . $filtros['buscar'] . '%';
            $params[':b3'] = '%' . $filtros['buscar'] . '%';
        }
        $filtroCarrera = !empty($filtros['restringir_carrera']) ? $filtros['restringir_carrera'] : (!empty($filtros['carrera']) ? $filtros['carrera'] : null);
        if ($filtroCarrera !== null) {
            $carr = normalizarCarrera($filtroCarrera);
            $primer = explode(' ', trim($carr))[0] ?? $carr;
            $cond[] = '(carrera = :carrera OR carrera = :carrera_norm OR carrera LIKE :carrera_like)';
            $params[':carrera'] = $filtroCarrera;
            $params[':carrera_norm'] = $carr;
            $params[':carrera_like'] = '%' . $primer . '%';
        }
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $cond[] = 'estado = :estado';
            $params[':estado'] = (int) $filtros['estado'];
        }

        return [$cond ? 'WHERE ' . implode(' AND ', $cond) : '', $params];
    }

    /**
     * Listar estudiantes paginados con filtros.
     *
     * @param int   $pagina
     * @param array $filtros
     * @param int   $porPagina
     * @return array
     */
    public static function listar(int $pagina = 1, array $filtros = [], int $porPagina = 15): array
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $offset = max(0, ($pagina - 1) * $porPagina);

            $sql = "SELECT id_estudiante, codigo_estudiante, nombre_completo,
                           cedula_identidad, carrera, curso, paralelo, telefono, estado
                    FROM estudiantes
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
            error_log('Error al listar estudiantes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar el total de estudiantes que cumplen los filtros.
     *
     * @param array $filtros
     * @return int
     */
    public static function contarTotal(array $filtros = []): int
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $stmt = $db->prepare("SELECT COUNT(*) FROM estudiantes {$where}");
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Error al contar estudiantes: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Buscar estudiantes por término libre (nombre, cédula o carrera).
     *
     * @param string $termino
     * @param int    $limite
     * @return array
     */
    public static function buscar(string $termino, int $limite = 50): array
    {
        return self::listar(1, ['buscar' => $termino], $limite);
    }

    /**
     * Verificar si una cédula está disponible (no registrada), excluyendo un ID.
     *
     * @param string   $cedula
     * @param int|null $idExcluir
     * @return bool true si la cédula está disponible
     */
    public static function verificarCedulaUnica(string $cedula, ?int $idExcluir = null): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT COUNT(*) FROM estudiantes WHERE cedula_identidad = :cedula';
            $params = [':cedula' => $cedula];
            if ($idExcluir !== null) {
                $sql .= ' AND id_estudiante <> :id';
                $params[':id'] = $idExcluir;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return ((int) $stmt->fetchColumn()) === 0;
        } catch (Throwable $e) {
            error_log('Error al verificar cédula: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener la lista de carreras distintas registradas (para filtros).
     *
     * @return array
     */
    public static function obtenerCarreras(): array
    {
        require_once __DIR__ . '/Carrera.php';
        return Carrera::obtenerActivas();
    }

    /**
     * Importar una lista de estudiantes en lote (desde Excel).
     *
     * Cada fila debe tener: nombre_completo, cedula_identidad, fecha_nacimiento,
     * carrera, curso, paralelo (opcional), telefono (opcional),
     * correo_electronico (opcional), direccion (opcional).
     * Valida carrera registrada y cédula única; omite filas inválidas o duplicadas.
     *
     * @param array  $filas        Lista de arreglos asociativos (filas)
     * @param int    $creadoPor    ID del usuario que importa
     * @param string|null $restringirCarrera Si se indica, solo se permiten filas de esa carrera
     * @return array{insertados:int,omitidos:int,errores:array}
     */
    public static function importarLote(array $filas, int $creadoPor, ?string $restringirCarrera = null): array
    {
        require_once __DIR__ . '/Carrera.php';
        $carrerasValidas = array_map('mb_strtolower', Carrera::obtenerActivas());

        $insertados = 0;
        $omitidos   = 0;
        $errores    = [];
        $numFila    = 1; // la fila 1 es el encabezado

        foreach ($filas as $fila) {
            $numFila++;
            $nombre  = trim((string) ($fila['nombre_completo'] ?? ''));
            $cedula  = trim((string) ($fila['cedula_identidad'] ?? ''));
            $fnac    = trim((string) ($fila['fecha_nacimiento'] ?? ''));
            $carrera = trim((string) ($fila['carrera'] ?? ''));
            $curso   = trim((string) ($fila['curso'] ?? ''));
            $paralelo = trim((string) ($fila['paralelo'] ?? ''));
            $tel     = trim((string) ($fila['telefono'] ?? ''));
            $correo  = trim((string) ($fila['correo_electronico'] ?? ''));
            $direccion = trim((string) ($fila['direccion'] ?? ''));

            // Saltar filas completamente vacías.
            if ($nombre === '' && $cedula === '' && $carrera === '') {
                continue;
            }

            // Validaciones mínimas.
            if ($nombre === '' || $cedula === '' || $carrera === '' || $curso === '') {
                $errores[] = "Fila {$numFila}: faltan datos obligatorios (nombre, cédula, carrera o curso).";
                $omitidos++;
                continue;
            }
            if (!preg_match('/^\d{5,10}$/', $cedula)) {
                $errores[] = "Fila {$numFila}: cédula inválida ({$cedula}).";
                $omitidos++;
                continue;
            }
            if (!in_array(mb_strtolower($carrera), $carrerasValidas, true)) {
                $errores[] = "Fila {$numFila}: la carrera \"{$carrera}\" no está registrada.";
                $omitidos++;
                continue;
            }
            if ($restringirCarrera !== null && mb_strtolower($carrera) !== mb_strtolower($restringirCarrera)) {
                $errores[] = "Fila {$numFila}: solo puede importar estudiantes de la carrera {$restringirCarrera}.";
                $omitidos++;
                continue;
            }
            if (!self::verificarCedulaUnica($cedula)) {
                $errores[] = "Fila {$numFila}: la cédula {$cedula} ya está registrada.";
                $omitidos++;
                continue;
            }
            // Normalizar fecha (acepta d/m/Y o Y-m-d); si no es válida, usar hoy como marcador.
            $fechaNormal = self::normalizarFecha($fnac);
            if ($fechaNormal === null) {
                $errores[] = "Fila {$numFila}: fecha de nacimiento inválida ({$fnac}).";
                $omitidos++;
                continue;
            }

            $id = self::crear([
                'nombre_completo'    => $nombre,
                'cedula_identidad'   => $cedula,
                'fecha_nacimiento'   => $fechaNormal,
                'carrera'            => $carrera,
                'curso'              => $curso,
                'paralelo'           => $paralelo,
                'telefono'           => $tel,
                'correo_electronico' => $correo,
                'direccion'          => $direccion,
            ], $creadoPor);

            if ($id === null) {
                $errores[] = "Fila {$numFila}: no se pudo guardar.";
                $omitidos++;
            } else {
                $insertados++;
            }
        }

        return ['insertados' => $insertados, 'omitidos' => $omitidos, 'errores' => $errores];
    }

    /**
     * Normalizar una fecha de varios formatos comunes a Y-m-d.
     *
     * @param string $valor
     * @return string|null Fecha Y-m-d o null si es inválida
     */
    private static function normalizarFecha(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $formato) {
            $d = DateTime::createFromFormat($formato, $valor);
            if ($d !== false && $d->format($formato) === $valor) {
                return $d->format('Y-m-d');
            }
        }
        // Último intento con strtotime.
        $ts = strtotime($valor);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }
}
