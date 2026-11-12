<?php
/**
 * Clase Usuario - Gestión de cuentas de usuario
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Encapsula el acceso a la tabla `usuarios`: verificación de credenciales
 * para el inicio de sesión y consultas por identificador o nombre de usuario.
 * Los métodos de CRUD administrativo se amplían en la Tarea 5.
 */

require_once __DIR__ . '/../config/database.php';

class Usuario
{
    /**
     * Verificar las credenciales de acceso de un usuario.
     *
     * Comprueba que el usuario exista, esté activo y que la contraseña
     * coincida con el hash almacenado (password_hash / password_verify).
     * Solo valida usuarios cuyo rol también esté activo.
     *
     * @param string $nombreUsuario Nombre de usuario ingresado
     * @param string $contrasena    Contraseña en texto plano ingresada
     * @return array|null Datos del usuario (sin el hash) si es válido, null si no
     */
    public static function verificarCredenciales(string $nombreUsuario, string $contrasena): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT u.*, r.nombre_rol, r.permisos
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    WHERE u.nombre_usuario = :nombre_usuario
                      AND u.estado = 1
                      AND r.estado = 1
                    LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute([':nombre_usuario' => $nombreUsuario]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                return null;
            }

            if (!password_verify($contrasena, $usuario['contrasena_hash'])) {
                return null;
            }

            // Asegurar campos carrera y telefono
            $usuario['carrera'] = $usuario['carrera'] ?? $usuario['especialidad'] ?? '';
            $usuario['telefono'] = $usuario['telefono'] ?? '';

