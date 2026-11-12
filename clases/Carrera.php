<?php
/**
 * Clase Carrera - Catálogo de carreras del instituto
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Gestiona el catálogo administrable de carreras usado en el registro de
 * estudiantes. Utiliza eliminación lógica (cambio de estado).
 */

require_once __DIR__ . '/../config/database.php';

class Carrera
{
    /**
     * Crear una nueva carrera.
     *
     * @param string $nombre Nombre de la carrera
     * @return int|null ID creado o null en caso de error
     */
    public static function crear(string $nombre): ?int
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('INSERT INTO carreras (nombre, estado) VALUES (:nombre, 1)');
            $stmt->execute([':nombre' => $nombre]);
            return (int) $db->lastInsertId();
        } catch (Throwable $e) {
            error_log('Error al crear carrera: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cambiar el estado (activa/inactiva) de una carrera.
     */
    public static function cambiarEstado(int $id, int $estado): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('UPDATE carreras SET estado = :estado WHERE id_carrera = :id');
            return $stmt->execute([':estado' => $estado ? 1 : 0, ':id' => $id]);
        } catch (Throwable $e) {
            error_log('Error al cambiar estado de carrera: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener los nombres de las carreras activas (para selectores).
     *
     * @return array Lista de nombres de carrera
     */
    public static function obtenerActivas(): array
    {
        try {
            $db = Database::obtenerInstancia();
            return $db->query('SELECT nombre FROM carreras WHERE estado = 1 ORDER BY nombre')
                      ->fetchAll(PDO::FETCH_COLUMN);
        } catch (Throwable $e) {
            error_log('Error al obtener carreras activas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Listar todas las carreras (activas e inactivas) para administración.
     *
     * @return array Filas con id_carrera, nombre, estado
     */
    public static function listar(): array
    {
        try {
            $db = Database::obtenerInstancia();
            return $db->query('SELECT id_carrera, nombre, estado FROM carreras ORDER BY nombre')
                      ->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al listar carreras: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un nombre de carrera está disponible (no registrado).
     *
     * @param string $nombre
     * @return bool true si está disponible
     */
    public static function nombreDisponible(string $nombre): bool
    {
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('SELECT COUNT(*) FROM carreras WHERE nombre = :nombre');
            $stmt->execute([':nombre' => $nombre]);
            return ((int) $stmt->fetchColumn()) === 0;
        } catch (Throwable $e) {
            error_log('Error al verificar carrera: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Comprobar si una carrera existe por nombre (activa o inactiva).
     */
    public static function existe(string $nombre): bool
    {
        return !self::nombreDisponible($nombre);
    }
}
