<?php
/**
 * Funciones de autenticación y control de acceso
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Verifica la autenticación del usuario, la vigencia de la sesión y los
 * permisos por rol/módulo/acción. Debe incluirse al inicio de toda página
 * protegida, después de config/session.php.
 */

require_once __DIR__ . '/helpers.php';

/**
 * Verificar que exista una sesión de usuario autenticado.
 * Si no hay sesión activa, redirige al login.
 *
 * @return void
 */
function verificarAutenticacion(): void
{
    if (empty($_SESSION['id_usuario'])) {
        mostrarMensaje('error', 'Debe iniciar sesión para acceder a esta página.');
        redirigir('modulos/autenticacion/login.php');
    }
}

/**
 * Verificar que la sesión siga activa (no expirada por inactividad).
 * El control de expiración principal reside en config/session.php; esta
 * función es una comprobación adicional de existencia de sesión.
 *
 * @return bool true si la sesión está activa
 */
function verificarSesionActiva(): bool
{
    return !empty($_SESSION['id_usuario']);
}

/**
 * Obtener el árbol de permisos correspondiente a un rol.
 *
 * Lee la columna JSON `permisos` de la tabla `roles`. Si el rol ya tiene
 * los permisos cargados en sesión, los reutiliza para evitar consultas.
 *
 * @param string $rol Nombre del rol (ej: administrador, docente, consulta)
 * @return array Estructura de permisos [modulo => [accion => bool]]
 */
function obtenerPermisosPorRol(string $rol): array
{
    // Reutilizar permisos ya cargados en la sesión del usuario actual.
    if (!empty($_SESSION['rol']) && $_SESSION['rol'] === $rol && !empty($_SESSION['permisos'])) {
        return $_SESSION['permisos'];
    }

    require_once __DIR__ . '/../config/database.php';

    try {
        $db = Database::obtenerInstancia();
        $sql = 'SELECT permisos FROM roles WHERE nombre_rol = :rol AND estado = 1 LIMIT 1';
        $stmt = $db->prepare($sql);
        $stmt->execute([':rol' => $rol]);
        $fila = $stmt->fetch();

        if (!$fila || empty($fila['permisos'])) {
            return [];
        }

        $permisos = json_decode($fila['permisos'], true);
        return is_array($permisos) ? $permisos : [];
    } catch (Throwable $e) {
        error_log('Error al obtener permisos del rol: ' . $e->getMessage());
        return [];
    }
}

/**
 * Verificar si el usuario autenticado tiene permiso para una acción sobre
 * un módulo específico.
 *
 * @param string $modulo Nombre del módulo (ej: estudiantes, practicas)
 * @param string $accion Acción requerida: ver, crear, editar, eliminar
 * @return bool true si tiene el permiso
 */
function tienePermiso(string $modulo, string $accion = 'ver'): bool
{
    if (empty($_SESSION['rol'])) {
        return false;
    }

    // Administrador siempre tiene acceso total a todos los módulos y acciones
    if ($_SESSION['rol'] === 'administrador') {
        return true;
    }

    // Docente Encargado: Privilegios de Administrador en su Carrera.
    // Puede ver, crear, editar, inactivar y eliminar estudiantes y prácticas de su carrera,
    // y asignar docentes supervisores a sus estudiantes.
    if ($_SESSION['rol'] === 'docente_encargado') {
        // En estudiantes, prácticas y supervisores: privilegios completos
        if (in_array($modulo, ['estudiantes', 'practicas', 'supervisores'], true)) {
            return true;
        }
        // En módulos informativos / operativos: acceso total
        if (in_array($modulo, ['instituciones', 'convenios', 'reportes', 'dashboard', 'busqueda'], true)) {
            return true;
        }
        // Módulos reservados exclusivamente al Administrador global del sistema
        if (in_array($modulo, ['usuarios', 'respaldos', 'auditoria', 'carreras'], true)) {
            return false;
        }
        return false;
    }

    // Docente Supervisor: solo lectura de sus prácticas y estudiantes asignados
    if ($_SESSION['rol'] === 'docente_supervisor') {
        if (in_array($modulo, ['practicas', 'estudiantes', 'instituciones', 'convenios', 'reportes', 'dashboard', 'busqueda'], true)) {
            return $accion === 'ver';
        }
        return false;
    }

    $permisos = obtenerPermisosPorRol($_SESSION['rol']);
    return !empty($permisos[$modulo][$accion]);
}

/**
 * Exigir un permiso concreto para continuar. Si el usuario no lo tiene,
 * registra el intento en auditoría (si es posible), muestra un mensaje y
 * redirige al dashboard.
 *
 * @param string $modulo Nombre del módulo
 * @param string $accion Acción requerida (por defecto: ver)
 * @return void
 */
function verificarPermiso(string $modulo, string $accion = 'ver'): void
{
    verificarAutenticacion();

    if (!tienePermiso($modulo, $accion)) {
        // Registrar el acceso denegado en auditoría.
        $rutaAuditoria = __DIR__ . '/../clases/Auditoria.php';
        if (file_exists($rutaAuditoria) && !empty($_SESSION['id_usuario'])) {
            require_once $rutaAuditoria;
            if (class_exists('Auditoria')) {
                Auditoria::registrar(
                    (int) $_SESSION['id_usuario'],
                    'acceso_denegado',
                    $modulo,
                    null,
                    null,
                    ['accion_denegada' => $accion]
                );
            }
        }

        mostrarMensaje('error', 'No tiene permisos para realizar esta acción.');
        redirigir('modulos/dashboard/index.php');
    }
}

/**
 * Obtener los datos básicos del usuario autenticado desde la sesión.
 *
 * @return array{id:int,nombre:string,usuario:string,rol:string}|null
 */
function usuarioActual(): ?array
{
    if (empty($_SESSION['id_usuario'])) {
        return null;
    }

    return [
        'id'      => (int) $_SESSION['id_usuario'],
        'nombre'  => $_SESSION['nombre_completo'] ?? '',
        'usuario' => $_SESSION['nombre_usuario'] ?? '',
        'rol'     => $_SESSION['rol'] ?? '',
    ];
}

/**
 * Indicar si el usuario autenticado es un docente encargado.
 *
 * @return bool
 */
function esDocenteEncargado(): bool
{
    $rol = $_SESSION['rol'] ?? '';
    return in_array($rol, ['docente_encargado', 'docente_supervisor'], true);
}

/**
 * Obtener la carrera a la que se debe restringir el alcance del usuario
 * actual. Para un docente encargado devuelve su carrera; para el resto de
 * roles (administrador, consulta) devuelve null (sin restricción).
 *
 * @return string|null
 */
function restriccionCarreraActual(): ?string
{
    if (esDocenteEncargado()) {
        $carrera = trim((string) ($_SESSION['carrera'] ?? ''));
        if ($carrera === '') {
            return null;
        }
        return normalizarCarrera($carrera);
    }
    return null;
}
