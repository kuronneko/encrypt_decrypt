// Local sendRequest function for DevExtreme compatibility
function sendRequest(url, method, data) {
    var d = $.Deferred();
    method = method || "GET";
    $.ajax(url, {
        method: method || "GET",
        data: data,
        cache: false,
        xhrFields: {
            withCredentials: true,
        },
    })
        .done(function (result) {
            d.resolve(result);
        })
        .fail(function (xhr) {
            if (xhr && xhr.responseJSON && xhr.responseJSON.errors) {
                Object.keys(xhr.responseJSON.errors)
                    .reverse()
                    .forEach((key) => {
                        xhr.responseJSON.errors[key].forEach((errorMessage) => {
                            showNotification("error", errorMessage);
                        });
                    });
            } else {
                showNotification("error", "Error al obtener los datos");
            }
            d.reject(xhr);
        });
    return d.promise();
}

$(document).ready(async function (e) {
    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    let tableDataUrl = `/users/list/`;

    let detailGrid;
    let items = new DevExpress.data.CustomStore({
        key: "id",
        load: function (loadOptions) {
            // Prepare parameters for server-side processing
            let params = {};

            // Pagination parameters
            if (loadOptions.skip !== undefined) {
                params.skip = loadOptions.skip;
            }
            if (loadOptions.take !== undefined) {
                params.take = loadOptions.take;
            }

            // Search parameters
            if (loadOptions.searchValue) {
                params.searchValue = loadOptions.searchValue;
            }

            // Filter parameters
            if (loadOptions.filter) {
                params.filter = JSON.stringify(loadOptions.filter);
            }

            // Sort parameters
            if (loadOptions.sort) {
                params.sort = JSON.stringify(loadOptions.sort);
            }

            return sendRequest(
                tableDataUrl + "?" + $.param(params),
                "GET"
            ).then(function (result) {
                return {
                    data: result.data,
                    totalCount: result.totalCount,
                };
            });
        },
    });

    DevExpress.localization.locale("es-CL");

    const dataGrid = $("#usersGrid")
        .dxDataGrid({
            dataSource: {
                store: items,
                // Enable server-side operations
                paginate: true,
                pageSize: 10,
            },
            // Enable remote operations
            remoteOperations: {
                paging: true,
                filtering: true,
                sorting: true,
                grouping: false,
                summary: false,
            },
            columnAutoWidth: true,
            showBorders: true, // mostrar bordes de la tabla
            hoverStateEnabled: true, // color en la fila al pasar el mouse por encima
            columnHidingEnabled: true, // ocultar columnas si no alcanzan a desplegarse en la resolucion
            allowColumnReordering: true, // permite mover las columnas (cambiar de orden) al actualizar vuelve a la normalidad
            // rowAlternationEnabled: true, // fila de color intercalada
            wordWrapEnabled: true, // permite visualizar todo el texto en una columna (pasa la siguiente, como si hiciera enter)
            /*            searchPanel: {
                // 1 panel para buscar palabras
                visible: true,
                width: "90%",
                placeholder: "Buscar...",
            }, */
            headerFilter: {
                // filtro para filtrar al seleccionar valores de la columna en la cabecera
                visible: true,
            },
            filterRow: {
                //lupita para buscar en columna
                visible: true,
                applyFilter: "auto", // puede ser auto u onClick
                betweenStartText: "Inicio",
                betweenEndText: "Fin",
            },
            pager: {
                // paginador, cuantas filas se muestran
                allowedPageSizes: [10, 25, 50, 100],
                showInfo: true,
                showNavigationButtons: true,
                showPageSizeSelector: true,
                visible: "auto",
            },
            paging: {
                // numero de filas a mostrar
                pageSize: 10,
            },

            columnChooser: {
                // escoger que columnas se muestran u ocultar al presionar un botón y seleccionar
                enabled: false,
                mode: "select",
            },
            columns: [
                {
                    type: "buttons",
                    width: 100,
                    cellTemplate: function (container, options) {
                        let deleteUser = $("<i>")
                            .addClass(
                                "fa-solid fa-trash table-icon deleteIconUser"
                            )
                            .attr("title", "deleteIconUser")
                            .on("click", function () {
                                //dataGrid.deleteRow(options.rowIndex);
                                Swal.fire({
                                    title: "¿Deseas eliminar este usuario?",
                                    icon: "question",
                                    showCancelButton: true,
                                    confirmButtonText: "Si",
                                    cancelButtonText: "No",
                                    showCloseButton: true,
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        let key = options.row.data.id; // Assuming 'id' is the key of the record
                                        $.ajax({
                                            url: "/users/" + key + "/delete/",
                                            type: "DELETE",
                                            data: {
                                                id: key,
                                            },
                                            success: function () {
                                                showNotification(
                                                    "success",
                                                    "Usuario eliminado."
                                                );
                                                dataGrid.refresh();
                                            },
                                            error: function (
                                                jqXHR,
                                                textStatus,
                                                errorThrown
                                            ) {
                                                let errorMessage =
                                                    jqXHR.responseJSON.message;
                                                showNotification(
                                                    "error",
                                                    errorMessage
                                                );
                                            },
                                        });
                                    }
                                });
                            });
                        //if (document.getElementById('adm_clientes_bodegas.edit').value == "1") {
                        // Edit Icon
                        let editIconUser = $("<a>")
                            .attr(
                                "href",
                                "/users/" + options.row.data.id + "/edit/"
                            )
                            .append(
                                $("<i>")
                                    .addClass(
                                        "fa-solid fa-pen-to-square table-icon editIconUser"
                                    )
                                    .attr("title", "editIconUser")
                                    .css("color", "#e54800")
                            );
                        //  }
                        $(container)
                            .append(editIconUser.addClass("icon-spacing"))
                            .append(deleteUser.addClass("icon-spacing"));
                    },
                },
                {
                    dataField: "id",
                    caption: "ID",
                    filterOperations: [
                        "=",
                        "<>",
                        "<",
                        "<=",
                        ">",
                        ">=",
                        "between",
                    ],
                    hidingPriority: 1, // prioridad para ocultar columna, 0 se oculta primero
                    allowEditing: true,
                    width: 70, // Set the width to a smaller size
                    dataType: "number",
                },
                {
                    dataField: "name",
                    caption: "Nombre",
                    filterOperations: ["contains"],
                    hidingPriority: 1, // prioridad para ocultar columna, 0 se oculta primero
                    allowEditing: true,
                },
                {
                    dataField: "email",
                    caption: "Email",
                    filterOperations: ["contains"],
                    hidingPriority: 1, // prioridad para ocultar columna, 0 se oculta primero
                    allowEditing: true,
                },
                {
                    dataField: "city",
                    caption: "Ciudad",
                    filterOperations: ["contains"],
                    hidingPriority: 1, // prioridad para ocultar columna, 0 se oculta primero
                    allowEditing: true,
                },
                {
                    dataField: "postal_code",
                    caption: "Código Postal",
                    filterOperations: ["contains"],
                    hidingPriority: 2, // prioridad para ocultar columna, 0 se oculta primero
                    allowEditing: false,
                    cellTemplate: function (container, options) {
                        const postalCode = options.value || "N/A";
                        container.append(`<span>${postalCode}</span>`);
                    },
                },
                {
                    dataField: "address",
                    caption: "Dirección",
                    filterOperations: ["contains"],
                    hidingPriority: 2, // prioridad para ocultar columna, 0 se oculta primero
                    allowEditing: false,
                    cellTemplate: function (container, options) {
                        const address = options.value || "N/A";
                        container.append(`<span>${address}</span>`);
                    },
                },
                {
                    dataField: "created_at",
                    caption: "Fecha de creación",
                    filterOperations: [
                        "=",
                        "<>",
                        "<",
                        "<=",
                        ">",
                        ">=",
                        "between",
                    ],
                    hidingPriority: 6, // prioridad para ocultar columna, 0 se oculta primero
                    dataType: "datetime",
                    format: "dd/MM/yyyy HH:mm",
                },
                {
                    dataField: "updated_at",
                    caption: "Última actualización",
                    filterOperations: [
                        "=",
                        "<>",
                        "<",
                        "<=",
                        ">",
                        ">=",
                        "between",
                    ],
                    hidingPriority: 6, // prioridad para ocultar columna, 0 se oculta primero
                    dataType: "datetime",
                    format: "dd/MM/yyyy HH:mm",
                },
                {
                    dataField: "email_verified_at",
                    caption: "Email verificado",
                    filterOperations: ["contains"],
                    hidingPriority: 6,
                    cellTemplate: function (container, options) {
                        const isVerified = options.value ? true : false;
                        const icon = isVerified ? "fa-check" : "fa-times";
                        const color = isVerified ? "green" : "red";
                        const text = isVerified
                            ? "Verificado"
                            : "No verificado";

                        container.append(
                            `<span style="color: ${color};">
                                <i class="fa-solid ${icon}"></i> ${text}
                            </span>`
                        );
                    },
                },
            ],
        })
        .dxDataGrid("instance");
});
