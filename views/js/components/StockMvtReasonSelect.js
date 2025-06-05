// Componente riutilizzabile per la select dei motivi di movimento magazzino (stock movement reason)
// Usage: import StockMvtReasonSelect from '../components/StockMvtReasonSelect.js';
//        const select = StockMvtReasonSelect.createSelect({ id: 'id_stock_mvt_reason', name: 'id_stock_mvt_reason', className: 'form-control select2 mvt-reasons' });
//        StockMvtReasonSelect.init();

// Componente riutilizzabile per la select dei motivi di movimento magazzino (stock movement reason)
// Usage:
// import StockMvtReasonSelect from '../components/StockMvtReasonSelect.js';
// const mvtReason = new StockMvtReasonSelect({ endpoint: '/admin/mpstockadv/mvt-reasons' });
// mvtReason.createSelect('#mvtReason-group', { id: 'id_stock_mvt_reason', name: 'id_stock_mvt_reason', className: 'form-control select2 mvt-reasons' });
// mvtReason.init();

class StockMvtReasonSelect {
    mvtReasons = [];
    select = null;
    options = {};
    fetchMvtReasonsUrl = "";

    /**
     * @param {Object} options - Opzioni di configurazione (endpoint, select2, ecc)
     */
    constructor(options = {}) {
        this.options = options;
        this.fetchMvtReasonsUrl = window.APP_ROUTES.fetchMvtReasonsUrl;
        this.init();
    }

    getSelect() {
        return this.select;
    }

    async getMvtReasons() {
        try {
            const response = await fetch(this.fetchMvtReasonsUrl);
            const data = await response.json();
            this.mvtReasons = data.result || [];
        } catch (error) {
            console.error("Error fetching mvt reasons:", error);
        }
    }

    /**
     * Crea dinamicamente un elemento <select> per i motivi movimento.
     * @param {Object} params - Parametri: id (string), name (string), className (string, opzionale), dataSign (string, opzionale), style (string, opzionale)
     * @returns {HTMLElement} - Il nodo <select> creato
     */
    createSelect() {
        const params = this.options.select;
        const mvtReasons = this.mvtReasons;
        const select = document.createElement("select");

        if (params.id) select.id = params.id;
        if (params.name) select.name = params.name;
        if (params.className) select.className = params.className;
        if (params.dataSign) select.setAttribute("data-sign", params.dataSign);
        if (params.style) select.style = params.style;
        select.style.width = "100%";

        for (const mvtReason of mvtReasons) {
            const option = document.createElement("option");
            option.value = mvtReason.id;
            option.text = mvtReason.name;
            option.setAttribute("data-select2-sign", mvtReason.sign);
            select.appendChild(option);
        }

        this.select = select;

        if (this.options.parent) {
            this.options.parent.appendChild(select);
        }

        return select;
    }

    /**
     * Inizializza Select2 per motivi movimento con AJAX e template custom
     * @param {HTMLElement|null} selectEl - Il select da inizializzare (se null usa quello istanza)
     * @param {Object} options - Opzioni aggiuntive (override)
     */
    async init() {
        await this.getMvtReasons();
        this.createSelect();
        const selectEl = this.select;

        if (!window.$ || !$.fn.select2) {
            console.error("getMvtReasons: Select2 non trovato");
            return;
        }

        if (!selectEl) {
            console.error("getMvtReasons: Select element non trovato");
            return;
        }

        const defaultOptions = {
            placeholder: "Seleziona tipo di movimento...",
            templateResult: function (data) {
                if (!data.id) return data.text;
                let icon = "";
                if (data.element.getAttribute("data-select2-sign") == 1) {
                    icon = '<span style="color:green;font-weight:bold;margin-left:8px;">+<i class="fa fa-arrow-up"></i></span>';
                } else if (data.element.getAttribute("data-select2-sign") == -1) {
                    icon = '<span style="color:red;font-weight:bold;margin-left:8px;">-<i class="fa fa-arrow-down"></i></span>';
                }
                return `<div style="display:flex;align-items:center;justify-content:space-between;width:100%;"><span>${data.text}</span>${icon}</div>`;
            },
            templateSelection: function (data) {
                if (!data.id) return data.text;
                let icon = "";
                if (data.element.getAttribute("data-select2-sign") == 1) {
                    icon = '<span style="color:green;font-weight:bold;margin-left:8px;">+<i class="fa fa-arrow-up"></i></span>';
                } else if (data.element.getAttribute("data-select2-sign") == -1) {
                    icon = '<span style="color:red;font-weight:bold;margin-left:8px;">-<i class="fa fa-arrow-down"></i></span>';
                }
                return `<span>${data.text}</span>${icon}`;
            },
            language: "it",
            theme: "bootstrap4",
            width: "100%",
            allowClear: true
        };

        $(selectEl).select2($.extend(true, {}, defaultOptions, this.options.select2 || {}));

        // Aggiorna l'attributo data-sign al cambio selezione
        $(selectEl).on("select2:select", function (e) {
            const sign = e.params.data.element.getAttribute("data-select2-sign");
            selectEl.setAttribute("data-sign", sign);
            // Evento custom opzionale
            const event = new CustomEvent("reasonSelected", {
                detail: {
                    reason: e.params.data,
                    timestamp: new Date(),
                    relatedElement: selectEl
                }
            });
            document.dispatchEvent(event);
        });

        $(selectEl).on("select2:open", function () {
            $(".select2-container--open").css("z-index", 200000);
            $(".select2-dropdown").css("z-index", 250000);
        });
    }
}

export default StockMvtReasonSelect;
