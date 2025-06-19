// StockMvtForm.js
// Modulo ES6 per gestire il form modale di inserimento movimento magazzino

import ProductAutocomplete from "../../../components/ProductAutocomplete.js";
import StockMvtReasonSelect from "../../../components/StockMvtReasonSelect.js";

export default class StockMvtForm {
    constructor({ onSaved, endpoints }) {
        this.onSaved = onSaved;
        this.endpoints = window.APP_ROUTES;
        this.dialog = null;
        this.form = null;
        this.productAutocomplete = null;
        this.stockMvtReasonSelect = null;
        this.injectStyle();
        this._init();

        console.log("StockMvtForm Endpoints:", this.endpoints);
    }

    injectStyle() {
        const style = document.createElement("style");
        style.textContent = `
            .stockmvt-dialog::backdrop { background: rgba(0,0,0,0.3); }
            .stockmvt-dialog { border: none; border-radius: 12px; padding: 0; box-shadow: 0 8px 32px rgba(0,0,0,0.18); min-width: 600px; max-width: 900px; }
            .btn { padding: 0.5rem 1.2rem; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; transition: background 0.18s; }
            .btn-primary { background: #1e88e5; color: #fff; }
            .btn-primary:hover { background: #1565c0; }
            .btn-secondary { background: #eee; color: #333; }
            .btn-secondary:hover { background: #bbb; }
        `;
        document.head.appendChild(style);
    }

    async _init() {
        // Import dinamico (ES6):
        // import ProductAutocomplete from '../../components/ProductAutocomplete.js';
        // Se già importato in alto, ignora questa riga.
        // import ProductAutocomplete from '../../components/ProductAutocomplete.js';

        // Crea il dialog se non esiste
        this.dialog = document.createElement("dialog");

        // HTML base del form con le select richieste
        this.dialog.className = "stockmvt-dialog";
        this.dialog.innerHTML = `
            <form method="dialog" class="bootstrap" id="stockmvt-form">
                <div class="card" style="border: none; padding: 1rem;">
                    <div class="card-header">
                        <h5 class="card-title">Nuovo movimento magazzino</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-4 pb-2" id="mvtReason-group" style="border-bottom: 1px solid #ccc;">
                            <label for="id_stock_mvt_reason">Tipo di movimento</label>
                            <!-- Il select sarà inserito dinamicamente qui -->
                        </div>
                        <div class="d-flex justify-content-start">
                            <div class="form-group">
                                <img id="product-image" src="${this.endpoints.img404}" alt="Immagine prodotto" class="img-fluid" style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                            <div class="form-group ml-2" id="product-autocomplete-group">
                                <label for="id_product">Prodotto</label>
                                <!-- Il select sarà inserito dinamicamente qui -->
                            </div>
                        </div>
                        <div class="form-group" id="combination-group" style="display:none">
                            <label for="id_product_attribute_name">Combinazione</label>
                            <input type="hidden" name="id_product_attribute" id="id_product_attribute" />
                            <input type="text" name="id_product_attribute_name" id="id_product_attribute_name" readonly class="form-control text-info" tabindex="-1"/>
                        </div>
                        <div class="form-group">
                            <label>Quantità</label>
                            <input type="number" name="quantity" required min="1" step="1" class="form-control"/>
                        </div>
                        <div class="form-group">
                            <label>Prezzo unitario</label>
                            <input type="number" name="price_te" required min="0" step="0.01" class="form-control"/>
                        </div>
                        <div class="form-group">
                            <label>Note</label>
                            <textarea name="referer" rows="2" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Salva</button>
                            <button type="button" class="btn btn-secondary" id="stockmvt-cancel">Annulla</button>
                        </div>
                    </div>
                </div>
            </form>
        `;

        // Inserisci dinamicamente il select prodotto con autocomplete
        const productGroup = this.dialog.querySelector("#product-autocomplete-group");
        if (productGroup) {
            this.productAutocomplete = new ProductAutocomplete({
                endpoint: this.endpoints.productSearch,
                select: {
                    id: "id_product",
                    name: "id_product",
                    className: "form-control select2 autocomplete",
                    placeholder: "Cerca prodotto..."
                },
                parent: productGroup
            });
        }

        // Inserisci dinamicamente la select dei motivi movimento
        const reasonGroup = this.dialog.querySelector("#mvtReason-group");
        if (reasonGroup) {
            this.stockMvtReasonSelect = new StockMvtReasonSelect({
                endpoints: {
                    reasonSearch: this.endpoints.reasonSearch
                },
                select: {
                    id: "id_stock_mvt_reason",
                    name: "id_stock_mvt_reason",
                    className: "form-control select2 mvt-reasons"
                },
                parent: reasonGroup
            });
        }

        document.body.appendChild(this.dialog);
        this.form = this.dialog.querySelector("#stockmvt-form");

        document.addEventListener("productSelected", (e) => {
            const combinationGroup = this.form.querySelector("#combination-group");
            if (e.detail.product.hasCombinations) {
                combinationGroup.style.display = "block";
                combinationGroup.querySelector("#id_product_attribute").value = e.detail.product.id_product_attribute;
                combinationGroup.querySelector("#id_product_attribute_name").value = e.detail.product.combination;
                this.form.querySelector("#product-image").src = e.detail.product.img;
            } else {
                combinationGroup.style.display = "none";
                combinationGroup.querySelector("#id_product_attribute").value = "";
                combinationGroup.querySelector("#id_product_attribute_name").value = "";
                this.form.querySelector("#product-image").src = this.endpoints.img404;
            }
        });

        // Annulla
        this.form.querySelector("#stockmvt-cancel").addEventListener("click", () => this.dialog.close());

        document.body.appendChild(this.dialog);
        this.form = this.dialog.querySelector("form");
        this.form.onsubmit = (e) => this._handleSubmit(e);
        // Animazione stile SWAL
        this.dialog.addEventListener("close", () => {
            this.dialog.classList.remove("open");
        });
    }

    open() {
        this.dialog.classList.add("open");
        this.dialog.showModal();
        // Dispatch CustomEvent per hook esterni
        const event = new CustomEvent("stockMvtForm:open", {
            detail: {
                form: this.form,
                dialog: this.dialog,
                productAutocomplete: this.productAutocomplete,
                stockMvtReasonSelect: this.stockMvtReasonSelect
            }
        });
        document.dispatchEvent(event);
        // Animazione stile SWAL
        this.dialog.animate(
            [
                { transform: "scale(0.7)", opacity: 0 },
                { transform: "scale(1)", opacity: 1 }
            ],
            {
                duration: 250,
                easing: "cubic-bezier(.68,-0.55,.27,1.55)"
            }
        );
    }

    close() {
        this.dialog.close();
    }

    async _handleSubmit(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this.form));
        // Salva via AJAX
        const resp = await fetch("/modules/mpstockadv/api/stock-mvt-add", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(data)
        });
        const result = await resp.json();
        if (result.success) {
            this.close();
            if (this.onSaved) this.onSaved(result);
        } else {
            alert(result.message || "Errore salvataggio");
        }
    }
}
