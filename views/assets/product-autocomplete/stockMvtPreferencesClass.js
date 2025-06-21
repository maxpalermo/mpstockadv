class StockMvtPreferences {
    _dialog = null;
    _id_warehouse = null;
    _id_stock_mvt_reason_load = null;
    _id_stock_mvt_reason_unload = null;
    _warehouses = null;
    _stock_mvt_reasons = null;

    async init() {
        await this._loadPreferences();
        await this._renderDialog();
    }

    async _loadPreferences() {
        const instance = this;
        const request = await fetch(loadStockMvtPreferences, {
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            }
        });
        const response = await request.json();

        if (response && response.success) {
            instance._id_warehouse = response.preferences.id_warehouse;
            instance._id_stock_mvt_reason_load = response.preferences.id_stock_mvt_reason_load;
            instance._id_stock_mvt_reason_unload = response.preferences.id_stock_mvt_reason_unload;
            instance._warehouses = response.preferences.warehouses;
            instance._stock_mvt_reasons = response.preferences.stock_mvt_reasons;
        } else {
            alert("Errore nel caricamento delle preferenze.");
        }
    }

    _renderDialog() {
        const instance = this;
        const html = `
            <dialog id="dialog-mpstock-preferences" class="bootstrap">
                <div class="card">
                    <div class="card-body">
                        <form id="stockMvtAdvPreferencesForm">
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="stockMvtDefaultWarehouse">Magazzino di default</label>
                                        <select id="stockMvtDefaultWarehouse" name="default_warehouse"class="form-control">
                                            <option value="">Seleziona</option>
                                            ${this._warehouses
                                                .map(
                                                    warehouse =>
                                                        `<option data-select2-address="${warehouse.address1 || ""} ${warehouse.zipcode || ""} ${warehouse.city || ""}"
                                                            value="${warehouse.id_warehouse}" ${warehouse.id_warehouse == this._id_warehouse ? "selected" : ""}>${warehouse.name}</option>`
                                                )
                                                .join("")}
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="stockMvtDefaultStockMvtReasonLoad">Tipo di movimento di default <span class="text-success">(CARICO)</span></label>
                                        <select id="stockMvtDefaultStockMvtReasonLoad" name="default_stock_mvt_reason_load" class="form-control">
                                            <option value="">Seleziona</option>
                                            ${this._stock_mvt_reasons
                                                .map(
                                                    stockMvtReason =>
                                                        `<option data-select2-sign="${stockMvtReason.sign}" value="${stockMvtReason.id_stock_mvt_reason}" ${
                                                            stockMvtReason.id_stock_mvt_reason == this._id_stock_mvt_reason_load ? "selected" : ""
                                                        }>${stockMvtReason.name}</option>`
                                                )
                                                .join("")}
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="stockMvtDefaultStockMvtReasonUnload">Tipo di movimento di default <span class="text-danger">(SCARICO)</span></label>
                                        <select id="stockMvtDefaultStockMvtReasonUnload" name="default_stock_mvt_reason_unload" class="form-control">
                                            <option value="">Seleziona</option>
                                            ${this._stock_mvt_reasons
                                                .map(
                                                    stockMvtReason =>
                                                        `<option data-select2-sign="${stockMvtReason.sign}" value="${stockMvtReason.id_stock_mvt_reason}" ${
                                                            stockMvtReason.id_stock_mvt_reason == this._id_stock_mvt_reason_unload ? "selected" : ""
                                                        }>${stockMvtReason.name}</option>`
                                                )
                                                .join("")}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-center align-items-center">
                            <button class="btn btn-primary" id="preferences-save-btn">
                                <span class="material-icons">save</span>
                                <span>Salva</span>
                            </button>
                            <button class="btn btn-secondary" id="preferences-close-btn">
                                <span class="material-icons">close</span>
                                <span>Chiudi</span>
                            </button>
                        </div>
                    </div>
                </div>
            </dialog>
        `;

        if (typeof defaultWarehouseId !== "undefined") {
            //imposto la variabile globale
            defaultWarehouseId = instance._id_warehouse;
        }

        const template = document.createElement("template");
        template.innerHTML = html;
        const cloneNode = template.content.cloneNode(true);
        this._dialog = cloneNode.querySelector("dialog");
        document.body.appendChild(this._dialog);

        const btnSavePreferences = document.getElementById("preferences-save-btn");
        const btnClosePreferences = document.getElementById("preferences-close-btn");

        btnSavePreferences.addEventListener("click", async e => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const form = document.getElementById("stockMvtAdvPreferencesForm");
            const formData = new FormData(form);
            const request = await fetch(savePreferencesUrl, {
                method: "POST",
                body: formData
            });
            const response = await request.json();
            if (response.success) {
                const formValues = Object.fromEntries(formData.entries());
                instance._id_warehouse = formValues.default_warehouse;
                instance._id_stock_mvt_reason_load = formValues.default_stock_mvt_reason_load;
                instance._id_stock_mvt_reason_unload = formValues.default_stock_mvt_reason_unload;

                if (typeof defaultWarehouseId !== "undefined") {
                    //imposto la variabile globale
                    console.log("SET GLOBAL defaultWarehouseId", instance._id_warehouse);
                    defaultWarehouseId = instance._id_warehouse;
                }

                this._dialog.close();
                alert("Preferenze salvate.");
            } else {
                alert(response.message);
            }
        });

        btnClosePreferences.addEventListener("click", e => {
            instance._dialog.close();
        });

        const selectWarehouse = instance._dialog.querySelector("#stockMvtDefaultWarehouse");
        const selectStockMvtReasonLoad = instance._dialog.querySelector("#stockMvtDefaultStockMvtReasonLoad");
        const selectStockMvtReasonUnload = instance._dialog.querySelector("#stockMvtDefaultStockMvtReasonUnload");

        $(selectWarehouse).select2({
            dropdownParent: instance._dialog,
            templateResult: instance.renderWarehouses,
            escapeMarkup: function(markup) {
                return markup;
            }
        });
        $(selectStockMvtReasonLoad).select2({
            dropdownParent: instance._dialog,
            templateResult: instance.renderMvtReasons,
            templateSelection: instance.renderMvtReasons,
            escapeMarkup: function(markup) {
                return markup;
            }
        });
        $(selectStockMvtReasonUnload).select2({
            dropdownParent: instance._dialog,
            templateResult: instance.renderMvtReasons,
            templateSelection: instance.renderMvtReasons,
            escapeMarkup: function(markup) {
                return markup;
            }
        });

        $(selectStockMvtReasonLoad).on("select2:open", function(e) {
            const target = e.target;

            const selectSearchField = document.querySelector(".select2-search__field");
            console.log("TARGET", selectSearchField);
            // Select2 4.x: il campo di ricerca ha classe .select2-search__field
            setTimeout(function() {
                selectSearchField.focus();
            }, 0);
        });

        $(selectStockMvtReasonUnload).on("select2:open", function(e) {
            const target = e.target;

            const selectSearchField = document.querySelector(".select2-search__field");
            console.log("TARGET", selectSearchField);
            // Select2 4.x: il campo di ricerca ha classe .select2-search__field
            setTimeout(function() {
                selectSearchField.focus();
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

    showModal() {
        this._dialog.showModal();
    }

    close() {
        this._dialog.close();
    }
}
