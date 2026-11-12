<?php
/**
 * Gestión del catálogo de carreras
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Permite al administrador ver, agregar y activar/inactivar carreras.
 * Se protege con el permiso del módulo de usuarios (acceso administrativo).
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Carrera.php';

verificarPermiso('usuarios', 'ver');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$carreras = Carrera::listar();
$puedeGestionar = tienePermiso('usuarios', 'crear');

$tituloPagina = 'Catálogo de Carreras';
$moduloActivo = 'carreras';
require __DIR__ . '/../../includes/header.php';
?>

<h2 class="h5 mb-3">Catálogo de carreras</h2>

<?php if ($puedeGestionar): ?>
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Agregar carrera</div>
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/carreras/procesar.php" method="post" class="row g-2" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="accion" value="crear">
            <div class="col-md-8">
                <input type="text" class="form-control" name="nombre" data-validar="requerido"
                       placeholder="Nombre de la carrera" maxlength="100" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">Agregar carrera</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="tabla-personalizada">
    <table class="table mb-0">
        <thead>
            <tr>
                <th>Carrera</th>
                <th>Estado</th>
                <?php if ($puedeGestionar): ?><th class="text-end">Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($carreras)): ?>
                <tr><td colspan="<?= $puedeGestionar ? 3 : 2 ?>" class="text-center text-muted py-3">No hay carreras registradas.</td></tr>
            <?php else: ?>
                <?php foreach ($carreras as $c): ?>
                    <tr>
                        <td><?= sanitizar($c['nombre']) ?></td>
                        <td>
                            <?php if ((int) $c['estado'] === 1): ?>
                                <span class="badge bg-success">Activa</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <?php if ($puedeGestionar): ?>
                            <td class="text-end acciones-tabla">
                                <form action="<?= $urlBase ?>/modulos/carreras/procesar.php" method="post" class="d-inline"
                                      onsubmit="return confirm('¿Confirma cambiar el estado de esta carrera?');">
                                    <?= campoCSRF() ?>
                                    <input type="hidden" name="accion" value="cambiar_estado">
                                    <input type="hidden" name="id" value="<?= (int) $c['id_carrera'] ?>">
                                    <input type="hidden" name="estado" value="<?= (int) $c['estado'] === 1 ? 0 : 1 ?>">
                                    <button type="submit" class="btn btn-outline-<?= (int) $c['estado'] === 1 ? 'danger' : 'success' ?>">
                                        <?= (int) $c['estado'] === 1 ? 'Inactivar' : 'Activar' ?>
                                    </button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
