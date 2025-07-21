window.addEventListener('load', () => {

    /***********************************
     *  Iniciar plugins
     **********************************/

    notificaciones();
    iniciarCroppie();
    iniciarCkeditor();

    window.addEventListener('click', (e) => {

        /**** Encargado de mostrar un aviso de confirmacion para eliminar ****/
        if (e.target.classList.contains('delete-confirmation')) {
            e.preventDefault();
            const boton = e.target;

            Swal.fire({
                title: '¿Deseas eliminar ' + boton.getAttribute("data-message") + ' ?',
                icon: 'question',
                confirmButtonText: 'Si',
                cancelButtonText: 'No',
                showCancelButton: true,
                showCloseButton: true
            }).then((result) => {
                if (result.isConfirmed) window.location = boton.getAttribute("href");
            });

        } else if (e.target.classList.contains('delete-image-croppie')) {
            e.preventDefault();
            e.target.parentElement.remove();
        } else if (e.target.classList.contains('btn-remove-clone')) {
            e.target.closest('.child-clone').remove();
        }
    });
});

/**********************************
 * Iniciar plugins
 *********************************/

const iniciarCkeditor = () => {

    const editors = document.querySelectorAll('.ckeditor-input');

    if (!editors) return;

    editors.forEach(editor => {
        CKEDITOR.replace(editor, { removeButtons: 'SImage' });
    });
}

const iniciarCroppie = () => {

    const croppies = document.querySelectorAll('.croppie-image');

    if (croppies.length <= 0) return;

    croppies.forEach((image, index) => {
        const defaul_image = image.closest('.croppie-container').querySelector('.default-image-croppie');
        const min_width = image.getAttribute('data-min-width');
        const min_height = image.getAttribute('data-min-height');

        let crop;
        let razon = 1;

        element = document.querySelectorAll('.imagen-input')[index];
        element && element.addEventListener('change', (event) => {
            const file = event.target.files[0];
            const reader = new FileReader();
            const extensions = ['image/jpg', 'image/jpeg', 'image/png'];

            //validamos las extensiones aceptadas
            if (!extensions.includes(file.type)) {
                event.target.value = '';
                return showNotificacion('error', 'La imagen no cumple con el formato.');
            }

            reader.onload = function (e) {

                const newImage = new Image();
                newImage.src = e.target.result;

                newImage.onload = function () {

                    //validamos el tamaño de la imagen
                    if (min_width > this.width || min_height > this.height) {
                        event.target.value = '';
                        return showNotificacion('error', 'La imagen no cumple con el tamaño mínimo.');
                    }

                    if (min_width > 1500) {
                        razon = 4;
                    } else if (min_width >= 500 && min_width < 1500) {
                        razon = 2;
                    }

                    image.classList.remove('d-none');
                    image.classList.add('d-inline-block', 'w-auto');

                    defaul_image.classList.add('d-none');

                    if (crop) crop.destroy();

                    crop = new Croppie(image, {
                        enableExif: true,
                        url: e.target.result,
                        viewport: {
                            width: min_width / razon,
                            height: min_height / razon,
                        },
                        boundary: {
                            width: (min_width / razon) + 30,
                            height: (min_height / razon) + 30
                        }
                    });
                };
            }

            if (file) reader.readAsDataURL(file);
        });

        element = document.querySelectorAll('.cancel-croppie')[index];
        element && element.addEventListener('click', (e) => cancelCroppie(defaul_image, image));

        element = document.querySelectorAll('.add-image-croppie')[index];
        element && element.addEventListener('click', (e) => {

            if (image.classList.contains('d-none')) return;

            const boton = e.currentTarget;
            const isSingleImage = image.classList.contains('single-image');

            crop.result({
                type: 'base64',
                format: 'jpeg|png|jpg',
                quality: 1,
                //size: 'original',
                size: { width: min_width, height: min_height }
            }).then(function (base64) {
                const new_image = document.createElement('img');
                const container = document.createElement('div');
                const input = document.createElement('input');
                const icon = "<button class='btn btn-danger position-absolute delete-image-croppie' type='button' style='right:20px'><i class='fas fa-trash-alt text-white pointer-none'></i></button>";

                new_image.src = base64;
                new_image.classList.add('w-100');

                input.name = "imagenes[]";
                input.type = "hidden";
                input.value = base64;

                container.classList.add('col-sm-6', 'col-md-4', 'pb-5');
                container.append(input);
                container.append(new_image);
                container.innerHTML += icon;


                if (isSingleImage) {
                    const gallery = boton.closest('.croppie-container').querySelector('.images-gallery');
                    gallery.innerHTML = '';
                    gallery.appendChild(container);
                }

                if (!isSingleImage) boton.closest('.croppie-container').querySelector('.images-gallery').append(container);

                boton.closest('.croppie-container').querySelector('.imagen-input').value = '';

                cancelCroppie(defaul_image, image);
            });
        });
    });
}



