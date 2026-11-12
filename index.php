<?php
/**
 * Punto de entrada del Sistema de Gestión de Prácticas Laborales
 * Instituto Técnico Superior de Educación Comercial SUCRE (ITSEC SUCRE)
 *
 * Redirige al panel principal si existe una sesión activa, o al formulario
 * de inicio de sesión en caso contrario.
 */

require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

if (!empty($_SESSION['id_usuario'])) {
    redirigir('modulos/dashboard/index.php');
}

redirigir('modulos/autenticacion/login.php');
