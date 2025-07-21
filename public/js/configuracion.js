window.addEventListener('load', () => {
    /******************************************************************
     * Si existe alguna notificaciones en session flash
     * entonces sera desplegada
     *******************************************************************/
    notifications();

    window.addEventListener('click', (e) => {

        /******************************************************************
         * Encargado de mostrar un aviso de confirmacion para eliminar
         *******************************************************************/

        if (e.target.classList.contains('delete-confirmation')) {
            e.preventDefault();
            const boton = e.target;
            const message = `¿Deseas eliminar ${boton.getAttribute("data-message")}?`;
            const redirect = boton.getAttribute("href") ?? boton.getAttribute("data-link");

            confirmDelete({ message, redirect });
        }

        /********************************************
          * Encargado de limpiar el mensaje
          * cuando interactuamos con nice-select
          *******************************************/

        if (e.target.classList.contains('nice-select') || e.target.closest('.nice-select')) {
            const niceContent = e.target.closest('.nice-select');
            const parent = niceContent.parentElement;
            const select = parent.querySelector('select');
            const error = parent.querySelector(`#error-${select.name}`);

            if (!error) return;

            error.textContent = '';
        }


        /********************************************
         * Encargado de limpiar los mensajes
         * de error en formularios
         *******************************************/
        if (e.target.closest('form')) {
            const form = e.target.closest('form');
            const error = form.querySelector(`#error-${e.target.id}`);

            if (!error) return;

            error.textContent = '';
        }
    });

    window.addEventListener('input', (e) => {
        if (e.target.closest('form')) {
            const form = e.target.closest('form');
            const error = form.querySelector(`#error-${e.target.id}`);

            if (!error) return;

            error.textContent = '';
        }
    });
});

/**********************************
 * SweetAlert
 *********************************/

// Fix conflicto selec2css con sweetalert
const addSweetAlertCSS = () => {
    const style = document.createElement('style');
    style.id = 'sweetalert-select2-hide';
    style.innerHTML = `
        .swal2-container .select2-container {
            display: none !important;
        }
    `;
    document.head.appendChild(style);
};

const removeSweetAlertCSS = () => {
    const style = document.getElementById('sweetalert-select2-hide');
    if (style) {
        document.head.removeChild(style);
    }
};

