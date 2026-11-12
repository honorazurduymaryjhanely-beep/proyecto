<?php
/**
 * Autocompletado de instituciones activas para el registro de prácticas
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Devuelve en JSON las instituciones activas que coinciden con el término
 * (nombre, NIT o código). Se usa en el buscador del formulario de prácticas.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Institucion.php';

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

// Solo instituciones activas que coincidan con el término.
$instituciones = Institucion::listar(1, ['buscar' => $termino, 'estado' => '1'], 15);

$items = array_map(function ($i) {
    return [
        'id'    => (int) $i['id_institucion'],
        'texto' => $i['nombre'] . ' (' . $i['codigo_institucion'] . ')',
    ];
}, $instituciones);

echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
