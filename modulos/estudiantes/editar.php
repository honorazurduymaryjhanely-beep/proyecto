<?php
/**
 * Formulario de edición de estudiante
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Estudiante.php';
require_once __DIR__ . '/../../clases/Carrera.php';

verificarPermiso('estudiantes', 'editar');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$id = (int) ($_GET['id'] ?? 0);
$estudiante = Estudiante::obtenerPorId($id);
if ($estudiante === null) {
    mostrarMensaje('error', 'El estudiante solicitado no existe.');
    redirigir('modulos/estudiantes/listar.php');
}

$restringirCarrera = restriccionCarreraActual();
if ($restringirCarrera !== null && normalizarCarrera($estudiante['carrera']) !== normalizarCarrera($restringirCarrera)) {
    mostrarMensaje('error', 'No tiene permisos para modificar estudiantes de otra carrera.');
    redirigir('modulos/estudiantes/listar.php');
}

$previo = $_SESSION['form_estudiante'] ?? [];
unset($_SESSION['form_estudiante']);
$d = array_merge($estudiante, $previo);

// Carreras activas del catálogo, incluyendo la actual del estudiante aunque
// esté inactiva, para no perder el valor guardado.
$carreras = Carrera::obtenerActivas();
if (!empty($d['carrera']) && !in_array($d['carrera'], $carreras, true)) {
    $carreras[] = $d['carrera'];
    sort($carreras);
}

$tituloPagina = 'Editar Estudiante';
$moduloActivo = 'estudiantes';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Editar estudiante <small class="text-muted"><?= sanitizar($estudiante['codigo_estudiante']) ?></small></h2>
    <a href="<?= $urlBase ?>/modulos/estudiantes/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/estudiantes/procesar.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id" value="<?= (int) $estudiante['id_estudiante'] ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre completo *</label>
                    <input type="text" class="form-control" name="nombre_completo"
                           data-validar="requerido" value="<?= sanitizar($d['nombre_completo']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cédula de identidad *</label>
                    <input type="text" class="form-control" name="cedula_identidad"
                           data-validar="requerido|cedula" value="<?= sanitizar($d['cedula_identidad']) ?>" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fecha de nacimiento *</label>
                    <input type="date" class="form-control" name="fecha_nacimiento"
                           data-validar="requerido" value="<?= sanitizar($d['fecha_nacimiento']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Carrera *</label>
                    <?php if ($restringirCarrera !== null): ?>
                        <input type="text" class="form-control" value="<?= sanitizar($restringirCarrera) ?>" readonly>
                        <input type="hidden" name="carrera" value="<?= sanitizar($restringirCarrera) ?>">
                    <?php else: ?>
                        <select class="form-select" name="carrera" data-validar="requerido" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($carreras as $c): ?>
                                <option value="<?= sanitizar($c) ?>" <?= $d['carrera'] === $c ? 'selected' : '' ?>><?= sanitizar($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Curso *</label>
                    <select class="form-select" name="curso" data-validar="requerido" required>
                        <option value="">Seleccione...</option>
                        <?php foreach (['1ro','2do','3ro'] as $c): ?>
                            <option value="<?= $c ?>" <?= ($d['curso'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Paralelo</label>
                    <input type="text" class="form-control" name="paralelo" maxlength="10"
                           placeholder="A, B..." value="<?= sanitizar($d['paralelo'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-control" name="telefono" value="<?= sanitizar($d['telefono'] ?? '') ?>">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" name="correo_electronico"
                           data-validar="email" value="<?= sanitizar($d['correo_electronico'] ?? '') ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección</label>
                    <input type="text" class="form-control" name="direccion" value="<?= sanitizar($d['direccion'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
