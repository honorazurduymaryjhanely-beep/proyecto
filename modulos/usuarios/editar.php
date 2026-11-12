<?php
/**
 * Formulario de edición de usuario
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Usuario.php';

verificarPermiso('usuarios', 'editar');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$id = (int) ($_GET['id'] ?? 0);
$usuario = Usuario::obtenerPorId($id);

if ($usuario === null) {
    mostrarMensaje('error', 'El usuario solicitado no existe.');
    redirigir('modulos/usuarios/listar.php');
}

// Datos previos tras error de validación tienen prioridad sobre los de BD.
$previo = $_SESSION['form_usuario'] ?? [];
unset($_SESSION['form_usuario']);
$datos = array_merge($usuario, $previo);

$roles = Usuario::obtenerRoles();
require_once __DIR__ . '/../../clases/Carrera.php';
$carreras = Carrera::obtenerActivas();

$rolesDocentesIds = [];
foreach ($roles as $r) {
    if (in_array($r['nombre_rol'], ['docente_encargado', 'docente_supervisor'], true)) {
        $rolesDocentesIds[] = (int) $r['id_rol'];
    }
}
$rolesDocentesStr = implode(',', $rolesDocentesIds);
$idRolSupervisor = $rolesDocentesIds[0] ?? 0;

$tituloPagina = 'Editar Usuario';
$moduloActivo = 'usuarios';
$scriptsPagina = ['assets/js/usuarios.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Editar usuario</h2>
    <a href="<?= $urlBase ?>/modulos/usuarios/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/usuarios/procesar.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id" value="<?= (int) $usuario['id_usuario'] ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre_completo" class="form-label">Nombre completo *</label>
                    <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                           data-validar="requerido" value="<?= sanitizar($datos['nombre_completo']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="nombre_usuario" class="form-label">Nombre de usuario</label>
                    <input type="text" class="form-control" id="nombre_usuario"
                           value="<?= sanitizar($datos['nombre_usuario']) ?>" readonly>
                    <div class="form-text">El nombre de usuario no puede modificarse.</div>
                </div>
                <div class="col-md-6">
                    <label for="correo_electronico" class="form-label">Correo electrónico *</label>
                    <input type="email" class="form-control" id="correo_electronico" name="correo_electronico"
                           data-validar="requerido|email" value="<?= sanitizar($datos['correo_electronico']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="id_rol" class="form-label">Rol *</label>
                    <select class="form-select" id="id_rol" name="id_rol" data-validar="requerido"
                            data-roles-docentes="<?= sanitizar($rolesDocentesStr) ?>"
                            data-rol-supervisor="<?= (int) $idRolSupervisor ?>" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int) $r['id_rol'] ?>" <?= (string) $datos['id_rol'] === (string) $r['id_rol'] ? 'selected' : '' ?>>
                                <?= sanitizar(etiquetaRol($r['nombre_rol'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Campos exclusivos del rol Docente Encargado -->
                <div class="col-md-6 campo-supervisor">
                    <label for="carrera" class="form-label">Carrera</label>
                    <select class="form-select" id="carrera" name="carrera">
                        <option value="">Seleccione...</option>
                        <?php foreach ($carreras as $c): ?>
                            <option value="<?= sanitizar($c) ?>" <?= ($datos['carrera'] ?? '') === $c ? 'selected' : '' ?>><?= sanitizar($c) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 campo-supervisor">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" class="form-control" id="telefono" name="telefono"
                           value="<?= sanitizar($datos['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label for="contrasena" class="form-label">Nueva contraseña</label>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" minlength="6">
                    <div class="form-text">Dejar en blanco para conservar la contraseña actual.</div>
                </div>
                <div class="col-md-6">
                    <label for="contrasena2" class="form-label">Confirmar nueva contraseña</label>
                    <input type="password" class="form-control" id="contrasena2" name="contrasena2" minlength="6">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
