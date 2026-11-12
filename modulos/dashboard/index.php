<?php
/**
 * Panel Administrativo (Dashboard)
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra indicadores generales del sistema, las últimas prácticas
 * registradas o modificadas y alertas sobre convenios próximos a expirar
 * y prácticas fuera de plazo.
 */

require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

// Requiere sesión activa y permiso de lectura sobre el dashboard.
verificarPermiso('dashboard', 'ver');

$db = Database::obtenerInstancia();

/**
 * Ejecutar una consulta escalar de forma segura, devolviendo 0 ante error.
 */
function contarSeguro(PDO $db, string $sql): int
{
    try {
        return (int) $db->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        error_log('Dashboard - error en consulta: ' . $e->getMessage());
        return 0;
    }
}

// --- Indicadores generales --------------------------------------------------
$totalEstudiantes    = contarSeguro($db, 'SELECT COUNT(*) FROM estudiantes WHERE estado = 1');
$totalInstituciones  = contarSeguro($db, 'SELECT COUNT(*) FROM instituciones_receptoras WHERE estado = 1');
$totalConvenios      = contarSeguro($db, "SELECT COUNT(*) FROM convenios WHERE estado = 'vigente' AND CURDATE() BETWEEN fecha_inicio_vigencia AND fecha_fin_vigencia");

// Prácticas por estado (para tarjetas y gráfico).
$practicasPorEstado = [
    'pendiente'  => 0,
    'en_curso'   => 0,
    'completada' => 0,
    'suspendida' => 0,
];
try {
    $stmt = $db->query('SELECT estado, COUNT(*) AS total FROM practicas_laborales GROUP BY estado');
    foreach ($stmt->fetchAll() as $fila) {
        if (isset($practicasPorEstado[$fila['estado']])) {
            $practicasPorEstado[$fila['estado']] = (int) $fila['total'];
        }
    }
} catch (Throwable $e) {
    error_log('Dashboard - error práticas por estado: ' . $e->getMessage());
}
$totalPracticas = array_sum($practicasPorEstado);

// --- Últimas 5 prácticas registradas o modificadas --------------------------
$ultimasPracticas = [];
try {
    $sql = "SELECT p.codigo_practica, p.estado, p.fecha_creacion, p.fecha_modificacion,
                   e.nombre_completo AS estudiante, i.nombre AS institucion
            FROM practicas_laborales p
            INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
            INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
            ORDER BY COALESCE(p.fecha_modificacion, p.fecha_creacion) DESC
            LIMIT 5";
    $ultimasPracticas = $db->query($sql)->fetchAll();
} catch (Throwable $e) {
    error_log('Dashboard - error últimas prácticas: ' . $e->getMessage());
}

// --- Alertas: convenios próximos a expirar (< 30 días) ----------------------
$conveniosPorExpirar = [];
try {
    $sql = "SELECT c.codigo_convenio, c.titulo, c.fecha_fin_vigencia,
                   i.nombre AS institucion,
                   DATEDIFF(c.fecha_fin_vigencia, CURDATE()) AS dias_restantes
            FROM convenios c
            INNER JOIN instituciones_receptoras i ON i.id_institucion = c.id_institucion
            WHERE c.estado = 'vigente'
              AND c.fecha_fin_vigencia >= CURDATE()
              AND c.fecha_fin_vigencia <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            ORDER BY c.fecha_fin_vigencia ASC";
    $conveniosPorExpirar = $db->query($sql)->fetchAll();
} catch (Throwable $e) {
    error_log('Dashboard - error convenios por expirar: ' . $e->getMessage());
}

// --- Alertas: prácticas fuera de plazo --------------------------------------
$practicasFueraPlazo = [];
try {
    $sql = "SELECT p.codigo_practica, p.fecha_fin_prevista,
                   e.nombre_completo AS estudiante, i.nombre AS institucion,
                   DATEDIFF(CURDATE(), p.fecha_fin_prevista) AS dias_vencidos
            FROM practicas_laborales p
            INNER JOIN estudiantes e ON e.id_estudiante = p.id_estudiante
            INNER JOIN instituciones_receptoras i ON i.id_institucion = p.id_institucion
            WHERE p.estado = 'en_curso'
              AND p.fecha_fin_prevista < CURDATE()
            ORDER BY p.fecha_fin_prevista ASC";
    $practicasFueraPlazo = $db->query($sql)->fetchAll();
} catch (Throwable $e) {
    error_log('Dashboard - error prácticas fuera de plazo: ' . $e->getMessage());
}

