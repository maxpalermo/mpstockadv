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

async function confirmImport() {
    const html = `
        <!-- Modale di conferma importazione giacenze -->
        <dialog id="importStockModal" class="bootstrap">
        <style>
            #importStockModal {
                width: 400px;
                margin: auto;
                padding: 20px;
                border-radius: 10px;
                background-color: #fff;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            #importStockModal .card-body {
                max-width: 400px;
                margin: auto;
            }
            #importStockModal .card-footer {
                display: flex;
                align-items: center;
                justify-content: center;
                max-width: 400px;
                margin: auto;
            }
            #importStockModal .card-footer button {
                margin: 0 10px;
            }
        </style>
            <div class="card-body" style="max-width:400px;margin:auto;">
                <h2>Importa giacenze</h2>
                <div class="form-control">
                    <p>Vuoi importare le giacenze iniziali? Scrivi <b>IMPORTA</b> per continuare</p>
                    <input type="text" id="importStockInput" class="form-control" style="width:100%;" placeholder="Scrivi IMPORTA qui">
                </div>
            </div>
            <div class="card-footer">
                <button id="confirmImportBtn" class="btn btn-primary" disabled>CONFERMA</button>
                <button id="closeImportModalBtn" class="btn btn-secondary">CHIUDI</button>
            </div>
        </dialog>
    `;

    if (!document.getElementById("importStockModal")) {
        const template = document.createElement("template");
        template.innerHTML = html;
        const cloneNode = template.content.cloneNode(true);
        const confirmDialog = cloneNode.querySelector("dialog");
        document.body.appendChild(confirmDialog);
    }

    const importStockModal = document.getElementById("importStockModal");
    const importStockInput = document.getElementById("importStockInput");
    const confirmImportBtn = document.getElementById("confirmImportBtn");
    const closeImportModalBtn = document.getElementById("closeImportModalBtn");

    importStockModal.showModal();
    importStockInput.focus();

    importStockInput.addEventListener("input", () => {
        if (importStockInput.value.toUpperCase() === "IMPORTA") {
            confirmImportBtn.disabled = false;
        } else {
            confirmImportBtn.disabled = true;
        }
    });

    closeImportModalBtn.addEventListener("click", () => {
        importStockModal.close();
    });

    confirmImportBtn.addEventListener("click", () => {
        importStockModal.close();
        importStockAvailable();
    });
}

async function importStockAvailable() {
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
        body: JSON.stringify({
            chunk: chunk
        })
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
        return `<span class="badge badge-lg badge-square bg-danger text-light">${value}</span>`;
    } else if (value == 0) {
        return `<span class="badge badge-lg badge-square bg-info text-light">${value}</span>`;
    } else {
        return `<span class="badge badge-lg badge-square bg-success text-light">${value}</span>`;
    }
}

function renderData(dateString) {
    if (dateString == "--") {
        return dateString;
    }
    const date = new Date(dateString);
    if (!isNaN(date.getTime())) {
        return date.toLocaleDateString("it-IT");
    } else {
        return "--";
    }
}

async function initStockMvt() {
    const ajaxProcessRefreshTableDataUrl = window.Routes.get("ajaxProcessRefreshTableDataUrl");
    const tableMvt = document.getElementById("stockMvtTable");
    if (tableMvt) {
        table = new DataTable(tableMvt, {
            order: [[1, "DESC"]],
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
                    className: "text-center",
                    render: function(data) {
                        return `<img src="${data}" alt="Product image" style="width: 72px; height: 72px; object-fit: cover; border: 1px solid #dcdcdc;border-radius: 0;">`;
                    },
                    orderable: false,
                    searchable: false
                },
                {
                    name: "id",
                    data: "id",
                    type: "numeric"
                },
                {
                    name: "ref_document",
                    data: "ref_document",
                    type: "string",
                    className: "text-right"
                },
                {
                    name: "date_document",
                    data: "date_document",
                    type: "date",
                    className: "text-center",
                    render: data => {
                        return renderData(data);
                    }
                },
                {
                    name: "type",
                    data: "type",
                    type: "string",
                    render: data => {
                        if (data == "") {
                            return `--`;
                        }
                        return data;
                    }
                },
                {
                    name: "sign",
                    data: "sign",
                    type: "numeric",
                    className: "text-center",
                    render: data => {
                        if (data == 1) {
                            return `<span class="material-icons" style="color: var(--success);">add_circle</span>`;
                        } else if (data == -1) {
                            return `<span class="material-icons" style="color: var(--danger);">do_not_disturb_on</span>`;
                        }

                        return `<span class="material-icons" style="color: var(--warning);">help</span>`;
                    }
                },
                {
                    name: "reference",
                    data: "reference",
                    type: "string",
                    className: "user-select"
                },
                {
                    name: "ean13",
                    data: "ean13",
                    type: "string",
                    className: "user-select"
                },
                {
                    name: "product_name",
                    data: "product_name",
                    type: "string"
                },
                {
                    name: "combination",
                    data: "combination",
                    type: "string"
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
                    name: "date",
                    data: "date",
                    type: "date",
                    className: "text-center",
                    render: data => {
                        return renderData(data);
                    }
                },
                {
                    name: "employee",
                    data: "employee",
                    type: "string"
                },
                {
                    name: "actions",
                    render: (data, type, row) => {
                        return `
                            <button class="btn btn-primary btn-sm" onclick="editMovement(${row.id})">
                                <span class="material-icons mr-2">edit</span>
                                <span>Edit</span>
                            </button>
                        `;
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
