export default class SupplyOrders {
    constructor(tableSelector, endpoints) {
        this.endpoints = endpoints;
        this.dialog = document.getElementById("supply-order-dialog");
        this.form = document.getElementById("supply-order-form");
        this.productsTable = document.getElementById("products-table").getElementsByTagName("tbody")[0];
        this.addProductBtn = document.getElementById("btn-add-product");
        this.cancelDialogBtn = document.getElementById("btn-cancel-dialog");
        this.initDialogEvents();

        this.table = document.querySelector(tableSelector);
        this.initEvents();
        this.injectStyle();
    }

    injectStyle() {
        const style = document.createElement("style");
        style.textContent = `
            .btn-remove-row {
                cursor: pointer;
            }

            dialog {
                background: var(--white);
                border: 1px solid var(--gray-200);
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                border-radius: 5px;
                padding: 16px;
            }

            dialog.modal-xxl {
                min-width: 1200px;
            }

            .card-heading {
                background-color: var(--gray-100);
                padding: 12px;
            }

            .select2-container {
                z-index: 9999;
                min-width: 80%;
                min-height: 38px;
                width: auto !important;
            }

            .product-autocomplete + .select2-container {
                min-width: 480px !important;
                width: auto !important;
                z-index: 2147483647 !important;
            }
            .select2-dropdown,
            .select2-results,
            .select2-container--open {
                z-index: 2147483647 !important;
            }
            .product-autocomplete + .select2-dropdown,
            .product-autocomplete + .select2-results,
            .product-autocomplete + .select2-container--open {
                z-index: 2147483647 !important;
            }
            .select2-selection {
                min-height: 38px;
            }

            table tr:hover td{
                cursor: pointer;
                color: #404040;
            }
        `;
        document.head.appendChild(style);
    }

    initProductAutocomplete(selectEl) {
        if (!window.$ || !$.fn.select2) return;
        $(selectEl).select2({
            placeholder: "Cerca prodotto...",
            minimumInputLength: 3,
            ajax: {
                url: this.endpoints.productSearch,
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            templateResult: function (data) {
                if (!data.id) return data.text;
                return $(
                    `<div style="display:flex;align-items:center;">
                        <img src="${data.img || "/img/p/en-default-home_default.jpg"}" style="width:40px;height:40px;object-fit:cover;margin-right:8px;">
                        <div>
                            <div><strong>${data.name || data.text}</strong></div>
                            <div style="font-size:12px;color:#666;">${data.combination || ""}</div>
                        </div>
                    </div>`
                );
            },
            templateSelection: function (data) {
                return data.name || data.text;
            },
            width: "100%",
            dropdownParent: $(selectEl).closest("dialog")
        });

        // Focus automatico sulla casella di ricerca quando si apre il dropdown
        $(selectEl).on("select2:open", function () {
            setTimeout(function () {
                let searchField = document.querySelector(".select2-container--open .select2-search__field");
                if (searchField) searchField.focus();
            }, 10);
        });

        $(selectEl).on("select2:select", function (e) {
            const data = e.params.data;
            let prod = {};
            try {
                prod = JSON.parse(data.id);
            } catch (err) {}
            // Trova la riga corrente
            const row = selectEl.closest("tr");
            if (row) {
                // Aggiorna i campi della riga
                const refInput = row.querySelector('input[name^="products"][name$="[reference]"]');
                const refEan13 = row.querySelector('input[name^="products"][name$="[ean13]"]');
                const refQty = row.querySelector('input[name^="products"][name$="[quantity_expected]"]');
                if (refInput) refInput.value = prod.reference || "";
                if (refEan13) refEan13.value = prod.ean13 || "";
                if (refQty) refQty.focus();
            }
        });
    }

    initDialogEvents() {
        // Apri dialog
        document.getElementById("btn-new-supply-order").addEventListener("click", (e) => {
            e.preventDefault();
            this.dialog.showModal();
            if (window.$ && $.fn.select2) {
                // Inizializza Select2 fornitori con logo e nome
                if (window.suppliersData) {
                    $("#supplier-select").select2({
                        data: window.suppliersData,
                        templateResult: function(data) {
                            if (!data.id) return data.text;
                            return $(
                                `<div style="display:flex;align-items:center;">
                                    <img src="${data.logo || '/img/supliers/default.jpg'}" style="width:32px;height:32px;object-fit:cover;margin-right:8px;border-radius:50%;">
                                    <span>${data.text}</span>
                                </div>`
                            );
                        },
                        templateSelection: function(data) {
                            return data.text;
                        },
                        dropdownParent: this.dialog
                    });
                }
                // Inizializza autocomplete sulla prima riga
                document.querySelectorAll(".product-autocomplete").forEach((el) => this.initProductAutocomplete(el));
            }
        });
        // Chiudi dialog
        this.cancelDialogBtn.addEventListener("click", (e) => {
            e.preventDefault();
            this.dialog.close();
            this.form.reset();
        });
        // Evita doppio listener: clona il bottone e riassegna
        const newAddBtn = this.addProductBtn.cloneNode(true);
        this.addProductBtn.parentNode.replaceChild(newAddBtn, this.addProductBtn);
        this.addProductBtn = newAddBtn;
        this.addProductBtn.addEventListener("click", (e) => {
            e.preventDefault();
            const idx = this.productsTable.rows.length;
            const row = document.createElement("tr");
            row.innerHTML = `
                <td><select name="products[${idx}][name]" class="form-control product-autocomplete" required data-row="${idx}"></select></td>
                <td><input type="text" name="products[${idx}][reference]" class="form-control"></td>
                <td><input type="text" name="products[${idx}][ean13]" class="form-control"></td>
                <td><input type="number" name="products[${idx}][quantity_expected]" class="form-control" min="1" required></td>
                <td><button type="button" class="btn btn-danger btn-remove-row">&times;</button></td>
            `;
            this.productsTable.appendChild(row);
            this.initProductAutocomplete(row.querySelector(".product-autocomplete"));
        });
        // Rimuovi prodotto
        this.productsTable.addEventListener("click", (e) => {
            if (e.target.classList.contains("btn-remove-row")) {
                e.preventDefault();
                e.target.closest("tr").remove();
            }
        });
        // Submit form
        this.form.addEventListener("submit", (e) => {
            e.preventDefault();
            const data = new FormData(this.form);
            fetch(window.location.pathname + "/create", {
                method: "POST",
                body: data
            })
                .then((r) => r.json())
                .then((res) => {
                    if (res.success) {
                        alert("Documento creato!");
                        window.location.reload();
                    } else {
                        alert(res.message || "Errore nella creazione.");
                    }
                });
        });
    }

    initEvents() {
        // Gestisci azioni edit e ricezione
        this.table.addEventListener("click", (e) => {
            if (e.target.classList.contains("btn-edit")) {
                this.editRow(e.target.closest("tr"));
            }
            if (e.target.classList.contains("btn-receive")) {
                this.openReceiveModal(e.target.closest("tr"));
            }
        });
    }

    editRow(row) {
        // Logica per editare la riga
        alert("Modifica documento di carico: " + row.dataset.id);
    }

    openReceiveModal(row) {
        // Logica per aprire modale ricezione
        alert("Ricevi merce per documento: " + row.dataset.id);
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const endpoints = {
        productSearch: document.body.dataset.productSearchUrl
    };

    console.log("Endpoints: ", endpoints);

    new SupplyOrders("#supply-orders-table", endpoints);
});
