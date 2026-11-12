<?php
/**
 * Clase Database - Conexión a Base de Datos
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 * 
 * Implementa el patrón Singleton para gestionar una conexión única
 * a MySQL mediante PDO con consultas preparadas.
 */

class Database
{
    /**
     * Instancia única de la clase Database (Singleton)
     * @var Database|null
     */
    private static $instancia = null;

    /**
     * Instancia de conexión PDO
     * @var PDO
     */
    private $conexion;

    /**
     * Constructor privado - Establece la conexión PDO a MySQL
     * 
     * Carga la configuración desde app.php y crea la conexión
     * con las opciones de seguridad y rendimiento adecuadas.
     * 
     * @throws PDOException Si no se puede establecer la conexión
     */
    private function __construct()
    {
        try {
            $config = require __DIR__ . '/app.php';

            $dsn = "mysql:host={$config['db_host']};dbname={$config['db_nombre']};charset={$config['db_charset']}";

            $this->conexion = new PDO($dsn, $config['db_usuario'], $config['db_contrasena'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Forzar la codificación de la conexión a UTF-8 (acentos y ñ).
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['db_charset']} COLLATE utf8mb4_unicode_ci",
            ]);

            // Sincronizar automáticamente el rol 'docente_encargado' (id_rol = 2)
            try {
                $this->conexion->exec("UPDATE roles SET nombre_rol = 'docente_encargado', descripcion = 'Docente encargado de las prácticas de su carrera' WHERE id_rol = 2 AND nombre_rol != 'docente_encargado'");
            } catch (Throwable $ignore) {}

            // Asegurar que exista el rol 'docente_supervisor' (id_rol = 4)
            try {
                $chkSup = $this->conexion->query("SELECT id_rol FROM roles WHERE nombre_rol = 'docente_supervisor' LIMIT 1")->fetch();
                if (!$chkSup) {
                    $this->conexion->exec("INSERT INTO roles (id_rol, nombre_rol, descripcion, permisos, estado) VALUES (4, 'docente_supervisor', 'Docente supervisor de prácticas laborales', '{}', 1)");
                }
            } catch (Throwable $ignore) {}

            // Habilitar la mayoría de las opciones (estudiantes, instituciones, convenios, prácticas) para docentes
            try {
                $permisosDocente = json_encode([
                    'dashboard'     => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false],
                    'usuarios'      => ['ver' => false, 'crear' => false, 'editar' => false, 'eliminar' => false],
                    'estudiantes'   => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false],
                    'instituciones' => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false],
                    'convenios'     => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false],
                    'supervisores'  => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false],
                    'practicas'     => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false],
                    'reportes'      => ['ver' => true, 'crear' => true, 'editar' => true, 'eliminar' => false],
                    'respaldos'     => ['ver' => false, 'crear' => false, 'editar' => false, 'eliminar' => false],
                    'busqueda'      => ['ver' => true, 'crear' => false, 'editar' => false, 'eliminar' => false],
                ]);
                $this->conexion->prepare("UPDATE roles SET permisos = :p WHERE id_rol IN (2, 4)")->execute([':p' => $permisosDocente]);
            } catch (Throwable $ignore) {}

            // Normalizar nombres de carrera en tablas para evitar inconsistencias ('Ing de Sistemas' vs 'Sistemas Informáticos')
            try {
                $this->conexion->exec("UPDATE usuarios SET carrera = 'Sistemas Informáticos' WHERE carrera IN ('Ing de Sistemas', 'Sistemas', 'Ing. de Sistemas')");
                $this->conexion->exec("UPDATE docentes_supervisores SET carrera = 'Sistemas Informáticos' WHERE carrera IN ('Ing de Sistemas', 'Sistemas', 'Ing. de Sistemas')");
                $this->conexion->exec("UPDATE estudiantes SET carrera = 'Sistemas Informáticos' WHERE carrera IN ('Ing de Sistemas', 'Sistemas', 'Ing. de Sistemas')");
            } catch (Throwable $ignore) {}

            // Asegurar la presencia de columnas carrera y teléfono en usuarios
            try {
                $cols = $this->conexion->query("SHOW COLUMNS FROM usuarios LIKE 'carrera'")->fetchAll();
                if (empty($cols)) {
                    $this->conexion->exec("ALTER TABLE usuarios ADD COLUMN carrera VARCHAR(100) NULL AFTER correo_electronico, ADD COLUMN telefono VARCHAR(20) NULL AFTER carrera");
                }
            } catch (Throwable $ignore) {}

        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            throw new PDOException("No se pudo conectar a la base de datos. Contacte al administrador del sistema.");
        }
    }

    /**
     * Obtener la instancia única de conexión PDO
     * 
     * Si no existe una instancia previa, la crea.
     * Retorna siempre la misma conexión PDO durante toda la ejecución.
     * 
     * @return PDO Instancia de conexión PDO
     */
    public static function obtenerInstancia(): PDO
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia->conexion;
    }

    /**
     * Prevenir la clonación de la instancia (Singleton)
     */
    private function __clone() {}

    /**
     * Prevenir la deserialización de la instancia (Singleton)
     */
    public function __wakeup()
    {
        throw new \Exception("No se puede deserializar una instancia Singleton.");
    }
}
