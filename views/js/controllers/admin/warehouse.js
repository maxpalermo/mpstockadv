class WarehouseManager {
    constructor() {
        this.tableBody = document.querySelector("#warehouse-table tbody");
        this.form = document.getElementById("create-warehouse-form");
        this.apiBase = "/admin/mpstockadv";
        this.init();
    }

    init() {
        this.loadWarehouses();
        this.form.addEventListener("submit", (e) => this.onCreate(e));
        this.tableBody.addEventListener("click", (e) => this.onTableClick(e));
    }

    async loadWarehouses() {
        const res = await fetch(`${this.apiBase}/warehouses`);
        const json = await res.json();
        this.renderWarehouses(json.data);
    }

    renderWarehouses(warehouses) {
        this.tableBody.innerHTML = "";
        warehouses.forEach((w) => {
            const tr = document.createElement("tr");
            tr.innerHTML = `
            <td><span class="warehouse-name">${w.name}</span></td>
            <td><span class="warehouse-location">${w.location}</span></td>
            <td>
                <span class="badge ${w.active ? "bg-success" : "bg-secondary"}">${w.active ? "Attivo" : "Disattivo"}</span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary me-1 btn-edit" data-id="${w.id}">Modifica</button>
                <button class="btn btn-sm ${w.active ? "btn-warning" : "btn-success"} me-1 btn-toggle" data-id="${w.id}">${w.active ? "Disattiva" : "Attiva"}</button>
            </td>
            `;
            tr.dataset.id = w.id;
            tr.dataset.active = w.active ? "1" : "0";
            this.tableBody.appendChild(tr);
        });
    }

    async onCreate(e) {
        e.preventDefault();
        const name = this.form.elements["name"].value.trim();
        const location = this.form.elements["location"].value.trim();
        if (!name || !location) return;

        const res = await fetch(`${this.apiBase}/warehouse`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ name, location })
        });
        const json = await res.json();
        if (json.success) {
            this.form.reset();
            this.loadWarehouses();
        } else {
            alert("Errore creazione magazzino");
        }
    }

    async onTableClick(e) {
        const btn = e.target.closest("button");
        if (!btn) return;

        const id = btn.dataset.id;
        if (btn.classList.contains("btn-edit")) {
            this.editWarehouse(id);
        } else if (btn.classList.contains("btn-toggle")) {
            this.toggleWarehouse(id);
        }
    }

    editWarehouse(id) {
        const tr = this.tableBody.querySelector(`tr[data-id='${id}']`);
        if (!tr) return;

        const name = tr.querySelector(".warehouse-name").textContent;
        const location = tr.querySelector(".warehouse-location").textContent;
        tr.innerHTML = `
            <td><input type="text" class="form-control form-control-sm" value="${name}" name="edit-name"></td>
            <td><input type="text" class="form-control form-control-sm" value="${location}" name="edit-location"></td>
            <td>${tr.dataset.active == "1" ? '<span class="badge bg-success">Attivo</span>' : '<span class="badge bg-secondary">Disattivo</span>'}</td>
            <td>
                <button class="btn btn-sm btn-success me-1 btn-save-edit" data-id="${id}">Salva</button>
                <button class="btn btn-sm btn-secondary btn-cancel-edit" data-id="${id}">Annulla</button>
            </td>
        `;
        tr.querySelector(".btn-save-edit").addEventListener("click", () => this.saveEditWarehouse(id));
        tr.querySelector(".btn-cancel-edit").addEventListener("click", () => this.loadWarehouses());
    }

    async saveEditWarehouse(id) {
        const tr = this.tableBody.querySelector(`tr[data-id='${id}']`);
        const name = tr.querySelector('input[name="edit-name"]').value.trim();
        const location = tr.querySelector('input[name="edit-location"]').value.trim();
        if (!name || !location) return;

        const res = await fetch(`${this.apiBase}/warehouse/${id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ name, location })
        });
        const json = await res.json();
        if (json.success) {
            this.loadWarehouses();
        } else {
            alert("Errore aggiornamento magazzino");
        }
    }

    async toggleWarehouse(id) {
        const res = await fetch(`${this.apiBase}/warehouse/${id}/toggle`, { method: "PATCH" });
        const json = await res.json();
        if (json.success) {
            this.loadWarehouses();
        } else {
            alert("Errore cambio stato magazzino");
        }
    }
}
document.addEventListener("DOMContentLoaded", () => new WarehouseManager());
