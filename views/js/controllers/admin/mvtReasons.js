export default class MvtReasons {
    constructor(endpoints) {
        this.endpoints = endpoints;
        this.dialogId = "mvt-reason-dialog";
        this.dialogElement = this.getDialogElement();
        this.editForm = null;
        this.tableBody = document.querySelector("#mvt-reasons-table tbody");
        this.searchInput = null;
        this.pagination = null;
        this.page = 1;
        this.limit = 10;
        this.total = 0;
        this.search = "";
        this.injectStyle();
        this.initSearchAndPagination();
        this.loadReasons();
    }

    injectStyle() {
        const style = document.createElement("style");
        style.textContent = `
            #mvt-reasons-table .table td {
                vertical-align: middle;
            }
            #mvt-reasons-table .table td i {
                font-size: 1.2rem;
            }
            #mvt-reasons-table .table td i.material-icons {
                vertical-align: middle;
            }
            #mvt-reasons-table tr:hover td {
                background-color:rgb(248, 194, 112);
                color: #303030;
                cursor: pointer;
            }

            #${this.dialogId} {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 auto !important;
                min-width: unset !important;
                min-height: unset !important;
                max-width: unset !important;
                max-height: unset !important;
                overflow: visible !important;
                z-index: 10002 !important;
                box-sizing: border-box !important;
                //Centra nella pagina con translate
                position: fixed !important;
                top: 50% !important;
                transform: translateY(-50%) !important;
            }
            
            #${this.dialogId}::backdrop {
                background: rgba(0,0,0,0.55) !important;
                backdrop-filter: blur(2px) !important;
                z-index: 10002 !important;
            }
        `;
        document.head.appendChild(style);
    }

    initSearchAndPagination() {
        const table = document.getElementById("mvt-reasons-table");
        // Search bar
        const searchDiv = document.createElement("div");
        searchDiv.className = "mb-2";
        searchDiv.innerHTML = `<input type="search" class="form-control" placeholder="Cerca..." id="mvt-reasons-search" style="max-width:300px;display:inline-block;" />`;
        table.parentElement.insertBefore(searchDiv, table);
        this.searchInput = document.getElementById("mvt-reasons-search");
        this.searchInput.addEventListener("input", () => {
            this.page = 1;
            this.search = this.searchInput.value;
            this.loadReasons();
        });
        // Pagination
        this.pagination = document.createElement("div");
        this.pagination.className = "mt-3 d-flex justify-content-center";
        table.parentElement.appendChild(this.pagination);
    }

    getDialogElement() {
        let dialogs = document.querySelectorAll("#" + this.dialogId);
        for (const dialog of dialogs) {
            dialog.remove();
        }

        const dialogHTML = `
            <dialog id="${this.dialogId}"></dialog>
        `;

        document.body.insertAdjacentHTML("beforeend", dialogHTML);
        this.dialogElement = document.getElementById(this.dialogId);

        if (!this.dialogElement) {
            console.error("Dialog element not found");
            return false;
        }

        return this.dialogElement;
    }

    initForm(reason) {
        this.dialogElement = this.getDialogElement();

        this.dialogElement.innerHTML = `
            <form id="mvt-reason-form" class="bootstrap needs-validation" novalidate>
                <input type="hidden" id="mvt-id" name="id" />
                <div class="card" style="min-width: 600px;">
                    <div class="card-header">
                        <h5 id="mvt-reason-title"></h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="mvt-alias" class="form-label">Alias</label>
                            <input type="text" class="form-control" id="mvt-alias" name="alias" required />
                        </div>
                        <div class="form-group mb-3">
                            <label for="mvt-name" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="mvt-name" name="name" required />
                        </div>
                        <div class="form-group mb-3">
                            <label for="mvt-sign" class="form-label">Segno</label>
                            <select class="form-control" id="mvt-sign" name="sign" required>
                                <option value="1">Entrata (+)</option>
                                <option value="-1">Uscita (-)</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-check-label" for="mvt-deleted">Eliminato</label>
                            <input type="checkbox" class="form-group" id="mvt-deleted" name="deleted" value="0" />
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary" id="mvt-dialog-cancel">Annulla</button>
                        <button type="submit" class="btn btn-primary">Salva</button>
                    </div>
                </div>
            </form>
        `;
        this.dialogElement.addEventListener("close", () => this.dialogElement.classList.remove("show"));

        this.bindFormEvents();
        this.compileForm(reason);
    }

    compileForm(reason) {
        this.editForm = this.dialogElement.querySelector("form");
        this.editForm.reset();
        this.editForm.querySelector("#mvt-id").value = reason ? reason.id_mpstock_mvt_reason : "";
        this.editForm.querySelector("#mvt-alias").value = reason ? reason.alias : "";
        this.editForm.querySelector("#mvt-name").value = reason ? reason.name : "";
        this.editForm.querySelector("#mvt-sign").value = reason ? reason.sign : "1";
        this.editForm.querySelector("#mvt-deleted").checked = reason ? !!reason.deleted : false;
    }

    bindFormEvents() {
        if (this.dialogElement) {
            this.dialogElement.querySelector("#mvt-dialog-cancel").addEventListener("click", () => this.dialogElement.close());
            this.dialogElement.querySelector("#mvt-reason-form").addEventListener("submit", (e) => this.saveReason(e));
        }
    }

    async loadReasons() {
        const formData = new FormData();
        formData.append("page", this.page);
        formData.append("limit", this.limit);
        formData.append("search", this.search || "");
        const res = await fetch(this.endpoints.list, {
            method: "POST",
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            this.total = data.total;
            this.renderTable(data.data);
            this.renderPagination();
        } else {
            this.tableBody.innerHTML = `<tr><td colspan="6">Errore nel caricamento.</td></tr>`;
        }
    }

    renderTable(reasons) {
        this.tableBody.innerHTML = "";
        if (!reasons.length) {
            this.tableBody.innerHTML = `<tr><td colspan="6">Nessun tipo di movimento trovato.</td></tr>`;
            return;
        }
        for (const reason of reasons) {
            const sign = reason.sign;
            const signClass = sign > 0 ? "text-success" : "text-danger";
            const signIcon = sign > 0 ? "arrow_upward" : "arrow_downward";
            const signDesc = sign > 0 ? "Carico" : "Scarico";

            const deleted = parseInt(reason.deleted);
            const deletedClass = deleted ? "text-danger" : "text-success";
            const deletedIcon = deleted ? "close" : "check";
            const deletedDesc = deleted ? "Eliminato" : "Attivo";

            this.tableBody.innerHTML += `
                <tr>
                    <td>${reason.id_mpstock_mvt_reason}</td>
                    <td>${reason.alias ?? "--"}</td>
                    <td>${reason.name}</td>
                    <td><i class="material-icons ${signClass}" title="${signDesc}">${signIcon}</i></td>
                    <td><i class="material-icons ${deletedClass}" title="${deletedDesc}">${deletedIcon}</i></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary me-1" data-id="${reason.id_mpstock_mvt_reason}" data-action="edit">Modifica</button>
                        <button class="btn btn-sm btn-outline-danger" data-id="${reason.id_mpstock_mvt_reason}" data-action="delete">Elimina</button>
                    </td>
                </tr>
            `;
        }
        this.tableBody.querySelectorAll('button[data-action="edit"]').forEach((btn) => btn.addEventListener("click", (e) => this.editReason(e)));
        this.tableBody.querySelectorAll('button[data-action="delete"]').forEach((btn) => btn.addEventListener("click", (e) => this.deleteReason(e)));
    }

    renderPagination() {
        const totalPages = Math.ceil(this.total / this.limit);
        if (totalPages <= 1) {
            this.pagination.innerHTML = "";
            return;
        }
        let html = '<nav><ul class="pagination pagination-sm">';
        for (let i = 1; i <= totalPages; i++) {
            html += `<li class="page-item${i === this.page ? " active" : ""}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        html += "</ul></nav>";
        this.pagination.innerHTML = html;
        this.pagination.querySelectorAll("a.page-link").forEach((a) => {
            a.addEventListener("click", (e) => {
                e.preventDefault();
                const newPage = parseInt(e.target.dataset.page);
                if (newPage !== this.page) {
                    this.page = newPage;
                    this.loadReasons();
                }
            });
        });
    }

    async saveReason(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const res = await fetch(this.endpoints.save, {
            method: "POST",
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            this.dialogElement.close();
            this.loadReasons();
        } else {
            alert(data.message || "Errore nel salvataggio");
        }
    }

    async editReason(e) {
        const id = e.target.dataset.id;
        const res = await fetch(this.endpoints.list);
        const data = await res.json();
        if (data.success) {
            const reason = data.data.find((r) => r.id_mpstock_mvt_reason == id);
            this.initForm(reason);
            this.dialogElement.showModal();
        }
    }

    async deleteReason(e) {
        if (!confirm("Vuoi davvero eliminare questo tipo di movimento?")) return;
        const id = e.target.dataset.id;
        const formData = new FormData();
        formData.append("id", id);
        const res = await fetch(this.endpoints.delete, {
            method: "POST",
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            this.loadReasons();
        } else {
            alert(data.message || "Errore nell'eliminazione");
        }
    }
}
