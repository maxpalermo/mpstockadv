export class ModalDialogProgress extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    position: fixed;
                    top: 0; left: 0; right: 0; bottom: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(30, 34, 45, 0.32);
                    z-index: 9999;
                }
                .modal {
                    background: #fff;
                    border-radius: 14px;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                    min-width: 340px;
                    max-width: 90vw;
                    padding: 32px 28px 24px 28px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    animation: fadeIn 0.22s cubic-bezier(.4,0,.2,1);
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: scale(0.96); }
                    to { opacity: 1; transform: scale(1); }
                }
                .title {
                    font-size: 1.25rem;
                    font-weight: 600;
                    margin-bottom: 8px;
                    color: #222b45;
                    text-align: center;
                }
                .desc {
                    font-size: 1rem;
                    color: #5b667d;
                    margin-bottom: 22px;
                    text-align: center;
                }
                .progress-container {
                    width: 100%;
                    background: #e7eaf3;
                    border-radius: 8px;
                    height: 18px;
                    margin-bottom: 18px;
                    overflow: hidden;
                }
                .progress-bar {
                    height: 100%;
                    background: linear-gradient(90deg, #4f8cff 0%, #5eead4 100%);
                    width: 0%;
                    transition: width 0.3s cubic-bezier(.4,0,.2,1);
                }
                .progress-label {
                    font-size: 0.95rem;
                    margin-bottom: 16px;
                    color: #4f8cff;
                    font-weight: 500;
                    text-align: center;
                }
                .actions {
                    margin-top: 10px;
                    display: flex;
                    gap: 8px;
                    width: 100%;
                    justify-content: center;
                }
                button {
                    padding: 7px 18px;
                    border-radius: 7px;
                    border: none;
                    background: #e53e3e;
                    color: #fff;
                    font-weight: 500;
                    font-size: 1rem;
                    cursor: pointer;
                    transition: background 0.18s;
                }
                button:disabled {
                    opacity: 0.7;
                    cursor: not-allowed;
                }
                .done {
                    background: #22c55e;
                }
                .error {
                    background: #e53e3e;
                }
            </style>
            <div class="modal">
                <div class="title"></div>
                <div class="desc"></div>
                <div class="progress-label"></div>
                <div class="progress-container">
                    <div class="progress-bar"></div>
                </div>
                <div class="actions">
                    <button class="abort">Annulla</button>
                </div>
            </div>
        `;
        this.$title = this.shadowRoot.querySelector(".title");
        this.$desc = this.shadowRoot.querySelector(".desc");
        this.$progressBar = this.shadowRoot.querySelector(".progress-bar");
        this.$progressLabel = this.shadowRoot.querySelector(".progress-label");
        this.$abortBtn = this.shadowRoot.querySelector(".abort");
        this.$actions = this.shadowRoot.querySelector(".actions");
        this.$modal = this.shadowRoot.querySelector(".modal");
        this._onAbort = null;
        this._aborted = false;
        this._abortController = new AbortController();
        this.$abortBtn.addEventListener("click", () => this.abort());
    }

    setTitle(text) {
        this.$title.textContent = text || "";
    }

    setDescription(text) {
        this.$desc.textContent = text || "";
    }

    update(progress, label = "", total = 100) {
        // progress: 0-100 or absolute
        let percent = progress;
        if (total !== 100) {
            percent = Math.round((progress / total) * 100);
        }
        this.$progressBar.style.width = percent + "%";
        this.$progressLabel.textContent = label || `${percent}%`;
    }

    showDone(message = "Completato!") {
        this.$progressBar.style.width = "100%";
        this.$progressBar.style.background = "#22c55e";
        this.$progressLabel.textContent = message;
        this.$abortBtn.disabled = true;
    }

    showError(message = "Errore!") {
        this.$progressBar.style.background = "#e53e3e";
        this.$progressLabel.textContent = message;
        this.$abortBtn.disabled = true;
    }

    abort() {
        if (!this._aborted) {
            this._aborted = true;
            this._abortController.abort();
            this.$abortBtn.disabled = true;
            this.$progressLabel.textContent = "Operazione annullata";
            if (typeof this._onAbort === "function") this._onAbort();
            this.dispatchEvent(new CustomEvent("aborted", { bubbles: true, composed: true }));
        }
    }

    onAbort(cb) {
        this._onAbort = cb;
    }

    get abortController() {
        return this._abortController;
    }

    get aborted() {
        return this._aborted;
    }

    close() {
        this.remove();
    }
}

customElements.define("modal-dialog-progress", ModalDialogProgress);

// Classe di gestione esterna, per praticità
export class ModalDialogProgressManager {
    constructor({ title = "", description = "" } = {}) {
        this.modal = document.createElement("modal-dialog-progress");
        document.body.appendChild(this.modal);
        this.modal.setTitle(title);
        this.modal.setDescription(description);
    }

    update(progress, label = "", total = 100) {
        this.modal.update(progress, label, total);
    }

    showDone(message = "Completato!") {
        this.modal.showDone(message);
    }

    showError(message = "Errore!") {
        this.modal.showError(message);
    }

    close() {
        this.modal.close();
    }

    onAbort(cb) {
        this.modal.onAbort(cb);
    }

    get abortController() {
        return this.modal.abortController;
    }

    get aborted() {
        return this.modal.aborted;
    }
}