/**********************************
 * Funciones auxiliares
 *********************************/

const cancelCroppie = (defaul_image, image) => {
    defaul_image.classList.remove('d-none');
    image.classList.add('d-none');
    image.classList.remove('d-inline-block', 'w-auto');
}

const configSweetAlert = () => {
    return Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 8000,
        timerProgressBar: true,
        showCloseButton: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })
}

const themeSweetAlert = (type, message) => {
    return {
        background: type == 'success' ? '#a5dc86' : type == 'error' ? '#f27474' : '#f8bb86',
        icon: type,
        title: message,
        iconColor: '#fff',
        color: '#fff',
        customClass: {
            closeButton: 'text-white',
        },
    }
}

const showNotificacion = (type, message) => {
    const Toast = configSweetAlert();

    Toast.fire(themeSweetAlert(type, message));
}

const showConfirmation = (callback, config) => {

    const style = {
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si',
        cancelButtonText: 'No'
    }

    return Swal.fire({ ...style, ...config }).then(callback);
}

const notificaciones = () => {

    const message = document.getElementById('msg-notify');
    const type = document.getElementById('tipo-notify');
    const route = document.getElementById('ruta-notify');

    if (!message || !type) return;

    const Toast = configSweetAlert();

    Toast.fire(themeSweetAlert(type.value, message.value));

    if (!route) return;
    if (route.value) setTimeout(() => window.location.href = route.value, 2000);

    message.remove();
    type.remove();
    route.remove();
}

const escapeHTML = (str) => {
    return String(str)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}



/////Convertir objeto date javascript a formato dd-mm-yyyy

function formatDate(date, formatoInvertido = false) {

    if (date) {

        let day = date.getDate();
        let month = date.getMonth() + 1;
        let year = date.getFullYear();
        if (month < 10) {
            month = '0' + month;
        }
        if (day < 10) {
            day = '0' + day;
        }

        if (formatoInvertido) {
            return year + '-' + month + '-' + day;
        } else {
            return day + '-' + month + '-' + year;
        }

    } else {
        return '';
    }

}

//formatear fecha yy-mm-dd a dd-mm-yy
function format_fecha(texto) {
    return texto.replace(/^(\d{4})-(\d{2})-(\d{2})$/g, '$3-$2-$1');
}

//function invertir formato fecha
function reverseDate(date) {
    return date.split("-").reverse().join("-")
}