const configSweetAlert = () => {
    return Swal.mixin({
        toast: true,
        position: 'top',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        showCloseButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        iconColor: '#333',
        didOpen: (toast) => {
            addSweetAlertCSS();
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        },
        didClose: () => {
            removeSweetAlertCSS();
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

export const showNotification = (type, message) => {
    const Toast = configSweetAlert();

    Toast.fire(themeSweetAlert(type, message));
}

const notifications = () => {

    const message = document.getElementById('msg-notify');
    const type = document.getElementById('type-notify');
    const route = document.getElementById('route-notify');

    if (!message || !type) return;

    const Toast = configSweetAlert();

    Toast.fire(themeSweetAlert(type.value, message.value));

    if (!route) return;
    if (route.value) setTimeout(() => window.location.href = route.value, 2000);

    message.remove();
    type.remove();
    route.remove();
}

// enviamos el mensaje que deseamos mostrar en la alerta,
//junto con una funcion que permitira gatillar cualquier accion adicional
export const confirmDelete = ({ message, redirect = null, onConfirmed = null }) => {
    Swal.mixin({
        customClass: {
            confirmButton: '!bg-primary focus:!shadow-md focus:!shadow-primary/50',
            cancelButton: '!bg-error focus:!shadow-md focus:!shadow-error/50'
        },
    }).fire({
        title: message,
        icon: 'question',
        confirmButtonText: 'Si',
        cancelButtonText: 'No',
        showCancelButton: true,
        showCloseButton: true
    }).then((result) => {
        if (result.isConfirmed && redirect) return window.location = redirect;
        if (result.isConfirmed && onConfirmed) return onConfirmed();
    });
}

/*************************************
 * Errors form
*************************************/

const showErrorsForm = (errors) => {
    const names = Object.keys(errors);

    //setear el error debajo del input
    names.forEach(name => {
        const indice = name.indexOf('.');
        const resultado = indice !== -1 ? name.substring(0, indice) : name;

        document.querySelector(`#error-${resultado}`).textContent = errors[name][0];
    });
}

/*************************************
 * Fetch form
*************************************/

const toggleButton = (button) => {
    const spinner = button?.querySelector('.loading-spinner');
    const span = button.querySelector('span');

    //si existe un boton asociado a la peticion
    //entonces lo deshabilitamos o habilitamos
    button.classList.toggle('pointer-events-none');

    //si existe un spiner dentro del boton
    //lo mostramos o ocultamos al iniciar la peticion o terminar
    if (spinner) {
        span.classList.toggle('hidden');
        spinner.classList.toggle('hidden');
    }
}

export const fetchRoute = async ({ data, method = 'GET', url, button = null, delay = 400 }) => {

    let result = null;

    if (button) toggleButton(button);

    //seteamos un delay a la peticion
    //en dado caso que se requiera mostrar al usuario
    //que el contenido esta cargando
    await new Promise((resolve) => setTimeout(() => resolve(), delay));

    await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: data
    }).then(response => response.json().then(data => ({ ok: response.ok, body: data })))
        .then(response => {

            result = response;

            if (button) toggleButton(button);

            //en dado caso que se gatille algun erro mediante
            //un formrequest, se imprimiran los mensajes debajo de los inputs
            if (typeof response.body.errors !== 'undefined') {
                return showErrorsForm(response.body.errors)
            }

            // en dado caso que existe un error controlado mediante un macro
            // o fortify devuelva un mensaje de error
            if (!response.ok) {
                return showNotification('error', response.body.message);
            }

            // en caso de que la peticion se ejecute con exito
            // asumimos que debemos mostrar un mensaje exitoso
            // if (response.message) {
            //     showNotification('success', response.message);
            // }
        }).catch(error => {
            if (button) toggleButton(button);
            showNotification('error', error.message)
        });

    return result;
}


/*************************************
 * Choice JS - Selectores
*************************************/

export function ChoicesJs(miSelect){

    return new Choices(miSelect, {
         searchEnabled: true,
         callbackOnCreateTemplates: function(strToEl) {
           var classNames = this.config.classNames;
           var itemSelectText = '';
           return {
             item: function(classNames, data) {
               return strToEl('\
                 <div\
                   class="'+ String(classNames.item) + ' ' + String(data.highlighted ? classNames.highlightedState : classNames.itemSelectable) + '"\
                   data-item\
                   data-id="'+ String(data.id) + '"\
                   data-value="'+ String(data.value) + '"\
                   '+ String(data.active ? 'aria-selected="true"' : '') + '\
                   '+ String(data.disabled ? 'aria-disabled="true"' : '') + '\
                   >\
                   ' + String(data.label) + '\
                 </div>\
               ');
             },
             choice: function(classNames, data) {
               return strToEl('\
                 <div\
                   class="'+ String(classNames.item) + ' ' + String(classNames.itemChoice) + ' ' + String(data.disabled ? classNames.itemDisabled : classNames.itemSelectable) + '"\
                   data-select-text="'+ String(itemSelectText) + '"\
                   data-choice \
                   '+ String(data.disabled ? 'data-choice-disabled aria-disabled="true"' : 'data-choice-selectable') + '\
                   data-id="'+ String(data.id) + '"\
                   data-value="'+ String(data.value) + '"\
                   '+ String(data.groupId > 0 ? 'role="treeitem"' : 'role="option"') + '\
                   >\
                    ' + String(data.label) + '\
                 </div>\
               ');
             },
           };
         }
       });

 }
