export class ModalDialogMessage extends HTMLElement {
    static get observedAttributes() {
        return ["type", "title", "timer"];
    }
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._timerId = null;
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    position: fixed;
                    top: 0; left: 0; right: 0; bottom: 0;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: rgba(30, 34, 45, 0.32);
                    z-index: 10000;
                }
                .modal {
                    background: #fff;
                    border-radius: 16px;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                    min-width: 340px;
                    max-width: 92vw;
                    padding: 38px 32px 28px 32px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    animation: fadeIn 0.25s cubic-bezier(.4,0,.2,1);
                    position: relative;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: scale(0.97); }
                    to { opacity: 1; transform: scale(1); }
                }
                .icon {
                    margin-bottom: 14px;
                    width: 54px;
                    height: 54px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: popIn 0.4s cubic-bezier(.4,0,.2,1);
                }
                @keyframes popIn {
                    0% { transform: scale(0.7); opacity: 0; }
                    80% { transform: scale(1.1); }
                    100% { transform: scale(1); opacity: 1; }
                }
                .title {
                    font-size: 1.3rem;
                    font-weight: 600;
                    margin-bottom: 8px;
                    color: #222b45;
                    text-align: center;
                }
                .content {
                    font-size: 1.07rem;
                    color: #384055;
                    margin-bottom: 24px;
                    text-align: center;
                    word-break: break-word;
                }
                .actions {
                    display: flex;
                    gap: 8px;
                    width: 100%;
                    justify-content: center;
                }
                .close-btn {
                    padding: 8px 22px;
                    border-radius: 8px;
                    border: none;
                    background: #4f8cff;
                    color: #fff;
                    font-weight: 500;
                    font-size: 1.06rem;
                    cursor: pointer;
                    transition: background 0.18s;
                }
                .close-btn:hover {
                    background: #2563eb;
                }
                /* Type styles */
                .modal.info .icon svg { color: #2563eb; }
                .modal.success .icon svg { color: #22c55e; }
                .modal.warning .icon svg { color: #f59e42; }
                .modal.error .icon svg { color: #e53e3e; }
            </style>
            <div class="modal info">
                <div class="icon"></div>
                <div class="title"></div>
                <div class="content"></div>
                <div class="actions">
                    <button class="close-btn">Chiudi</button>
                </div>
            </div>
        `;
        this.$modal = this.shadowRoot.querySelector(".modal");
        this.$icon = this.shadowRoot.querySelector(".icon");
        this.$title = this.shadowRoot.querySelector(".title");
        this.$content = this.shadowRoot.querySelector(".content");
        this.$closeBtn = this.shadowRoot.querySelector(".close-btn");
        this.$closeBtn.addEventListener("click", () => this.close());
        // Blocca esc e click esterno
        this.addEventListener("keydown", (e) => {
            if (e.key === "Escape") e.stopPropagation();
        });
        this.addEventListener("click", (e) => {
            if (e.target === this) e.stopPropagation();
        });
    }

    connectedCallback() {
        this.setAttribute("tabindex", "-1");
        this.focus();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "type") this._updateType(newValue);
        if (name === "title") this.setTitle(newValue);
        if (name === "timer") this._setTimer(newValue);
    }

    setType(type) {
        this.setAttribute("type", type);
    }

    setTitle(text) {
        this.$title.textContent = text || "";
    }

    setContent(html) {
        this.$content.innerHTML = html || "";
    }

    setTimer(ms) {
        this.setAttribute("timer", ms);
    }

    _setTimer(ms) {
        if (this._timerId) clearTimeout(this._timerId);
        if (ms && !isNaN(ms)) {
            this._timerId = setTimeout(() => this.close(), parseInt(ms));
        }
    }

    _updateType(type) {
        this.$modal.classList.remove("info", "success", "warning", "error");
        const t = ["info", "success", "warning", "error"].includes(type) ? type : "info";
        this.$modal.classList.add(t);
        this.$icon.innerHTML = this._getIcon(t);
    }

    _getIcon(type) {
        // SVG animati per ogni tipo
        switch (type) {
            case "success":
                return `<svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3"><circle cx="24" cy="24" r="22" stroke="#22c55e" stroke-width="3" fill="#e8fbee"><animate attributeName="r" values="20;22;20" dur="1.2s" repeatCount="indefinite"/></circle><polyline points="15,25 22,32 34,18" stroke="#22c55e" stroke-width="3" fill="none"><animate attributeName="points" values="15,25 22,32 34,18;15,25 22,32 34,18" dur="1.2s" repeatCount="indefinite"/></polyline></svg>`;
            case "error":
                return `<svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3"><circle cx="24" cy="24" r="22" stroke="#e53e3e" stroke-width="3" fill="#fee2e2"><animate attributeName="r" values="20;22;20" dur="1.2s" repeatCount="indefinite"/></circle><line x1="17" y1="17" x2="31" y2="31" stroke="#e53e3e" stroke-width="3"><animate attributeName="x2" values="31;29;31" dur="0.7s" repeatCount="indefinite"/></line><line x1="31" y1="17" x2="17" y2="31" stroke="#e53e3e" stroke-width="3"><animate attributeName="x2" values="17;19;17" dur="0.7s" repeatCount="indefinite"/></line></svg>`;
            case "warning":
                return `<svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3"><circle cx="24" cy="24" r="22" stroke="#f59e42" stroke-width="3" fill="#fff7ed"><animate attributeName="r" values="20;22;20" dur="1.2s" repeatCount="indefinite"/></circle><rect x="21" y="13" width="6" height="15" rx="3" fill="#f59e42"><animate attributeName="y" values="13;15;13" dur="0.8s" repeatCount="indefinite"/></rect><rect x="21" y="32" width="6" height="6" rx="3" fill="#f59e42"></rect></svg>`;
            case "info":
            default:
                return `<svg viewBox="0 0 48 48" width="48" height="48" fill="none" stroke="currentColor" stroke-width="3"><circle cx="24" cy="24" r="22" stroke="#2563eb" stroke-width="3" fill="#e0e7ff"><animate attributeName="r" values="20;22;20" dur="1.2s" repeatCount="indefinite"/></circle><rect x="21" y="15" width="6" height="6" rx="3" fill="#2563eb"></rect><rect x="21" y="25" width="6" height="13" rx="3" fill="#2563eb"><animate attributeName="y" values="25;27;25" dur="0.8s" repeatCount="indefinite"/></rect></svg>`;
        }
    }

    close() {
        this.remove();
    }
}

customElements.define("modal-dialog-message", ModalDialogMessage);

export class ModalDialogMessageManager {
    constructor({ type = "info", title = "", content = "", timer = null } = {}) {
        this.modal = document.createElement("modal-dialog-message");
        document.body.appendChild(this.modal);
        this.modal.setType(type);
        this.modal.setTitle(title);
        this.modal.setContent(content);
        if (timer) this.modal.setTimer(timer);
    }
    setType(type) {
        this.modal.setType(type);
    }
    setTitle(title) {
        this.modal.setTitle(title);
    }
    setContent(html) {
        this.modal.setContent(html);
    }
    setTimer(ms) {
        this.modal.setTimer(ms);
    }
    close() {
        this.modal.close();
    }
}
