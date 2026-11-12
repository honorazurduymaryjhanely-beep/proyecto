<?php
/**
 * Endpoint AJAX: convenios vigentes de una institución
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Devuelve en JSON los convenios vigentes de la institución indicada, para
 * poblar dinámicamente el selector de convenios en el registro de prácticas.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Convenio.php';

verificarAutenticacion();
header('Content-Type: application/json; charset=utf-8');

if (!tienePermiso('practicas', 'ver')) {
    http_response_code(403);
    echo json_encode(['error' => 'Sin permisos']);
    exit;
}

$idInstitucion = (int) ($_GET['id_institucion'] ?? 0);
$convenios = $idInstitucion > 0 ? Convenio::obtenerVigentesPorInstitucion($idInstitucion) : [];

$resultado = array_map(function ($c) {
    return [
        'id_convenio' => (int) $c['id_convenio'],
        'texto'       => $c['codigo_convenio'] . ' - ' . $c['titulo'],
    ];
}, $convenios);

echo json_encode(['total' => count($resultado), 'convenios' => $resultado], JSON_UNESCAPED_UNICODE);
