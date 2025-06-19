class ConfirmModal {
    constructor(options = {}) {
        this.options = Object.assign(
            {
                title: "Sei sicuro?",
                html: "",
                alertType: "info", // info, success, warning, danger
                icon: "info", // es: 'warning', 'check_circle', 'info', 'delete' (Material Icons)
                confirmText: "Conferma",
                cancelText: "Annulla",
                showClose: false
            },
            options
        );
        this._build();
    }

    _build() {
        // Rimuovi eventuale modale precedente
        if (document.getElementById("confirm-modal")) {
            document.getElementById("confirm-modal").remove();
        }

        // Crea overlay
        this.overlay = document.createElement("div");
        this.overlay.className = "confirm-modal-root confirm-overlay";

        // Crea modale
        this.modal = document.createElement("div");
        this.modal.className = "confirm-modal";
        this.modal.id = "confirm-modal";
        this.modal.innerHTML = `
            <div class="confirm-card alert-${this.options.alertType}">
                <div class="confirm-header">
                    ${this.options.icon ? `<span class="confirm-icon material-icons">${this.options.icon}</span>` : ""}
                    <span class="confirm-title">${this.options.title}</span>
                    ${this.options.showClose ? `<button class="confirm-close" aria-label="Chiudi">&times;</button>` : ""}
                </div>
                <div class="confirm-body">
                    ${this.options.html || ""}
                </div>
                <div class="confirm-footer">
                    <button class="btn btn-confirm">${this.options.confirmText}</button>
                    <button class="btn btn-cancel">${this.options.cancelText}</button>
                </div>
            </div>
        `;
        this.overlay.appendChild(this.modal);
        document.body.appendChild(this.overlay);
        this._bindEvents();
    }

    _bindEvents() {
        this.promise = new Promise((resolve) => {
            this.modal.querySelector(".btn-confirm").onclick = () => {
                this.close();
                resolve(true);
            };
            this.modal.querySelector(".btn-cancel").onclick = () => {
                this.close();
                resolve(false);
            };
            if (this.options.showClose) {
                this.modal.querySelector(".confirm-close").onclick = () => {
                    this.close();
                    resolve(false);
                };
            }
            // Chiudi con ESC
            this._escHandler = (e) => {
                if (e.key === "Escape") {
                    this.close();
                    resolve(false);
                }
            };
            document.addEventListener("keydown", this._escHandler);
        });
    }

    show() {
        setTimeout(() => {
            this.overlay.classList.add("show");
            // Animazione SWAL-like
            const card = this.modal.querySelector(".confirm-card");
            if (card) {
                card.classList.remove("swal-animate"); // reset se già presente
                // Forza reflow per ri-triggerare animazione se necessario
                void card.offsetWidth;
                card.classList.add("swal-animate");
                // Rimuovi la classe dopo l'animazione
                card.addEventListener("animationend", function handler() {
                    card.classList.remove("swal-animate");
                    card.removeEventListener("animationend", handler);
                });
            }
        }, 10);
        return this.promise;
    }

    close() {
        if (this.overlay) {
            this.overlay.classList.remove("show");
            setTimeout(() => {
                if (this.overlay && this.overlay.parentNode) {
                    this.overlay.parentNode.removeChild(this.overlay);
                }
            }, 200);
        }
        document.removeEventListener("keydown", this._escHandler);
    }

    // Factory helper per alert semplice
    static alert(options = {}) {
        const defaults = {
            title: "Attenzione",
            html: "<div>Sei sicuro di voler continuare?</div>",
            alertType: "warning",
            icon: "warning",
            confirmText: "Sì",
            cancelText: "No",
            showClose: true
        };
        const modal = new ConfirmModal(Object.assign({}, defaults, options));
        return modal.show();
    }
}

// Stili moderni e colorati
const confirmModalStyles = `
    .confirm-modal-root.confirm-overlay {
        position: fixed; left: 0; top: 0; width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.25); z-index: 99999;
        display: flex; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.2s;
    }
    .confirm-modal-root.confirm-overlay.show { opacity: 1; }
    .confirm-modal-root .confirm-modal {
        background: none; border: none; box-shadow: none; max-width: 95vw;
    }
    .confirm-modal-root .confirm-card {
        min-width: 320px; max-width: 400px; background: #fff; border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.18);
        overflow: hidden;
        display: flex; flex-direction: column;
    }
    .confirm-modal-root .swal-animate {
        animation: popIn 0.38s cubic-bezier(0.22, 1, 0.36, 1);
    }
    @keyframes popIn {
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
    .confirm-modal-root .swal-animate {
        animation: popIn 0.38s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .confirm-modal-root @keyframes popIn { from { transform: scale(0.7); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .confirm-modal-root .confirm-header {
        display: flex; align-items: center; padding: 1rem 1.5rem 0.5rem 1.5rem;
        font-size: 1.2rem; font-weight: 600; border-bottom: 1px solid #f0f0f0;
        background: transparent;
    }
    .confirm-modal-root .confirm-icon { margin-right: 0.75rem; font-size: 1.7rem; }
    .confirm-modal-root .confirm-title { flex: 1; }
    .confirm-modal-root .confirm-close {
        background: none; border: none; color: #888; font-size: 1.4rem; cursor: pointer;
        margin-left: 0.5rem; transition: color 0.15s;
    }
    .confirm-modal-root .confirm-close:hover { color: #e74c3c; }
    .confirm-modal-root .confirm-body {
        padding: 1.25rem 1.5rem; font-size: 1.06rem; color: #333;
    }
    .confirm-modal-root .confirm-footer {
        display: flex; justify-content: flex-end; gap: 0.5rem; padding: 0.8rem 1.5rem 1rem 1.5rem;
        background: transparent;
    }
    .confirm-modal-root .btn {
        border: none; border-radius: 5px; padding: 0.5rem 1.3rem;
        font-weight: 600; font-size: 1rem; cursor: pointer; transition: background 0.15s;
    }
    .confirm-modal-root .btn-confirm { background: #3b82f6; color: #fff; }
    .confirm-modal-root .btn-confirm:hover { background: #2563eb; }
    .confirm-modal-root .btn-cancel { background: #f3f4f6; color: #333; }
    .confirm-modal-root .btn-cancel:hover { background: #e5e7eb; }
    .confirm-modal-root .alert-warning { border-left: 5px solid #fbbf24; }
    .confirm-modal-root .alert-info { border-left: 5px solid #3b82f6; }
    .confirm-modal-root .alert-danger { border-left: 5px solid #ef4444; }
    .confirm-modal-root .alert-success { border-left: 5px solid #22c55e; }
`;

if (!document.getElementById("confirm-modal-style")) {
    const style = document.createElement("style");
    style.id = "confirm-modal-style";
    style.innerHTML = confirmModalStyles;
    document.head.appendChild(style);
}

export default ConfirmModal;
