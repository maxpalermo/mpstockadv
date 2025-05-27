// Gestione movimenti magazzino: tabella AJAX, toolbar e ricerca

class StockMvtController {
    constructor(endpoints) {
        this.endpoints = endpoints;
        this.currentPage = 1;
        this.perPage = 20;
        this.search = "";
    }

    async init() {
        this.initToolbar();
        this.initSearchbar();
        await this.loadTable();
    }

    initToolbar() {
        // Eventi per i pulsanti della toolbar
        document.getElementById("add-mvt").addEventListener("click", () => {
            // TODO: apri popup nuovo movimento
            alert("Nuovo movimento (da implementare)");
        });
        document.getElementById("import-mvt").addEventListener("click", () => {
            // TODO: apri dialog importazione
            alert("Importa movimenti (da implementare)");
        });
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
        const url = this.endpoints.ajaxList;
        const tableDiv = document.getElementById("stock-mvt-table");
        tableDiv.innerHTML = '<div class="text-center">Caricamento movimenti...</div>';
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                page: this.currentPage,
                perPage: this.perPage,
                search: this.search
            })
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
}
