let table = null;
let dialogMovements = null;
let dialogPreferences = null;
let productAutocomplete = null;
let stockMvtPreferences = null;

function newMovement() {
    if (!productAutocomplete) {
        alert("Form dei movimenti non trovato.");
        return;
    }
    productAutocomplete.showModal();
}

async function loadModalMovements() {
    const loadModalMovementsUrl = window.Routes.get("loadModalMovementsUrl");
    const request = await fetch(loadModalMovementsUrl, {
        method: "GET",
        headers: {
            "Content-Type": "application/json"
        }
    });

    if (!request.ok) {
        alert("Errore durante il caricamento del form dei movimenti.");
        return;
    }
    const response = await request.json();

    if (response.success) {
        const template = document.createElement("template");
        template.innerHTML = response.html;
        const cloneNode = template.content.cloneNode(true);
        dialogMovements = cloneNode.querySelector("dialog");
        document.body.appendChild(dialogMovements);

        productAutocomplete = new ProductAutocomplete(dialogMovements);

        dialogMovements.addEventListener("stockMvtSaved", e => {
            alert("Movimento salvato");
            dialogMovements.close();
            table.ajax.reload();
        });

        return;
    }

    alert("Form movimenti non caricata.");
}

async function importStockAvailable() {
    if (confirm("Inizio importazione delle giacenze?") == false) {
        return;
    }
    const ajaxProcessTruncateStockTablesUrl = window.Routes.get("ajaxProcessTruncateStockTablesUrl");
    const requestTruncate = await fetch(ajaxProcessTruncateStockTablesUrl);
    const responseTruncate = await requestTruncate.json();
    console.log("Tabelle troncate", responseTruncate);

    const ajaxProcessGetStockAvailableRowsUrl = window.Routes.get("ajaxProcessGetStockAvailableRowsUrl");
    const request = await fetch(ajaxProcessGetStockAvailableRowsUrl);
    const response = await request.json();
    if ("rows" in response) {
        const resultImport = await importChunk(response.rows);
        if (resultImport.success) {
            table.ajax.reload();
        }
    } else {
        alert("Nessuna giacenza da importare");
    }
}

async function importChunk(list) {
    const chunk = list.splice(0, 200);
    const ajaxProcessImportChunkUrl = window.Routes.get("ajaxProcessImportChunkUrl");
    const request = await fetch(ajaxProcessImportChunkUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({ chunk: chunk })
    });
    const response = await request.json();
    if ("error" in response && response.error != false) {
        alert("si è verificato un errore.\n" + response.error);
    } else if (list.length == 0) {
        alert("Importazione terminata");
        return true;
    } else {
        return importChunk(list);
    }
}

function renderMvtReasons(data) {
    if (!data.id) {
        return data.text;
    }
    const sign = data.element ? data.element.getAttribute("data-select2-sign") : data.sign;
    let icon = "";
    if (sign == "1") {
        icon = `<span class="material-icons" style="color: var(--success)">add_circle</span>`;
    } else if (sign == "-1") {
        icon = `<span class="material-icons" style="color: var(--danger)">do_not_disturb_on</span>`;
    }
    const label = `<span class='data-text'>${data.text}</span>`;

    return $(`${icon} ${label}`);
}

async function showModalPreferences2() {
    if (dialogPreferences) {
        dialogPreferences.showModal();

        return;
    }

    alert("Form Preferenze non trovato.");
}

async function showModalPreferences() {
    if (stockMvtPreferences) {
        stockMvtPreferences.showModal();

        return;
    }

    alert("Form Preferenze non trovato.");
}

function setStockColor(value) {
    if (value < 0) {
        return `<span class="text-danger">${value}</span>`;
    } else if (value == 0) {
        return `<span class="text-info">${value}</span>`;
    } else {
        return `<span class="text-success">${value}</span>`;
    }
}

async function initStockMvt() {
    const ajaxProcessRefreshTableDataUrl = window.Routes.get("ajaxProcessRefreshTableDataUrl");
    const tableMvt = document.getElementById("stockMvtTable");
    if (tableMvt) {
        table = new DataTable(tableMvt, {
            serverSide: true,
            processing: true,
            language: {
                url: window.TableDataLanguageItUrl
            },
            ajax: {
                url: ajaxProcessRefreshTableDataUrl,
                type: "POST"
            },
            columns: [
                {
                    name: "img_url",
                    data: "img_url",
                    type: "string",
                    className: "text-right",
                    render: function(data) {
                        return `<img src="${data}" alt="Product image" style="width: 72px; height: 72px; object-fit: cover; border: 1px solid #dcdcdc;border-radius: 0;">`;
                    }
                },
                {
                    name: "id",
                    data: "id",
                    type: "numeric"
                },
                {
                    name: "date",
                    data: "date",
                    type: "date",
                    className: "text-center",
                    render: data => {
                        const date = new Date(data);
                        if (!isNaN(date.getTime())) {
                            return date.toLocaleDateString("it-IT");
                        } else {
                            return "--";
                        }
                    }
                },
                {
                    name: "type",
                    data: "type",
                    type: "string"
                },
                {
                    name: "sign",
                    data: "sign",
                    type: "numeric",
                    className: "text-center",
                    render: function(data) {
                        if (data == "+") {
                            return `<span class="material-icons" style="color: var(--success);">add_circle</span>`;
                        }

                        return `<span class="material-icons" style="color: var(--danger);">do_not_disturb_on</span>`;
                    }
                },
                {
                    name: "reference",
                    data: "reference",
                    type: "string"
                },
                {
                    name: "ean13",
                    data: "ean13",
                    type: "string"
                },
                {
                    name: "product_name",
                    data: "product_name",
                    type: "string"
                },
                {
                    name: "combination",
                    data: "combination",
                    type: "string",
                    render: function(data) {
                        if (!data) {
                            return "--";
                        }

                        return data;
                    }
                },
                {
                    name: "warehouse",
                    data: "warehouse",
                    type: "string"
                },
                {
                    name: "stock_before",
                    data: "stock_before",
                    type: "numeric",
                    className: "text-right",
                    render: data => {
                        return setStockColor(data);
                    }
                },
                {
                    name: "movement",
                    data: "movement",
                    type: "numeric",
                    className: "text-right",
                    render: data => {
                        return setStockColor(data);
                    }
                },
                {
                    name: "stock_after",
                    data: "stock_after",
                    type: "numeric",
                    className: "text-right",
                    render: data => {
                        return setStockColor(data);
                    }
                },
                {
                    name: "employee",
                    data: "employee",
                    type: "string"
                },
                {
                    name: "actions",
                    render: function(data, type, row) {
                        return `<button class="btn btn-primary btn-sm" onclick="editMovement(${row.id})">Edit</button>`;
                    },
                    type: "string"
                }
            ]
        });
    }

    await loadModalMovements();
    stockMvtPreferences = new StockMvtPreferences();
    await stockMvtPreferences.init();

    console.log("StockMvt.js loaded.");
}
