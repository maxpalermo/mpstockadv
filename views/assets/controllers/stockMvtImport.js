let datasetXmlImport = [];
let dtXmlImport = null;

function setQuantityColor(value, badge = false) {
    let color = "text-info";

    if (badge) {
        if (value < 0) {
            badge = "badge badge-lg badge-danger";
        } else {
            badge = "badge badge-lg badge-success";
        }
    } else {
        badge = "";
    }

    if (value < 0) {
        color = "text-danger";
    } else {
        color = "text-success";
    }

    return `<span class="${badge ? badge : color}">${value}</span>`;
}

async function selectXmlTableRows(allRows) {
    const selectedRowsData = [];
    const table = document.getElementById("table-import-xml");
    if (!table) {
        alert("Tabella importazione non trovata.");
        return false;
    }
    if (allRows == true) {
        dtXmlImport.rows().every(function() {
            selectedRowsData.push(this.data());
        });
    } else {
        dtXmlImport.rows().every(function() {
            var $checkbox = $(this.node()).find('input[type="checkbox"]');
            if ($checkbox.prop("checked")) {
                selectedRowsData.push(this.data());
            }
        });
    }

    return selectedRowsData;
}

async function importXmlStock(rowsData) {
    const formData = new FormData();
    const documentData = {
        document_number: document.getElementById("document-number").value,
        document_date_iso: document.getElementById("document-date-iso").value,
        document_movement_id: document.getElementById("document-movement-id").value,
        document_movement_sign: document.getElementById("document-movement-sign").value,
        document_supplier_id: document.getElementById("document-supplier-id").value
    };
    formData.append("document", JSON.stringify(documentData));
    formData.append("rows", JSON.stringify(rowsData));

    const request = await fetch(window.importXmlStockUrl, {
        method: "POST",
        body: formData
    });

    const response = await request.json();
    console.log(response);
    alert("Operazione eseguita");
}

function initDataTable() {
    const tableImportXml = document.getElementById("table-import-xml");
    if (!tableImportXml) {
        alert("Tabella importazione XML non trovata.");
        return false;
    }

    const dtXmlImport = new DataTable(tableImportXml, {
        serverSide: false,
        processing: false,
        language: {
            url: window.tableDataLanguageItUrl
        },
        pageLength: 25,
        lengthMenu: [
            [10, 25, 50, 100, 200, 500],
            [10, 25, 50, 100, 200, 500]
        ],
        data: datasetXmlImport,
        columns: [
            {
                name: "checkbox",
                type: "checkbox",
                className: "text-center",
                sortable: false,
                searchable: false,
                render: data => {
                    return `<input type="checkbox" id="check-rows-import-xml" name="check-row-xml[]" class="row-checkbox">`;
                }
            },
            {
                name: "prog",
                type: "integer",
                className: "text-right",
                render: (data, type, row, meta) => {
                    return meta.row + 1;
                }
            },
            {
                name: "img",
                data: "img",
                type: "string",
                className: "text-center",
                render: function(data) {
                    return `<img src="${data}" alt="Product image" style="width: 72px; height: 72px; object-fit: cover; border: 1px solid #dcdcdc;border-radius: 0;">`;
                },
                orderable: false,
                searchable: false
            },
            {
                name: "supplier",
                data: "supplier_name",
                type: "string"
            },
            {
                name: "reference",
                data: "reference",
                type: "string"
            },
            {
                name: "ean13",
                data: "ean13",
                type: "string"
            },
            {
                name: "product",
                data: "product",
                type: "string"
            },
            {
                name: "combination",
                data: "combination",
                type: "string",
                render: data => {
                    return `
                                <span class="text-info text-bold">${data}</span>
                            `;
                }
            },
            {
                name: "qty_signed",
                data: "qty_signed",
                type: "integer",
                className: "text-right",
                render: data => {
                    return setQuantityColor(data, true);
                }
            },
            {
                name: "warehouse",
                data: "warehouse",
                type: "string"
            }
        ]
    });

    const checkImportXmlAll = document.getElementById("check-all-import");

    checkImportXmlAll.addEventListener("click", e => {
        const is_checked = $(e.target).is(":checked");
        const table = document.getElementById("table-import-xml");
        const checkBoxes = table.querySelectorAll("tbody tr .row-checkbox");

        // Usa DataTables per trovare tutti i checkbox nelle righe
        checkBoxes.forEach(checkbox => {
            checkbox.checked = is_checked;
        });
    });

    return dtXmlImport;
}