//2024-03-23 21:55:00 al formato yyyy-MM-dd
function formatearFecha(fechaStr) {

    // Comprobar si la fecha es "0000-00-00 00:00:00"
    if (fechaStr === "0000-00-00 00:00:00") {
        return '';
    }

    const fecha = new Date(fechaStr);

    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0'); // Los meses en JavaScript van de 0 a 11, así que sumamos 1
    const day = String(fecha.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}


//2024-03-23 21:55:00 al formato dd/mm/yyyy
function formatearFechaMostrar(fechaStr) {

    // Comprobar si la fecha es "0000-00-00 00:00:00"
    if (fechaStr === "0000-00-00 00:00:00") {
        return '';
    }
    const fecha = new Date(fechaStr);
    const year = fecha.getFullYear();
    const month = String(fecha.getMonth() + 1).padStart(2, '0'); // Los meses en JavaScript van de 0 a 11, así que sumamos 1
    const day = String(fecha.getDate()).padStart(2, '0');
    return `${day}/${month}/${year}`;

}



function isValidDate(d) {
    return d instanceof Date && !isNaN(d);
}


//valida el rango de fechas
function ajustarFechas($desde, $hasta) {
    // Añadir evento change a ambos campos
    $desde.on('change', function () {
        // Obtener fechas
        const fechaDesde = new Date($desde.val());
        const fechaHasta = new Date($hasta.val());

        // Comparar fechas y ajustar si es necesario
        if (fechaDesde > fechaHasta) {
            $hasta.val($desde.val());
        }
    });

    $hasta.on('change', function () {
        // Obtener fechas
        const fechaDesde = new Date($desde.val());
        const fechaHasta = new Date($hasta.val());

        // Comparar fechas y ajustar si es necesario
        if (fechaHasta < fechaDesde) {
            $desde.val($hasta.val());
        }
    });
}

//valida solo ingrese fecha igual o superior a la actual
function validarFechaActual($elementoFecha) {
    // Añadir evento change al campo de fecha
    $elementoFecha.on('change', function () {
        // Obtener la fecha actual
        const fechaActual = new Date();

        // Obtener la fecha seleccionada por el usuario
        const fechaSeleccionada = new Date($elementoFecha.val());
        // Restar un día a la fecha seleccionada
        fechaSeleccionada.setDate(fechaSeleccionada.getDate() + 1);

        // Comparar la fecha seleccionada con la fecha actual
        if (fechaSeleccionada < fechaActual) {
            showNotificacion('error', 'La fecha no puede ser anterior a la actual');
            $elementoFecha.val(''); // Limpiar el campo si la fecha es inválida
        }
    });
}



//formatear número con separador de miles
function format_number(input) {
    var num = input.toString().replace(/\./g, '');
    if (!isNaN(num)) {
        num = num.toString().split('').reverse().join('').replace(/(?=\d*\.?)(\d{3})/g, '$1.');
        num = num.split('').reverse().join('').replace(/^[\.]/, '');
        return num;
    }
    return '';
}


//999999.9 se convierte en 999.999,9
function formatearNumero(numero, numeroDecimales = 1) {
    // Verificar si el número es un entero
    const esEntero = numero % 1 === 0;

    // Determinar el número máximo de dígitos decimales
    const maxDigitosDecimales = esEntero ? 0 : numeroDecimales;

    // Utilizar Intl.NumberFormat con opciones personalizadas
    const formatoNumero = new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: maxDigitosDecimales,
        maximumFractionDigits: maxDigitosDecimales,
        minimumIntegerDigits: 1,
        useGrouping: true,
    });

    // Formatear el número y devolverlo
    return formatoNumero.format(numero);
}

//hace la inversa de la funcion de arriba
function transformarNumero(numero) {

    // Eliminar puntos
    numero = numero.replace(/\./g, '');
    // Reemplazar comas por puntos
    numero = numero.replace(/,/g, '.');
    return numero
}


//subir el scroll

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth' // Puedes cambiar a 'auto' para un desplazamiento instantáneo
    });
}


/**********************************
 * Funciones devextreme
 *********************************/

function sendRequest(url, method, data) { // función para solicitudes de devxtreme

    var d = $.Deferred();
    method = method || "GET";
    $.ajax(url, {
        method: method || "GET",
        data: data,
        cache: false,
        xhrFields: {
            withCredentials: true
        }
    }).done(function (result) {
        /* d.resolve(method === "GET" ? result.data : result); */
        d.resolve(result);
    }).fail(function (xhr) {
        if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
            Object.keys(xhr.responseJSON.errors).reverse().forEach(key => {
                xhr.responseJSON.errors[key].forEach(errorMessage => {
                    showNotificacion('error', errorMessage);
                });
            });
        } else {
            showNotificacion('error', 'Error al obtener los datos');
        }
        d.reject(xhr.responseJSON ? xhr.responseJSON.Message : xhr.statusText);
    });
    return d.promise();
}

$.fn.multiline = function (text) { // función para salto de lineas en maestro detalle (cuando se apreta un botón para mostrar harto texto por ejemplo)
    this.text(text);
    this.html(this.html().replace(/\n/g, '<br/>'));
    return this;
}
