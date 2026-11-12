<?php
/**
 * Formulario de creación de usuario
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Usuario.php';

verificarPermiso('usuarios', 'crear');

require_once __DIR__ . '/../../clases/Carrera.php';

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$roles = Usuario::obtenerRoles();
$carreras = Carrera::obtenerActivas();

// IDs de los roles de docentes (para mostrar campos de carrera y teléfono).
$rolesDocentesIds = [];
foreach ($roles as $r) {
    if (in_array($r['nombre_rol'], ['docente_encargado', 'docente_supervisor'], true)) {
        $rolesDocentesIds[] = (int) $r['id_rol'];
    }
}
$rolesDocentesStr = implode(',', $rolesDocentesIds);
$idRolSupervisor = $rolesDocentesIds[0] ?? 0;

// Recuperar datos previos en caso de error de validación.
$previo = $_SESSION['form_usuario'] ?? [];
unset($_SESSION['form_usuario']);

$tituloPagina = 'Nuevo Usuario';
$moduloActivo = 'usuarios';
$scriptsPagina = ['assets/js/usuarios.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Nuevo usuario</h2>
    <a href="<?= $urlBase ?>/modulos/usuarios/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/usuarios/procesar.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre_completo" class="form-label">Nombre completo *</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                           data-validar="requerido" value="<?= sanitizar($previo['nombre_completo'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nombre_usuario" class="form-label">Nombre de usuario *</label>
                    <input type="text" class="form-control" id="nombre_usuario" name="nombre_usuario"
                           data-validar="requerido" value="<?= sanitizar($previo['nombre_usuario'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="correo_electronico" class="form-label">Correo electrónico *</label>
                    <input type="email" class="form-control" id="correo_electronico" name="correo_electronico"
                           data-validar="requerido|email" value="<?= sanitizar($previo['correo_electronico'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="id_rol" class="form-label">Rol *</label>
                    <select class="form-select" id="id_rol" name="id_rol" data-validar="requerido"
                            data-roles-docentes="<?= sanitizar($rolesDocentesStr) ?>"
                            data-rol-supervisor="<?= (int) $idRolSupervisor ?>" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id_rol'] ?>" <?= (string) ($previo['id_rol'] ?? '') === (string) $r['id_rol'] ? 'selected' : '' ?>>
                                <?= sanitizar(etiquetaRol($r['nombre_rol'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Campos exclusivos de roles docentes (Carrera y Teléfono) -->
                <div class="col-md-6 campo-supervisor">
                    <label for="carrera" class="form-label">Carrera</label>
                    <select class="form-select" id="carrera" name="carrera">
                        <option value="">Seleccione...</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= sanitizar($c) ?>" <?= ($previo['carrera'] ?? '') === $c ? 'selected' : '' ?>><?= sanitizar($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 campo-supervisor">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="telefono" name="telefono"
                           value="<?= sanitizar($previo['telefono'] ?? '') ?>">
                </div>

                <div class="col-md-6">
                    <label for="contrasena" class="form-label">Contraseña *</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena"
                           data-validar="requerido" minlength="6" required>
                    <div class="form-text">Mínimo 6 caracteres.</div>
                </div>
                <div class="col-md-6">
                    <label for="contrasena2" class="form-label">Confirmar contraseña *</label>
                    <input type="password" class="form-control" id="contrasena2" name="contrasena2"
                           data-validar="requerido" minlength="6" required>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Guardar usuario</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
