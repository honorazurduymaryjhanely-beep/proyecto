-- =====================================================
-- Sistema de Gestión de Prácticas Laborales
-- Instituto Técnico Superior de Educación Comercial SUCRE
-- Archivo: datos_iniciales.sql
-- Descripción: Datos semilla para la configuración inicial
--              del sistema (roles y usuario administrador)
-- =====================================================

-- Asegurar la codificación UTF-8 del cliente para conservar acentos y ñ.
SET NAMES utf8mb4;

-- Seleccionar la base de datos
USE sistema_practicas;

-- =====================================================
-- Inserción de Roles del Sistema
-- Se definen 3 roles con permisos diferenciados:
--   1. Administrador: acceso completo a todos los módulos
--   2. Docente: acceso a consulta y supervisión de prácticas
--   3. Consulta: acceso de solo lectura a consultas y reportes
-- =====================================================

-- Rol: Administrador - Acceso completo a todos los módulos
INSERT INTO roles (id_rol, nombre_rol, descripcion, permisos, estado) VALUES
(1, 'administrador', 'Acceso completo a todos los módulos del sistema', '{
    "dashboard": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "usuarios": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "estudiantes": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "instituciones": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "convenios": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "supervisores": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "practicas": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "reportes": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "respaldos": {"ver": true, "crear": true, "editar": true, "eliminar": true},
    "busqueda": {"ver": true, "crear": true, "editar": true, "eliminar": true}
}', 1);

-- Rol: Docente Encargado - Docente encargado de las prácticas de su carrera
INSERT INTO roles (id_rol, nombre_rol, descripcion, permisos, estado) VALUES
(2, 'docente_encargado', 'Docente encargado de las prácticas de su carrera', '{
    "dashboard": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "usuarios": {"ver": false, "crear": false, "editar": false, "eliminar": false},
    "estudiantes": {"ver": true, "crear": true, "editar": true, "eliminar": false},
    "instituciones": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "convenios": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "supervisores": {"ver": true, "crear": true, "editar": true, "eliminar": false},
    "practicas": {"ver": true, "crear": true, "editar": true, "eliminar": false},
    "reportes": {"ver": true, "crear": true, "editar": false, "eliminar": false},
    "respaldos": {"ver": false, "crear": false, "editar": false, "eliminar": false},
    "busqueda": {"ver": true, "crear": false, "editar": false, "eliminar": false}
}', 1);

-- Rol: Consulta - Acceso de solo lectura a consultas y reportes
INSERT INTO roles (id_rol, nombre_rol, descripcion, permisos, estado) VALUES
(3, 'consulta', 'Acceso de solo lectura a consultas y reportes', '{
    "dashboard": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "usuarios": {"ver": false, "crear": false, "editar": false, "eliminar": false},
    "estudiantes": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "instituciones": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "convenios": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "supervisores": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "practicas": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "reportes": {"ver": true, "crear": false, "editar": false, "eliminar": false},
    "respaldos": {"ver": false, "crear": false, "editar": false, "eliminar": false},
    "busqueda": {"ver": true, "crear": false, "editar": false, "eliminar": false}
}', 1);

-- =====================================================
-- Inserción del Usuario Administrador por Defecto
-- Credenciales iniciales:
--   Usuario: admin
--   Contraseña: admin123
--   IMPORTANTE: Cambiar la contraseña después del primer
--              inicio de sesión por seguridad
-- =====================================================

-- Hash bcrypt generado con password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO usuarios (
    nombre_completo,
    nombre_usuario,
    contrasena_hash,
    correo_electronico,
    id_rol,
    estado
) VALUES (
    'Administrador del Sistema',
    'admin',
    '$2y$10$b1OLoKq.n.ycZBNN8L/gW.4PnKqMRq1G9zxddU5FgFkkCBf0GXDN2',
    'admin@itsec.edu.bo',
    1,
    1
);

-- =====================================================
-- Fin del archivo de datos iniciales
-- Nota: El hash de contraseña corresponde a 'admin123'
-- generado con password_hash() de PHP (algoritmo bcrypt)
-- =====================================================

-- =====================================================
-- Carreras iniciales del instituto
-- =====================================================
INSERT INTO carreras (nombre) VALUES
    ('Secretariado Ejecutivo'),
    ('Contaduría General'),
    ('Sistemas Informáticos'),
    ('Turismo'),
    ('Mercadotecnia');
