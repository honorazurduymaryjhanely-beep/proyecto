/**
 * Validaciones del lado cliente
 * Sistema de Gestión de Prácticas Laborales - ITSEC SUCRE
 *
 * Proporciona validaciones de formularios en el navegador para mejorar la
 * experiencia de usuario. La validación definitiva siempre ocurre en el
 * servidor; estas comprobaciones son un primer filtro.
 */
(function () {
    'use strict';

    /**
     * Validar una cédula de identidad: solo dígitos, entre 5 y 10 caracteres.
     * @param {string} valor
     * @returns {boolean}
     */
    function validarCedula(valor) {
        return /^\d{5,10}$/.test(String(valor).trim());
    }

    /**
     * Validar el formato de un correo electrónico.
     * @param {string} valor
     * @returns {boolean}
     */
    function validarEmail(valor) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(valor).trim());
    }

    /**
     * Validar un NIT: dígitos con guion y dígito verificador opcionales.
     * @param {string} valor
     * @returns {boolean}
     */
    function validarNit(valor) {
        return /^\d{5,15}(-\d{1,3})?$/.test(String(valor).trim());
    }

    /**
     * Validar que un campo no esté vacío.
     * @param {string} valor
     * @returns {boolean}
     */
    function validarRequerido(valor) {
        return String(valor).trim().length > 0;
    }

    /**
     * Validar que la fecha de inicio sea anterior o igual a la fecha de fin.
     * @param {string} inicio  Fecha en formato YYYY-MM-DD
     * @param {string} fin     Fecha en formato YYYY-MM-DD
     * @returns {boolean}
     */
    function validarRangoFechas(inicio, fin) {
        if (!inicio || !fin) {
            return true;
        }
        return new Date(inicio) <= new Date(fin);
    }

    /**
     * Marcar un campo como inválido y mostrar un mensaje de error.
     * @param {HTMLElement} campo
     * @param {string} mensaje
     */
    function marcarInvalido(campo, mensaje) {
        campo.classList.add('is-invalid');
        var feedback = campo.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            campo.parentNode.appendChild(feedback);
        }
        feedback.textContent = mensaje;
    }

    /**
     * Limpiar el estado de error de un campo.
     * @param {HTMLElement} campo
     */
    function limpiarError(campo) {
        campo.classList.remove('is-invalid');
    }

    /**
     * Validar un formulario según los atributos data-validar de sus campos.
     * Tipos soportados: cedula, email, nit, requerido.
     * @param {HTMLFormElement} form
     * @returns {boolean} true si el formulario es válido
     */
    function validarFormulario(form) {
        var valido = true;
        var campos = form.querySelectorAll('[data-validar]');

        campos.forEach(function (campo) {
            var tipos = campo.getAttribute('data-validar').split('|');
            var valor = campo.value;
            limpiarError(campo);

            tipos.forEach(function (tipo) {
                if (campo.classList.contains('is-invalid')) {
                    return;
                }
                switch (tipo) {
                    case 'requerido':
                        if (!validarRequerido(valor)) {
                            marcarInvalido(campo, 'Este campo es obligatorio.');
                            valido = false;
                        }
                        break;
                    case 'cedula':
                        if (valor && !validarCedula(valor)) {
                            marcarInvalido(campo, 'La cédula debe tener entre 5 y 10 dígitos.');
                            valido = false;
                        }
                        break;
                    case 'email':
                        if (valor && !validarEmail(valor)) {
                            marcarInvalido(campo, 'Ingrese un correo electrónico válido.');
                            valido = false;
                        }
                        break;
                    case 'nit':
                        if (valor && !validarNit(valor)) {
                            marcarInvalido(campo, 'Ingrese un NIT válido.');
                            valido = false;
                        }
                        break;
                }
            });
        });

        // Validar rangos de fechas (campos con data-fecha-inicio / data-fecha-fin).
        var inicioCampo = form.querySelector('[data-fecha-inicio]');
        var finCampo = form.querySelector('[data-fecha-fin]');
        if (inicioCampo && finCampo && !validarRangoFechas(inicioCampo.value, finCampo.value)) {
            marcarInvalido(finCampo, 'La fecha de fin debe ser posterior o igual a la de inicio.');
            valido = false;
        }

        return valido;
    }

    // Enlazar la validación al envío de todos los formularios marcados.
    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('form[data-validar-form]');
        forms.forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!validarFormulario(form)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });
    });

    // Exponer utilidades en el ámbito global por si otras páginas las requieren.
    window.Validaciones = {
        cedula: validarCedula,
        email: validarEmail,
        nit: validarNit,
        requerido: validarRequerido,
        rangoFechas: validarRangoFechas,
        validarFormulario: validarFormulario
    };
})();
