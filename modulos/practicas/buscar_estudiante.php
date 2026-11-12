<?php
/**
 * Autocompletado de estudiantes activos para el registro de prácticas
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Devuelve en JSON los estudiantes activos que coinciden con el término
 * (nombre, cédula o código). Se usa en el buscador del formulario de prácticas.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Estudiante.php';

verificarAutenticacion();
header('Content-Type: application/json; charset=utf-8');

if (!tienePermiso('practicas', 'ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit;
}

$termino = trim($_GET['q'] ?? '');
if (mb_strlen($termino) < 2) {
    echo json_encode(['items' => []]);
    exit;
}

// Solo estudiantes activos que coincidan con el término.
$restringirCarrera = restriccionCarreraActual();
$filtrosBusqueda = ['buscar' => $termino, 'estado' => '1'];
if ($restringirCarrera !== null) {
    $filtrosBusqueda['carrera'] = $restringirCarrera;
}
$estudiantes = Estudiante::listar(1, $filtrosBusqueda, 15);

$items = array_map(function ($e) {
    return [
        'id'    => (int) $e['id_estudiante'],
        'texto' => $e['nombre_completo'] . ' (' . $e['codigo_estudiante'] . ') — ' . $e['carrera'],
    ];
}, $estudiantes);

echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
