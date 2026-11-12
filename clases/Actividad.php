<?php
/**
 * Clase Actividad - Actividades realizadas durante una práctica
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Encapsula el acceso a la tabla `actividades_practica`.
 */

require_once __DIR__ . '/../config/database.php';

class Actividad
{
    /**
     * Registrar una actividad para una práctica.
     *
     * @param int    $idPractica
     * @param string $nombre
     * @param string $descripcion
     * @param int    $registradoPor
     * @return int|null ID de la actividad creada o null en caso de error
     */
    public static function crear(int $idPractica, string $nombre, string $descripcion, int $registradoPor): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'INSERT INTO actividades_practica
                        (id_practica, nombre_actividad, descripcion, fecha_registro, registrado_por)
                    VALUES (:practica, :nombre, :descripcion, CURDATE(), :por)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':practica'    => $idPractica,
                ':nombre'      => $nombre,
                ':descripcion' => $descripcion ?: null,
                ':por'         => $registradoPor,
            ]);
            return (int) $db->lastInsertId();
        } catch (Throwable $e) {
            error_log('Error al crear actividad: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Listar las actividades de una práctica ordenadas por fecha.
     *
     * @param int $idPractica
     * @return array
     */
    public static function listarPorPractica(int $idPractica): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT a.id_actividad, a.nombre_actividad, a.descripcion,
                           a.fecha_registro, u.nombre_completo AS registrado_por
                    FROM actividades_practica a
                    INNER JOIN usuarios u ON u.id_usuario = a.registrado_por
                    WHERE a.id_practica = :id
                    ORDER BY a.fecha_registro DESC, a.id_actividad DESC';
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $idPractica]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al listar actividades: ' . $e->getMessage());
            return [];
        }
    }
}
