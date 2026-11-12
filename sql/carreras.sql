-- =====================================================
-- Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
-- Archivo: carreras.sql
-- Descripción: Catálogo de carreras (tabla y datos semilla).
--              Ejecutar sobre la base de datos sistema_practicas.
-- =====================================================

-- Asegurar la codificación UTF-8 del cliente para conservar acentos y ñ.
SET NAMES utf8mb4;

USE sistema_practicas;

-- =====================================================
-- Tabla: carreras
-- Catálogo administrable de carreras del instituto.
-- =====================================================
CREATE TABLE IF NOT EXISTS carreras (
    id_carrera INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=activa, 0=inactiva',
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_carrera),
    UNIQUE KEY uk_nombre_carrera (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Catálogo de carreras del ITSEC SUCRE';

-- =====================================================
-- Datos semilla: las 5 carreras iniciales del instituto.
-- =====================================================
INSERT INTO carreras (nombre) VALUES
    ('Secretariado Ejecutivo'),
    ('Contaduría General'),
    ('Sistemas Informáticos'),
    ('Turismo'),
    ('Mercadotecnia')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);
