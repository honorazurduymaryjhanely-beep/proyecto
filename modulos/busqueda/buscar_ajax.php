<?php
/**
 * Endpoint AJAX de búsqueda general
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Devuelve en JSON los resultados agrupados por categoría para el término
 * indicado. Respeta los permisos por módulo del usuario.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Busqueda.php';

verificarAutenticacion();
header('Content-Type: application/json; charset=utf-8');

if (!tienePermiso('busqueda', 'ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit;
}

$termino = trim($_GET['q'] ?? '');
if (mb_strlen($termino) < 2) {
    echo json_encode(['categorias' => []]);
    exit;
}

$restringirCarrera = restriccionCarreraActual();
$resultados = Busqueda::buscarGlobal($termino, $restringirCarrera);

// Mapa de módulo por categoría para filtrar por permisos de "ver".
$moduloPorCategoria = [
    'estudiantes'   => 'estudiantes',
    'instituciones' => 'instituciones',
    'supervisores'  => 'supervisores',
    'convenios'     => 'convenios',
    'practicas'     => 'practicas',
];

$categorias = [];
foreach ($resultados as $clave => $grupo) {
    $modulo = $moduloPorCategoria[$clave] ?? null;
    // Omitir categorías sin permiso de lectura o sin resultados.
    if ($modulo !== null && !tienePermiso($modulo, 'ver')) {
        continue;
    }
    if (empty($grupo['items'])) {
        continue;
    }
    $categorias[] = [
        'clave'    => $clave,
        'etiqueta' => $grupo['etiqueta'],
        'ruta'     => $grupo['ruta'],
        'items'    => $grupo['items'],
    ];
}

echo json_encode(['categorias' => $categorias], JSON_UNESCAPED_UNICODE);