            // No devolver nunca el hash de contraseña al llamador.
            unset($usuario['contrasena_hash']);
            return $usuario;
        } catch (Throwable $e) {
            error_log('Error al verificar credenciales: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener un usuario por su identificador.
     *
     * @param int $id Identificador del usuario
     * @return array|null Datos del usuario (sin hash) o null si no existe
     */
    public static function obtenerPorId(int $id): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT u.*, r.nombre_rol, r.permisos
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    WHERE u.id_usuario = :id
                    LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $usuario = $stmt->fetch();
            if ($usuario) {
                $usuario['carrera'] = $usuario['carrera'] ?? $usuario['especialidad'] ?? '';
                $usuario['telefono'] = $usuario['telefono'] ?? '';
            }
            return $usuario ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener usuario por ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener un usuario por su nombre de usuario.
     *
     * @param string $nombreUsuario Nombre de usuario
     * @return array|null Datos del usuario (sin hash) o null si no existe
     */
    public static function obtenerPorNombreUsuario(string $nombreUsuario): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT u.id_usuario, u.nombre_completo, u.nombre_usuario,
                           u.correo_electronico, u.id_rol, u.estado,
                           r.nombre_rol, r.permisos
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    WHERE u.nombre_usuario = :nombre_usuario
                    LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute([':nombre_usuario' => $nombreUsuario]);
            $usuario = $stmt->fetch();
            return $usuario ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener usuario por nombre: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar la marca de tiempo del último acceso del usuario.
     *
     * @param int $id Identificador del usuario
     * @return void
     */
    public static function registrarUltimoAcceso(int $id): void
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id');
            $stmt->execute([':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al registrar último acceso: ' . $e->getMessage());
        }
    }

    /**
     * Crear un nuevo usuario.
     *
     * @param array $datos Claves: nombre_completo, nombre_usuario, contrasena,
     *                     correo_electronico, id_rol
     * @return int|null ID del usuario creado o null en caso de error
     */
    public static function crear(array $datos): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'INSERT INTO usuarios
                        (nombre_completo, nombre_usuario, contrasena_hash,
                         correo_electronico, carrera, telefono,
                         id_rol, estado)
                    VALUES
                        (:nombre_completo, :nombre_usuario, :contrasena_hash,
                         :correo, :carrera, :telefono, :id_rol, 1)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nombre_completo' => $datos['nombre_completo'],
                ':nombre_usuario'  => $datos['nombre_usuario'],
                ':contrasena_hash' => password_hash($datos['contrasena'], PASSWORD_DEFAULT),
                ':correo'          => $datos['correo_electronico'],
                ':carrera'         => ($datos['carrera'] ?? '') ?: null,
                ':telefono'        => ($datos['telefono'] ?? '') ?: null,
                ':id_rol'          => (int) $datos['id_rol'],
            ]);
            $nuevoId = (int) $db->lastInsertId();
            if ($nuevoId > 0) {
                self::sincronizarDocenteSupervisor($nuevoId, $datos);
            }
            return $nuevoId;
        } catch (Throwable $e) {
            error_log('Error al crear usuario: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar los datos de un usuario. La contraseña solo se cambia si se
     * proporciona un valor no vacío en la clave 'contrasena'.
     *
     * @param int   $id    ID del usuario a actualizar
     * @param array $datos Claves: nombre_completo, correo_electronico, id_rol,
     *                     [contrasena]
     * @return bool true si la actualización fue exitosa
     */
    public static function actualizar(int $id, array $datos): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $campos = [
                'nombre_completo = :nombre_completo',
                'correo_electronico = :correo',
                'carrera = :carrera',
                'telefono = :telefono',
                'id_rol = :id_rol',
            ];
            $params = [
                ':nombre_completo' => $datos['nombre_completo'],
                ':correo'          => $datos['correo_electronico'],
                ':carrera'         => ($datos['carrera'] ?? '') ?: null,
                ':telefono'        => ($datos['telefono'] ?? '') ?: null,
                ':id_rol'          => (int) $datos['id_rol'],
                ':id'              => $id,
            ];

            if (!empty($datos['contrasena'])) {
                $campos[] = 'contrasena_hash = :contrasena_hash';
                $params[':contrasena_hash'] = password_hash($datos['contrasena'], PASSWORD_DEFAULT);
            }

            $sql = 'UPDATE usuarios SET ' . implode(', ', $campos) . ' WHERE id_usuario = :id';
            $stmt = $db->prepare($sql);
            $res = $stmt->execute($params);
            if ($res) {
                self::sincronizarDocenteSupervisor($id, $datos);
            }
            return $res;
        } catch (Throwable $e) {
            error_log('Error al actualizar usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Deshabilitar (o habilitar) un usuario mediante cambio de estado lógico.
     *
     * @param int $id     ID del usuario
     * @param int $estado 0 = deshabilitado, 1 = habilitado
     * @return bool true si la operación fue exitosa
     */
    public static function cambiarEstado(int $id, int $estado): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('UPDATE usuarios SET estado = :estado WHERE id_usuario = :id');
            $res = $stmt->execute([':estado' => $estado ? 1 : 0, ':id' => $id]);
            if ($res) {
                $db->prepare('UPDATE docentes_supervisores SET estado = :estado WHERE id_usuario = :id')
                   ->execute([':estado' => $estado ? 1 : 0, ':id' => $id]);
            }
            return $res;
        } catch (Throwable $e) {
            error_log('Error al cambiar estado de usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar físicamente un usuario si no es el usuario actual ni tiene registros vinculados.
     *
     * @param int $id ID del usuario a eliminar
     * @param int $idActual ID del usuario en sesión
     * @return array{0:bool, 1:string} [éxito, mensaje]
     */
    public static function eliminar(int $id, int $idActual): array
    {
        if ($id === $idActual) {
            return [false, 'No puede eliminar su propia cuenta de usuario.'];
        }

        try {
            $db = Database::obtenerInstancia();

            // Si está en docentes_supervisores, verificar si tiene asignaciones
            $stmtDoc = $db->prepare('SELECT id_docente FROM docentes_supervisores WHERE id_usuario = :id');
            $stmtDoc->execute([':id' => $id]);
            $idDocente = $stmtDoc->fetchColumn();
            if ($idDocente) {
                $checkAsig = $db->prepare('SELECT COUNT(*) FROM asignaciones_supervision WHERE id_docente = :id');
                $checkAsig->execute([':id' => $idDocente]);
                if ((int) $checkAsig->fetchColumn() > 0) {
                    return [false, 'No se puede eliminar el usuario porque tiene asignaciones de prácticas como Docente Supervisor. Utilice la opción Deshabilitar.'];
                }
                // Si no tiene asignaciones, eliminar primero el registro vinculado de docente
                $db->prepare('DELETE FROM docentes_supervisores WHERE id_docente = :id')->execute([':id' => $idDocente]);
            }

            $stmt = $db->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
            $stmt->execute([':id' => $id]);

            if ($stmt->rowCount() > 0) {
                return [true, 'Usuario eliminado definitivamente del sistema.'];
            }
            return [false, 'No se encontró el usuario para eliminar.'];
        } catch (Throwable $e) {
            error_log('Error al eliminar usuario: ' . $e->getMessage());
            return [false, 'No se puede eliminar el usuario porque tiene registros vinculados (auditorías, prácticas o creaciones previas). En este caso, use la opción Deshabilitar.'];
        }
    }

    /**
     * Sincronizar automáticamente un usuario docente con la tabla docentes_supervisores.
     *
     * @param int   $idUsuario
     * @param array $datos
     * @return void
     */
    public static function sincronizarDocenteSupervisor(int $idUsuario, array $datos): void
    {
        try {
            $db = Database::obtenerInstancia();
            $rol = (int) ($datos['id_rol'] ?? 0);
            if (!in_array($rol, [2, 4], true)) {
                return;
            }

            $carrera = normalizarCarrera($datos['carrera'] ?? '');
            $ci = trim($datos['nombre_usuario'] ?? ($datos['telefono'] ?? '0'));

            $stmt = $db->prepare('SELECT id_docente FROM docentes_supervisores WHERE id_usuario = :id LIMIT 1');
            $stmt->execute([':id' => $idUsuario]);
            $existente = $stmt->fetchColumn();

            if ($existente) {
                $upd = $db->prepare('UPDATE docentes_supervisores SET
                                        nombre_completo = :nombre,
                                        carrera = :carrera,
                                        telefono = :tel,
                                        correo_electronico = :correo
                                     WHERE id_docente = :id');
                $upd->execute([
                    ':nombre'  => trim($datos['nombre_completo']),
                    ':carrera' => $carrera,
                    ':tel'     => trim($datos['telefono'] ?? '') ?: null,
                    ':correo'  => trim($datos['correo_electronico'] ?? '') ?: null,
                    ':id'      => (int) $existente,
                ]);
            } else {
                $ins = $db->prepare('INSERT INTO docentes_supervisores
                                        (codigo_docente, id_usuario, nombre_completo, cedula_identidad, carrera, telefono, correo_electronico, estado, creado_por)
                                     VALUES
                                        (:codigo, :id_usuario, :nombre, :ci, :carrera, :tel, :correo, 1, 1)');
                $ins->execute([
                    ':codigo'     => 'TMP',
                    ':id_usuario' => $idUsuario,
                    ':nombre'     => trim($datos['nombre_completo']),
                    ':ci'         => $ci,
                    ':carrera'    => $carrera,
                    ':tel'        => trim($datos['telefono'] ?? '') ?: null,
                    ':correo'     => trim($datos['correo_electronico'] ?? '') ?: null,
                ]);
                $nuevoDocId = (int) $db->lastInsertId();
                $codigo = 'DOC-' . str_pad($nuevoDocId, 6, '0', STR_PAD_LEFT);
                $db->prepare('UPDATE docentes_supervisores SET codigo_docente = :cod WHERE id_docente = :id')
                   ->execute([':cod' => $codigo, ':id' => $nuevoDocId]);
            }
        } catch (Throwable $e) {
            error_log('Error al sincronizar docente supervisor con usuario: ' . $e->getMessage());
        }
    }

    /**
     * Construir la cláusula WHERE y los parámetros a partir de filtros.
     *
     * @param array $filtros Claves opcionales: buscar, id_rol, estado
     * @return array{0:string,1:array} [whereSql, params]
     */
    private static function construirFiltros(array $filtros): array
    {
        $condiciones = [];
        $params = [];

        if (!empty($filtros['buscar'])) {
            $condiciones[] = '(u.nombre_completo LIKE :buscar OR u.nombre_usuario LIKE :buscar2)';
            $params[':buscar']  = '%' . $filtros['buscar'] . '%';
            $params[':buscar2'] = '%' . $filtros['buscar'] . '%';
        }
        if (isset($filtros['id_rol']) && $filtros['id_rol'] !== '') {
            $condiciones[] = 'u.id_rol = :id_rol';
            $params[':id_rol'] = (int) $filtros['id_rol'];
        }
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $condiciones[] = 'u.estado = :estado';
            $params[':estado'] = (int) $filtros['estado'];
        }

        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        return [$where, $params];
    }

    /**
     * Listar usuarios paginados con su rol.
     *
     * @param int   $pagina    Página actual (1-indexada)
     * @param array $filtros   Filtros: buscar, id_rol, estado
     * @param int   $porPagina Registros por página
     * @return array Lista de usuarios
     */
    public static function listar(int $pagina = 1, array $filtros = [], int $porPagina = 15): array
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $offset = max(0, ($pagina - 1) * $porPagina);

            $sql = "SELECT u.id_usuario, u.nombre_completo, u.nombre_usuario,
                           u.correo_electronico, u.estado, u.ultimo_acceso,
                           r.nombre_rol
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    {$where}
                    ORDER BY u.nombre_completo ASC
                    LIMIT :limite OFFSET :offset";
            $stmt = $db->prepare($sql);
            foreach ($params as $clave => $valor) {
                $stmt->bindValue($clave, $valor);
            }
            $stmt->bindValue(':limite', $porPagina, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al listar usuarios: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar el total de usuarios que cumplen los filtros.
     *
     * @param array $filtros Filtros: buscar, id_rol, estado
     * @return int Total de usuarios
     */
    public static function contarTotal(array $filtros = []): int
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $sql = "SELECT COUNT(*) FROM usuarios u {$where}";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Error al contar usuarios: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar si un nombre de usuario ya existe (excluyendo un ID opcional).
     *
     * @param string   $nombreUsuario Nombre de usuario a verificar
     * @param int|null $idExcluir     ID a excluir (para edición)
     * @return bool true si el nombre de usuario está disponible
     */
    public static function nombreUsuarioDisponible(string $nombreUsuario, ?int $idExcluir = null): bool
    {
        return self::valorDisponible('nombre_usuario', $nombreUsuario, $idExcluir);
    }

    /**
     * Verificar si un correo ya existe (excluyendo un ID opcional).
     *
     * @param string   $correo    Correo a verificar
     * @param int|null $idExcluir ID a excluir (para edición)
     * @return bool true si el correo está disponible
     */
    public static function correoDisponible(string $correo, ?int $idExcluir = null): bool
    {
        return self::valorDisponible('correo_electronico', $correo, $idExcluir);
    }

    /**
     * Comprobar la disponibilidad (unicidad) de un valor en una columna.
     *
     * @param string   $columna   Columna a comprobar (lista blanca interna)
     * @param string   $valor     Valor a buscar
     * @param int|null $idExcluir ID a excluir
     * @return bool true si el valor está disponible (no existe)
     */
    private static function valorDisponible(string $columna, string $valor, ?int $idExcluir): bool
    {
        $columnasPermitidas = ['nombre_usuario', 'correo_electronico'];
        if (!in_array($columna, $columnasPermitidas, true)) {
            return false;
        }
        try {
            $db = Database::obtenerInstancia();
            $sql = "SELECT COUNT(*) FROM usuarios WHERE {$columna} = :valor";
            $params = [':valor' => $valor];
            if ($idExcluir !== null) {
                $sql .= ' AND id_usuario <> :id';
                $params[':id'] = $idExcluir;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return ((int) $stmt->fetchColumn()) === 0;
        } catch (Throwable $e) {
            error_log('Error al verificar disponibilidad: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener la lista de roles activos para poblar selectores.
     *
     * @return array Lista de roles (id_rol, nombre_rol)
     */
    public static function obtenerRoles(): array
    {
        try {
            $db = Database::obtenerInstancia();
            return $db->query('SELECT id_rol, nombre_rol FROM roles WHERE estado = 1 ORDER BY nombre_rol')
                      ->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener roles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener los usuarios activos con el rol de docente encargado.
     * (El supervisor de un estudiante en una práctica se toma del catálogo
     * de docentes, clase DocenteSupervisor, no de aquí.)
     *
     * @return array Filas con id_usuario, nombre_completo, carrera
     */
    public static function obtenerDocentesEncargados(): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = "SELECT u.id_usuario, u.nombre_completo, u.carrera, u.telefono
                    FROM usuarios u
                    INNER JOIN roles r ON r.id_rol = u.id_rol
                    WHERE r.nombre_rol = 'docente_encargado' AND u.estado = 1
                    ORDER BY u.nombre_completo";
            return $db->query($sql)->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener docentes encargados: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Delegación de compatibilidad con el catálogo de docentes supervisores.
     */
    public static function obtenerSupervisoresActivos(?string $carrera = null): array
    {
        require_once __DIR__ . '/DocenteSupervisor.php';
        return DocenteSupervisor::obtenerActivos($carrera);
    }

    /**
     * Delegación de compatibilidad para verificar si un docente supervisor está activo.
     */
    public static function esSupervisorActivo(int $idDocente): bool
    {
        require_once __DIR__ . '/DocenteSupervisor.php';
        $d = DocenteSupervisor::obtenerPorId($idDocente);
        return $d !== null && (int) $d['estado'] === 1;
    }
}
