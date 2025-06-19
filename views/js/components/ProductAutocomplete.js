// Componente riutilizzabile per autocomplete prodotto con Select2
// Usage: import ProductAutocomplete from '../components/ProductAutocomplete.js';
//        const autocomplete = new ProductAutocomplete({ endpoint: '/custom/url', select2: { ... } });
//        autocomplete.createSelect({ id: 'mySelect', name: 'mySelect' });
//        autocomplete.init();

export class ProductAutocomplete {
    parentElement = null;
    selectElement = null;
    endpointSearchUrl = null;

    /**
     * @param parentElement - Elemento genitore per il dropdown
     * @param selectElement - Elemento select da inizializzare
     * @param endpointSearchUrl - URL per la ricerca prodotti
     */
    constructor(parentElement, selectElement, endpointSearchUrl) {
        this.parentElement = parentElement;
        this.selectElement = selectElement;
        this.endpointSearchUrl = endpointSearchUrl;
        this.init();
    }

    getParentElement() {
        return this.parentElement;
    }

    getSelectElement() {
        return this.selectElement;
    }

    getEndpointSearchUrl() {
        return this.endpointSearchUrl;
    }

    /**
     * Inizializza Select2 per prodotti con AJAX
     */
    init() {
        console.log("INIZIALIZZAZIONE AUTOCOMPLETE");
        console.log("parentElement", this.parentElement);
        console.log("selectElement", this.selectElement);
        console.log("endpointSearchUrl", this.endpointSearchUrl);

        const parentElement = this.parentElement;
        const selectEl = this.selectElement;
        const endpoint = this.endpointSearchUrl;

        if (!window.$ || !$.fn.select2) {
            console.error("Select2 non trovato");
            return;
        }
        if (!selectEl) {
            console.error("Select element non trovato");
            return;
        }
        if (!endpoint) {
            console.error("Endpoint non trovato");
            return;
        }

        const defaultOptions = {
            placeholder: "Cerca prodotto...",
            minimumInputLength: 3,
            dropdownParent: parentElement,
            theme: "bootstrap4",
            ajax: {
                url: endpoint,
                dataType: "json",
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    // Supporta sia array semplice che oggetto {results: []}
                    if (Array.isArray(data)) {
                        return {
                            results: data.map((p) => ({
                                id: p.id_product,
                                name: p.name,
                                text: `[${p.reference || ""}] ${p.name} ${p.ean13 ? "EAN:" + p.ean13 : ""} ${p.upc ? "UPC:" + p.upc : ""} ${p.isbn ? "ISBN:" + p.isbn : ""}`.trim(),
                                img: p.img || null,
                                combination: p.combination || "",
                                hasCombinations: p.has_combinations
                            }))
                        };
                    }
                    return data;
                },
                cache: true
            },
            templateResult: function (data) {
                if (!data.id) return data.text;
                return $(`
                    <div style="display:flex;align-items:center;">
                        <img src="${data.img || "/img/404.gif"}" style="width:32px;height:32px;object-fit:cover;margin-right:8px;">
                        <div>
                            <div><strong>${data.name || data.text}</strong></div>
                            <div style="font-size:12px;color:#666;">${data.combination || ""}</div>
                        </div>
                    </div>
                `);
            },
            templateSelection: function (data) {
                return data.name || data.text || "";
            },
            width: "100%",
            allowClear: true
        };

        $(selectEl).select2(defaultOptions);

        $(selectEl).on("select2:select", function (e) {
            const event = new CustomEvent("productSelected", {
                detail: {
                    product: e.params.data,
                    timestamp: new Date(),
                    relatedElement: this
                }
            });
            console.log("FIRED PRODUCT SELECTED EVENT");
            document.dispatchEvent(event);
        });

        $(selectEl).on("select2:open", function (e) {
            // Per Select2 >= 4.0.6
            setTimeout(function () {
                document.querySelector(".select2-container--open .select2-search__field").focus();
            }, 0);
        });
    }
}