document.addEventListener("DOMContentLoaded", () => {
    dtXmlImport = initDataTable();

    const btnParseXmlFile = document.getElementById("btn-parse-xml-file");
    const btnImportSelected = document.getElementById("btn-import-selected");
    const btnImportAll = document.getElementById("btn-import-all");

    btnParseXmlFile.addEventListener("click", async e => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        const xmlForm = document.getElementById("import-xml-form");
        const formData = new FormData(xmlForm);
        try {
            const request = await fetch(window.parseXmlFileUrl, {
                method: "POST",
                body: formData
            });
            const data = await request.json();

            if (data.success) {
                const importStatusDialog = document.getElementById("import-status-dialog");
                importStatusDialog.showModal();
                importStatusDialog.querySelector("#import-status-title").textContent = "Importazione avvenuta con successo!";
                importStatusDialog.querySelector("#import-status-content").textContent = "Importazione avvenuta con successo!";
                importStatusDialog.querySelector("#close-dialog").addEventListener("click", () => {
                    importStatusDialog.close();
                });
                const tableData = data.content.rows;

                // Svuota la tabella tramite DataTables
                datasetXmlImport = tableData;
                dtXmlImport.destroy();
                dtXmlImport = initDataTable();
                document.getElementById("btn-import-selected").style.display = "flex";
                document.getElementById("btn-import-all").style.display = "flex";

                //Inserisco i dati del documento
                const document_number = document.getElementById("document-number");
                const document_date = document.getElementById("document-date");
                const document_date_iso = document.getElementById("document-date-iso");
                const document_supplier_id = document.getElementById("document-supplier-id");
                const document_supplier_id_span = document.getElementById("document-supplier-id-span");
                const document_supplier_name = document.getElementById("document-supplier-name");
                const document_movement_id = document.getElementById("document-movement-id");
                const document_movement_id_span = document.getElementById("document-movement-id-span");
                const document_movement_alias = document.getElementById("document-movement-alias");
                const document_movement_alias_span = document.getElementById("document-movement-alias-span");
                const document_movement_sign = document.getElementById("document-movement-sign");
                const document_movement_name = document.getElementById("document-movement-name");

                document_number.value = data.content.file.doc_number;
                document_date.value = data.content.file.doc_date;
                document_date_iso.value = data.content.file.doc_date_iso;
                document_supplier_id.value = data.content.file.supplier_id;
                document_supplier_id_span.textContent = data.content.file.supplier_id;
                document_supplier_name.value = data.content.file.supplier_name;
                document_movement_id.value = data.content.file.movement_id;
                document_movement_id_span.textContent = data.content.file.movement_id;
                document_movement_alias.value = data.content.file.movement_alias;
                document_movement_alias_span.textContent = data.content.file.movement_alias;
                document_movement_sign.value = data.content.file.movement_sign;
                document_movement_name.value = data.content.file.movement_name;
            } else {
                alert("Si è verificato un errore durante l'importazione.");
            }
        } catch (error) {
            console.error("Si è verificato un errore:", error);
            alert("Si è verificato un errore durante l'importazione.");
        }
        return false;
    });

    btnImportSelected.addEventListener("click", async e => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const selectedRowsData = await selectXmlTableRows(false);
        const imported = await importXmlStock(selectedRowsData);
    });

    btnImportAll.addEventListener("click", async e => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        const selectedRowsData = await selectXmlTableRows(true);
        const imported = await importXmlStock(selectedRowsData);
    });
});
