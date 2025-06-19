import { ModernToolbarManager } from "../../../customElements/modern-toolbar/modernToolbar.js";
import { ModernTable } from "../../../customElements/modern-table/modernTable.js";
import { ModernDialog } from "../../../customElements/modern-dialog/modernDialog.js";
import { ModernProgress } from "../../../customElements/modern-progress/modernProgress.js";
import { ProductAutocomplete } from "../../../customElements/product-autocomplete/productAutocomplete.js";

async function showMovementForm() {
    const dialog = document.getElementById(window.idDialog);
    if (dialog) {
        dialog.showModal();
    } else {
        const response = await fetch(window.stockAdvRenderFormUrl);
        const json = await response.json();
        const dialogHtml = json.formHtml || `<div class="alert alert-danger">Errore</div>`;
        const element = document.createElement("template");
        element.innerHTML = dialogHtml;
        document.body.appendChild(element.content.cloneNode(true));
        const dialog = document.getElementById(window.idDialog);

        const parentElement = dialog;
        const selectElement = dialog.querySelector("#id_product");
        const endpointSearchUrl = window.endpointProductSearchUrl;

        console.log(parentElement);
        console.log(selectElement);
        console.log(endpointSearchUrl);

        const productAutocomplete = new ProductAutocomplete(parentElement, selectElement, endpointSearchUrl);

        dialog.showModal();
    }
}

async function showMovementForm2() {
    const dialog = document.getElementById(window.idDialog);
    if (dialog) {
        dialog.showModal();
    } else {
        const response = await fetch(window.stockAdvRenderFormUrl);
        const json = await response.json();
        const dialogHtml = json.formHtml || `<div class="alert alert-danger">Errore</div>`;
        const element = document.createElement("template");
        element.innerHTML = dialogHtml;
        document.body.appendChild(element.content.cloneNode(true));
        const dialog = document.getElementById(window.idDialog);

        const parentElement = dialog;
        const selectElement = dialog.querySelector("#id_product");
        const endpointSearchUrl = window.endpointProductSearchUrl;

        console.log(parentElement);
        console.log(selectElement);
        console.log(endpointSearchUrl);

        const productAutocomplete = new ProductAutocomplete(parentElement, selectElement, endpointSearchUrl);

        dialog.showModal();
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const menuData = [
        {
            icon: "home",
            label: "Home",
            href: window.stockmvtMenuItems.stockViewURL
        },
        {
            icon: "list",
            label: "Menu",
            children: [
                {
                    label: "Magazzini",
                    href: window.stockmvtMenuItems.warehouseURL,
                    icon: "home"
                },
                {
                    label: "Tipi di movimento",
                    href: window.stockmvtMenuItems.mvtReasonsURL,
                    icon: "home"
                },
                {
                    label: "Documenti di carico",
                    href: window.stockmvtMenuItems.supplyOrdersURL,
                    icon: "add"
                },
                {
                    label: "Documenti di scarico",
                    href: window.stockmvtMenuItems.stockExitURL,
                    icon: "add"
                },
                {
                    label: "Movimenti",
                    href: window.stockmvtMenuItems.stockMvtURL,
                    icon: "home"
                },
                {
                    label: "Giacenze",
                    href: window.stockmvtMenuItems.stockViewURL,
                    icon: "home"
                },
                {
                    label: "Import/Export",
                    href: window.stockmvtMenuItems.stockImportExportURL,
                    icon: "home"
                },
                {
                    label: "Impostazioni",
                    href: window.stockmvtMenuItems.stockSettingsURL,
                    icon: "home"
                }
            ]
        },
        {
            icon: "home",
            label: "Movimenti",
            children: [
                {
                    icon: "add",
                    label: "Nuovo Movimento",
                    href: "javascript:void(0);",
                    action: "showNewMovement",
                    dialogId: window.idDialog
                },
                {
                    icon: "download",
                    label: "Importa",
                    href: "javascript:void(0);"
                }
            ]
        }
    ];

    const actionButtonsData = [];

    /*
			const actionButtonsData = [
                {
                    icon: "add_circle",
                    label: "Nuovo",
                    actionId: "createNew"
                },
                {
                    icon: "save",
                    label: "Salva",
                    actionId: "saveDocument",
                    background: "#ff9800",
                    color: "white"
                }
            ];
			*/

    const toolbarManager = new ModernToolbarManager({
        targetElement: "#toolbar-container",
        logoSrc: window.logoSrc,
        menuItems: menuData,
        actionButtons: actionButtonsData
    });

    const toolbarInstance = toolbarManager.getToolbarInstance();

    toolbarInstance.addEventListener("menu-item-click", (e) => {
        const detail = e.detail;

        if (detail.action) {
            switch (detail.action) {
                case "showNewMovement":
                    showMovementForm();
                    break;
                case "importOrders":
                    // Import orders
                    break;
                default:
                    break;
            }
        }
    });

    toolbarInstance.addEventListener("action-button-click", (e) => {
        console.log(e.detail);
    });

    toolbarInstance.addEventListener("toolbar-search", (e) => {
        console.log(e.detail);
    });

    document.addEventListener("productSelected", (e) => {
        console.log(e.detail);
        const product = JSON.parse(e.detail.id) || null;
        if (!product) return;

        const combination = product.attribute_names || "";
        document.getElementById("id_product_attribute").value = product.id_product;
        document.getElementById("id_combination").value = combination;
    });
});
