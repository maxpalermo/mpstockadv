console.log("StockMvt script loaded");
let table = null;
let dialogMovements = null;
let dialogPreferences = null;
let productAutocomplete = null;

function newMovement() {
    if (!productAutocomplete) {
        alert("Form dei movimenti non trovato.");
        return;
    }
    productAutocomplete.showModal();
}

async function loadModalMovements() {
    console.log("Loading dialog...");

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

        return;
    }

    alert("Form movimenti non caricata.");
}

async function loadModalPreferences() {
    let request = null;
    try {
        request = await fetch(loadModalPreferencesUrl, {
            method: "GET",
            headers: {
                "Content-Type": "application/json"
            }
        });
    } catch (error) {
        alert("Errore: " + error.message);
        return;
    }

    const response = await request.json();
    if (response.success) {
        const template = document.createElement("template");
        template.innerHTML = `
            <dialog id="dialog-preferences" class="bootstrap">
                <div class="card">
                    <div class="card-body">
                        ${response.html}
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-center align-items-center">
                        <button class="btn btn-primary" id="preferences-save-btn">
                            <span class="material-icons">save</span>
                            <span>Salva</span>
                        </button>
                        <button class="btn btn-secondary" id="preferences-close-btn">
                            <span class="material-icons">close</span>
                            <span>Chiudi</span>
                        </button>
                    </div>
                </div>
            </dialog>
        `;
        const cloneNode = template.content.cloneNode(true);
        dialogPreferences = cloneNode.querySelector("dialog");
        document.body.appendChild(dialogPreferences);

        const selectWarehouse = document.getElementById("stockMvtDefaultWarehouse");
        const selectStockMvtReason = document.getElementById("stockMvtDefaultStockMvtReason");
        const btnSavePreferences = document.getElementById("preferences-save-btn");
        const btnClosePreferences = document.getElementById("preferences-close-btn");

        btnSavePreferences.addEventListener("click", async e => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const form = document.getElementById("stockMvtAdvPreferencesForm");
            const formData = new FormData(form);
            const request = await fetch(savePreferencesUrl, {
                method: "POST",
                body: formData
            });
            const response = await request.json();
            if (response.success) {
                dialogPreferences.close();
                alert("Preferenze salvate.");
            } else {
                alert(response.message);
            }
        });

        btnClosePreferences.addEventListener("click", e => {
            dialogPreferences.close();
        });

        $(selectWarehouse).select2({
            dropdownParent: dialogPreferences
        });
        $(selectStockMvtReason).select2({
            dropdownParent: dialogPreferences,
            templateResult: renderMvtReasons,
            templateSelection: renderMvtReasons,
            escapeMarkup: function(markup) {
                return markup;
            }
        });

        $(selectStockMvtReason).on("select2:open", function(e) {
            // Select2 4.x: il campo di ricerca ha classe .select2-search__field
            setTimeout(function() {
                document.querySelector(".select2-search__field").focus();
            }, 0);
        });

        return;
    }

    alert("Form preferenze non caricata.");
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

async function showModalPreferences() {
    if (dialogPreferences) {
        dialogPreferences.showModal();

        return;
    }

    alert("Form Preferenze non trovato.");
}

document.addEventListener("DOMContentLoaded", e => {
    console.log("StockMvt DOMContentLoaded script loaded");
    const tableMvt = document.getElementById("stockMvtTable");
    if (tableMvt) {
        table = new DataTable(tableMvt, {
            language: {
                url: tableDataLanguageItUrl
            },
            ajax: {
                url: ajaxProcessRefreshTableData,
                dataSrc: "data"
            },
            columns: [
                { data: "id", type: "numeric" },
                { data: "date", type: "date" },
                { data: "type", type: "string" },
                { data: "sign", type: "numeric" },
                { data: "reference", type: "string" },
                { data: "ean13", type: "string" },
                { data: "product", type: "string" },
                { data: "combination", type: "string" },
                { data: "stock_before", type: "numeric" },
                { data: "movement", type: "numeric" },
                { data: "stock_after", type: "numeric" },
                { data: "employee", type: "string" },
                { data: "actions", type: "string" }
            ]
        });
    }
});

document.addEventListener("DOMContentLoaded", async () => {
    await loadModalMovements();
    await loadModalPreferences();
});
