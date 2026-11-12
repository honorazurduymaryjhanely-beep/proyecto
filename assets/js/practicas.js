/**
 * Interacciones del módulo de prácticas
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * 1) Buscadores con autocompletado (estudiante e institución): al escribir,
 *    consultan la base y muestran coincidencias para seleccionar.
 * 2) Carga dinámica de los convenios vigentes de la institución seleccionada.
 */
(function () {
    'use strict';

    /**
     * Escapar texto para insertarlo de forma segura como HTML.
     */
    function escapar(texto) {
        var div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    /**
     * Inicializar un componente de autocompletado.
     *
     * Estructura esperada dentro del contenedor .autocompletar[data-url]:
     *   input.autocompletar-input   -> campo de texto visible
     *   input.autocompletar-id       -> campo oculto con el id seleccionado
     *   div.autocompletar-lista      -> contenedor de resultados
     *
     * @param {HTMLElement} contenedor
     * @param {Function} alSeleccionar  Callback(id) tras elegir un elemento.
     */
    function iniciarAutocompletar(contenedor, alSeleccionar) {
        var url = contenedor.getAttribute('data-url');
        var input = contenedor.querySelector('.autocompletar-input');
        var campoId = contenedor.querySelector('.autocompletar-id');
        var lista = contenedor.querySelector('.autocompletar-lista');
        if (!url || !input || !campoId || !lista) {
            return;
        }

        var temporizador = null;

        function ocultarLista() {
            lista.innerHTML = '';
            lista.classList.remove('autocompletar-lista--abierta');
        }

        function mostrarItems(items) {
            if (!items || items.length === 0) {
                lista.innerHTML = '<div class="list-group-item text-muted">Sin coincidencias</div>';
                lista.classList.add('autocompletar-lista--abierta');
                return;
            }
            var html = '';
            items.forEach(function (item) {
                html += '<button type="button" class="list-group-item list-group-item-action" ' +
                        'data-id="' + escapar(item.id) + '">' + escapar(item.texto) + '</button>';
            });
            lista.innerHTML = html;
            lista.classList.add('autocompletar-lista--abierta');

            Array.prototype.forEach.call(lista.querySelectorAll('button'), function (btn) {
                btn.addEventListener('click', function () {
                    input.value = this.textContent;
                    campoId.value = this.getAttribute('data-id');
                    input.classList.remove('is-invalid');
                    ocultarLista();
                    if (typeof alSeleccionar === 'function') {
                        alSeleccionar(campoId.value);
                    }
                });
            });
        }

        function buscar(termino) {
            fetch(url + '?q=' + encodeURIComponent(termino))
                .then(function (r) { return r.json(); })
                .then(function (data) { mostrarItems(data.items); })
                .catch(function () { ocultarLista(); });
        }

        input.addEventListener('input', function () {
            // Al modificar el texto, se invalida la selección previa.
            campoId.value = '';
            if (typeof alSeleccionar === 'function') {
                alSeleccionar('');
            }
            var termino = this.value.trim();
            clearTimeout(temporizador);
            if (termino.length < 2) {
                ocultarLista();
                return;
            }
            temporizador = setTimeout(function () { buscar(termino); }, 300);
        });

        // Cerrar la lista al hacer clic fuera del componente.
        document.addEventListener('click', function (e) {
            if (!contenedor.contains(e.target)) {
                ocultarLista();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var selConvenio = document.getElementById('id_convenio');
        var aviso = document.getElementById('aviso_convenio');
        var campoInstitucion = document.getElementById('id_institucion');

        /**
         * Cargar los convenios vigentes de una institución en el selector.
         */
        function cargarConvenios(idInstitucion, seleccionado) {
            if (!selConvenio) {
                return;
            }
            if (aviso) {
                aviso.classList.add('d-none');
            }
            if (!idInstitucion) {
                selConvenio.innerHTML = '<option value="">Seleccione primero una institución...</option>';
                return;
            }
            var urlConv = campoInstitucion.getAttribute('data-url-convenios');
            selConvenio.innerHTML = '<option value="">Cargando...</option>';
            fetch(urlConv + '?id_institucion=' + encodeURIComponent(idInstitucion))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.convenios || data.convenios.length === 0) {
                        selConvenio.innerHTML = '<option value="">Sin convenios vigentes</option>';
                        if (aviso) {
                            aviso.classList.remove('d-none');
                        }
                        return;
                    }
                    var opciones = '<option value="">Seleccione...</option>';
                    data.convenios.forEach(function (c) {
                        var sel = (String(c.id_convenio) === String(seleccionado)) ? ' selected' : '';
                        opciones += '<option value="' + c.id_convenio + '"' + sel + '>' + escapar(c.texto) + '</option>';
                    });
                    selConvenio.innerHTML = opciones;
                })
                .catch(function () {
                    selConvenio.innerHTML = '<option value="">Error al cargar convenios</option>';
                });
        }

        // Inicializar los autocompletados del formulario.
        Array.prototype.forEach.call(document.querySelectorAll('.autocompletar'), function (cont) {
            var esInstitucion = cont.querySelector('#id_institucion') !== null;
            iniciarAutocompletar(cont, function (id) {
                if (esInstitucion) {
                    cargarConvenios(id, null);
                }
            });
        });

        // Reenvío tras error: si ya hay institución seleccionada, cargar sus convenios.
        if (campoInstitucion && campoInstitucion.value) {
            cargarConvenios(campoInstitucion.value, null);
        }

        // Al enviar: exigir que cada autocompletado tenga un elemento seleccionado
        // (texto escrito pero sin id no es una selección válida).
        var form = document.querySelector('form[data-validar-form]');
        if (form) {
            form.addEventListener('submit', function (e) {
                var invalido = false;
                Array.prototype.forEach.call(document.querySelectorAll('.autocompletar'), function (cont) {
                    var input = cont.querySelector('.autocompletar-input');
                    var campoId = cont.querySelector('.autocompletar-id');
                    if (input && campoId && input.value.trim() !== '' && campoId.value === '') {
                        input.classList.add('is-invalid');
                        invalido = true;
                    }
                });
                if (invalido) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            }, true);
        }
    });
})();
