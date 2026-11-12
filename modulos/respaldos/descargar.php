<?php
/**
 * Descarga de un archivo de respaldo
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Respaldo.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarPermiso('respaldos', 'ver');

$nombre = $_GET['archivo'] ?? '';
$ruta = Respaldo::rutaArchivo($nombre);

if ($ruta === null) {
    mostrarMensaje('error', 'El archivo de respaldo solicitado no existe.');
    redirigir('modulos/respaldos/index.php');
}

Auditoria::registrar((int) $_SESSION['id_usuario'], 'crear', 'respaldos', null, null, ['descarga' => $nombre]);

// Enviar el archivo para descarga.
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($ruta) . '"');
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: no-cache, must-revalidate');
readfile($ruta);
exit;
