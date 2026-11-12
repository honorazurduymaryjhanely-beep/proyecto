<?php
/**
 * Clase HorasCumplidas - Registro de horas trabajadas en una práctica
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Encapsula el acceso a la tabla `horas_cumplidas` y el cálculo de avance.
 */

require_once __DIR__ . '/../config/database.php';

class HorasCumplidas
{
    /**
     * Registrar horas cumplidas en una fecha.
     *
     * @param int    $idPractica
     * @param string $fecha        Fecha (Y-m-d)
     * @param float  $cantidad     Horas (> 0 y <= 12)
     * @param string $descripcion  Descripción de las actividades
     * @param int    $registradoPor
     * @return int|null ID del registro o null en caso de error
     */
    public static function registrar(int $idPractica, string $fecha, float $cantidad, string $descripcion, int $registradoPor): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'INSERT INTO horas_cumplidas
                        (id_practica, fecha, cantidad_horas, descripcion_actividades, registrado_por)
                    VALUES (:practica, :fecha, :cantidad, :descripcion, :por)';
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':practica'    => $idPractica,
                ':fecha'       => $fecha,
                ':cantidad'    => $cantidad,
                ':descripcion' => $descripcion,
                ':por'         => $registradoPor,
            ]);
            return (int) $db->lastInsertId();
        } catch (Throwable $e) {
            error_log('Error al registrar horas: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Listar los registros de horas de una práctica.
     *
     * @param int $idPractica
     * @return array
     */
    public static function listarPorPractica(int $idPractica): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT h.id_registro_horas, h.fecha, h.cantidad_horas,
                           h.descripcion_actividades, u.nombre_completo AS registrado_por
                    FROM horas_cumplidas h
                    INNER JOIN usuarios u ON u.id_usuario = h.registrado_por
                    WHERE h.id_practica = :id
                    ORDER BY h.fecha DESC, h.id_registro_horas DESC';
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $idPractica]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al listar horas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcular el total de horas acumuladas en una práctica.
     *
     * @param int $idPractica
     * @return float
     */
    public static function calcularTotalHoras(int $idPractica): float
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('SELECT COALESCE(SUM(cantidad_horas), 0) FROM horas_cumplidas WHERE id_practica = :id');
            $stmt->execute([':id' => $idPractica]);
            return (float) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('Error al calcular total de horas: ' . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Calcular el porcentaje de avance respecto al total de horas requeridas.
     *
     * @param int $idPractica
     * @return float Porcentaje (0-100, redondeado a 1 decimal)
     */
    public static function calcularPorcentajeAvance(int $idPractica): float
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('SELECT total_horas_requeridas FROM practicas_laborales WHERE id_practica = :id');
            $stmt->execute([':id' => $idPractica]);
            $requeridas = (float) $stmt->fetchColumn();
            if ($requeridas <= 0) {
                return 0.0;
            }
            $acumuladas = self::calcularTotalHoras($idPractica);
            return round(min(100, ($acumuladas / $requeridas) * 100), 1);
        } catch (Throwable $e) {
            error_log('Error al calcular porcentaje de avance: ' . $e->getMessage());
            return 0.0;
        }
    }
}
