<?php
/**
 * Importación de estudiantes desde Excel (.xlsx)
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Columnas esperadas (en este orden, con fila de encabezado):
 *   A: Nombre completo
 *   B: Cédula
 *   C: Fecha de nacimiento (YYYY-MM-DD o DD/MM/AAAA)
 *   D: Carrera (debe estar registrada)
 *   E: Curso (1ro, 2do, 3ro)
 *   F: Paralelo (A, B...) [opcional]
 *   G: Teléfono [opcional]
 *   H: Correo electrónico [opcional]
 *   I: Dirección [opcional]
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../clases/Estudiante.php';
require_once __DIR__ . '/../../clases/LectorExcel.php';
require_once __DIR__ . '/../../clases/Auditoria.php';

verificarPermiso('estudiantes', 'crear');

$config = require __DIR__ . '/../../config/app.php';
$urlBase = rtrim($config['url_base'], '/');
$idUsuario = (int) $_SESSION['id_usuario'];

// Restricción de carrera para el docente encargado.
$restringirCarrera = restriccionCarreraActual();

// Descarga de plantilla de ejemplo para importación.
if (isset($_GET['plantilla'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="plantilla_estudiantes.csv"');
    $salida = fopen('php://output', 'w');
    fprintf($salida, "\xEF\xBB\xBF");
    fputcsv($salida, [
        'Nombre completo',
        'Cédula',
        'Fecha nacimiento (AAAA-MM-DD)',
        'Carrera',
        'Curso (1er Año/2do Año/3ro Año)',
        'Paralelo',
        'Teléfono',
        'Correo electrónico',
        'Dirección',
    ]);
    fputcsv($salida, [
        'Juan Carlos Pérez López',
        '87654321',
        '2003-05-20',
        $restringirCarrera ?: 'Sistemas Informáticos',
        '1er Año',
        'A',
        '71234567',
        'juan.perez@itsec.edu.bo',
        'Av. Las Delicias #123',
    ]);
    fclose($salida);
    exit;
}

$resultado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validarTokenCSRF($_POST['token_csrf'] ?? '')) {
        mostrarMensaje('error', 'Token de seguridad inválido.');
        redirigir('modulos/estudiantes/importar.php');
    }
    if (empty($_FILES['archivo']['name']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        mostrarMensaje('error', 'Debe seleccionar un archivo Excel válido.');
        redirigir('modulos/estudiantes/importar.php');
    }

    $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
    if ($ext !== 'xlsx') {
        mostrarMensaje('error', 'El archivo debe tener formato .xlsx');
        redirigir('modulos/estudiantes/importar.php');
    }

    try {
        $filasCrudas = LectorExcel::leer($_FILES['archivo']['tmp_name']);

        // Mapear columnas por posición, descartando la fila de encabezado.
        $filas = [];
        foreach (array_slice($filasCrudas, 1) as $f) {
            $filas[] = [
                'nombre_completo'    => $f[0] ?? '',
                'cedula_identidad'   => $f[1] ?? '',
                'fecha_nacimiento'   => $f[2] ?? '',
                'carrera'            => $f[3] ?? '',
                'curso'              => $f[4] ?? '',
                'paralelo'           => $f[5] ?? '',
                'telefono'           => $f[6] ?? '',
                'correo_electronico' => $f[7] ?? '',
                'direccion'          => $f[8] ?? '',
            ];
        }

        $resultado = Estudiante::importarLote($filas, $idUsuario, $restringirCarrera);

        Auditoria::registrar($idUsuario, 'crear', 'estudiantes', null, null, [
            'importacion' => true,
            'insertados'  => $resultado['insertados'],
            'omitidos'    => $resultado['omitidos'],
        ]);

        if ($resultado['insertados'] > 0) {
            mostrarMensaje('exito', "Importación finalizada: {$resultado['insertados']} estudiante(s) agregado(s), {$resultado['omitidos']} omitido(s).");
        } else {
            mostrarMensaje('advertencia', 'No se importó ningún estudiante. Revise el detalle.');
        }
    } catch (Throwable $e) {
        error_log('Error al importar Excel: ' . $e->getMessage());
        mostrarMensaje('error', 'No se pudo procesar el archivo: ' . $e->getMessage());
        redirigir('modulos/estudiantes/importar.php');
    }
}

$tituloPagina = 'Importar Estudiantes';
$moduloActivo = 'estudiantes';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Importar estudiantes desde Excel</h2>
    <div class="d-flex gap-2">
        <a href="?plantilla=1" class="btn btn-outline-primary btn-sm">Descargar plantilla de ejemplo</a>
        <a href="<?= $urlBase ?>/modulos/estudiantes/listar.php" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <p class="mb-2">Suba un archivo <strong>.xlsx</strong> con una fila de encabezado y las siguientes columnas en este orden:</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>A</th><th>B</th><th>C</th><th>D</th><th>E</th><th>F</th><th>G</th><th>H</th><th>I</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nombre completo</td>
                        <td>Cédula</td>
                        <td>Fecha nac. (AAAA-MM-DD)</td>
                        <td>Carrera</td>
                        <td>Curso (1ro/2do/3ro)</td>
                        <td>Paralelo</td>
                        <td>Teléfono</td>
                        <td>Correo</td>
                        <td>Dirección</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="small text-muted mb-0">
            La carrera debe estar registrada en el catálogo. Las filas con cédula duplicada o datos inválidos se omiten.
            <?php if ($restringirCarrera !== null): ?>
                <br>Solo se importarán estudiantes de la carrera <strong><?= sanitizar($restringirCarrera) ?></strong>.
            <?php endif; ?>
        </p>

        <form action="<?= $urlBase ?>/modulos/estudiantes/importar.php" method="post" enctype="multipart/form-data" class="mt-3">
            <?= campoCSRF() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <label class="form-label">Archivo Excel (.xlsx) *</label>
                    <input type="file" class="form-control" name="archivo" accept=".xlsx" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Importar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($resultado !== null && !empty($resultado['errores'])): ?>
<div class="card">
    <div class="card-header bg-white fw-semibold">Detalle de filas omitidas (<?= count($resultado['errores']) ?>)</div>
    <div class="card-body">
        <ul class="mb-0 small">
            <?php foreach ($resultado['errores'] as $er): ?>
                <li><?= sanitizar($er) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
