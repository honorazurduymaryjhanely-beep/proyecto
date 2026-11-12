-- =====================================================
-- Sistema de Gestión de Prácticas Laborales
-- Instituto Técnico Superior de Educación Comercial SUCRE
-- Archivo: estructura.sql
-- Descripción: Sentencias DDL para la creación de la
--              base de datos y todas las tablas del sistema
-- =====================================================

-- Eliminar la base de datos si existe (para reinstalación limpia)
DROP DATABASE IF EXISTS sistema_practicas;

-- Crear la base de datos con soporte completo de caracteres
CREATE DATABASE sistema_practicas
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Seleccionar la base de datos
USE sistema_practicas;

-- Asegurar la codificación UTF-8 del cliente para conservar acentos y ñ.
SET NAMES utf8mb4;

-- =====================================================
-- Tabla: roles
-- Descripción: Almacena los roles del sistema que definen
--              los permisos de acceso de cada usuario
-- =====================================================
CREATE TABLE roles (
    id_rol INT NOT NULL AUTO_INCREMENT,
    nombre_rol VARCHAR(50) NOT NULL,
    descripcion VARCHAR(255) NULL,
    permisos TEXT NULL COMMENT 'JSON con permisos detallados del rol',
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_rol),
    UNIQUE KEY uk_nombre_rol (nombre_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Roles del sistema con permisos de acceso';

-- =====================================================
-- Tabla: usuarios
-- Descripción: Almacena las cuentas de usuario que acceden
--              al sistema con sus credenciales y rol asignado
-- =====================================================
CREATE TABLE usuarios (
    id_usuario INT NOT NULL AUTO_INCREMENT,
    nombre_completo VARCHAR(150) NOT NULL,
    nombre_usuario VARCHAR(50) NOT NULL,
    contrasena_hash VARCHAR(255) NOT NULL,
    correo_electronico VARCHAR(150) NOT NULL,
    carrera VARCHAR(100) NULL COMMENT 'Carrera del docente encargado',
    telefono VARCHAR(20) NULL COMMENT 'Teléfono del docente encargado',
    id_rol INT NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    ultimo_acceso DATETIME NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uk_nombre_usuario (nombre_usuario),
    UNIQUE KEY uk_correo_electronico (correo_electronico),
    KEY idx_id_rol (id_rol),
    KEY idx_estado (estado),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (id_rol)
        REFERENCES roles (id_rol)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Usuarios del sistema con credenciales y rol asignado';

-- =====================================================
-- Tabla: estudiantes
-- Descripción: Almacena los datos personales y académicos
--              de los estudiantes que realizan prácticas
-- =====================================================
CREATE TABLE estudiantes (
    id_estudiante INT NOT NULL AUTO_INCREMENT,
    codigo_estudiante VARCHAR(20) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    cedula_identidad VARCHAR(10) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    carrera VARCHAR(100) NOT NULL,
    curso VARCHAR(20) NOT NULL COMMENT '1ro, 2do, 3ro (carrera anualizada de 3 años)',
    paralelo VARCHAR(10) NULL COMMENT 'Paralelo: A, B, etc.',
    telefono VARCHAR(20) NULL,
    correo_electronico VARCHAR(150) NULL,
    direccion VARCHAR(255) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    creado_por INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_estudiante),
    UNIQUE KEY uk_codigo_estudiante (codigo_estudiante),
    UNIQUE KEY uk_cedula_identidad (cedula_identidad),
    KEY idx_carrera (carrera),
    KEY idx_estado (estado),
    KEY idx_nombre_completo (nombre_completo),
    KEY idx_creado_por (creado_por),
    CONSTRAINT fk_estudiantes_creado_por FOREIGN KEY (creado_por)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Estudiantes registrados para realizar prácticas laborales';

-- =====================================================
-- Tabla: instituciones_receptoras
-- Descripción: Almacena las empresas e instituciones que
--              reciben estudiantes para prácticas laborales
-- =====================================================
CREATE TABLE instituciones_receptoras (
    id_institucion INT NOT NULL AUTO_INCREMENT,
    codigo_institucion VARCHAR(20) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    nit VARCHAR(20) NOT NULL,
    direccion VARCHAR(255) NOT NULL,
    telefono VARCHAR(20) NOT NULL COMMENT 'Teléfono de la institución',
    correo_electronico VARCHAR(150) NULL,
    nombre_mae VARCHAR(150) NULL COMMENT 'Máxima Autoridad Ejecutiva',
    telefono_mae VARCHAR(20) NULL COMMENT 'Teléfono de la MAE',
    nombre_contacto VARCHAR(150) NOT NULL,
    cargo_contacto VARCHAR(100) NOT NULL,
    telefono_contacto VARCHAR(20) NULL COMMENT 'Teléfono de la persona de contacto',
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    creado_por INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_institucion),
    UNIQUE KEY uk_codigo_institucion (codigo_institucion),
    UNIQUE KEY uk_nit (nit),
    KEY idx_nombre (nombre),
    KEY idx_estado (estado),
    KEY idx_creado_por (creado_por),
    CONSTRAINT fk_instituciones_creado_por FOREIGN KEY (creado_por)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Instituciones receptoras de estudiantes en prácticas';

-- =====================================================
-- Tabla: convenios
-- Descripción: Almacena los convenios institucionales entre
--              el ITSEC SUCRE y las instituciones receptoras
-- =====================================================
CREATE TABLE convenios (
    id_convenio INT NOT NULL AUTO_INCREMENT,
    codigo_convenio VARCHAR(20) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    id_institucion INT NOT NULL,
    fecha_firma DATE NOT NULL,
    fecha_inicio_vigencia DATE NOT NULL,
    fecha_fin_vigencia DATE NOT NULL,
    documento_adjunto VARCHAR(255) NULL COMMENT 'Ruta del archivo adjunto',
    estado ENUM('vigente', 'expirado', 'cancelado') NOT NULL DEFAULT 'vigente',
    creado_por INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_convenio),
    UNIQUE KEY uk_codigo_convenio (codigo_convenio),
    KEY idx_id_institucion (id_institucion),
    KEY idx_estado (estado),
    KEY idx_fecha_vigencia (fecha_inicio_vigencia, fecha_fin_vigencia),
    KEY idx_creado_por (creado_por),
    CONSTRAINT fk_convenios_institucion FOREIGN KEY (id_institucion)
        REFERENCES instituciones_receptoras (id_institucion)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_convenios_creado_por FOREIGN KEY (creado_por)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Convenios institucionales entre ITSEC SUCRE e instituciones receptoras';

-- =====================================================
-- Tabla: docentes_supervisores
-- Descripción: Catálogo de docentes que supervisan a los
--              estudiantes en sus prácticas (no inician sesión).
--              El docente ENCARGADO de la carrera es un usuario.
-- =====================================================
CREATE TABLE docentes_supervisores (
    id_docente INT NOT NULL AUTO_INCREMENT,
    codigo_docente VARCHAR(20) NOT NULL,
    nombre_completo VARCHAR(150) NOT NULL,
    cedula_identidad VARCHAR(10) NOT NULL,
    carrera VARCHAR(100) NOT NULL,
    telefono VARCHAR(20) NULL,
    correo_electronico VARCHAR(150) NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
    creado_por INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_docente),
    UNIQUE KEY uk_codigo_docente (codigo_docente),
    UNIQUE KEY uk_cedula_identidad_docente (cedula_identidad),
    KEY idx_carrera (carrera),
    KEY idx_estado (estado),
    KEY idx_creado_por (creado_por),
    CONSTRAINT fk_docentes_creado_por FOREIGN KEY (creado_por)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catálogo de docentes supervisores de estudiantes en prácticas';

-- =====================================================
-- Tabla: practicas_laborales
-- Descripción: Almacena las prácticas laborales asignadas
--              vinculando estudiante, institución y convenio
-- =====================================================
CREATE TABLE practicas_laborales (
    id_practica INT NOT NULL AUTO_INCREMENT,
    codigo_practica VARCHAR(20) NOT NULL,
    id_estudiante INT NOT NULL,
    id_institucion INT NOT NULL,
    id_convenio INT NOT NULL,
    descripcion_actividades TEXT NULL,
    area_departamento VARCHAR(150) NULL COMMENT 'Área o departamento en la institución',
    fecha_inicio DATE NOT NULL,
    fecha_fin_prevista DATE NOT NULL,
    fecha_finalizacion_real DATE NULL,
    total_horas_requeridas INT NOT NULL,
    estado ENUM('pendiente', 'en_curso', 'completada', 'suspendida') NOT NULL DEFAULT 'pendiente',
    creado_por INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_practica),
    UNIQUE KEY uk_codigo_practica (codigo_practica),
    KEY idx_id_estudiante (id_estudiante),
    KEY idx_id_institucion (id_institucion),
    KEY idx_id_convenio (id_convenio),
    KEY idx_estado (estado),
    KEY idx_fecha_inicio (fecha_inicio),
    KEY idx_fecha_fin_prevista (fecha_fin_prevista),
    KEY idx_creado_por (creado_por),
    CONSTRAINT fk_practicas_estudiante FOREIGN KEY (id_estudiante)
        REFERENCES estudiantes (id_estudiante)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_practicas_institucion FOREIGN KEY (id_institucion)
        REFERENCES instituciones_receptoras (id_institucion)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_practicas_convenio FOREIGN KEY (id_convenio)
        REFERENCES convenios (id_convenio)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_practicas_creado_por FOREIGN KEY (creado_por)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Prácticas laborales asignadas a estudiantes';

-- =====================================================
-- Tabla: asignaciones_supervision
-- Descripción: Registra las asignaciones de docentes
--              supervisores a prácticas laborales
-- =====================================================
CREATE TABLE asignaciones_supervision (
    id_asignacion INT NOT NULL AUTO_INCREMENT,
    id_practica INT NOT NULL,
    id_docente INT NOT NULL,
    fecha_asignacion DATE NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=reasignado',
    observaciones TEXT NULL,
    asignado_por INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_asignacion),
    KEY idx_id_practica (id_practica),
    KEY idx_id_docente (id_docente),
    KEY idx_estado (estado),
    KEY idx_asignado_por (asignado_por),
    CONSTRAINT fk_asignaciones_practica FOREIGN KEY (id_practica)
        REFERENCES practicas_laborales (id_practica)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_asignaciones_docente FOREIGN KEY (id_docente)
        REFERENCES docentes_supervisores (id_docente)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_asignaciones_asignado_por FOREIGN KEY (asignado_por)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Asignaciones de docentes supervisores a prácticas';

-- =====================================================
-- Tabla: actividades_practica
-- Descripción: Registra las actividades específicas que
--              realiza el estudiante durante la práctica
-- =====================================================
CREATE TABLE actividades_practica (
    id_actividad INT NOT NULL AUTO_INCREMENT,
    id_practica INT NOT NULL,
    nombre_actividad VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    fecha_registro DATE NOT NULL,
    registrado_por INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_actividad),
    KEY idx_id_practica (id_practica),
    KEY idx_fecha_registro (fecha_registro),
    KEY idx_registrado_por (registrado_por),
    CONSTRAINT fk_actividades_practica FOREIGN KEY (id_practica)
        REFERENCES practicas_laborales (id_practica)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_actividades_registrado_por FOREIGN KEY (registrado_por)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Actividades realizadas durante las prácticas laborales';

-- =====================================================
-- Tabla: horas_cumplidas
-- Descripción: Registra las horas trabajadas por el
--              estudiante en cada día de la práctica
-- =====================================================
CREATE TABLE horas_cumplidas (
    id_registro_horas INT NOT NULL AUTO_INCREMENT,
    id_practica INT NOT NULL,
    fecha DATE NOT NULL,
    cantidad_horas DECIMAL(4,2) NOT NULL,
    descripcion_actividades VARCHAR(500) NOT NULL,
    registrado_por INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_registro_horas),
    KEY idx_id_practica (id_practica),
    KEY idx_fecha (fecha),
    KEY idx_registrado_por (registrado_por),
    CONSTRAINT fk_horas_practica FOREIGN KEY (id_practica)
        REFERENCES practicas_laborales (id_practica)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_horas_registrado_por FOREIGN KEY (registrado_por)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_cantidad_horas CHECK (cantidad_horas > 0 AND cantidad_horas <= 12)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de horas cumplidas por los estudiantes en prácticas';

-- =====================================================
-- Tabla: practicas_historial_estado
-- Descripción: Almacena el historial de todos los cambios
--              de estado realizados en las prácticas
-- =====================================================
CREATE TABLE practicas_historial_estado (
    id_historial INT NOT NULL AUTO_INCREMENT,
    id_practica INT NOT NULL,
    estado_anterior ENUM('pendiente', 'en_curso', 'completada', 'suspendida') NOT NULL,
    estado_nuevo ENUM('pendiente', 'en_curso', 'completada', 'suspendida') NOT NULL,
    fecha_cambio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NOT NULL,
    observaciones TEXT NULL COMMENT 'Motivo del cambio de estado',
    PRIMARY KEY (id_historial),
    KEY idx_id_practica (id_practica),
    KEY idx_fecha_cambio (fecha_cambio),
    KEY idx_id_usuario (id_usuario),
    CONSTRAINT fk_historial_practica FOREIGN KEY (id_practica)
        REFERENCES practicas_laborales (id_practica)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_historial_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de cambios de estado de las prácticas laborales';

-- =====================================================
-- Tabla: auditoria
-- Descripción: Registro de auditoría de todas las acciones
--              realizadas por los usuarios en el sistema
-- =====================================================
CREATE TABLE auditoria (
    id_auditoria INT NOT NULL AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    accion ENUM('crear', 'modificar', 'eliminar', 'login', 'logout', 'acceso_denegado') NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    id_registro INT NULL COMMENT 'ID del registro afectado en la tabla',
    datos_anteriores TEXT NULL COMMENT 'JSON con datos antes del cambio',
    datos_nuevos TEXT NULL COMMENT 'JSON con datos después del cambio',
    ip_direccion VARCHAR(45) NULL COMMENT 'Dirección IP del usuario (IPv4 o IPv6)',
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_auditoria),
    KEY idx_id_usuario (id_usuario),
    KEY idx_accion (accion),
    KEY idx_tabla_afectada (tabla_afectada),
    KEY idx_fecha_hora (fecha_hora),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Log de auditoría de acciones del sistema';

-- =====================================================
-- Tabla: respaldos
-- Descripción: Registra los respaldos de base de datos
--              generados por los administradores
-- =====================================================
CREATE TABLE respaldos (
    id_respaldo INT NOT NULL AUTO_INCREMENT,
    nombre_archivo VARCHAR(255) NOT NULL,
    tamano_bytes BIGINT NULL COMMENT 'Tamaño del archivo en bytes',
    id_usuario INT NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_respaldo),
    KEY idx_id_usuario (id_usuario),
    KEY idx_fecha_creacion (fecha_creacion),
    CONSTRAINT fk_respaldos_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios (id_usuario)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de respaldos de la base de datos del sistema';

-- =====================================================
-- Tabla: carreras
-- Descripción: Catálogo administrable de carreras del
--              instituto, usado en el registro de estudiantes
-- =====================================================
CREATE TABLE carreras (
    id_carrera INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activa, 0=inactiva',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_carrera),
    UNIQUE KEY uk_nombre_carrera (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catálogo de carreras del ITSEC SUCRE';
