<?php
/**
 * Clase Respaldo - Respaldo manual de la base de datos
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Genera un volcado SQL de la base de datos con mysqldump, lo almacena en la
 * carpeta de respaldos y registra la operación en la tabla `respaldos`.
 */

require_once __DIR__ . '/../config/database.php';

class Respaldo
{
    /**
     * Generar un respaldo de la base de datos.
     *
     * @param int $idUsuario ID del usuario que solicita el respaldo
     * @return array{0:bool,1:string} [éxito, mensaje o nombre de archivo]
     */
    public static function generar(int $idUsuario): array
    {
        $config = require __DIR__ . '/../config/app.php';

        $rutaDestino = $config['ruta_respaldos'];
        if (!is_dir($rutaDestino) && !mkdir($rutaDestino, 0755, true) && !is_dir($rutaDestino)) {
            return [false, 'No se pudo acceder a la carpeta de respaldos.'];
        }

        $nombreArchivo = 'respaldo_' . $config['db_nombre'] . '_' . date('Ymd_His') . '.sql';
        $rutaArchivo = rtrim($rutaDestino, '/\\') . DIRECTORY_SEPARATOR . $nombreArchivo;

        // Localizar el ejecutable mysqldump (XAMPP en Windows por defecto).
        $mysqldump = self::localizarMysqldump();
        if ($mysqldump === null) {
            return [false, 'No se encontró el ejecutable mysqldump en el servidor.'];
        }

        // Construir el comando con argumentos escapados.
        $comando = escapeshellarg($mysqldump)
            . ' --host=' . escapeshellarg($config['db_host'])
            . ' --user=' . escapeshellarg($config['db_usuario']);
        if ($config['db_contrasena'] !== '') {
            $comando .= ' --password=' . escapeshellarg($config['db_contrasena']);
        }
        $comando .= ' --default-character-set=utf8mb4 --routines --events '
            . escapeshellarg($config['db_nombre'])
            . ' > ' . escapeshellarg($rutaArchivo) . ' 2>&1';

        $salida = [];
        $codigo = 0;
        exec($comando, $salida, $codigo);

        if ($codigo !== 0 || !file_exists($rutaArchivo) || filesize($rutaArchivo) === 0) {
            // Limpiar archivo vacío si se creó.
            if (file_exists($rutaArchivo) && filesize($rutaArchivo) === 0) {
                @unlink($rutaArchivo);
            }
            error_log('Error mysqldump: ' . implode("\n", $salida));
            return [false, 'No se pudo generar el respaldo. Verifique la configuración del servidor.'];
        }

        $tamano = filesize($rutaArchivo);

        // Registrar en la tabla respaldos.
        try {
            $db = Database::obtenerInstancia();
            $stmt = $db->prepare('INSERT INTO respaldos (nombre_archivo, tamano_bytes, id_usuario)
                                  VALUES (:nombre, :tamano, :usuario)');
            $stmt->execute([
                ':nombre'  => $nombreArchivo,
                ':tamano'  => $tamano,
                ':usuario' => $idUsuario,
            ]);
        } catch (Throwable $e) {
            error_log('Error al registrar respaldo: ' . $e->getMessage());
        }

        return [true, $nombreArchivo];
    }

    /**
     * Listar el historial de respaldos con el usuario que los generó.
     *
     * @return array
     */
    public static function listarRespaldos(): array
    {
        try {
            $db = Database::obtenerInstancia();
            $sql = 'SELECT r.id_respaldo, r.nombre_archivo, r.tamano_bytes, r.fecha_creacion,
                           u.nombre_completo AS usuario
                    FROM respaldos r
                    INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
                    ORDER BY r.fecha_creacion DESC';
            return $db->query($sql)->fetchAll();
        } catch (Throwable $e) {
            error_log('Error al listar respaldos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener la ruta absoluta de un archivo de respaldo validando el nombre.
     *
     * @param string $nombreArchivo Nombre del archivo (sin ruta)
     * @return string|null Ruta absoluta o null si no es válido / no existe
     */
    public static function rutaArchivo(string $nombreArchivo): ?string
    {
        // Evitar path traversal: solo se admite el nombre base con formato esperado.
        if (!preg_match('/^respaldo_[A-Za-z0-9_]+\.sql$/', $nombreArchivo)) {
            return null;
        }
        $config = require __DIR__ . '/../config/app.php';
        $ruta = rtrim($config['ruta_respaldos'], '/\\') . DIRECTORY_SEPARATOR . $nombreArchivo;
        return file_exists($ruta) ? $ruta : null;
    }

    /**
     * Localizar el ejecutable mysqldump.
     *
     * @return string|null Ruta al ejecutable o null si no se encuentra
     */
    private static function localizarMysqldump(): ?string
    {
        $candidatos = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            '/opt/lampp/bin/mysqldump',
            '/usr/bin/mysqldump',
            'mysqldump',
        ];
        foreach ($candidatos as $ruta) {
            if ($ruta === 'mysqldump' || file_exists($ruta)) {
                return $ruta;
            }
        }
        return null;
    }
}
