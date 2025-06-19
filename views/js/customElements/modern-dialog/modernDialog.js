export class ModernDialog extends HTMLElement {
    static get observedAttributes() {
        return ["type", "title", "timer"];
    }
    static get observedAttributes() {
        return ["type", "title", "timer", "data-animation"];
    }
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._timerId = null;
        this._isClosing = false;
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
                    border: 0.22rem solid var(--dialog-border, #2563eb);
                    box-shadow: 0 8px 32px rgba(0,0,0,0.18), 0 0 0 2px var(--dialog-border-glow, #2563eb22);
                    transition: border-color 0.18s, box-shadow 0.22s, opacity 0.28s cubic-bezier(.4,0,.2,1), transform 0.28s cubic-bezier(.4,0,.2,1);
                    opacity: 1;
                    transform: scale(1);
                    background: #fff;
                    border-radius: 16px;
                    min-width: 340px;
                    max-width: 92vw;
                    padding: 38px 32px 28px 32px;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    animation: fadeIn 0.25s cubic-bezier(.4,0,.2,1);
                    position: relative;
                }
                .modal.fade-in {
                    opacity: 1;
                    transform: scale(1);
                }
                .modal.fade-out {
                    opacity: 0;
                    pointer-events: none;
                    transform: scale(0.97);
                }
                .modal.scale-in {
                    opacity: 1;
                    transform: scale(1.04);
                }
                .modal.scale-out {
                    opacity: 0;
                    transform: scale(0.8);
                    pointer-events: none;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: scale(0.97); }
                    to { opacity: 1; transform: scale(1); }
                }
                .icon {
                    margin-bottom: 24px;
                    margin-top: 0;
                    width: 68px;
                    height: 68px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    animation: popIn 0.44s cubic-bezier(.4,0,.2,1);
                    /* niente position/left/transform: solo flex centrato */
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
                    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
                    font-weight: 500;
                    font-size: 1.06rem;
                    cursor: pointer;
                    transition: background 0.18s;
                }
                .close-btn:hover {
                    background: #2563eb;
                }
                /* Pulsante SI (yes-btn) */
                .yes-btn {
                    padding: 8px 22px;
                    border-radius: 8px;
                    border: none;
                    background: var(--yes-btn-bg, #2563eb);
                    color: #fff;
                    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
                    font-weight: 600;
                    font-size: 1.06rem;
                    cursor: pointer;
                    transition: background 0.18s, box-shadow 0.18s;
                    box-shadow: 0 1px 4px #2563eb11;
                }
                .yes-btn:hover {
                    background: var(--yes-btn-bg-hover, #174bb5);
                }
                /* Pulsante NO (no-btn) */
                .no-btn {
                    padding: 8px 22px;
                    border-radius: 8px;
                    border: none;
                    background: #e5e7eb;
                    color: #384055;
                    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
                    font-weight: 500;
                    font-size: 1.06rem;
                    cursor: pointer;
                    transition: background 0.18s, color 0.18s;
                }
                .no-btn:hover {
                    background: #cbd5e1;
                    color: #222b45;
                }
                .yes-btn:hover {
                    background: var(--yes-btn-bg-hover, #174bb5);
                }
                /* Pulsante NO (no-btn) */
                .no-btn {
                    padding: 8px 22px;
                    border-radius: 8px;
                    border: none;
                    background: #e5e7eb;
                    color: #384055;
                    font-family: 'Segoe UI', Roboto, Arial, sans-serif;
                    font-weight: 500;
                    font-size: 1.06rem;
                    cursor: pointer;
                    transition: background 0.18s, color 0.18s;
                }
                .no-btn:hover {
                    background: #cbd5e1;
                    color: #222b45;
                }
                /* Colori SI per tipo */
                .modal.success .yes-btn {
                    --yes-btn-bg: #22c55e;
                    --yes-btn-bg-hover: #15803d;
                }
                .modal.error .yes-btn {
                    --yes-btn-bg: #e53e3e;
                    --yes-btn-bg-hover: #b91c1c;
                }
                .modal.warning .yes-btn {
                    --yes-btn-bg: #f59e42;
                    --yes-btn-bg-hover: #d97706;
                }
                .modal.info .yes-btn {
                    --yes-btn-bg: #2563eb;
                    --yes-btn-bg-hover: #174bb5;
                }
                /* Type styles */
                .modal.info .icon svg { color: #2563eb; }
                .modal.success .icon svg { color: #22c55e; }
                .modal.warning .icon svg { color: #f59e42; }
                .modal.error .icon svg { color: #e53e3e; }

                .modal.info { --dialog-border: #2563eb; --dialog-border-glow: #2563eb44; }
                .modal.success { --dialog-border: #22c55e; --dialog-border-glow: #22c55e44; }
                .modal.warning { --dialog-border: #f59e42; --dialog-border-glow: #f59e4244; }
                .modal.error { --dialog-border: #e53e3e; --dialog-border-glow: #e53e3e44; }
            </style>
            <div class="modal info">
                <div class="icon"></div>
                <div class="title"></div>
                <div class="content"></div>
                <div class="actions">
                    <!-- I pulsanti saranno renderizzati dinamicamente -->
                </div>
            </div>
        `;
        this.$modal = this.shadowRoot.querySelector(".modal");
        this.$icon = this.shadowRoot.querySelector(".icon");
        this.$title = this.shadowRoot.querySelector(".title");
        this.$content = this.shadowRoot.querySelector(".content");
        this.$actions = this.shadowRoot.querySelector(".actions");
        this._renderActions();
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
        // Applica animazione di apertura ogni volta che il dialog viene mostrato
        let modal = this.$modal;
        const getAnim = () => (this.getAttribute("data-animation") || "scale-in").toLowerCase();
        // Observer per display: ogni volta che il dialog viene mostrato, riapplica l'animazione
        if (!this._displayObserver) {
            this._displayObserver = new MutationObserver(() => {
                if (this.style.display !== "none") {
                    // Riapplica animazione
                    modal.classList.remove("fade-in", "fade-out", "scale-in", "scale-out");
                    const anim = getAnim();
                    if (anim === "fade-in") {
                        modal.classList.add("fade-in");
                    } else if (anim === "scale-in") {
                        modal.classList.add("scale-in");
                    } // else nessuna animazione
                }
            });
            this._displayObserver.observe(this, { attributes: true, attributeFilter: ["style"] });
        }
        // Prima apertura
        modal.classList.remove("fade-in", "fade-out", "scale-in", "scale-out");
        const anim = getAnim();
        if (anim === "fade-in") {
            modal.classList.add("fade-in");
        } else if (anim === "scale-in") {
            modal.classList.add("scale-in");
        } // else nessuna animazione
        // reset chiusura
        this._isClosing = false;
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (["show-confirm-buttons", "data-yes-label", "data-no-label", "data-callback"].includes(name)) {
            this._renderActions();
        }
        if (name === "title") this.setTitle(newValue);
        if (name === "timer") this._setTimer(newValue);
        if (name === "type") this._updateType(newValue);
        if (name === "data-animation" && this.isConnected) {
            let modal = this.$modal;
            modal.classList.remove("fade-in", "fade-out", "scale-in", "scale-out");
            const anim = (newValue || "scale-in").toLowerCase();
            if (anim === "fade-in") {
                modal.classList.add("fade-in");
            } else if (anim === "scale-in") {
                modal.classList.add("scale-in");
            }
        }
    }

    setType(type) {
        this.setAttribute("type", type);
        this._updateType(type);
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

    _renderActions() {
        if (!this.$actions) return;
        const showConfirm = this.hasAttribute("show-confirm-buttons");
        const yesLabel = this.getAttribute("data-yes-label") || "SI";
        const noLabel = this.getAttribute("data-no-label") || "NO";
        this.$actions.innerHTML = "";
        if (showConfirm) {
            // Pulsante NO
            const btnNo = document.createElement("button");
            btnNo.className = "no-btn";
            btnNo.textContent = noLabel;
            btnNo.addEventListener("click", () => {
                this._handleResult(0);
            });
            // Pulsante SI
            const btnYes = document.createElement("button");
            btnYes.className = "yes-btn";
            btnYes.textContent = yesLabel;
            btnYes.addEventListener("click", () => {
                this._handleResult(1);
            });
            this.$actions.appendChild(btnNo);
            this.$actions.appendChild(btnYes);
        } else {
            // Pulsante Chiudi classico
            const btnClose = document.createElement("button");
            btnClose.className = "close-btn";
            btnClose.textContent = "Chiudi";
            btnClose.addEventListener("click", () => this.close());
            this.$actions.appendChild(btnClose);
            this.$closeBtn = btnClose;
        }
    }

    _handleResult(val) {
        const cbName = this.getAttribute("data-callback");
        let handled = false;
        if (cbName && typeof window[cbName] === "function") {
            window[cbName](val);
            handled = true;
        }
        // Evento custom se nessun callback
        if (!handled) {
            this.dispatchEvent(new CustomEvent("dialog-result", { detail: val, bubbles: true }));
        }
        this.close();
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
        if (this._isClosing) return;
        this._isClosing = true;
        const anim = (this.getAttribute("data-animation") || "fade-in").toLowerCase();
        let modal = this.$modal;
        modal.classList.remove("fade-in", "fade-out", "scale-in", "scale-out");
        if (anim === "scale-in") {
            modal.classList.add("scale-out");
        } else if (anim === "none") {
            this.remove();
            return;
        } else {
            modal.classList.add("fade-out");
        }
        const handler = () => {
            modal.removeEventListener("transitionend", handler);
            this._isClosing = false;
            this.remove();
        };
        modal.addEventListener("transitionend", handler);
    }
}

customElements.define("modern-dialog", ModernDialog);

export class ModernDialogManager {
    constructor(opts) {
        this.type = opts.type || "info";
        this.title = opts.title || "Dialog";
        this.content = opts.content || "";
        this.yesLabel = opts.yesLabel || "SI";
        this.noLabel = opts.noLabel || "NO";
        this.callback = opts.callback || null;
        this.animation = opts.animation || "scale-in";
        this.dialogs = [];
        this._init();
    }

    _init() {
        document.addEventListener("dialog-result", (e) => {
            const dlg = this.dialogs.find((d) => d.id === e.detail.id);
            if (dlg) {
                dlg.close();
                this.dialogs.splice(this.dialogs.indexOf(dlg), 1);
            }
        });
    }

    /**
     * Mostra un dialog di conferma SI/NO con API semplice.
     * @param {Object} opts
     * @param {string} [opts.type] - Tipo dialog ('info', 'success', 'warning', 'error')
     * @param {string} [opts.title] - Titolo
     * @param {string} [opts.content] - Contenuto (anche HTML)
     * @param {string} [opts.yesLabel] - Etichetta SI
     * @param {string} [opts.noLabel] - Etichetta NO
     * @param {function} [opts.callback] - Funzione richiamata con 1 (SI) o 0 (NO)
     * @param {string} [opts.animation] - Animazione ('scale-in', 'fade-in', 'none')
     * @returns {ModernDialogManager} - Il manager creato, per aggiungere eventualmente listener
     * @example
     * ModernDialogManager.showConfirm({
     *   type: 'warning',
     *   title: 'Sei sicuro?',
     *   content: 'Vuoi procedere?',
     *   yesLabel: 'Sì',
     *   noLabel: 'No',
     *   callback: function(result) { ... },
     *   animation: 'scale-in'
     * });
     */
    static showConfirm({ type = "info", title = "", content = "", yesLabel = "SI", noLabel = "NO", callback = null, animation = "scale-in" } = {}) {
        const mgr = new ModernDialogManager({ type, title, content, yesLabel, noLabel, callback, animation });
        mgr.modal.setAttribute("show-confirm-buttons", "");
        if (callback && typeof callback === "function") {
            // Registra la funzione come globale temporanea e la rimuove dopo l'uso
            const cbName = "__dlg_cb_" + Math.random().toString(36).slice(2);
            window[cbName] = function (val) {
                callback(val);
                delete window[cbName];
            };
            mgr.modal.setAttribute("data-callback", cbName);
        }
        return mgr;
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
