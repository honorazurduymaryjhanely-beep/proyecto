<?php
/**
 * Búsqueda de usuarios (respuesta JSON para actualización asíncrona)
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Usuario.php';

verificarAutenticacion();

header('Content-Type: application/json; charset=utf-8');

if (!tienePermiso('usuarios', 'ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit;
}

$filtros = [
    'buscar' => trim($_GET['termino'] ?? ''),
    'id_rol' => $_GET['id_rol'] ?? '',
    'estado' => $_GET['estado'] ?? '',
];

$usuarios = Usuario::listar(1, $filtros, 50);

$resultado = array_map(function ($u) {
    return [
        'id_usuario'      => (int) $u['id_usuario'],
        'nombre_completo' => $u['nombre_completo'],
        'nombre_usuario'  => $u['nombre_usuario'],
        'rol'             => ucfirst($u['nombre_rol']),
        'estado'          => (int) $u['estado'],
    ];
}, $usuarios);

echo json_encode(['total' => count($resultado), 'usuarios' => $resultado], JSON_UNESCAPED_UNICODE);
