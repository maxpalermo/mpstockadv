// Gestione movimenti magazzino: tabella AJAX, toolbar e ricerca

import StockMvtForm from "./stockmvt.Form.js";

// Classe principale per la gestione della tabella e degli strumenti della pagina
export default class StockMvtPage {
    constructor(endpoints) {
        this.endpoints = endpoints;
        this.currentPage = 1;
        this.perPage = 20;
        this.search = "";
    }

    async init() {
        this.initToolbar();
        this.initSearchbar();
        this.bindNewMvt();
        await this.loadTable();
    }

    bindNewMvt() {
        let stockMvtForm = null;
        const btn = document.getElementById("add-mvt");
        if (btn) {
            if (!stockMvtForm) {
                const endpoints = this.endpoints;

                stockMvtForm = new StockMvtForm({
                    onSaved: () => {
                        if (window.reloadStockMvtTable) window.reloadStockMvtTable();
                        if (window.reloadStockAvailable) window.reloadStockAvailable();
                    },
                    endpoints: endpoints
                });
            }
            btn.addEventListener("click", () => stockMvtForm.open());
        }
    }

    initToolbar() {
        // Eventi per i pulsanti della toolbar diversi dal nuovo movimento
        const importBtn = document.getElementById("import-mvt");
        if (importBtn) {
            importBtn.addEventListener("click", () => {
                // TODO: apri dialog importazione
            });
        }
    }

    async initSearchbar() {
        const searchForm = document.getElementById("stock-mvt-search-form");
        if (searchForm) {
            searchForm.addEventListener("submit", async (e) => {
                e.preventDefault();
                this.search = document.getElementById("search-mvt").value;
                this.currentPage = 1;
                await this.loadTable();
            });
        }
    }

    async loadTable() {
        // Costruisci la query string per GET
        const formData = new FormData();
        formData.append("page", this.currentPage);
        formData.append("perPage", this.perPage);
        formData.append("search", this.search);

        const urlWithParams = this.endpoints.ajaxList;
        const tableDiv = document.getElementById("stock-mvt-table");
        tableDiv.innerHTML = '<div class="text-center">Caricamento movimenti...</div>';

        const response = await fetch(urlWithParams, {
            method: "POST",
            body: formData
        });
        const data = await response.json();
        tableDiv.innerHTML = this.renderTable(data.data) + this.renderPagination(data.page, data.perPage, data.total);

        // Eventi paginazione
        tableDiv.querySelectorAll(".stock-mvt-page-link").forEach((el) => {
            el.addEventListener("click", async (e) => {
                e.preventDefault();
                this.currentPage = parseInt(el.dataset.page);
                await this.loadTable();
            });
        });
    }

    renderTable(data) {
        if (!data.length) {
            return '<div class="alert alert-info">Nessun movimento trovato.</div>';
        }
        let html = `<table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Prodotto</th>
                    <th>Quantità</th>
                    <th>Tipo movimento</th>
                    <th>Riferimento</th>
                </tr>
            </thead>
            <tbody>`;
        data.forEach((row) => {
            html += `<tr>
                <td>${row.id_stock_mvt}</td>
                <td>${row.date_add ? row.date_add.replace("T", " ").substring(0, 19) : ""}</td>
                <td>${row.id_product || ""}</td>
                <td>${row.physical_quantity || ""}</td>
                <td>${row.reason_name || ""}</td>
                <td>${row.id_order ? "Ordine #" + row.id_order : row.id_supplier_order ? "Fornitore #" + row.id_supplier_order : ""}</td>
            </tr>`;
        });
        html += "</tbody></table>";
        return html;
    }

    renderPagination(page, perPage, total) {
        if (total <= perPage) return "";
        const totalPages = Math.ceil(total / perPage);
        let html = '<nav><ul class="pagination justify-content-center">';
        for (let p = 1; p <= totalPages; p++) {
            html += `<li class="page-item${p === page ? " active" : ""}"><a href="#" data-page="${p}" class="page-link stock-mvt-page-link">${p}</a></li>`;
        }
        html += "</ul></nav>";
        return html;
    }

    addMvt() {
        return true;
    }

    importMvt() {
        return true;
    }
}
