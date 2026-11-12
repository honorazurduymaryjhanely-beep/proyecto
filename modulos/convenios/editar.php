<?php
/**
 * Formulario de edición de convenio
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Convenio.php';
require_once __DIR__ . '/../../clases/Institucion.php';

verificarPermiso('convenios', 'editar');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');

$id = (int) ($_GET['id'] ?? 0);
$convenio = Convenio::obtenerPorId($id);
if ($convenio === null) {
    mostrarMensaje('error', 'El convenio solicitado no existe.');
    redirigir('modulos/convenios/listar.php');
}

$previo = $_SESSION['form_convenio'] ?? [];
unset($_SESSION['form_convenio']);
$d = array_merge($convenio, $previo);

$instituciones = Institucion::obtenerActivas();

$tituloPagina = 'Editar Convenio';
$moduloActivo = 'convenios';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Editar convenio <small class="text-muted"><?= sanitizar($convenio['codigo_convenio']) ?></small></h2>
    <a href="<?= $urlBase ?>/modulos/convenios/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/convenios/procesar.php" method="post"
              enctype="multipart/form-data" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="id" value="<?= (int) $convenio['id_convenio'] ?>">

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Título *</label>
                    <input type="text" class="form-control" name="titulo"
                           data-validar="requerido" value="<?= sanitizar($d['titulo']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Institución *</label>
                    <select class="form-select" name="id_institucion" data-validar="requerido" required>
                        <?php foreach ($instituciones as $i): ?>
                            <option value="<?= (int) $i['id_institucion'] ?>" <?= (string) $d['id_institucion'] === (string) $i['id_institucion'] ? 'selected' : '' ?>>
                                <?= sanitizar($i['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="3"><?= sanitizar($d['descripcion'] ?? '') ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha de firma *</label>
                    <input type="date" class="form-control" name="fecha_firma"
                           data-validar="requerido" value="<?= sanitizar($d['fecha_firma']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Inicio de vigencia *</label>
                    <input type="date" class="form-control" name="fecha_inicio_vigencia"
                           data-validar="requerido" data-fecha-inicio value="<?= sanitizar($d['fecha_inicio_vigencia']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fin de vigencia *</label>
                    <input type="date" class="form-control" name="fecha_fin_vigencia"
                           data-validar="requerido" data-fecha-fin value="<?= sanitizar($d['fecha_fin_vigencia']) ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Reemplazar documento adjunto (PDF)</label>
                    <input type="file" class="form-control" name="documento" accept="application/pdf">
                    <?php if (!empty($convenio['documento_adjunto'])): ?>
                        <div class="form-text">
                            Documento actual:
                            <a href="<?= $urlBase ?>/uploads/convenios/<?= rawurlencode($convenio['documento_adjunto']) ?>" target="_blank">
                                <?= sanitizar($convenio['documento_adjunto']) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
