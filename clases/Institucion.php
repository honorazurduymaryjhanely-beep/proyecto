<?php
/**
 * Clase Institucion - Gestión de instituciones receptoras
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Encapsula el acceso a la tabla `instituciones_receptoras`: alta, edición,
 * consulta, listado paginado y verificación de NIT único. Utiliza
 * eliminación lógica mediante cambio de estado.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

class Institucion
{
    /**
     * Crear una institución receptora asignándole el código INS-XXXXXX.
     *
     * @param array $datos     Datos de la institución
     * @param int   $creadoPor ID del usuario que registra
     * @return int|null ID creado o null en caso de error
     */
    public static function crear(array $datos, int $creadoPor): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $db->beginTransaction();

            $sql = 'INSERT INTO instituciones_receptoras
                        (codigo_institucion, nombre, nit, direccion, telefono,
                         correo_electronico, nombre_mae, telefono_mae,
                         nombre_contacto, cargo_contacto, telefono_contacto,
                         estado, creado_por)
                    VALUES
                        (:codigo, :nombre, :nit, :direccion, :telefono,
                         :correo, :nombre_mae, :telefono_mae,
                         :contacto, :cargo, :tel_contacto, 1, :creado_por)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':codigo'       => 'TMP',
                ':nombre'       => $datos['nombre'],
                ':nit'          => $datos['nit'],
                ':direccion'    => $datos['direccion'],
                ':telefono'     => $datos['telefono'],
                ':correo'       => $datos['correo_electronico'] ?: null,
                ':nombre_mae'   => ($datos['nombre_mae'] ?? '') ?: null,
                ':telefono_mae' => ($datos['telefono_mae'] ?? '') ?: null,
                ':contacto'     => $datos['nombre_contacto'],
                ':cargo'        => $datos['cargo_contacto'],
                ':tel_contacto' => ($datos['telefono_contacto'] ?? '') ?: null,
                ':creado_por'   => $creadoPor,
            ]);
            $id = (int) $db->lastInsertId();

            $codigo = generarCodigo('INS', $id);
            $upd = $db->prepare('UPDATE instituciones_receptoras SET codigo_institucion = :codigo WHERE id_institucion = :id');
            $upd->execute([':codigo' => $codigo, ':id' => $id]);

            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al crear institución: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar los datos de una institución.
     *
     * @param int   $id
     * @param array $datos
     * @return bool
     */
    public static function actualizar(int $id, array $datos): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'UPDATE instituciones_receptoras SET
                        nombre = :nombre,
                        nit = :nit,
                        direccion = :direccion,
                        telefono = :telefono,
                        correo_electronico = :correo,
                        nombre_mae = :nombre_mae,
                        telefono_mae = :telefono_mae,
                        nombre_contacto = :contacto,
                        cargo_contacto = :cargo,
                        telefono_contacto = :tel_contacto
                    WHERE id_institucion = :id';
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                ':nombre'       => $datos['nombre'],
                ':nit'          => $datos['nit'],
                ':direccion'    => $datos['direccion'],
                ':telefono'     => $datos['telefono'],
                ':correo'       => $datos['correo_electronico'] ?: null,
                ':nombre_mae'   => ($datos['nombre_mae'] ?? '') ?: null,
                ':telefono_mae' => ($datos['telefono_mae'] ?? '') ?: null,
                ':contacto'     => $datos['nombre_contacto'],
                ':cargo'        => $datos['cargo_contacto'],
                ':tel_contacto' => ($datos['telefono_contacto'] ?? '') ?: null,
                ':id'           => $id,
            ]);
        } catch (Throwable $e) {
            error_log('Error al actualizar institución: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambiar el estado (activo/inactivo) de una institución.
     *
     * @param int $id
     * @param int $estado 1=activa, 0=inactiva
     * @return bool
     */
    public static function cambiarEstado(int $id, int $estado): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('UPDATE instituciones_receptoras SET estado = :estado WHERE id_institucion = :id');
            return $stmt->execute([':estado' => $estado ? 1 : 0, ':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al cambiar estado de institución: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener una institución por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public static function obtenerPorId(int $id): ?array
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('SELECT * FROM instituciones_receptoras WHERE id_institucion = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: null;
        } catch (Throwable $e) {
            error_log('Error al obtener institución: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Construir cláusula WHERE y parámetros a partir de filtros.
     *
     * @param array $filtros Claves: buscar, estado
     * @return array{0:string,1:array}
     */
    private static function construirFiltros(array $filtros): array
    {
        $cond = [];
        $params = [];
        if (!empty($filtros['buscar'])) {
            $cond[] = '(nombre LIKE :b1 OR nit LIKE :b2 OR codigo_institucion LIKE :b3)';
            $params[':b1'] = '%' . $filtros['buscar'] . '%';
            $params[':b2'] = '%' . $filtros['buscar'] . '%';
            $params[':b3'] = '%' . $filtros['buscar'] . '%';
        }
        if (isset($filtros['estado']) && $filtros['estado'] !== '') {
            $cond[] = 'estado = :estado';
            $params[':estado'] = (int) $filtros['estado'];
        }
        return [$cond ? 'WHERE ' . implode(' AND ', $cond) : '', $params];
    }

    /**
     * Listar instituciones paginadas con filtros.
     */
    public static function listar(int $pagina = 1, array $filtros = [], int $porPagina = 15): array
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $offset = max(0, ($pagina - 1) * $porPagina);
            $sql = "SELECT id_institucion, codigo_institucion, nombre, nit,
                           telefono, nombre_contacto, estado
                    FROM instituciones_receptoras
                    {$where}
                    ORDER BY nombre ASC
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
            error_log('Error al listar instituciones: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar el total de instituciones que cumplen los filtros.
     */
    public static function contarTotal(array $filtros = []): int
    {
        try {
            $db = Database::obtenerInstancia();
            [$where, $params] = self::construirFiltros($filtros);
            $stmt = $db->prepare("SELECT COUNT(*) FROM instituciones_receptoras {$where}");
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Error al contar instituciones: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Buscar instituciones por término libre (nombre o NIT).
     */
    public static function buscar(string $termino, int $limite = 50): array
    {
        return self::listar(1, ['buscar' => $termino], $limite);
    }

    /**
     * Obtener las instituciones activas (para selectores).
     *
     * @return array Lista de instituciones activas (id, nombre)
     */
    public static function obtenerActivas(): array
    {
        try {
            $db = Database::obtenerInstancia();
            return $db->query('SELECT id_institucion, nombre, codigo_institucion
                               FROM instituciones_receptoras
                               WHERE estado = 1
                               ORDER BY nombre')->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al obtener instituciones activas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un NIT está disponible (no registrado), excluyendo un ID.
     *
     * @param string   $nit
     * @param int|null $idExcluir
     * @return bool true si está disponible
     */
    public static function verificarNitUnico(string $nit, ?int $idExcluir = null): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT COUNT(*) FROM instituciones_receptoras WHERE nit = :nit';
            $params = [':nit' => $nit];
            if ($idExcluir !== null) {
                $sql .= ' AND id_institucion <> :id';
                $params[':id'] = $idExcluir;
            }
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return ((int) $stmt->fetchColumn()) === 0;
        } catch (Throwable $e) {
            error_log('Error al verificar NIT: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Determinar si una institución está activa.
     *
     * @param int $id
     * @return bool
     */
    public static function estaActiva(int $id): bool
    {
        $inst = self::obtenerPorId($id);
        return $inst !== null && (int) $inst['estado'] === 1;
    }
}
