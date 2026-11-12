<?php
/**
 * Componente de paginación reutilizable
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Renderiza la barra de paginación y el resumen de registros. Conserva los
 * filtros activos en los enlaces de página.
 *
 * Variables esperadas antes de incluir:
 *   $paginacion  array  Resultado de paginar() con: pagina, total_paginas,
 *                       inicio, fin, total.
 *   $qsFiltros   string Query string de filtros (sin el parámetro 'pagina').
 *   $etiquetaRegistros string (opcional) Nombre plural de los registros.
 */

$qsFiltros = $qsFiltros ?? '';
$etiquetaRegistros = $etiquetaRegistros ?? 'registros';
?>
<?php if (($paginacion['total_paginas'] ?? 1) > 1): ?>
<nav class="mt-3">
    <ul class="pagination pagination-sm justify-content-center">
        <?php for ($i = 1; $i <= $paginacion['total_paginas']; $i++): ?>
            <li class="page-item <?= $i === $paginacion['pagina'] ? 'active' : '' ?>">
                <a class="page-link" href="?pagina=<?= $i ?><?= $qsFiltros ? '&' . $qsFiltros : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<p class="text-muted small mt-2">
    Mostrando <?= (int) $paginacion['inicio'] ?>-<?= (int) $paginacion['fin'] ?>
    de <?= (int) $paginacion['total'] ?> <?= sanitizar($etiquetaRegistros) ?>.
</p>