// Etiquetas y clases de badge por estado.
$badgesEstado = [
    'pendiente'  => ['Pendiente', 'badge-estado-pendiente'],
    'en_curso'   => ['En curso', 'badge-estado-en-curso'],
    'completada' => ['Completada', 'badge-estado-completada'],
    'suspendida' => ['Suspendida', 'badge-estado-suspendida'],
];

$tituloPagina = 'Panel Principal';
$moduloActivo = 'dashboard';
$scriptsPagina = ['assets/js/dashboard.js'];

require __DIR__ . '/../../includes/header.php';
?>

<!-- Indicadores generales -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card card-dashboard h-100">
            <div class="card-body">
                <div class="card-icon bg-primario">&#128101;</div>
                <p class="card-valor"><?= (int) $totalEstudiantes ?></p>
                <p class="card-etiqueta">Estudiantes activos</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-dashboard h-100">
            <div class="card-body">
                <div class="card-icon bg-secundario">&#127970;</div>
                <p class="card-valor"><?= (int) $totalInstituciones ?></p>
                <p class="card-etiqueta">Instituciones activas</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-dashboard h-100">
            <div class="card-body">
                <div class="card-icon bg-acento">&#128196;</div>
                <p class="card-valor"><?= (int) $totalConvenios ?></p>
                <p class="card-etiqueta">Convenios vigentes</p>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card card-dashboard h-100">
            <div class="card-body">
                <div class="card-icon bg-info">&#128188;</div>
                <p class="card-valor"><?= (int) $totalPracticas ?></p>
                <p class="card-etiqueta">Prácticas totales</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Gráfico de prácticas por estado -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Prácticas por estado</div>
            <div class="card-body">
                <div id="graficoPracticas"
                     data-pendiente="<?= (int) $practicasPorEstado['pendiente'] ?>"
                     data-en-curso="<?= (int) $practicasPorEstado['en_curso'] ?>"
                     data-completada="<?= (int) $practicasPorEstado['completada'] ?>"
                     data-suspendida="<?= (int) $practicasPorEstado['suspendida'] ?>">
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas prácticas -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Últimas prácticas registradas o modificadas</div>
            <div class="card-body p-0">
                <div class="tabla-personalizada">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Estudiante</th>
                                <th>Institución</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ultimasPracticas)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Sin prácticas registradas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($ultimasPracticas as $p): ?>
                                    <?php [$etiqueta, $clase] = $badgesEstado[$p['estado']] ?? [$p['estado'], 'bg-secondary']; ?>
                                    <tr>
                                        <td><?= sanitizar($p['codigo_practica']) ?></td>
                                        <td><?= sanitizar($p['estudiante']) ?></td>
                                        <td><?= sanitizar($p['institucion']) ?></td>
                                        <td><span class="badge <?= sanitizar($clase) ?>"><?= sanitizar($etiqueta) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alertas -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card card-alerta h-100">
            <div class="card-header bg-white fw-semibold">Convenios próximos a expirar (30 días)</div>
            <div class="card-body">
                <?php if (empty($conveniosPorExpirar)): ?>
                    <p class="text-muted mb-0">No hay convenios próximos a expirar.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($conveniosPorExpirar as $c): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>
                                    <strong><?= sanitizar($c['titulo']) ?></strong><br>
                                    <small class="text-muted"><?= sanitizar($c['institucion']) ?> · <?= sanitizar($c['codigo_convenio']) ?></small>
                                </span>
                                <span class="badge bg-warning text-dark">
                                    <?= (int) $c['dias_restantes'] ?> día(s)
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-alerta alerta-peligro h-100">
            <div class="card-header bg-white fw-semibold">Prácticas fuera de plazo</div>
            <div class="card-body">
                <?php if (empty($practicasFueraPlazo)): ?>
                    <p class="text-muted mb-0">No hay prácticas fuera de plazo.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($practicasFueraPlazo as $p): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span>
                                    <strong><?= sanitizar($p['estudiante']) ?></strong><br>
                                    <small class="text-muted"><?= sanitizar($p['institucion']) ?> · <?= sanitizar($p['codigo_practica']) ?></small>
                                </span>
                                <span class="badge badge-estado-fuera-plazo">
                                    +<?= (int) $p['dias_vencidos'] ?> día(s)
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
