class ProductAutocomplete {
    _dialog = null;
    _employee_id = null;
    _warehouse_id = null;
    _stock_mvt_reason_id = null;
    _sign = null;
    _product_img = null;
    _product_reference = null;
    _product_ean13 = null;
    _product_upc = null;
    _product_mpn = null;
    _product_id = null;
    _product_attribute_id = null;
    _product_attribute_name = null;
    _product_quantity = null;
    _product_price_te = null;
    _product_price_ti = null;
    _product_tax_rate = null;
    _product_last_wa = null;
    _product_current_wa = null;

    constructor(dialog) {
        this._dialog = dialog;
        this._bind();
    }

    _bind() {
        this._employee_id = this._dialog.querySelector("#id_employee");
        this._warehouse_id = this._dialog.querySelector("#id_warehouse");
        this._stock_mvt_reason_id = this._dialog.querySelector("#id_stock_mvt_reason");
        this._sign = this._dialog.querySelector("#sign");
        this._produt_img = this._dialog.querySelector("#stock-mvt-product-image");
        this._product_reference = this._dialog.querySelector("#reference");
        this._product_ean13 = this._dialog.querySelector("#ean13");
        this._product_upc = this._dialog.querySelector("#upc");
        this._product_mpn = this._dialog.querySelector("#mpn");
        this._product_id = this._dialog.querySelector("#id_product");
        this._product_attribute_id = this._dialog.querySelector("#id_product_attribute");
        this._product_attribute_name = this._dialog.querySelector("#product_attribute_name");
        this._product_quantity = this._dialog.querySelector("#physical_quantity");
        this._product_price_te = this._dialog.querySelector("#price_te");
        this._product_price_ti = this._dialog.querySelector("#price_ti");
        this._product_tax_rate = this._dialog.querySelector("#tax_rate");
        this._product_last_wa = this._dialog.querySelector("#last_wa");
        this._product_current_wa = this._dialog.querySelector("#current_wa");

        this._bindEvents();
    }

    _bindEvents() {
        const instance = this;
        $(instance._stock_mvt_reason_id).select2({
            dropdownParent: instance._dialog,
            templateResult: instance.renderMvtReasons,
            templateSelection: instance.renderMvtReasons,
            escapeMarkup: function(markup) {
                return markup;
            }
        });

        $(instance._stock_mvt_reason_id).on("select2:open", function(e) {
            // Select2 4.x: il campo di ricerca ha classe .select2-search__field
            setTimeout(function() {
                document.querySelector(".select2-search__field").focus();
            }, 0);
        });

        $(instance._warehouse_id).select2({
            dropdownParent: instance._dialog,
            templateResult: instance.renderWarehouses,
            escapeMarkup: function(markup) {
                return markup;
            }
        });

        $(instance._warehouse_id).on("select2:open", function(e) {
            // Select2 4.x: il campo di ricerca ha classe .select2-search__field
            setTimeout(function() {
                document.querySelector(".select2-search__field").focus();
            }, 0);
        });

        $(instance._product_id).select2({
            dropdownParent: instance._dialog,
            templateResult: instance.renderProduct,
            escapeMarkup: function(markup) {
                return markup;
            },
            ajax: {
                url: productAutocompleteUrl, // Cambia con l’URL che restituisce i prodotti
                dataType: "json",
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term // il termine di ricerca digitato dall’utente
                        // puoi aggiungere altri parametri se necessario
                    };
                },
                processResults: function(data) {
                    // data deve essere un array di oggetti {id:..., text:...}
                    return {
                        results: data
                    };
                },
                cache: true
            },
            minimumInputLength: 2 // opzionale: inizia la ricerca dopo 2 caratteri
        });

        $(instance._product_id).on("select2:open", function(e) {
            // Select2 4.x: il campo di ricerca ha classe .select2-search__field
            setTimeout(function() {
                document.querySelector(".select2-search__field").focus();
            }, 0);
        });
    }

    renderMvtReasons(data) {
        if (!data.id) {
            return data.text;
        }
        const sign = data.element ? data.element.getAttribute("data-select2-sign") : data.sign;
        let icon = "";
        if (sign == "1") {
            icon = `<span class="material-icons" style="color: var(--success)">add_circle</span>`;
        } else if (sign == "-1") {
            icon = `<span class="material-icons" style="color: var(--danger)">do_not_disturb_on</span>`;
        }
        const label = `<span class='data-text'>${data.text}</span>`;
        const outputString = $(`${icon} ${label}`);

        return outputString;
    }

    renderWarehouses(data) {
        if (!data.id) {
            return data.text;
        }
        const address = data.element.getAttribute("data-select2-address") || "--";
        const label = `<span class='data-text'>${data.text}</span>`;
        const outputString = `
            <div class="row">
                <div class="col-12">
                    ${label}
                </div>
                <div class="col-12">
                    <small class="text-info">${address}</small>
                </div>
            </div>
        `;

        return outputString;
    }

    renderProduct(data) {
        console.log("RenderProduct", data);
        if (!data.id) {
            return data.text;
        }
        const label = `
            <div class="d-flex justify-content-start align-items-center">
                <div class="img-thumbnail" style="margin-right: 4px;">
                    <img src="${data.img_url}" class="img-fluid" alt="${data.name}" style="width: 100px; height: 100px; object-fit: cover;">
                </div>
                <div class="product-details">
                    <div>
                        <strong>${data.name}</strong>
                    </div>
                    <div>
                        ${data.combination}
                    </div>
                    <div class="d-flex justify-content-start align-items-center">
                        <div style="border-right: 1px solid #dcdcdc; margin-right: 8px; padding-right: 8px;">
                            EAN13: <strong>${data.ean13}</strong>
                        </div>
                        <div style="text-right; border-right: 1px solid #dcdcdc; margin-right: 8px; padding-right: 8px;">
                            PREZZO: <strong>${data.price_ti_currency}</strong>
                        </div>
                        <div style="text-right;border-right: 1px solid #dcdcdc; margin-right: 8px; padding-right: 8px;">
                            STOCK: <strong>${data.stock}</strong>
                        </div>
                    </div>
                </div>
            </div>
        `;

        return label;
    }

    showModal() {
        this._dialog.showModal();
    }
}
