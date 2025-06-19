class StockMvtReasonsSelectOptions {
    constructor(parentElement, selectElement) {
        this.parentElement = parentElement;
        this.selectElement = selectElement;
        this.init();
    }

    getParentElement() {
        return this.parentElement;
    }

    getSelectElement() {
        return this.selectElement;
    }

    init() {
        const self = this;
        const parentElement = this.parentElement;
        const selectElement = this.selectElement[0] || null;
        if (!selectElement) {
            console.error("Select element not found");
            return;
        }
        const defaultOptions = {
            placeholder: "Seleziona tipo di movimento...",
            dropdownParent: parentElement,
            templateResult: function (data) {
                return self.templateRowData(data);
            },
            templateSelection: function (data) {
                return self.templateRowData(data);
            },
            language: "it",
            theme: "bootstrap4",
            width: "100%",
            allowClear: true
        };

        $(selectElement).select2($.extend(true, {}, defaultOptions));

        // Aggiorna l'attributo data-sign al cambio selezione
        $(selectElement).on("select2:select", function (e) {
            const sign = e.params.data.element.getAttribute("data-sign");
            selectElement.setAttribute("data-sign", sign);
            // Evento custom opzionale
            const event = new CustomEvent("reasonSelected", {
                detail: {
                    reason: e.params.data,
                    timestamp: new Date(),
                    relatedElement: selectElement
                }
            });
            document.dispatchEvent(event);
        });

        $(selectElement).on("select2:open", function () {
            $(".select2-container--open").css("z-index", 999999);
            $(".select2-dropdown").css("z-index", 999999);
        });
    }

    getSignIcon(sign) {
        if (sign == 1) {
            return '<span class="badge" style="color:green;font-weight:bold;margin-left:8px;"><i class="material-icons">arrow_upward</i></span>';
        } else if (sign == -1) {
            return '<span class="badge" style="color:red;font-weight:bold;margin-left:8px;"><i class="material-icons">arrow_downward</i></span>';
        }
        return "";
    }

    createOptionRow(sign, text) {
        const icon = this.getSignIcon(sign);
        return $(`
            <div style="display:flex;align-items:center;justify-content:space-between;width: auto;">
                <span>${text}</span>
                ${icon}
            </div>
        `);
    }

    templateRowData(data) {
        const option = data.element;

        const text = data.text;
        const sign = option ? option.dataset.sign : 0;

        if (!option || sign == 0) {
            return text;
        }

        return this.createOptionRow(sign, text);
    }
}

export default StockMvtReasonsSelectOptions;
