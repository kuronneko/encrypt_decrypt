// Local sendRequest function for DevExtreme compatibility
import { fetchRoute, showNotification } from "../configuracion.js";

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
                paginate: true,
                pageSize: 10,
            },

            remoteOperations: {
                paging: true,
                filtering: true,
                sorting: true,
                grouping: false,
                summary: false,
            },
            columnAutoWidth: true,
            showBorders: true,
            hoverStateEnabled: true,
            columnHidingEnabled: true,
            allowColumnReordering: true,
            wordWrapEnabled: true,
            /*            searchPanel: {
                // 1 panel para buscar palabras
                visible: true,
                width: "90%",
                placeholder: "Buscar...",
            }, */
            headerFilter: {
                visible: false,
            },
            filterRow: {
                visible: true,
                applyFilter: "auto",
                betweenStartText: "Inicio",
                betweenEndText: "Fin",
            },
            pager: {
                allowedPageSizes: [10, 25, 50, 100],
                showInfo: true,
                showNavigationButtons: true,
                showPageSizeSelector: true,
                visible: "auto",
            },
            paging: {
                pageSize: 10,
            },

            columnChooser: {
                enabled: false,
                mode: "select",
            },
            columns: [
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
                    hidingPriority: 1,
                    allowEditing: true,
                    width: 70,
                    dataType: "number",
                },
                {
                    dataField: "name",
                    caption: "Nombre",
                    filterOperations: ["contains"],
                    hidingPriority: 1,
                    allowEditing: true,
                },
                {
                    dataField: "email",
                    caption: "Email",
                    filterOperations: ["contains"],
                    hidingPriority: 1,
                    allowEditing: true,
                },
                {
                    dataField: "city",
                    caption: "Ciudad",
                    filterOperations: ["contains"],
                    hidingPriority: 1,
                    allowEditing: true,
                },
                {
                    dataField: "postal_code",
                    caption: "Código Postal",
                    filterOperations: ["contains"],
                    hidingPriority: 2,
                    allowEditing: false,
                    cellTemplate: function (container, options) {
                        const postalCode = options.value || "N/A";
                        container.append(`<span>${postalCode}</span>`);
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
                    hidingPriority: 6,
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
                    hidingPriority: 6,
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

    window.refreshUsersGrid = function() {
        $("#usersGrid").dxDataGrid("instance").refresh();
    };

    $("#refreshBtn").on("click", function() {
        refreshUsersGrid();
    });
});
