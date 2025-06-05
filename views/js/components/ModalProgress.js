/**
 * ModalProgress.js
 * Modale con barra di avanzamento aggiornata in tempo reale e supporto AbortController.
 * Usage:
 *   const modal = new ModalProgress({ title: "Importazione", icon: "hourglass_empty" });
 *   modal.update(30, "Elaborazione...");
 *   modal.onAbort = () => { ... };
 *   modal.open();
 *   // ...
 *   modal.close();
 */

export default class ModalProgress {
    constructor(options = {}) {
        this.options = Object.assign(
            {
                title: "",
                icon: "",
                showClose: true,
                confirmText: "Chiudi",
                initialValue: 0,
                initialLabel: ""
            },
            options
        );
        this.overlay = document.createElement("div");
        this.overlay.className = "modal-progress-overlay";
        this.modal = document.createElement("div");
        this.modal.className = "modal-progress";
        this.abortController = new AbortController();
        this._render();
        this._bindEvents();
        this.onAbort = null; // callback custom
    }

    _render() {
        const modalHTML = `
        <template id="modal-progress-template">
            <dialog class="modal-progress-dialog" id="modal-progress-dialog">
                <form method="dialog" class="modal-progress-content">
                    <div class="modal-progress-header">
                        ${this.options.icon ? `<span class="modal-progress-icon material-icons">${this.options.icon}</span>` : ""}
                        <span class="modal-progress-title">${this.options.title}</span>
                        ${this.options.showClose ? `<button type="button" class="modal-progress-close" aria-label="Chiudi">&times;</button>` : ""}
                    </div>
                    <div class="modal-progress-body">
                        <div class="modal-progress-bar-container">
                            <progress class="modal-progress-bar" value="${this.options.initialValue}" max="100"></progress>
                            <span class="modal-progress-label">${this.options.initialLabel}</span>
                        </div>
                    </div>
                    <div class="modal-progress-footer">
                        <button type="button" class="btn btn-close">${this.options.confirmText}</button>
                    </div>
                </form>
            </dialog>
        </template>
        `;

        try {
            // Crea parser e parsea l'HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(modalHTML, "text/html");

            // Estrai il template
            const template = doc.querySelector("#modal-progress-template");
            if (!template) throw new Error("Template non trovato");

            // Clona e inserisci
            this.modal = template.content.querySelector("dialog").cloneNode(true);
        } catch (error) {
            console.error("Errore creazione modal:", error);
            return null;
        }
    }

    _bindEvents() {
        // Bottone chiudi (footer)
        this.modal.querySelector(".btn-close").onclick = () => {
            this.abort();
        };
        // Bottone chiudi (header)
        if (this.options.showClose) {
            const btn = this.modal.querySelector(".modal-progress-close");
            if (btn) btn.onclick = () => this.abort();
        }
    }

    open() {
        let element = document.body.querySelector("#modal-progress-dialog");
        if (!element) {
            element = document.body.appendChild(this.modal);
        }
        element.showModal();
    }

    close() {
        let element = document.body.querySelector("#modal-progress-dialog");
        if (element) {
            element.close();
            element.remove();
        }
    }

    /**
     * Aggiorna la barra di avanzamento e il testo
     * @param {number} percent Percentuale (0-100)
     * @param {string} label Testo da visualizzare
     */
    update(percent, label = "") {
        const bar = this.modal.querySelector(".modal-progress-bar");
        const lbl = this.modal.querySelector(".modal-progress-label");
        if (bar) bar.value = percent;
        if (lbl) lbl.textContent = label;
    }

    /**
     * Triggera l'abort delle chiamate e chiude il modale
     */
    abort() {
        if (this.abortController) {
            this.abortController.abort();
        }
        if (typeof this.onAbort === "function") {
            this.onAbort();
        }
        this.close();
    }

    /**
     * Restituisce il signal per fetch/AbortController
     */
    get signal() {
        return this.abortController.signal;
    }
}

/*
CSS suggerito:
.modal-progress-overlay { position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:9999; display:flex; align-items:center; justify-content:center; }
.modal-progress { background:#fff; border-radius:6px; min-width:320px; max-width:90vw; box-shadow:0 2px 16px rgba(0,0,0,0.2); }
.modal-progress-header { display:flex; align-items:center; justify-content:space-between; padding:1em 1em 0.5em 1em; }
.modal-progress-title { font-weight:bold; font-size:1.1em; }
.modal-progress-close { background:none; border:none; font-size:1.5em; cursor:pointer; }
.modal-progress-body { padding:1em; }
.modal-progress-bar-container { display:flex; flex-direction:column; align-items:center; }
.modal-progress-bar { width:100%; height:18px; margin-bottom:0.5em; }
.modal-progress-label { font-size:0.95em; color:#444; }
.modal-progress-footer { padding:0.5em 1em 1em 1em; display:flex; justify-content:flex-end; }
.btn.btn-close { background:#1976d2; color:#fff; border:none; border-radius:3px; padding:0.5em 1.2em; cursor:pointer; }
*/
