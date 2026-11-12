/**
 * Gráfico del panel administrativo
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Dibuja un gráfico de barras horizontales de prácticas por estado sin
 * dependencias externas (solo DOM/CSS), respetando el requisito de
 * funcionamiento local sin CDN.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var contenedor = document.getElementById('graficoPracticas');
        if (!contenedor) {
            return;
        }

        var datos = [
            { etiqueta: 'Pendiente', valor: parseInt(contenedor.dataset.pendiente, 10) || 0, color: '#FFC107' },
            { etiqueta: 'En curso', valor: parseInt(contenedor.dataset.enCurso, 10) || 0, color: '#1B3A6B' },
            { etiqueta: 'Completada', valor: parseInt(contenedor.dataset.completada, 10) || 0, color: '#28A745' },
            { etiqueta: 'Suspendida', valor: parseInt(contenedor.dataset.suspendida, 10) || 0, color: '#DC3545' }
        ];

        var maximo = datos.reduce(function (max, d) {
            return Math.max(max, d.valor);
        }, 0);

        if (maximo === 0) {
            contenedor.innerHTML = '<p class="text-muted mb-0">Sin datos de prácticas para graficar.</p>';
            return;
        }

        var html = '';
        datos.forEach(function (d) {
            var porcentaje = Math.round((d.valor / maximo) * 100);
            html += '<div class="grafico-barra-fila">';
            html += '  <div class="grafico-barra-etiqueta">' + d.etiqueta + '</div>';
            html += '  <div class="grafico-barra-pista">';
            html += '    <div class="grafico-barra-relleno" style="width:' + porcentaje + '%;background-color:' + d.color + ';">';
            html += '      <span class="grafico-barra-valor">' + d.valor + '</span>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';
        });

        contenedor.innerHTML = html;
    });
})();
