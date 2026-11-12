/**
 * Interacciones del módulo de usuarios
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Muestra u oculta los campos de carrera y teléfono según el rol seleccionado.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var selRol = document.getElementById('id_rol');
        if (!selRol) {
            return;
        }
        var rolesDocentes = (selRol.getAttribute('data-roles-docentes') || selRol.getAttribute('data-rol-supervisor') || '').split(',');
        var campos = document.querySelectorAll('.campo-supervisor');

        function actualizar() {
            var val = String(selRol.value);
            var esDocente = rolesDocentes.indexOf(val) !== -1;
            campos.forEach(function (c) {
                c.style.display = esDocente ? '' : 'none';
            });
        }

        selRol.addEventListener('change', actualizar);
        selRol.addEventListener('input', actualizar);
        actualizar(); // estado inicial
    });
})();
