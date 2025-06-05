document.addEventListener("DOMContentLoaded", async () => {
    function formatPrice(price, decimals) {
        const parsed = parseFloat(price);
        if (isNaN(parsed)) {
            return "0.00";
        }
        return parsed.toFixed(decimals);
    }

    const toolbar = new window.ModuleClasses.toolBar({
        parent: document.querySelector("#stock-mvt-toolbar"),
        buttons: [
            {
                id: "stock-mvt-new-mvt",
                label: "Nuovo movimento",
                icon: "add",
                className: "btn btn-primary",
                action: async () => {
                    const response = await fetch(window.APP_ROUTES.fetchDialogFormUrl);
                    const data = await response.json();
                    const formHtml = data.formHtml;
                    const idDialog = data.idDialog;
                    const dialog = document.getElementById(idDialog);
                    if (dialog) {
                        dialog.remove();
                    }
                    document.body.insertAdjacentHTML("beforeend", formHtml);
                    const dialogElement = document.getElementById(idDialog);
                    const productAutocompleteSelect = $(document.querySelector("#id_product"));
                    const warehouseSelect = $(document.querySelector("#id_warehouse"));
                    const stockMvtSelect = $(document.querySelector("#id_stock_mvt_reason"));

                    if (!productAutocompleteSelect || !warehouseSelect || !stockMvtSelect) {
                        if (!productAutocompleteSelect) {
                            console.error("Product autocomplete select element not found");
                        }
                        if (!warehouseSelect) {
                            console.error("Warehouse select element not found");
                        }
                        if (!stockMvtSelect) {
                            console.error("Stock mvt select element not found");
                        }
                        return;
                    }

                    $(warehouseSelect).select2({
                        placeholder: "Seleziona un magazzino...",
                        dropdownParent: $(dialogElement),
                        language: "it",
                        theme: "bootstrap4"
                    });

                    const productAutocompleteClass = new window.ModuleClasses.productAutocomplete(dialogElement, productAutocompleteSelect, window.APP_ROUTES.fetchProductAutocompleteUrl);
                    const stockMvtReasonsSelectOptionsClass = new window.ModuleClasses.stockMvtReasonsSelectOptions(dialogElement, stockMvtSelect);
                    const btnSaveMovement = $(document.querySelector("#btn-save-movement"));
                    if (!btnSaveMovement) {
                        console.error("Btn save movement element not found");
                        return false;
                    }

                    btnSaveMovement.click(async () => {
                        const form = document.getElementById(window.idFormMovement);
                        console.log("FORM", window.idFormMovement, form);
                        if (!form) {
                            console.error("Form element not found");
                            return false;
                        }
                        const formData = new FormData(form);
                        const response = await fetch(window.APP_ROUTES.postSaveMvt, {
                            method: "POST",
                            body: formData
                        });
                        const result = await response.json();
                        if (result.success) {
                            dialogElement.close();
                            alert("Movimento salvato con successo");
                        } else {
                            const errorCode = result.errorCode;
                            const errorMessage = result.errorMessage;
                            alert(`Errore durante il salvataggio del movimento: ${errorCode} - ${errorMessage}`);
                        }
                    });

                    dialogElement.showModal();
                }
            },
            {
                id: "stock-mvt-import-mvt",
                label: "Importa giacenze iniziali",
                icon: "download",
                className: "btn btn-secondary",
                action: async (e) => {
                    e.preventDefault();

                    // Uso base con HTML custom
                    const result = await window.ModuleClasses.confirmModal.alert({
                        title: "Importare le giacenze iniziali?",
                        html: "<div><strong>Sarà importato il file JSON con tutte le giacenze iniziali.</strong></div>",
                        alertType: "warning",
                        icon: "help",
                        confirmText: "Importa",
                        cancelText: "Annulla",
                        showClose: false
                    });

                    if (result) {
                        const modal = new window.ModuleClasses.modalProgress({
                            title: "Importazione giacenze",
                            icon: "hourglass_empty",
                            initialLabel: "Preparazione importazione..."
                        });

                        let aborted = false;
                        modal.onAbort = () => {
                            aborted = true;
                            // Qui puoi fare altre operazioni di cleanup se necessario
                        };

                        modal.open();

                        const endpoints = {
                            truncate: window.APP_ROUTES.initImporter.truncateUrl,
                            getJson: window.APP_ROUTES.initImporter.getJsonUrl,
                            import: window.APP_ROUTES.initImporter.importUrl
                        };
                        const initStockImporterClass = new window.ModuleClasses.initStockImporter(endpoints, 250, modal);

                        // Callback per aggiornare il progresso
                        function onProgress(progress) {
                            if (progress.error) {
                                console.error("Errore nel chunk:", progress.message);
                                modal.update(Math.round((progress.current / progress.total) * 100), "Errore: " + progress.message);
                            } else {
                                console.log(`Chunk ${progress.current}/${progress.total} - Inviati: ${progress.totalSent}, Inseriti: ${progress.totalInserted}`);
                                modal.update(Math.round((progress.current / progress.total) * 100), "Chunk ${progress.current}/${progress.total} - Inviati: ${progress.totalSent}, Inseriti: ${progress.totalInserted}");
                            }

                            // Se l’utente ha abortito, interrompi subito
                            if (aborted) throw new Error("Importazione annullata dall’utente");
                        }

                        // Avvia l’importazione
                        initStockImporterClass
                            .run(onProgress)
                            .then(({ totalSent, totalInserted, errors }) => {
                                console.log(`Importazione completata! Inviati: ${totalSent}, Inseriti: ${totalInserted}`);
                                modal.update(100, `Importazione completata! (${totalInserted} inseriti)`);
                                setTimeout(() => modal.close(), 1500);
                            })
                            .catch((err) => {
                                console.error("Errore durante l’importazione: " + err.message);
                                modal.update(0, "Importazione interrotta: " + err.message);
                                setTimeout(() => modal.close(), 2000);
                            });
                    } else {
                        window.ModuleClasses.modalProgress.alert({
                            title: "Importazione annullata",
                            message: "L’importazione è stata annullata",
                            alertType: "warning",
                            icon: "info",
                            confirmText: "Chiudi",
                            cancelText: null,
                            showClose: false
                        });
                    }
                }
            },
            {
                id: "stock-mvt-back-to-menu",
                label: "Torna al menu principale",
                icon: "home",
                className: "btn btn-outline-secondary",
                action: () => {
                    window.location.href = window.APP_ROUTES.backToMainMenu;
                }
            }
        ]
    });

    toolbar.init();

    document.addEventListener("productSelected", async (e) => {
        const product = e.detail.product;
        console.log("product", product);
        const productImage = document.querySelector("#stock-mvt-product-image");
        const productCombinationId = document.querySelector("#id_product_attribute");
        const productCombinationName = document.querySelector("#product_attribute_name");
        const productQuantity = document.querySelector("#physical_quantity");
        const productPriceTe = document.querySelector("#price_te");
        const productTaxRate = document.querySelector("#tax_rate");
        const productPriceTi = document.querySelector("#price_ti");
        const productLastWa = document.querySelector("#last_wa");
        const productCurrentWa = document.querySelector("#current_wa");

        if (productImage) {
            productImage.src = product.img;
        }
        if (productCombinationId) {
            productCombinationId.value = product.id_product_attribute;
        }
        if (product.hasCombinations && productCombinationName) {
            productCombinationName.value = product.combination;
        } else if (!product.hasCombinations && productCombinationName) {
            productCombinationName.value = "";
        } else {
            productCombinationName.value = "--";
        }
        if (productQuantity) {
            productQuantity.value = "0";
            productQuantity.focus();
            productQuantity.select();
        }
        if (productPriceTe) {
            productPriceTe.value = formatPrice(product.price_te, 6);
        }
        if (productTaxRate) {
            productTaxRate.value = formatPrice(product.tax_rate, 2);
        }
        if (productPriceTi) {
            productPriceTi.value = formatPrice(product.price_ti, 2);
        }
        if (productLastWa) {
            productLastWa.value = formatPrice(product.last_wa, 2);
        }
        if (productCurrentWa) {
            productCurrentWa.value = formatPrice(product.current_wa, 2);
        }
    });
});
