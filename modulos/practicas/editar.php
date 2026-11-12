<?php
/**
 * Edición de datos generales de una práctica laboral
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Permite editar los datos generales (área, fechas, horas, descripción).
 * El estudiante, la institución y el convenio no se modifican aquí.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';

verificarPermiso('practicas', 'editar');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$id = (int) ($_GET['id'] ?? 0);
$practica = Practica::obtenerPorId($id);
if ($practica === null) {
    mostrarMensaje('error', 'La práctica solicitada no existe.');
    redirigir('modulos/practicas/listar.php');
}

$previo = $_SESSION['form_practica'] ?? [];
unset($_SESSION['form_practica']);
$d = array_merge($practica, $previo);

$tituloPagina = 'Editar Práctica';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Editar práctica · <?= sanitizar($practica['codigo_practica']) ?></h2>
    <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $id ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-muted small">
            Estudiante: <strong><?= sanitizar($practica['estudiante']) ?></strong> ·
            Institución: <strong><?= sanitizar($practica['institucion']) ?></strong> ·
            Convenio: <strong><?= sanitizar($practica['codigo_convenio']) ?></strong>
        </p>

        <form action="<?= $urlBase ?>/modulos/practicas/procesar.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Área o departamento</label>
                    <input type="text" class="form-control" name="area_departamento"
                           value="<?= sanitizar($d['area_departamento'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Total de horas requeridas *</label>
                    <input type="number" class="form-control" name="total_horas_requeridas" min="1"
                           data-validar="requerido" value="<?= sanitizar($d['total_horas_requeridas']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de inicio *</label>
                    <input type="date" class="form-control" name="fecha_inicio"
                           data-validar="requerido" data-fecha-inicio value="<?= sanitizar($d['fecha_inicio']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de fin prevista *</label>
                    <input type="date" class="form-control" name="fecha_fin_prevista"
                           data-validar="requerido" data-fecha-fin value="<?= sanitizar($d['fecha_fin_prevista']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción de actividades</label>
                    <textarea class="form-control" name="descripcion_actividades" rows="3"><?= sanitizar($d['descripcion_actividades'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
