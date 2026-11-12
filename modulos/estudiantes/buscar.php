<?php
/**
 * Búsqueda de estudiantes (respuesta JSON)
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Estudiante.php';

verificarAutenticacion();
header('Content-Type: application/json; charset=utf-8');

if (!tienePermiso('estudiantes', 'ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit;
}

$termino = trim($_GET['termino'] ?? '');
$estudiantes = Estudiante::buscar($termino, 50);

$resultado = array_map(function ($e) {
    return [
        'id_estudiante'     => (int) $e['id_estudiante'],
        'codigo'            => $e['codigo_estudiante'],
        'nombre_completo'   => $e['nombre_completo'],
        'cedula'            => $e['cedula_identidad'],
        'carrera'           => $e['carrera'],
        'estado'            => (int) $e['estado'],
    ];
}, $estudiantes);

echo json_encode(['total' => count($resultado), 'estudiantes' => $resultado], JSON_UNESCAPED_UNICODE);
