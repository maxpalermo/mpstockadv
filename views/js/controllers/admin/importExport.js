class ImportExport {
    mvtForm = null;
    ordersForm = null;
    xmlForm = null;
    endpoints = null;
    dialogId = "dialogImportPanel";
    static dialogElement = null;
    dialogCard = null;
    dialogHeader = null;
    dialogContent = null;
    dialogFooter = null;
    dialogCloseBtn = null;

    constructor(endpoints) {
        this.endpoints = endpoints;
        this.dialogElement = this.initDialog();
        this.initFileInputs();
        this.initForms();

        if (this.closeBtn) {
            this.closeBtn.addEventListener("click", () => this.dialogElement.close());
        }

        console.log("CLASS IMPORT EXPORT LOADED ");
    }

    initFileInputs() {
        // Mostra nome file selezionato per ogni input file
        const fileInputs = [
            { input: "mvt-reason-file", label: "mvt-reason-file-name" },
            { input: "xml-file", label: "xml-file-name" }
        ];
        fileInputs.forEach((f) => {
            const input = document.getElementById(f.input);
            const label = document.getElementById(f.label);
            if (input && label) {
                input.addEventListener("change", () => {
                    label.textContent = input.files.length ? input.files[0].name : "";
                });
            }
        });
    }

    initForms() {
        // Tipi di movimento (CSV/JSON)
        console.log("INIT FORMS");
        this.mvtForm = document.getElementById("import-mvt-reason-form");
        if (this.mvtForm) {
            this.mvtForm.addEventListener("submit", (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                this.importMvtReason(this.mvtForm);
            });
        }
        // Movimenti da ordini
        this.ordersForm = document.getElementById("import-orders-form");
        if (this.ordersForm) {
            this.ordersForm.addEventListener("submit", (e) => {
                e.preventDefault();
                this.importOrders(this.ordersForm);
            });
        }
        // XML
        this.xmlForm = document.getElementById("import-xml-form");
        if (this.xmlForm) {
            this.xmlForm.addEventListener("submit", (e) => {
                e.preventDefault();
                this.importXML(this.xmlForm);
            });
        }
    }

    _injectDialogStyle() {
        const style = document.createElement("style");
        const dialogId = this.dialogId;
        style.textContent = `
            #${dialogId} {
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                padding: 0;
                margin: 0;
                min-width: unset;
                min-height: unset;
                max-width: unset;
                max-height: unset;
                overflow: visible;
            }
            #${dialogId}[open] {
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                width: 100vw;
                position: fixed;
                top: 0;
                left: 0;
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            #${dialogId}::backdrop {
                background: rgba(0,0,0,0.35);
                backdrop-filter: blur(2px);
                z-index: 10002;
            }
            #${dialogId} .card {
                position: relative;
                margin: auto;
                min-width: 50rem;
                min-height: 10rem;
                border: none;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 8px 32px rgba(0,0,0,0.33), 0 1.5px 8px rgba(0,0,0,0.12);
                margin: 0;
                background: #fff;
                animation: swal-popin 0.4s cubic-bezier(0.23, 1.25, 0.32, 1) both;
            }
            @keyframes swal-popin {
                0% {
                    opacity: 0;
                    transform: scale(0.7);
                }
                45% {
                    opacity: 1;
                    transform: scale(1.05);
                }
                80% {
                    transform: scale(0.98);
                }
                100% {
                    opacity: 1;
                    transform: scale(1);
                }
            }
            #${dialogId} .card-header {
                background: var(--blue) !important;
                color: var(--white) !important;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #e9ecef;
                font-size: 1.2rem;
                font-weight: 500;
            }
            #${dialogId} .card-header h5 {
                color: var(--white);
            }
            #${dialogId} .card-body {
                padding: 1.5rem;
                font-size: 1rem;
                background: #fff;
            }
            #${dialogId} .card-footer {
                background: #f8f9fa;
                padding: 1rem 1.5rem;
                border-top: 1px solid #e9ecef;
                text-align: right;
            }
            #${dialogId} .btn {
                min-width: 90px;
                font-size: 1rem;
                border-radius: 8px;
                box-shadow: none;
            }
            @media (max-width: 600px) {
                #${dialogId} .card { min-width: 90vw; }
            }
        `;
        document.head.appendChild(style);
    }

    initDialog() {
        this._injectDialogStyle();
        const dialogHTML = `
            <dialog id="${this.dialogId}">
                <div class="card">
                    <div class="card-header">
                        <h5 id="import-status-title"></h5>
                    </div>
                    <div class="card-body">
                        <div id="import-status-content"></div>
                    </div>
                    <div class="card-footer">
                        <button id="close-dialog" class="btn btn-secondary">Chiudi</button>
                    </div>
                </div>
            </dialog>
        `;

        const dialogElement = document.getElementById(this.dialogId);
        if (!dialogElement) {
            document.body.insertAdjacentHTML("beforeend", dialogHTML);
        }

        this.dialogElement = document.getElementById(this.dialogId);
        if (!this.dialogElement) {
            console.error("Dialog element not found");
            return false;
        }

        this.dialogCard = this.dialogElement.querySelector(".card");
        this.dialogHeader = this.dialogCard.querySelector(".card-header");
        this.dialogContent = this.dialogCard.querySelector(".card-body");
        this.dialogFooter = this.dialogCard.querySelector(".card-footer");
        this.dialogCloseBtn = this.dialogFooter.querySelector("button");

        this.dialogCloseBtn.addEventListener("click", () => this.dialogElement.close());

        return this.dialogElement;
    }

    showDialog(title = "Progresso", content = "[...]") {
        this.dialogHeader.querySelector("h5").textContent = title;
        this.dialogContent.innerHTML = content;
        this.dialogElement.showModal();
    }

    updateDialog(content) {
        this.dialogContent.innerHTML = content;
    }

    async importMvtReason(form) {
        const formData = new FormData(form);
        this.showDialog("Importazione Tipi di Movimento", "Importazione in corso...");
        try {
            const response = await fetch(this.endpoints.mvtReason, {
                method: "POST",
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                this.updateDialog('<span class="text-success">Importazione completata!</span>');
            } else {
                const message = data.message || "Errore generico";
                const type = data.type || "";
                const mimeType = data.mimeType || "";
                const errors = data.errors || [];
                this.updateDialog(`
                    <span class="text-danger">Errore: ${message}</span>
                    <pre>${JSON.stringify({ type, mimeType }, null, 2)}</pre>
                    <pre>${JSON.stringify(errors, null, 2)}</pre>
                `);
            }
        } catch (err) {
            this.updateDialog('<span class="text-danger">Errore di comunicazione col server.</span>');
        }
    }

    async importOrders(form) {
        this.showDialog("Importazione Movimenti da Ordini", "Importazione in corso...");
        try {
            const response = await fetch(this.endpoints.orders, {
                method: "POST",
                body: new FormData(form)
            });
            const data = await response.json();
            if (data.success) {
                this.updateDialog('<span class="text-success">Importazione completata!</span>');
            } else {
                this.updateDialog('<span class="text-danger">Errore: ' + (data.error || "Errore generico") + "</span>");
            }
        } catch (err) {
            this.updateDialog('<span class="text-danger">Errore di comunicazione col server.</span>');
        }
    }

    async importXML(form) {
        const formData = new FormData(form);
        this.showDialog("Importazione da XML", "Importazione in corso...");
        try {
            const response = await fetch(this.endpoints.xml, {
                method: "POST",
                body: formData
            });
            const data = await response.json();
            if (data.success) {
                this.updateDialog('<span class="text-success">Importazione completata!</span>');
            } else {
                this.updateDialog('<span class="text-danger">Errore: ' + (data.error || "Errore generico") + "</span>");
            }
        } catch (err) {
            this.updateDialog('<span class="text-danger">Errore di comunicazione col server.</span>');
        }
    }
}

export default ImportExport;
