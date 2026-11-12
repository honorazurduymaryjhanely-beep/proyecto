/**
 * Búsqueda general asíncrona
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Consulta el endpoint de búsqueda mientras el usuario escribe (con debounce)
 * y renderiza los resultados agrupados por categoría con enlaces al detalle.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.getElementById('busqueda_global');
        var contenedor = document.getElementById('resultados_busqueda');
        if (!input || !contenedor) {
            return;
        }

        var urlBusqueda = input.getAttribute('data-url');
        var urlBase = input.getAttribute('data-base');
        var temporizador = null;

        /**
         * Escapar texto para evitar inyección de HTML.
         */
        function escapar(texto) {
            var div = document.createElement('div');
            div.textContent = texto == null ? '' : String(texto);
            return div.innerHTML;
        }

        function render(categorias) {
            if (!categorias || categorias.length === 0) {
                contenedor.innerHTML = '<p class="text-muted">No se encontraron resultados.</p>';
                return;
            }
            var html = '';
            categorias.forEach(function (cat) {
                html += '<div class="card mb-3">';
                html += '  <div class="card-header bg-white fw-semibold">' + escapar(cat.etiqueta) +
                        ' <span class="badge bg-secondary">' + cat.items.length + '</span></div>';
                html += '  <ul class="list-group list-group-flush">';
                cat.items.forEach(function (item) {
                    var url = urlBase + '/' + cat.ruta + '?id=' + encodeURIComponent(item.id);
                    html += '<li class="list-group-item d-flex justify-content-between align-items-center">';
                    html += '  <span><a href="' + url + '">' + escapar(item.titulo) + '</a><br>' +
                            '<small class="text-muted">' + escapar(item.subtitulo) + '</small></span>';
                    html += '</li>';
                });
                html += '  </ul>';
                html += '</div>';
            });
            contenedor.innerHTML = html;
        }

        function buscar(termino) {
            if (termino.length < 2) {
                contenedor.innerHTML = '<p class="text-muted">Escribe al menos 2 caracteres.</p>';
                return;
            }
            contenedor.innerHTML = '<p class="text-muted">Buscando...</p>';
            fetch(urlBusqueda + '?q=' + encodeURIComponent(termino))
                .then(function (r) { return r.json(); })
                .then(function (data) { render(data.categorias); })
                .catch(function () {
                    contenedor.innerHTML = '<p class="text-danger">Error al realizar la búsqueda.</p>';
                });
        }

        input.addEventListener('input', function () {
            var termino = this.value.trim();
            clearTimeout(temporizador);
            temporizador = setTimeout(function () {
                buscar(termino);
            }, 300);
        });
    });
})();
