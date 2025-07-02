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
    _product_name = null;
    _product_attribute_id = null;
    _product_attribute_name = null;
    _product_quantity = null;
    _product_price_te = null;
    _product_price_ti = null;
    _product_tax_rate = null;
    _product_last_wa = null;
    _product_current_wa = null;
    _product_stock_before = null;

    constructor(dialog) {
        this._dialog = dialog;
        this._bind();
        this._bindEvents();
    }

    _bind() {
        this._employee_id = this._dialog.querySelector("#id_employee");
        this._warehouse_id = this._dialog.querySelector("#id_warehouse");
        this._stock_mvt_reason_id = this._dialog.querySelector("#id_stock_mvt_reason");
        this._sign = this._dialog.querySelector("#sign");
        this._product_img = this._dialog.querySelector("#stock-mvt-product-image");
        this._product_reference = this._dialog.querySelector("#reference");
        this._product_ean13 = this._dialog.querySelector("#ean13");
        this._product_upc = this._dialog.querySelector("#upc");
        this._product_mpn = this._dialog.querySelector("#mpn");
        this._product_id = this._dialog.querySelector("#id_product");
        this._product_name = this._dialog.querySelector("#product_name");
        this._product_attribute_id = this._dialog.querySelector("#id_product_attribute");
        this._product_attribute_name = this._dialog.querySelector("#product_attribute_name");
        this._product_quantity = this._dialog.querySelector("#physical_quantity");
        this._product_price_te = this._dialog.querySelector("#price_te");
        this._product_price_ti = this._dialog.querySelector("#price_ti");
        this._product_tax_rate = this._dialog.querySelector("#tax_rate");
        this._product_last_wa = this._dialog.querySelector("#last_wa");
        this._product_current_wa = this._dialog.querySelector("#current_wa");
        this._product_stock_before = this._dialog.querySelector("#product_stock_before");
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

        const productAutocompleteUrl = window.Routes.get("productAutocompleteUrl");

        $(instance._product_name).select2({
            dropdownParent: instance._dialog,
            templateResult: instance.renderProduct,
            templateSelection: instance.selectProduct,
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

        $(instance._product_name).on("select2:open", function(e) {
            // Select2 4.x: il campo di ricerca ha classe .select2-search__field
            setTimeout(function() {
                document.querySelector(".select2-search__field").focus();
            }, 0);
        });

        $(instance._product_name).on("select2:select", function(e) {
            // Select2 4.x: il campo di ricerca ha classe .select2-search__field
            setTimeout(function() {
                try {
                    document
                        .querySelector("#physical_quantity")
                        .select()
                        .focus();
                } catch (error) {
                    console.log(error);
                }
            }, 0);
        });

        const btnSave = this._dialog.querySelector("#btn-save-movement");
        btnSave.addEventListener("click", function() {
            instance._save();
        });
    }

    async _save() {
        const instance = this;
        const data = {
            employee_id: instance._employee_id.value,
            warehouse_id: instance._warehouse_id.value,
            stock_mvt_reason_id: instance._stock_mvt_reason_id.value,
            sign: instance._sign.value,
            product_img: instance._product_img.value,
            product_reference: instance._product_reference.value,
            product_ean13: instance._product_ean13.value,
            product_upc: instance._product_upc.value,
            product_mpn: instance._product_mpn.value,
            product_id: instance._product_id.value,
            product_name: instance._product_name.value,
            product_attribute_id: instance._product_attribute_id.value,
            product_attribute_name: instance._product_attribute_name.value,
            product_quantity: instance._product_quantity.value,
            product_price_te: instance._product_price_te.value,
            product_price_ti: instance._product_price_ti.value,
            product_tax_rate: instance._product_tax_rate.value,
            product_last_wa: instance._product_last_wa.value,
            product_current_wa: instance._product_current_wa.value
        };

        const productAutocompleteSaveUrl = window.Routes.get("productAutocompleteSaveUrl");
        const response = await fetch(productAutocompleteSaveUrl, {
            headers: {
                "Content-Type": "application/json"
            },
            method: "POST",
            body: JSON.stringify(data)
        });

        const result = await response.json();
        if (result.success) {
            //attivo un il custom event
            instance._dialog.dispatchEvent(
                new CustomEvent("stockMvtSaved", {
                    detail: {
                        result: result,
                        data: data
                    }
                })
            );
        } else {
            alert("Si è verificato un errore durante il salvataggio dei dati.");
            console.error(result);
        }
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

    selectProduct = data => {
        if (!data.id) {
            return data.text;
        }

        this._product_id.value = data.id_product;
        this._product_name.value = data.name;
        this._product_attribute_id.value = data.id_product_attribute;
        this._product_attribute_name.value = data.combination;
        this._product_quantity.value = "0";
        this._product_price_te.value = data.price_te;
        this._product_price_ti.value = Number(data.price_ti).toFixed(2);
        this._product_tax_rate.value = data.tax_rate;
        this._product_last_wa.value = data.last_wa;
        this._product_current_wa.value = data.current_wa;
        this._product_stock_before.value = data.stock;
        this._product_img.src = data.img_url;
        this._product_reference.value = data.reference;
        this._product_ean13.value = data.ean13;
        this._product_upc.value = data.upc;
        this._product_mpn.value = data.mpn;

        return data.name;
    };

    clearProduct = () => {
        this._warehouse_id.value = window.defaultWarehouseId || 0;
        this._product_id.value = 0;
        this._product_name.value = "";
        this._product_attribute_id.value = 0;
        this._product_attribute_name.value = "";
        this._product_quantity.value = "0";
        this._product_price_te.value = "0.000000";
        this._product_price_ti.value = "0.00";
        this._product_tax_rate.value = "0.00";
        this._product_last_wa.value = "0.00";
        this._product_stock_before.value = "0";
        this._product_current_wa.value = "0.00";
        this._product_img.src = "/img/404.gif";
        this._product_reference.value = "";
        this._product_ean13.value = "";
        this._product_upc.value = "";
        this._product_mpn.value = "";
    };

    showModal() {
        this.clearProduct();
        this._dialog.showModal();
    }
}
