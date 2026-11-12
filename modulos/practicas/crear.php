<?php
/**
 * Formulario de registro de práctica laboral
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Estudiante.php';
require_once __DIR__ . '/../../clases/Institucion.php';
require_once __DIR__ . '/../../clases/Convenio.php';
require_once __DIR__ . '/../../clases/DocenteSupervisor.php';

verificarPermiso('practicas', 'crear');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$restringirCarrera = restriccionCarreraActual();
$supervisoresDisponibles = DocenteSupervisor::obtenerActivos($restringirCarrera);
if (empty($supervisoresDisponibles)) {
    $supervisoresDisponibles = DocenteSupervisor::obtenerActivos();
}

$d = $_SESSION['form_practica'] ?? [];
unset($_SESSION['form_practica']);

// Si venimos de un reenvío tras error, recuperar las etiquetas de los valores
// previamente seleccionados para mostrarlas en los buscadores.
$estudianteTexto = '';
$institucionTexto = '';
if (!empty($d['id_estudiante'])) {
    $prev = Estudiante::obtenerPorId((int) $d['id_estudiante']);
    if ($prev) {
        $estudianteTexto = $prev['nombre_completo'] . ' (' . $prev['codigo_estudiante'] . ')';
    }
}
if (!empty($d['id_institucion'])) {
    $prevI = Institucion::obtenerPorId((int) $d['id_institucion']);
    if ($prevI) {
        $institucionTexto = $prevI['nombre'] . ' (' . $prevI['codigo_institucion'] . ')';
    }
}

$tituloPagina = 'Registrar Práctica';
$moduloActivo = 'practicas';
$scriptsPagina = ['assets/js/practicas.js'];
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Registrar práctica laboral</h2>
    <a href="<?= $urlBase ?>/modulos/practicas/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <form action="<?= $urlBase ?>/modulos/practicas/procesar.php" method="post" data-validar-form novalidate>
            <?= campoCSRF() ?>
            <input type="hidden" name="accion" value="crear">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="busca_estudiante">Estudiante *</label>
                    <div class="autocompletar" data-url="<?= $urlBase ?>/modulos/practicas/buscar_estudiante.php">
                        <input type="text" class="form-control autocompletar-input" id="busca_estudiante"
                               placeholder="Escriba nombre, cédula o código..." autocomplete="off"
                               data-validar="requerido" value="<?= sanitizar($estudianteTexto) ?>" required>
                        <input type="hidden" name="id_estudiante" class="autocompletar-id"
                               value="<?= sanitizar($d['id_estudiante'] ?? '') ?>">
                        <div class="autocompletar-lista list-group"></div>
                    </div>
                    <div class="form-text">Comience a escribir para buscar el estudiante.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="busca_institucion">Institución *</label>
                    <div class="autocompletar" data-url="<?= $urlBase ?>/modulos/practicas/buscar_institucion.php">
                        <input type="text" class="form-control autocompletar-input" id="busca_institucion"
                               placeholder="Escriba nombre, NIT o código..." autocomplete="off"
                               data-validar="requerido" value="<?= sanitizar($institucionTexto) ?>" required>
                        <input type="hidden" name="id_institucion" id="id_institucion" class="autocompletar-id"
                               data-url-convenios="<?= $urlBase ?>/modulos/practicas/convenios_institucion.php"
                               value="<?= sanitizar($d['id_institucion'] ?? '') ?>">
                        <div class="autocompletar-lista list-group"></div>
                    </div>
                    <div class="form-text">Comience a escribir para buscar la institución.</div>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Convenio vigente *</label>
                    <select class="form-select" id="id_convenio" name="id_convenio" data-validar="requerido" required>
                        <option value="">Seleccione primero una institución...</option>
                    </select>
                    <div id="aviso_convenio" class="form-text text-danger d-none">
                        No existe un convenio vigente con la institución seleccionada.
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Área o departamento</label>
                    <input type="text" class="form-control" name="area_departamento"
                           value="<?= sanitizar($d['area_departamento'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="id_docente_supervisor">Docente Supervisor a cargo</label>
                    <select class="form-select" id="id_docente_supervisor" name="id_docente_supervisor">
                        <option value="">-- Asignar supervisor más tarde --</option>
                        <?php foreach ($supervisoresDisponibles as $ds): ?>
                            <option value="<?= (int) $ds['id_docente'] ?>" <?= (!empty($d['id_docente_supervisor']) && (int) $d['id_docente_supervisor'] === (int) $ds['id_docente']) ? 'selected' : '' ?>>
                                <?= sanitizar($ds['nombre_completo']) ?> (<?= sanitizar($ds['carrera']) ?>)<?= !empty($ds['telefono']) ? ' — Tel: ' . sanitizar($ds['telefono']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Docente que supervisará y acompañará al estudiante en la institución.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Total de horas requeridas *</label>
                    <input type="number" class="form-control" name="total_horas_requeridas" min="1"
                           data-validar="requerido" value="<?= sanitizar($d['total_horas_requeridas'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de inicio *</label>
                    <input type="date" class="form-control" name="fecha_inicio"
                           data-validar="requerido" data-fecha-inicio value="<?= sanitizar($d['fecha_inicio'] ?? '') ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de fin prevista *</label>
                    <input type="date" class="form-control" name="fecha_fin_prevista"
                           data-validar="requerido" data-fecha-fin value="<?= sanitizar($d['fecha_fin_prevista'] ?? '') ?>" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción de actividades</label>
                    <textarea class="form-control" name="descripcion_actividades" rows="3"><?= sanitizar($d['descripcion_actividades'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Registrar práctica</button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
