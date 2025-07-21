// import { formatearRut, separarUnidadMil } from "./utils/index.js";

/**
* Solo números
* 
* Agregar la clase "solo-numeros".
*/
const elementos = document.querySelectorAll('.solo-numeros');

Array.from(elementos).forEach(elemento => {
    elemento.addEventListener('keydown', function (event) {
        if (!(/[0-9\/b]/i.test(event.key))) {
            event.preventDefault();
        };
    });

    elemento.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    })
});

/**
 * Solo letras
 * 
 * Agregar la clase "solo-letras".
 */
const elementosLetras = document.querySelectorAll('.solo-letras');

Array.from(elementosLetras).forEach(elemento => {
    elemento.addEventListener('keydown', function (event) {
        const key = event.key;
        const validCharacters = /[A-Za-zÁ-Úá-ú ]/;

        // Verificar si la tecla presionada es válida (letra con tilde o espacio)
        if (!validCharacters.test(key) && !(event.ctrlKey && key === "v")) {
            event.preventDefault();
        }
    });

    elemento.addEventListener('paste', function (event) {
        const clipboardData = event.clipboardData || window.clipboardData;
        const pastedText = clipboardData.getData('text');
        const validCharacters = /^[A-Za-zÁ-Úá-ú ]*$/;

        // Verificar si el texto pegado contiene solo caracteres válidos
        if (!validCharacters.test(pastedText)) {
            event.preventDefault();
        }
    });
});

/**
 * Separador de unidad de mil
 */
const elementosUnidadMil = document.querySelectorAll('.unidad-mil-separada');

Array.from(elementosUnidadMil).forEach(elemento => {
    elemento.addEventListener('input', function () {
        this.value = separarUnidadMil(this.value);
    });
});

/**
 * Formatear rut
 */
const elementosRut = document.getElementsByClassName('formato-rut');

Array.from(elementosRut).forEach(elemento => {
    elemento.addEventListener('input', function () {
        this.value = formatearRut(this.value);
    });
});
