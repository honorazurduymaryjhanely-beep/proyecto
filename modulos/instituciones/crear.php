<?php
/**
 * Formulario de creación de institución receptora
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';

verificarPermiso('instituciones', 'crear');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$d = $_SESSION['form_institucion'] ?? [];
unset($_SESSION['form_institucion']);

$tituloPagina = 'Nueva Institución';
$moduloActivo = 'instituciones';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Nueva institución</h2>
    <a href="<?= $urlBase ?>/modulos/instituciones/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/instituciones/procesar.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Nombre *</label>
                    <input type="text" class="form-control" name="nombre"
                           data-validar="requerido" value="<?= sanitizar($d['nombre'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">NIT *</label>
                    <input type="text" class="form-control" name="nit"
                           data-validar="requerido|nit" value="<?= sanitizar($d['nit'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Dirección *</label>
                    <input type="text" class="form-control" name="direccion"
                           data-validar="requerido" value="<?= sanitizar($d['direccion'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono institución *</label>
                    <input type="text" class="form-control" name="telefono"
                           data-validar="requerido" value="<?= sanitizar($d['telefono'] ?? '') ?>" required>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" name="correo_electronico"
                           data-validar="email" value="<?= sanitizar($d['correo_electronico'] ?? '') ?>">
                </div>

                <!-- Máxima Autoridad Ejecutiva (MAE) -->
                <div class="col-12"><hr class="my-1"><h3 class="h6 text-primary mb-0">Máxima Autoridad Ejecutiva (MAE)</h3></div>
                <div class="col-md-6">
                    <label class="form-label">Nombre de la MAE</label>
                    <input type="text" class="form-control" name="nombre_mae"
                           value="<?= sanitizar($d['nombre_mae'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Teléfono de la MAE</label>
                    <input type="text" class="form-control" name="telefono_mae"
                           value="<?= sanitizar($d['telefono_mae'] ?? '') ?>">
                </div>

                <!-- Persona de contacto -->
                <div class="col-12"><hr class="my-1"><h3 class="h6 text-primary mb-0">Persona de contacto</h3></div>
                <div class="col-md-4">
                    <label class="form-label">Nombre del contacto *</label>
                    <input type="text" class="form-control" name="nombre_contacto"
                           data-validar="requerido" value="<?= sanitizar($d['nombre_contacto'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cargo del contacto *</label>
                    <input type="text" class="form-control" name="cargo_contacto"
                           data-validar="requerido" value="<?= sanitizar($d['cargo_contacto'] ?? '') ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Teléfono del contacto</label>
                    <input type="text" class="form-control" name="telefono_contacto"
                           value="<?= sanitizar($d['telefono_contacto'] ?? '') ?>">
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Guardar institución</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
