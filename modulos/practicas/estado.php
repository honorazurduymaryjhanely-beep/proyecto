<?php
/**
 * Cambio de estado de una práctica laboral
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra las transiciones válidas desde el estado actual y procesa el cambio,
 * registrando el historial (mediante Practica::cambiarEstado).
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Practica.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarPermiso('practicas', 'editar');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$idUsuario = (int) $_SESSION['id_usuario'];

$etiquetasEstado = [
    'pendiente'  => 'Pendiente',
    'en_curso'   => 'En curso',
    'completada' => 'Completada',
    'suspendida' => 'Suspendida',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
        mostrarMensaje('error', 'Token de seguridad inválido.');
        redirigir('modulos/practicas/listar.php');
    }
    $idPractica = (int) ($_POST['id_practica'] ?? 0);
    $nuevoEstado = $_POST['nuevo_estado'] ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');

    [$ok, $mensaje] = Practica::cambiarEstado($idPractica, $nuevoEstado, $idUsuario, $observaciones);
    if ($ok) {
        Auditoria::registrar($idUsuario, 'modificar', 'practicas_laborales', $idPractica, null, ['nuevo_estado' => $nuevoEstado]);
        mostrarMensaje('exito', $mensaje);
    } else {
        mostrarMensaje('error', $mensaje);
    }
    redirigir('modulos/practicas/ver.php?id=' . $idPractica);
}

$id = (int) ($_GET['id'] ?? 0);
$p = Practica::obtenerPorId($id);
if ($p === null) {
    mostrarMensaje('error', 'La práctica solicitada no existe.');
    redirigir('modulos/practicas/listar.php');
}
$transiciones = Practica::TRANSICIONES[$p['estado']] ?? [];

$tituloPagina = 'Cambiar Estado';
$moduloActivo = 'practicas';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Cambiar estado · <?= sanitizar($p['codigo_practica']) ?></h2>
    <a href="<?= $urlBase ?>/modulos/practicas/ver.php?id=<?= (int) $id ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<div class="card">
    <div class="card-body">
        <p>Estado actual:
            <strong><?= sanitizar($etiquetasEstado[$p['estado']] ?? $p['estado']) ?></strong>
        </p>

        <?php if (empty($transiciones)): ?>
            <div class="alert alert-info mb-0">Esta práctica está en un estado final. No admite cambios de estado.</div>
        <?php else: ?>
            <form action="<?= $urlBase ?>/modulos/practicas/estado.php" method="post" data-validar-form novalidate>
                <?= campoCSRF() ?>
                <input type="hidden" name="id_practica" value="<?= (int) $id ?>">

                <div class="mb-3">
                    <label class="form-label">Nuevo estado *</label>
                    <select class="form-select" name="nuevo_estado" data-validar="requerido" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($transiciones as $t): ?>
                            <option value="<?= sanitizar($t) ?>"><?= sanitizar($etiquetasEstado[$t] ?? $t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Actualizar estado</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
