<?php
/**
 * Perfil de usuario y cambio de contraseña
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Usuario.php';
require_once __DIR__ . '/../../clases/Auditoria.php';
require_once __DIR__ . '/../../config/database.php';

verificarAutenticacion();

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$idUsuario = (int) $_SESSION['id_usuario'];
$db = Database::obtenerInstancia();

// Cargar información completa del perfil
$stmt = $db->prepare('SELECT u.*, r.nombre_rol FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol WHERE u.id_usuario = :id LIMIT 1');
$stmt->execute([':id' => $idUsuario]);
$perfil = $stmt->fetch();

if (!$perfil) {
    mostrarMensaje('error', 'Usuario no encontrado en el sistema.');
    redirigir('modulos/dashboard/index.php');
}

// Procesar formulario POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
        mostrarMensaje('error', 'Token de seguridad inválido. Intente nuevamente.');
        redirigir('modulos/usuarios/perfil.php');
    }

    $accion = $_POST['accion'] ?? 'password';

    if ($accion === 'actualizar_datos') {
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $correo = trim($_POST['correo_electronico'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        $errores = [];
        if ($nombreCompleto === '') {
            $errores[] = 'El nombre completo es requerido.';
        }
        if ($correo === '' || !validarEmail($correo)) {
            $errores[] = 'El correo electrónico no es válido.';
        } else {
            // Validar unicidad del correo
            $chk = $db->prepare('SELECT id_usuario FROM usuarios WHERE correo_electronico = :correo AND id_usuario != :id LIMIT 1');
            $chk->execute([':correo' => $correo, ':id' => $idUsuario]);
            if ($chk->fetch()) {
                $errores[] = 'El correo electrónico ya está registrado por otro usuario.';
            }
        }

        if (!empty($errores)) {
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/usuarios/perfil.php');
        }

        $sql = 'UPDATE usuarios SET nombre_completo = :nombre, correo_electronico = :correo, telefono = :telefono WHERE id_usuario = :id';
        $upd = $db->prepare($sql);
        if ($upd->execute([':nombre' => $nombreCompleto, ':correo' => $correo, ':telefono' => $telefono, ':id' => $idUsuario])) {
            $_SESSION['nombre_completo'] = $nombreCompleto;
            Auditoria::registrar($idUsuario, 'modificar', 'usuarios', $idUsuario, null, [
                'nombre_completo' => $nombreCompleto,
                'correo_electronico' => $correo,
                'telefono' => $telefono,
            ]);
            mostrarMensaje('exito', 'Datos de perfil actualizados correctamente.');
        } else {
            mostrarMensaje('error', 'No se pudieron actualizar los datos.');
        }
        redirigir('modulos/usuarios/perfil.php');

    } elseif ($accion === 'password') {
        $actual = $_POST['contrasena_actual'] ?? '';
        $nueva = $_POST['contrasena_nueva'] ?? '';
        $confirmar = $_POST['contrasena_confirmar'] ?? '';

        $errores = [];

        // Validar contraseña actual
        if (!password_verify($actual, $perfil['contrasena_hash'])) {
            $errores[] = 'La contraseña actual no es correcta.';
        }

        // Validar nueva
        if (strlen($nueva) < 6) {
            $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        }
        if ($nueva !== $confirmar) {
            $errores[] = 'La confirmación de la contraseña no coincide.';
        }

        if (!empty($errores)) {
            mostrarMensaje('error', implode(' ', $errores));
            redirigir('modulos/usuarios/perfil.php');
        }

        // Actualizar hash
        $hash = password_hash($nueva, PASSWORD_DEFAULT);
        $upd = $db->prepare('UPDATE usuarios SET contrasena_hash = :hash WHERE id_usuario = :id');
        if ($upd->execute([':hash' => $hash, ':id' => $idUsuario])) {
            Auditoria::registrar($idUsuario, 'modificar', 'usuarios', $idUsuario, null, ['cambio_contrasena' => true]);
            mostrarMensaje('exito', 'Contraseña actualizada correctamente.');
        } else {
            mostrarMensaje('error', 'No se pudo actualizar la contraseña.');
        }
        redirigir('modulos/usuarios/perfil.php');
    }
}

$tituloPagina = 'Mi Perfil';
$moduloActivo = '';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="h5 mb-0">Mi Perfil</h2>
        <small class="text-muted">Administre su información personal y credenciales de acceso</small>
    </div>
</div>

<div class="row g-3">
    <!-- Información de cuenta y edición de datos -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                Mis Datos Personales
            </div>
            <div class="card-body">
                <form action="<?= $urlBase ?>/modulos/usuarios/perfil.php" method="post" data-validar-form novalidate>
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="actualizar_datos">

                    <div class="mb-3">
                        <label class="form-label text-muted small">Nombre de usuario (Login)</label>
                        <input type="text" class="form-control bg-light" value="<?= sanitizar($perfil['nombre_usuario']) ?>" readonly disabled>
                        <div class="form-text">El nombre de usuario no puede ser modificado.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small">Rol en el sistema</label>
                        <div>
                            <span class="badge bg-primary px-3 py-2"><?= sanitizar(etiquetaRol($perfil['nombre_rol'] ?? '')) ?></span>
                            <?php if (!empty($perfil['carrera'])): ?>
                                <span class="badge bg-info text-dark px-3 py-2"><?= sanitizar($perfil['carrera']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label">Nombre completo *</label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                               data-validar="requerido" value="<?= sanitizar($perfil['nombre_completo']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="correo_electronico" class="form-label">Correo electrónico *</label>
                        <input type="email" class="form-control" id="correo_electronico" name="correo_electronico"
                               data-validar="requerido|email" value="<?= sanitizar($perfil['correo_electronico']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono / Celular</label>
                        <input type="text" class="form-control" id="telefono" name="telefono"
                               value="<?= sanitizar($perfil['telefono'] ?? '') ?>" placeholder="Ej: 71234567">
                    </div>

                    <div class="mb-3 text-muted small">
                        <strong>Último acceso:</strong>
                        <?= !empty($perfil['ultimo_acceso']) ? date('d/m/Y H:i:s', strtotime($perfil['ultimo_acceso'])) : 'Primer acceso registrado' ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar Cambios de Perfil</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Cambio de contraseña -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                Cambiar Contraseña
            </div>
            <div class="card-body">
                <form action="<?= $urlBase ?>/modulos/usuarios/perfil.php" method="post" data-validar-form novalidate>
                    <?= campoCSRF() ?>
                    <input type="hidden" name="accion" value="password">

                    <div class="mb-3">
                        <label for="contrasena_actual" class="form-label">Contraseña actual *</label>
                        <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual"
                               data-validar="requerido" required autocomplete="current-password">
                        <div class="form-text">Ingrese su contraseña actual para confirmar su identidad.</div>
                    </div>

                    <div class="mb-3">
                        <label for="contrasena_nueva" class="form-label">Nueva contraseña *</label>
                        <input type="password" class="form-control" id="contrasena_nueva" name="contrasena_nueva"
                               data-validar="requerido" minlength="6" required autocomplete="new-password">
                        <div class="form-text">Debe tener un mínimo de 6 caracteres.</div>
                    </div>

                    <div class="mb-4">
                        <label for="contrasena_confirmar" class="form-label">Confirmar nueva contraseña *</label>
                        <input type="password" class="form-control" id="contrasena_confirmar" name="contrasena_confirmar"
                               data-validar="requerido" minlength="6" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-warning">Actualizar Contraseña</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
