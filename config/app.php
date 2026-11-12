<?php
/**
 * Configuraciones generales del Sistema de Gestión de Prácticas Laborales
 * ITSEC SUCRE
 * 
 * Este archivo retorna un array con todas las configuraciones del sistema.
 */

return [
    // ============================================================
    // Información del Sistema
    // ============================================================
    'nombre_sistema'    => 'Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE',
    'version'           => '1.0.0',
    'institucion'       => 'Instituto Técnico Superior de Educación Comercial Sucre',
    'siglas_institucion'=> 'ITSEC SUCRE',

    // ============================================================
    // Configuración de Rutas y URLs
    // ============================================================
    'url_base'          => (function () {
        if (!empty($_SERVER['SCRIPT_NAME'])) {
            $parts = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'));
            if (!empty($parts[0])) {
                return '/' . $parts[0] . '/';
            }
        }
        return '/pasantias2/';
    })(),
    'ruta_raiz'         => __DIR__ . '/../',

    // ============================================================
    // Configuración de Base de Datos
    // ============================================================
    'db_host'           => 'localhost',
    'db_nombre'         => 'sistema_practicas',
    'db_usuario'        => 'root',
    'db_contrasena'     => '',
    'db_charset'        => 'utf8mb4',

    // ============================================================
    // Configuración de Paginación
    // ============================================================
    'registros_por_pagina' => 15,

    // ============================================================
    // Configuración de Zona Horaria
    // ============================================================
    'zona_horaria'      => 'America/La_Paz',

    // ============================================================
    // Rutas de Subida de Archivos
    // ============================================================
    'ruta_uploads'      => __DIR__ . '/../uploads/',
    'ruta_convenios'    => __DIR__ . '/../uploads/convenios/',

    // ============================================================
    // Ruta de Respaldos
    // ============================================================
    'ruta_respaldos'    => __DIR__ . '/../respaldos/',

    // ============================================================
    // Configuración de Sesiones
    // ============================================================
    'tiempo_inactividad' => 1800, // 30 minutos en segundos

    // ============================================================
    // Configuración de Seguridad
    // ============================================================
    'intentos_login_max' => 10,
    'bloqueo_minutos' => 1,
];
