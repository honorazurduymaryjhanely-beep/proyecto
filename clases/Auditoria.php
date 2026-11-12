<?php
/**
 * Clase Auditoria - Registro de auditoría del sistema
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Registra en la tabla `auditoria` las acciones relevantes de los usuarios
 * (crear, modificar, eliminar, login, logout, acceso_denegado) para trazabilidad.
 */

require_once __DIR__ . '/../config/database.php';

class Auditoria
{
    /**
     * Acciones permitidas según el ENUM de la tabla auditoria.
     */
    private const ACCIONES_VALIDAS = [
        'crear',
        'modificar',
        'eliminar',
        'login',
        'logout',
        'acceso_denegado',
    ];

    /**
     * Registrar una acción en el log de auditoría.
     *
     * @param int        $idUsuario       ID del usuario que ejecuta la acción
     * @param string     $accion          Acción realizada (ver ACCIONES_VALIDAS)
     * @param string     $tabla           Tabla o módulo afectado
     * @param int|null   $idRegistro      ID del registro afectado (si aplica)
     * @param array|null $datosAnteriores Datos antes del cambio (se serializa a JSON)
     * @param array|null $datosNuevos     Datos después del cambio (se serializa a JSON)
     * @return bool true si el registro se guardó correctamente
     */
    public static function registrar(
        int $idUsuario,
        string $accion,
        string $tabla,
        ?int $idRegistro = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): bool {
        // Validar la acción contra el conjunto permitido.
        if (!in_array($accion, self::ACCIONES_VALIDAS, true)) {
            error_log("Auditoria: acción no válida '{$accion}'");
            return false;
        }

        try {
            $db = Database::obtenerInstancia();

            $sql = 'INSERT INTO auditoria
                        (id_usuario, accion, tabla_afectada, id_registro,
                         datos_anteriores, datos_nuevos, ip_direccion)
                    VALUES
                        (:id_usuario, :accion, :tabla, :id_registro,
                         :datos_anteriores, :datos_nuevos, :ip)';

            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':id_usuario'       => $idUsuario,
                ':accion'           => $accion,
                ':tabla'            => $tabla,
                ':id_registro'      => $idRegistro,
                ':datos_anteriores' => $datosAnteriores !== null
                    ? json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE)
                    : null,
                ':datos_nuevos'     => $datosNuevos !== null
                    ? json_encode($datosNuevos, JSON_UNESCAPED_UNICODE)
                    : null,
                ':ip'               => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            return true;
        } catch (Throwable $e) {
            // La auditoría no debe interrumpir el flujo principal.
            error_log('Error al registrar auditoría: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Listar registros de auditoría paginados con datos del usuario.
     *
     * @param int $limite Número de registros a devolver
     * @param int $offset Desplazamiento para paginación
     * @return array Lista de registros de auditoría
     */
    public static function listar(int $limite = 50, int $offset = 0): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT a.*, u.nombre_completo, u.nombre_usuario
                    FROM auditoria a
                    INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
                    ORDER BY a.fecha_hora DESC
                    LIMIT :limite OFFSET :offset';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al listar auditoría: ' . $e->getMessage());
            return [];
        }
    }
}
