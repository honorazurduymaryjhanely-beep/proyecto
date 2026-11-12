<?php
/**
 * Funciones auxiliares (helpers) del sistema
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Contiene funciones utilitarias reutilizables: sanitización, validaciones,
 * generación de códigos, paginación, protección CSRF, redirecciones y mensajes.
 */

require_once __DIR__ . '/../config/session.php';

/**
 * Sanitizar una cadena para su presentación en HTML (previene XSS).
 *
 * @param mixed $valor Valor a sanitizar
 * @return string Valor seguro para imprimir en HTML
 */
function sanitizar($valor): string
{
    if ($valor === null) {
        return '';
    }
    return htmlspecialchars(trim((string) $valor), ENT_QUOTES, 'UTF-8');
}

/**
 * Validar el formato de un correo electrónico.
 *
 * @param string $correo Correo a validar
 * @return bool true si es válido
 */
function validarEmail(string $correo): bool
{
    return filter_var(trim($correo), FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validar una cédula de identidad (solo dígitos, entre 5 y 10 caracteres).
 *
 * @param string $cedula Cédula a validar
 * @return bool true si es válida
 */
function validarCedula(string $cedula): bool
{
    $cedula = trim($cedula);
    return (bool) preg_match('/^\d{5,10}$/', $cedula);
}

/**
 * Validar un NIT (dígitos, opcionalmente con guion y dígito verificador).
 *
 * @param string $nit NIT a validar
 * @return bool true si es válido
 */
function validarNit(string $nit): bool
{
    $nit = trim($nit);
    return (bool) preg_match('/^\d{5,15}(-\d{1,3})?$/', $nit);
}

/**
 * Validar que una fecha tenga el formato Y-m-d y sea una fecha real.
 *
 * @param string $fecha Fecha en formato YYYY-MM-DD
 * @return bool true si es válida
 */
function validarFecha(string $fecha): bool
{
    $fecha = trim($fecha);
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d !== false && $d->format('Y-m-d') === $fecha;
}

/**
 * Generar un código con formato PREFIJO-XXXXXX a partir de un número.
 *
 * @param string $prefijo Prefijo (ej: EST, INS, CON, DOC, PRA)
 * @param int    $numero  Número secuencial
 * @return string Código formateado (ej: EST-000001)
 */
function generarCodigo(string $prefijo, int $numero): string
{
    return strtoupper($prefijo) . '-' . str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
}

/**
 * Calcular los datos de paginación.
 *
 * @param int $totalRegistros Total de registros disponibles
 * @param int $paginaActual   Página actual (1-indexada)
 * @param int $porPagina      Registros por página
 * @return array{pagina:int,por_pagina:int,total:int,total_paginas:int,offset:int,inicio:int,fin:int}
 */
function paginar(int $totalRegistros, int $paginaActual = 1, int $porPagina = 15): array
{
    $porPagina = max(1, $porPagina);
    $totalPaginas = (int) max(1, ceil($totalRegistros / $porPagina));
    $paginaActual = max(1, min($paginaActual, $totalPaginas));
    $offset = ($paginaActual - 1) * $porPagina;

    return [
        'pagina'        => $paginaActual,
        'por_pagina'    => $porPagina,
        'total'         => $totalRegistros,
        'total_paginas' => $totalPaginas,
        'offset'        => $offset,
        'inicio'        => $totalRegistros === 0 ? 0 : $offset + 1,
        'fin'           => min($offset + $porPagina, $totalRegistros),
    ];
}

/**
 * Renderizar la paginación HTML en base a los datos calculados por paginar().
 *
 * @param array $paginacion Datos de paginación devueltos por paginar()
 * @param string $urlBase Ruta base a la que apuntarán los enlaces
 * @param array $parametros Parámetros adicionales de query string a conservar
 * @return string HTML con la barra de paginación
 */
function renderizarPaginacion(array $paginacion, string $urlBase = '', array $parametros = []): string
{
    if (($paginacion['total_paginas'] ?? 1) <= 1) {
        return '';
    }

    unset($parametros['pagina']);
    $qs = http_build_query(array_filter($parametros, fn($v) => $v !== '' && $v !== null));
    $separador = str_contains($urlBase, '?') ? '&' : '?';
    $prefijo = $urlBase !== '' ? $urlBase . $separador : '?';

    $html = '<nav aria-label="Navegación de páginas"><ul class="pagination pagination-sm justify-content-center mb-0">';
    
    // Botón anterior
    if ($paginacion['pagina'] > 1) {
        $linkAnt = $prefijo . 'pagina=' . ($paginacion['pagina'] - 1) . ($qs ? '&' . $qs : '');
        $html .= '<li class="page-item"><a class="page-link" href="' . sanitizar($linkAnt) . '">&laquo; Anterior</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Anterior</span></li>';
    }

    // Números de página
    for ($i = 1; $i <= $paginacion['total_paginas']; $i++) {
        if ($i === $paginacion['pagina']) {
            $html .= '<li class="page-item active" aria-current="page"><span class="page-link">' . $i . '</span></li>';
        } else {
            $link = $prefijo . 'pagina=' . $i . ($qs ? '&' . $qs : '');
            $html .= '<li class="page-item"><a class="page-link" href="' . sanitizar($link) . '">' . $i . '</a></li>';
        }
    }

    // Botón siguiente
    if ($paginacion['pagina'] < $paginacion['total_paginas']) {
        $linkSig = $prefijo . 'pagina=' . ($paginacion['pagina'] + 1) . ($qs ? '&' . $qs : '');
        $html .= '<li class="page-item"><a class="page-link" href="' . sanitizar($linkSig) . '">Siguiente &raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Siguiente &raquo;</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Generar (si no existe) y devolver el token CSRF de la sesión actual.
 *
 * @return string Token CSRF
 */
function generarTokenCSRF(): string
{
    if (empty($_SESSION['token_csrf'])) {
        $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token_csrf'];
}

/**
 * Validar un token CSRF recibido contra el almacenado en sesión.
 *
 * @param string|null $token Token recibido del formulario
 * @return bool true si el token es válido
 */
function validarTokenCSRF(?string $token): bool
{
    if (empty($token) || empty($_SESSION['token_csrf'])) {
        return false;
    }
    return hash_equals($_SESSION['token_csrf'], $token);
}

/**
 * Devolver el campo oculto HTML con el token CSRF para insertar en formularios.
 *
 * @return string HTML del input hidden
 */
function campoCSRF(): string
{
    $token = generarTokenCSRF();
    return '<input type="hidden" name="token_csrf" value="' . sanitizar($token) . '">';
}

/**
 * Redirigir a una ruta relativa a la base del sistema y detener la ejecución.
 *
 * @param string $ruta Ruta relativa (ej: 'modulos/dashboard/index.php')
 * @return void
 */
function redirigir(string $ruta): void
{
    $config = require __DIR__ . '/../config/app.php';
    $base = rtrim($config['url_base'], '/');
    $ruta = ltrim($ruta, '/');
    header('Location: ' . $base . '/' . $ruta);
    exit;
}

/**
 * Guardar un mensaje flash en sesión o devolver y limpiar el mensaje pendiente.
 *
 * Uso para establecer: mostrarMensaje('exito', 'Guardado correctamente');
 * Uso para consumir:   $msg = mostrarMensaje();
 *
 * @param string|null $tipo  Tipo de mensaje: exito, error, advertencia, info
 * @param string|null $texto Texto del mensaje
 * @return array{tipo:string,texto:string}|null Mensaje pendiente o null
 */
function mostrarMensaje(?string $tipo = null, ?string $texto = null): ?array
{
    if ($tipo !== null && $texto !== null) {
        $_SESSION['mensaje_flash'] = ['tipo' => $tipo, 'texto' => $texto];
        return null;
    }

    if (!empty($_SESSION['mensaje_flash'])) {
        $mensaje = $_SESSION['mensaje_flash'];
        unset($_SESSION['mensaje_flash']);
        return $mensaje;
    }

    return null;
}

/**
 * Renderizar la alerta Bootstrap del mensaje flash pendiente (si existe).
 *
 * @return string HTML de la alerta o cadena vacía
 */
function renderizarMensajeFlash(): string
{
    $mensaje = mostrarMensaje();
    if ($mensaje === null) {
        return '';
    }

    $clases = [
        'exito'       => 'alert-success',
        'error'       => 'alert-danger',
        'advertencia' => 'alert-warning',
        'info'        => 'alert-info',
    ];
    $clase = $clases[$mensaje['tipo']] ?? 'alert-info';

    return '<div class="alert ' . $clase . ' alert-dismissible fade show" role="alert">'
        . sanitizar($mensaje['texto'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>'
        . '</div>';
}

/**
 * Obtener la dirección IP del cliente actual.
 *
 * @return string Dirección IP o 'desconocida'
 */
function obtenerIP(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
}

/**
 * Formatear una fecha (Y-m-d o datetime) al formato d/m/Y para presentación.
 *
 * @param string|null $fecha Fecha en formato ISO
 * @param bool        $conHora Incluir la hora (d/m/Y H:i)
 * @return string Fecha formateada o cadena vacía
 */
function formatearFecha(?string $fecha, bool $conHora = false): string
{
    if (empty($fecha) || $fecha === '0000-00-00' || $fecha === '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($fecha);
    if ($ts === false) {
        return '';
    }
    return date($conHora ? 'd/m/Y H:i' : 'd/m/Y', $ts);
}

/**
 * Devolver la etiqueta legible de un rol del sistema.
 *
 * @param string $nombreRol Nombre interno del rol
 * @return string Etiqueta para mostrar al usuario
 */
function etiquetaRol(?string $nombreRol): string
{
    if ($nombreRol === null || $nombreRol === '') {
        return '—';
    }
    $etiquetas = [
        'administrador'      => 'Administrador',
        'docente_encargado'  => 'Docente Encargado',
        'docente_supervisor' => 'Docente Supervisor',
        'consulta'           => 'Consulta',
    ];
    return $etiquetas[$nombreRol] ?? ucfirst(str_replace('_', ' ', $nombreRol));
}

/**
 * Normalizar el nombre de una carrera para evitar discrepancias
 * como 'Ing de Sistemas' vs 'Sistemas Informáticos'.
 *
 * @param string|null $carrera Nombre de carrera
 * @return string Nombre estandarizado
 */
function normalizarCarrera(?string $carrera): string
{
    if ($carrera === null || trim($carrera) === '') {
        return '';
    }
    $c = trim($carrera);
    if (stripos($c, 'sistemas') !== false) {
        return 'Sistemas Informáticos';
    }
    if (stripos($c, 'contadur') !== false) {
        return 'Contaduría General';
    }
    if (stripos($c, 'secretariado') !== false) {
        return 'Secretariado Ejecutivo';
    }
    if (stripos($c, 'turismo') !== false) {
        return 'Turismo';
    }
    if (stripos($c, 'mercadotecnia') !== false) {
        return 'Mercadotecnia';
    }
    return $c;
}
