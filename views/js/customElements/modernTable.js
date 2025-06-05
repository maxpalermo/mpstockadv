export class ModernTable extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._data = [];
        this._columns = [];
        this._currentPage = 1;
        this.rowsPerPageOptionsArray = [5, 10, 25, 50, 100]; // Default options
        this._rowsPerPage = 10; // Default rows per page
        this._sortColumn = null;
        this._sortDirection = "asc"; // 'asc' or 'desc'
    }

    connectedCallback() {
        queueMicrotask(() => {
            this._initializeRowsPerPageOptions(); // Prima!
            this._render();
            this._applyBorderStyle(this.getAttribute("data-border"));
        });
    }

    _getTableStructure() {
        // Se ci sono dati JS, restituisci quelli
        if (this._data && Array.isArray(this._data) && this._data.length > 0) {
            return {
                data: this._data,
                columns: this._columns && this._columns.length > 0 ? this._columns : this._data.length > 0 ? Object.keys(this._data[0]).map((key) => ({ key, label: key })) : []
            };
        }
        // Altrimenti, prova a estrarre la tabella dal DOM
        const domTable = this.querySelector("table");
        if (domTable) {
            const { data, columns } = this._extractDataFromDOMTable(domTable);
            return { data, columns };
        }
        // Nessun dato disponibile
        return { data: [], columns: [] };
    }

    _render() {
        // Chiama la funzione getTableStructure() per ottenere i dati
        const JSONdata = this._getTableStructure();
        this._columns = JSONdata.columns || [];
        this._data = JSONdata.data || [];

        this.shadowRoot.innerHTML = "";
        this._injectStyle();

        if (JSONdata.data && Array.isArray(JSONdata.data) && JSONdata.data.length) {
            const columns = JSONdata.columns && JSONdata.columns.length ? JSONdata.columns : null;
            let sortedData = this._getSortedData();
            let paginatedData = this._getPaginatedData(sortedData);
            const table = this._buildTableFromData(paginatedData, columns);
            this.shadowRoot.appendChild(table);
            this._renderHeaders();
            this._renderPagination();
            this._applyHeaderStyle(this.getAttribute("data-header-style"));
        } else {
            // Nessun dato disponibile
            this.shadowRoot.innerHTML += "<div class='bootstrap alert alert-info'>No data to display</div>";
        }

        this._tableRendered = true;
    }

    /**
     * Estrae dati e meta-informazioni colonne da una tabella HTML.
     * @param {HTMLTableElement} table
     * @returns {{data: Array<Object>, columns: Array<Object>}}
     */
    _extractDataFromDOMTable(table) {
        const ths = Array.from(table.querySelectorAll("thead th"));
        const columns = ths.map((th) => ({
            key: th.dataset.key || th.textContent.trim(),
            label: th.textContent.trim(),
            renderFunction: th.dataset.renderFunction,
            align: th.dataset.align
        }));
        const rows = Array.from(table.querySelectorAll("tbody tr"));
        const data = rows.map((row) => {
            const obj = {};
            columns.forEach((col, idx) => {
                const cell = row.children[idx];
                obj[col.key] = cell ? cell.textContent.trim() : "";
            });
            return obj;
        });
        //Elimina la tabella dal DOM, sarà ricostruita da _buildTableFromData
        table.remove();

        return { data, columns };
    }

    /**
     * Costruisce una tabella HTML da un array di oggetti dati JS e meta colonne.
     * @param {Array<Object>} data
     * @param {Array<Object>} columns
     * @returns {HTMLTableElement}
     */
    _buildTableFromData(data, columns = null) {
        const cols = columns || Object.keys(data[0]).map((k) => ({ key: k, label: k }));
        // Costruisci la tabella
        const table = document.createElement("table");
        // Header
        const thead = document.createElement("thead");
        const headerRow = document.createElement("tr");
        cols.forEach((col) => {
            const th = document.createElement("th");
            th.textContent = col.label || col.key;
            th.dataset.key = col.key;
            if (col.align) th.dataset.align = col.align;
            if (col.renderFunction) th.dataset.renderFunction = col.renderFunction;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);

        //Action Buttons
        const actionButtons = this.getAttribute("data-action-buttons");
        if (actionButtons) {
            const th = document.createElement("th");
            th.textContent = "Actions";
            th.style.textAlign = "center";
            th.classList.add("actions-header");
            headerRow.appendChild(th);
        }

        // Body
        const tbody = document.createElement("tbody");
        if (!data.length) {
            const tr = document.createElement("tr");
            const td = document.createElement("td");
            td.innerHTML = `
                <div class="bootstrap alert alert-info">
                    No data to display
                </div>
            `;
            td.style.textAlign = "center";
            td.colSpan = cols.length;
            tr.appendChild(td);
            tbody.appendChild(tr);
        } else {
            data.forEach((rowData, rowIndex) => {
                const tr = document.createElement("tr");
                tr.classList.add("fade-in");
                setTimeout(() => tr.classList.remove("fade-in"), 400);
                cols.forEach((col) => {
                    const td = document.createElement("td");
                    let value = rowData[col.key];
                    let renderer = col.renderFunction;

                    switch (renderer) {
                        case "formatCurrency":
                        case "formatPrice":
                            value = this._formatCurrency(value);
                            break;
                        case "formatPercent":
                            value = this._formatPercent(value);
                            break;
                        case "formatDate":
                            value = this._formatDate(value);
                            break;
                        case "formatDateTime":
                            value = this._formatDateTime(value);
                            break;
                        case "formatBoolean":
                            value = this._formatBoolean(value);
                            break;
                        case "formatInteger":
                        case "formatQuantity":
                            value = this._formatInteger(value);
                            break;
                        case "formatFloat":
                            value = this._formatFloat(value);
                            break;
                        default:
                            value = this._formatString(value);
                    }
                    td.innerHTML = value;
                    if (col.align) td.style.textAlign = col.align;
                    tr.appendChild(td);
                });
                //Action Buttons
                const actionButtons = this.getAttribute("data-action-buttons");
                if (actionButtons) {
                    const td = document.createElement("td");
                    td.style.textAlign = "center";
                    td.classList.add("actions-cell");
                    const buttons = JSON.parse(actionButtons);
                    buttons.forEach((button) => {
                        const btn = document.createElement("button");
                        btn.textContent = button.label;
                        btn.classList.add("btn", "btn-sm", "btn-" + button.style);
                        btn.dataset.action = button.action;
                        td.appendChild(btn);
                    });
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
            });
        }
        table.appendChild(tbody);

        // Aggiungi il tfoot con la barra di paginazione
        const tfoot = document.createElement("tfoot");
        const tr = document.createElement("tr");
        const td = document.createElement("td");
        td.colSpan = cols.length + (actionButtons ? 1 : 0); // Se hai la colonna azioni
        td.innerHTML = '<div class="pagination-bar"></div>';
        tr.appendChild(td);
        tfoot.appendChild(tr);
        table.appendChild(tfoot);

        return table;
    }

    _initializeRowsPerPageOptions() {
        const optionsAttr = this.getAttribute("data-rows-per-page-options");
        let newOptions = [5, 10, 25, 50, 100]; // Default options

        if (optionsAttr) {
            try {
                const parsedOptions = JSON.parse(optionsAttr);
                if (Array.isArray(parsedOptions) && parsedOptions.length > 0 && parsedOptions.every((n) => typeof n === "number" && n > 0 && Number.isInteger(n))) {
                    newOptions = [...new Set(parsedOptions)].sort((a, b) => a - b);
                } else {
                    console.warn("ModernTable: data-rows-per-page-options attribute is invalid. Using default options:", parsedOptions);
                }
            } catch (e) {
                console.warn("ModernTable: Failed to parse data-rows-per-page-options. Using default options:", e);
            }
        }
        this.rowsPerPageOptionsArray = newOptions;

        const currentRowsPerPageAttrVal = this.getAttribute("rows-per-page");
        let initialRowsPerPage = this.rowsPerPageOptionsArray[0] || 10;

        if (currentRowsPerPageAttrVal !== null) {
            const parsedAttrRows = parseInt(currentRowsPerPageAttrVal, 10);
            if (!isNaN(parsedAttrRows) && this.rowsPerPageOptionsArray.includes(parsedAttrRows)) {
                initialRowsPerPage = parsedAttrRows;
            }
        }
        // Set _rowsPerPage directly. If it needs to be reflected as an attribute,
        // attributeChangedCallback for 'rows-per-page' will handle it if called by setAttribute.
        this._rowsPerPage = initialRowsPerPage;

        // If the current 'rows-per-page' attribute doesn't match the validated _rowsPerPage,
        // update the attribute. This ensures consistency and triggers attributeChangedCallback if needed.
        if (String(this._rowsPerPage) !== currentRowsPerPageAttrVal) {
            this.setAttribute("rows-per-page", String(this._rowsPerPage));
        }
    }

    static get observedAttributes() {
        return ["data-url", "rows-per-page", "data-header-style", "data-border", "data-rows-per-page-options"];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue === newValue) return;

        if (name === "data-url") {
            this.loadDataFromUrl(newValue);
        }
        if (name === "rows-per-page") {
            let newRows = parseInt(newValue, 10);
            let valueWasCorrected = false;

            // Ensure rowsPerPageOptionsArray is initialized.
            if (!this.rowsPerPageOptionsArray || this.rowsPerPageOptionsArray.length === 0) {
                console.warn("ModernTable: rowsPerPageOptionsArray not available during rows-per-page change. Re-initializing.");
                this._initializeRowsPerPageOptions();
            }

            if (isNaN(newRows) || newRows <= 0 || !this.rowsPerPageOptionsArray.includes(newRows)) {
                const attemptedValue = newValue;
                newRows = this.rowsPerPageOptionsArray[0] || 10; // Default to first option or a fallback
                console.warn(`ModernTable: rows-per-page value "${attemptedValue}" is invalid or not in options [${this.rowsPerPageOptionsArray.join(", ")}]. Defaulting to ${newRows}.`);
                valueWasCorrected = true;
            }

            // Only proceed if the value actually changes or if correction happened
            if (this._rowsPerPage !== newRows || valueWasCorrected) {
                this._rowsPerPage = newRows;
                this._currentPage = 1; // Always reset to page 1 on rowsPerPage change
                this._render();
                // If the value was corrected, update the attribute to reflect the actual _rowsPerPage.
                // This ensures the attribute is the source of truth and consistent.
                // The 'return' prevents an infinite loop as setAttribute re-triggers this callback.
                if (valueWasCorrected && String(this._rowsPerPage) !== newValue) {
                    this.setAttribute("rows-per-page", String(this._rowsPerPage));
                    return;
                }
            }
        }
        if (name === "data-header-style") {
            this._applyHeaderStyle(newValue);
        }
        if (name === "data-border") {
            this._applyBorderStyle(newValue);
        }
        if (name === "data-rows-per-page-options") {
            const oldRowsPerPageValue = this._rowsPerPage;
            this._initializeRowsPerPageOptions(); // This re-parses attribute and updates rowsPerPageOptionsArray and _rowsPerPage

            if (this._tableRendered) {
                let needsBodyRender = false;
                // If the actual _rowsPerPage value changed because the old one is no longer valid or was adjusted.
                if (oldRowsPerPageValue !== this._rowsPerPage) {
                    this._currentPage = 1;
                    needsBodyRender = true;
                }

                // Pagination always needs re-render as select options might have changed.
                if (needsBodyRender) {
                    this._updateTableBodyWithTransition(() => {
                        this._renderBody();
                        this._renderPagination(); // Update pagination along with body
                    });
                } else {
                    this._renderPagination(); // Only pagination if body doesn't need update
                }
            }
        }
    }

    setData(data, columns) {
        this._data = data || [];
        this._columns = columns || (this._data.length > 0 ? Object.keys(this._data[0]).map((key) => ({ key, label: this._formatHeaderLabel(key) })) : []);
        this._currentPage = 1;
        this._sortColumn = null;
        this._sortDirection = "asc";
        this._render();
    }

    async loadDataFromUrl(url) {
        if (!url) {
            this.setData([], []);
            return;
        }
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const jsonData = await response.json();
            if (jsonData.data && Array.isArray(jsonData.data) && jsonData.columns && Array.isArray(jsonData.columns)) {
                this._searchableColumns = jsonData.searchableColumns === undefined ? jsonData.columns.map((col) => col.key) : jsonData.searchableColumns;
                this._itemsPerPage = jsonData.itemsPerPage || 10;
                this._currentPage = 1;
                this._showPagination = jsonData.showPagination === undefined ? true : jsonData.showPagination;
                this._showSearch = jsonData.showSearch === undefined ? true : jsonData.showSearch;
                this._exportOptions = jsonData.exportOptions || { csv: true, excel: true, pdf: false };
                this._showExport = jsonData.showExport === undefined ? true : jsonData.showExport;
                this._tableTitle = jsonData.tableTitle || "";
                this._language = jsonData.language || navigator.language || "en-US";
                this._dateFormat = jsonData.dateFormat || {}; // Example: { year: 'numeric', month: 'short', day: 'numeric' }
                this._currencyFormat = jsonData.currencyFormat || {}; // Example: { style: 'currency', currency: 'EUR' }
                this._numberFormat = jsonData.numberFormat || {}; // Example: { minimumFractionDigits: 2, maximumFractionDigits: 2 }
                this._percentFormat = jsonData.percentFormat || { style: "percent", minimumFractionDigits: 1 };
                this._actionButtons = jsonData.buttons || []; // Added to parse buttons

                this.setData(jsonData.data, jsonData.columns);
            } else if (Array.isArray(jsonData)) {
                this.setData(jsonData);
            } else {
                console.error("Invalid data structure from URL", jsonData);
                this.setData([], []);
            }
        } catch (error) {
            console.error("Failed to load data from URL:", error);
            this.shadowRoot.innerHTML = `<p style="padding:1em; color:red;">Error loading data: ${error.message}</p>`;
        }
    }

    _formatPrice(value, column = {}) {
        return this._formatCurrency(value, column);
    }

    _formatCurrency(value, column = {}) {
        const number = parseFloat(value);
        if (isNaN(number)) {
            return value; // Return original value if not a number or empty
        }

        const locale = column.locale || "it-IT"; // Default to Italian locale
        const currencyCode = column.currencyCode || "EUR"; // Default to EUR

        try {
            // Format the number part first
            const numberPart = new Intl.NumberFormat(locale, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(number);

            return `${numberPart} ${currencyCode.toUpperCase()}`;
        } catch (e) {
            console.error(`ModernTable: Error formatting currency for value "${value}" with locale "${locale}" and currency "${currencyCode}":`, e);
            // Fallback to simple concatenation if formatting fails
            return `${String(value)} ${currencyCode.toUpperCase()}`;
        }
    }

    _formatDate(value, column = {}) {
        if (!value) return ""; // Return empty if no value
        const locale = column.locale || "it-IT"; // Default to Italian locale
        try {
            // Attempt to parse the date. This handles ISO strings, numbers (timestamps), etc.
            const date = new Date(value);
            // Check if the date is valid after parsing
            if (isNaN(date.getTime())) {
                console.warn(`ModernTable: Invalid date value "${value}" for column ${column.key}. Returning original value.`);
                return String(value);
            }
            return new Intl.DateTimeFormat(locale).format(date);
        } catch (e) {
            console.error(`ModernTable: Error formatting date for value "${value}" with locale "${locale}":`, e);
            return String(value); // Fallback to original value
        }
    }

    _formatPercent(value, column = {}) {
        const number = parseFloat(value);
        if (isNaN(number)) {
            return value; // Return original value if not a number or empty
        }
        const locale = column.locale || "it-IT"; // Use locale for number formatting part
        try {
            // Format the number part, then append %
            // Assuming value is like 0.25 for 25%
            const numberPart = new Intl.NumberFormat(locale, {
                minimumFractionDigits: 0, // Adjust as needed
                maximumFractionDigits: 2 // Adjust as needed
            }).format(number * 100);
            return `${numberPart}%`;
        } catch (e) {
            console.error(`ModernTable: Error formatting percent for value "${value}":`, e);
            return `${String(value * 100)}%`; // Basic fallback
        }
    }

    _formatBoolean(value, column = {}) {
        const locale = column.locale || "it-IT";
        try {
            //Restituisce due icone in base al valore
            return value ? "<i class='material-icons text-success'>check</i>" : "<i class='material-icons text-danger'>close</i>";
        } catch (e) {
            console.error(`ModernTable: Error formatting boolean for value "${value}":`, e);
            return String(value);
        }
    }

    _formatInteger(value, column = {}) {
        const locale = column.locale || "it-IT";
        try {
            return new Intl.NumberFormat(locale, {
                minimumFractionDigits: 0, // Adjust as needed
                maximumFractionDigits: 0 // Adjust as needed
            }).format(value);
        } catch (e) {
            console.error(`ModernTable: Error formatting integer for value "${value}":`, e);
            return String(value);
        }
    }

    _formatFloat(value, column = {}) {
        const locale = column.locale || "it-IT";
        try {
            return new Intl.NumberFormat(locale, {
                minimumFractionDigits: 2, // Adjust as needed
                maximumFractionDigits: 2 // Adjust as needed
            }).format(value);
        } catch (e) {
            console.error(`ModernTable: Error formatting float for value "${value}":`, e);
            return String(value);
        }
    }

    _formatString(value, column = {}) {
        return String(value);
    }

    async _updateTableBodyWithTransition(updateLogic) {
        const tbody = this.shadowRoot.querySelector("tbody");
        if (!tbody) {
            if (typeof updateLogic === "function") updateLogic.call(this);
            return;
        }

        const transitionDuration = 250; // Should match CSS transition duration in ms

        tbody.classList.add("modern-table-body-fading-out");
        tbody.classList.remove("modern-table-body-fading-in"); // Ensure clean state

        await new Promise((resolve) => {
            const handler = () => {
                tbody.removeEventListener("transitionend", handler);
                resolve();
            };
            tbody.addEventListener("transitionend", handler);
            // Fallback if transitionend doesn't fire (e.g. display:none or no actual change)
            setTimeout(() => {
                tbody.removeEventListener("transitionend", handler); // Clean up just in case
                resolve();
            }, transitionDuration + 50); // Add a small buffer
        });

        if (typeof updateLogic === "function") {
            updateLogic.call(this); // Execute the update logic (e.g., _renderBody)
        }

        // Force reflow to ensure the browser picks up the class removal and re-addition for transition
        void tbody.offsetWidth;

        tbody.classList.remove("modern-table-body-fading-out");
        tbody.classList.add("modern-table-body-fading-in"); // This should trigger the fade-in
    }

    _formatHeaderLabel(key) {
        return key
            .replace(/_/g, " ")
            .replace(/([A-Z])/g, " $1")
            .replace(/^./, (str) => str.toUpperCase())
            .trim();
    }

    _injectStyle() {
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: block;
                    font-family: 'Segoe UI', Arial, sans-serif;
                    color: #333;
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                    overflow: hidden; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th, td {
                    padding: 0.3rem 0.5rem; /* Ridotto padding per compattezza */
                    text-align: left;
                }

                :host(.table-bordered) table th,
                :host(.table-bordered) table td {
                    border: 1px solid #dee2e6; /* Colore standard per bordi tabella */
                }

                :host(.table-bordered) table thead th {
                    border-bottom-width: 2px;
                }

                /* white-space: nowrap; Removed to allow content wrapping if needed */
                thead th {
                    background-color: #f8f9fa;
                    font-weight: 600;
                    cursor: pointer;
                    position: relative;
                    user-select: none;
                }

                /* Header Theme Styles */
                thead.header-info th {
                    background-color: #17a2b8; /* Bootstrap info color */
                    color: white;
                }
                thead.header-warning th {
                    background-color: #ffc107; /* Bootstrap warning color */
                    color: #212529; /* Dark text for light background */
                }
                thead.header-success th {
                    background-color: #28a745; /* Bootstrap success color */
                    color: white;
                }
                thead.header-danger th {
                    background-color: #dc3545; /* Bootstrap danger color */
                    color: white;
                }
                thead.header-black th {
                    background-color: #343a40; /* Bootstrap dark/black color */
                    color: white;
                }
                thead.header-light th {
                    background-color: #f8f9fa; /* Bootstrap light color (similar to default) */
                    color: #212529; /* Dark text */
                    border-bottom: 1px solid #dee2e6; /* Add a subtle border for light theme */
                }
                thead th .sort-icon {
                    font-family: 'Material Icons'; 
                    font-size: 18px;
                    margin-left: 6px;
                    vertical-align: middle;
                    opacity: 0.5;
                }
                thead th.sorted .sort-icon {
                    opacity: 1;
                }
                thead th:hover .sort-icon {
                    opacity: 0.8;
                }
                tbody tr:hover {
                    background-color: #f1f1f1;
                }
                tbody tr:last-child td {
                    border-bottom: none;
                }
                tbody {
                    /* Ensure tbody behaves correctly with opacity transitions */
                    display: table-row-group; /* Default for tbody, but good to be explicit */
                    transition: opacity 0.15s ease-in-out;
                }

                tbody.modern-table-body-fading-out {
                    opacity: 0;
                }

                tbody.modern-table-body-fading-in {
                    opacity: 1; /* Target state for fade-in */
                }

                tfoot {
                    background-color: #f8f9fa;
                    border-top: 1px solid #e0e0e0;
                }
                .pagination-bar {
                    display: flex;
                    justify-content: space-between; /* Distribute items */
                    align-items: center;
                    padding: 8px 10px;
                    font-size: 0.85em;
                    flex-wrap: wrap; /* Allow wrapping on small screens */
                }
                .pagination-nav-controls,
                .pagination-summary,
                .pagination-rows-options {
                    display: flex;
                    align-items: center;
                    margin: 3px 5px; /* Add some margin for spacing when wrapped */
                }
                .pagination-summary {
                    flex-grow: 1; /* Allow summary to take available space if needed */
                    justify-content: center; /* Center summary text */
                    text-align: center; /* Ensure text itself is centered if it wraps */
                }
                .pagination-rows-options select.rows-per-page-select {
                    padding: 8px 30px 8px 12px; /* Adjusted padding for custom arrow */
                    font-family: inherit; /* Added for consistency */
                    font-size: 1em; /* Adjusted for consistency */
                    border: 1px solid #ccc;
                    border-radius: 6px;
                    background-color: #fff;
                    margin-left: 8px;
                    cursor: pointer;
                    appearance: none;
                    -webkit-appearance: none;
                    -moz-appearance: none;
                    background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23333%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E');
                    background-repeat: no-repeat;
                    background-position: right 10px center;
                    background-size: 10px 10px;
                    transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
                }

                .pagination-rows-options select.rows-per-page-select:hover {
                    border-color: #aaa;
                }

                .pagination-rows-options select.rows-per-page-select:focus {
                    border-color: #007bff;
                    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
                    outline: none;
                }

                .custom-tooltip {
                    position: fixed;
                    background-color: #333;
                    color: white;
                    padding: 6px 10px;
                    border-radius: 4px;
                    font-size: 0.8em;
                    z-index: 1000;
                    opacity: 0;
                    visibility: hidden;
                    transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out;
                    pointer-events: none;
                    white-space: nowrap;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                }

                .custom-tooltip.visible {
                    opacity: 1;
                    visibility: visible;
                }
                .pagination-bar button,
                .pagination-bar .page-input-container input {
                    padding: 5px 8px;
                    margin: 0 2px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                    background-color: #f0f0f0;
                    cursor: pointer;
                    font-family: inherit; /* Added for consistency */
                    font-size: 1em; /* Adjusted for consistency */
                    transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
                }
                .pagination-bar button:hover:not(:disabled) {
                    background-color: #e9e9e9;
                    border-color: #bbb;
                }
                .pagination-bar button:disabled {
                    cursor: not-allowed;
                    opacity: 0.5;
                }
                .pagination-bar .page-input-container input {
                    width: 40px;
                    text-align: center;
                    cursor: auto;
                }
                .pagination-bar .page-info {
                    margin: 0 8px;
                }

            .button-group {
                display: inline-flex;
                gap: 2px; /* Reduced gap for tighter button group appearance */
                justify-content: center;
                align-items: center;
            }
            .btn {
                padding: 4px 8px; /* Reduced padding for more compact buttons */
                border: 1px solid transparent;
                border-radius: 0.25rem;
                cursor: pointer;
                font-family: inherit; /* Ensure font consistency with the table */
                font-size: 0.875rem;
                line-height: 1.5;
                text-align: center;
                white-space: nowrap;
                vertical-align: middle;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
                text-decoration: none;
            }
            .btn .icon.material-icons {
                font-size: 1.125rem; /* 18px */
                vertical-align: text-bottom;
            }
            .btn .icon.material-icons + .label {
                margin-left: 0.35rem;
            }
            .btn .label + .icon.material-icons { /* Icon after label */
                margin-left: 0.35rem;
            }
            .btn .icon.material-icons:only-child {
                margin-left: 0;
            }
            .btn:hover {
                filter: brightness(90%);
            }
            .btn:disabled {
                cursor: not-allowed;
                opacity: 0.65;
            }
            .btn-primary {
                color: #fff;
                background-color: #007bff;
                border-color: #007bff;
            }
            .btn-secondary {
                color: #fff;
                background-color: #6c757d;
                border-color: #6c757d;
            }
            .btn-success {
                color: #fff;
                background-color: #28a745;
                border-color: #28a745;
            }
            .btn-danger {
                color: #fff;
                background-color: #dc3545;
                border-color: #dc3545;
            }
            .btn-warning {
                color: #212529;
                background-color: #ffc107;
                border-color: #ffc107;
            }
            .btn-info {
                color: #fff;
                background-color: #17a2b8;
                border-color: #17a2b8;
            }
            .btn-light {
                color: #212529;
                background-color: #f8f9fa;
                border-color: #f8f9fa;
            }
            .btn-dark {
                color: #fff;
                background-color: #343a40;
                border-color: #343a40;
            }
            </style>
            <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        `;
    }

    _renderHeaders() {
        const headerRow = this.shadowRoot.querySelector("thead tr");
        if (!headerRow) return;
        headerRow.innerHTML = "";

        // Add Action Buttons Header if they exist
        if (this._actionButtons && this._actionButtons.length > 0) {
            const th = document.createElement("th");
            th.textContent = "Actions";
            th.style.textAlign = "center";
            th.classList.add("actions-header");
            headerRow.appendChild(th);
        }

        this._columns.forEach((col) => {
            const th = document.createElement("th");
            th.textContent = col.label || this._formatHeaderLabel(col.key);
            th.dataset.key = col.key;

            const sortIcon = document.createElement("span");
            sortIcon.classList.add("sort-icon");
            sortIcon.classList.add("material-icons"); // Use class for Material Icons
            if (this._sortColumn === col.key) {
                th.classList.add("sorted");
                sortIcon.textContent = this._sortDirection === "asc" ? "arrow_upward" : "arrow_downward";
            } else {
                sortIcon.textContent = "unfold_more";
            }
            th.appendChild(sortIcon);

            th.addEventListener("click", () => this._handleSort(col.key));
            headerRow.appendChild(th);
        });

        const tfootTd = this.shadowRoot.querySelector("tfoot td");
        if (tfootTd) {
            tfootTd.setAttribute("colspan", (this._columns.length || 0) + (this._actionButtons && this._actionButtons.length > 0 ? 1 : 0) || 1);
        }
    }

    _renderBody() {
        const tbody = this.shadowRoot.querySelector("tbody");
        if (!tbody) return;
        tbody.innerHTML = "";

        const sortedData = this._getSortedData();
        const paginatedData = this._getPaginatedData(sortedData);

        if (paginatedData.length === 0) {
            const tr = tbody.insertRow();
            const td = tr.insertCell();
            td.setAttribute("colspan", (this._columns.length || 0) + (this._actionButtons && this._actionButtons.length > 0 ? 1 : 0) || 1);
            td.textContent = this._data.length === 0 ? "No data to display." : "No results for this page.";
            td.style.textAlign = "center";
            td.style.padding = "20px";
            return;
        }

        paginatedData.forEach((row) => {
            const tr = tbody.insertRow();
            this._columns.forEach((col) => {
                const td = tr.insertCell();
                const rawValue = row[col.key];

                switch (col.type) {
                    case "price":
                        td.style.textAlign = "right";
                        td.textContent = this._formatCurrency(rawValue, col);
                        break;
                    case "date":
                        td.style.textAlign = "center";
                        td.textContent = this._formatDate(rawValue, col);
                        break;
                    case "number":
                        td.style.textAlign = "right";
                        // For 'number', we might want specific formatting later, but for now, direct value.
                        // Ensure it's treated as a number for potential future Intl.NumberFormat use if needed.
                        td.textContent = rawValue !== undefined && rawValue !== null && !isNaN(parseFloat(rawValue)) ? String(parseFloat(rawValue)) : rawValue !== undefined && rawValue !== null ? String(rawValue) : "";
                        break;
                    case "percent":
                        td.style.textAlign = "right";
                        td.textContent = this._formatPercent(rawValue, col);
                        break;
                    case "image":
                        td.style.textAlign = col.align || "center"; // Default to center for images
                        const img = document.createElement("img");
                        img.src = rawValue !== undefined && rawValue !== null ? String(rawValue) : "";
                        img.style.objectFit = "contain";
                        img.style.maxWidth = col.width || "100%"; // Use column width or default to 100% of cell
                        img.style.maxHeight = col.height || "50px"; // Default max height for consistency, can be overridden by col.height
                        img.style.display = "block"; // Helps with centering if text-align is used on parent
                        img.style.margin = "auto"; // Another way to center block display elements
                        if (col.width) {
                            // If a specific width is provided, ensure it's respected as max-width
                            // and allow it to scale down if cell is smaller.
                            img.style.width = "100%"; // Make it responsive within the cell up to its max-width
                        }
                        td.appendChild(img);
                        break;
                    default:
                        td.style.textAlign = col.align || "left"; // Default to left, or use col.align if specified
                        td.textContent = rawValue !== undefined && rawValue !== null ? String(rawValue) : "";
                }
            });

            // Add action buttons cell
            if (this._actionButtons && this._actionButtons.length > 0) {
                const actionTd = tr.insertCell();
                actionTd.style.textAlign = "center"; // Center the button group
                const buttonGroup = document.createElement("div");
                buttonGroup.className = "button-group";

                this._actionButtons.forEach((buttonDef) => {
                    const button = document.createElement("button");
                    if (buttonDef.id) button.id = buttonDef.id;
                    if (buttonDef.name) button.name = buttonDef.name; // Set name attribute if present
                    if (buttonDef.description) button.dataset.tooltipText = buttonDef.description; // Set data-tooltip-text attribute

                    button.className = "btn"; // Base class
                    if (buttonDef.style) {
                        button.classList.add(`btn-${buttonDef.style}`); // e.g., btn-primary
                    } else {
                        button.classList.add("btn-secondary"); // Default style
                    }

                    let buttonContent = "";
                    if (buttonDef.icon) {
                        buttonContent += `<span class="icon material-icons">${buttonDef.icon}</span>`;
                    }
                    if (buttonDef.label) {
                        if (buttonDef.icon && buttonDef.label) buttonContent += " "; // Add space if both icon and label
                        buttonContent += `<span class="label">${buttonDef.label}</span>`;
                    }
                    button.innerHTML = buttonContent.trim() || "Action"; // Fallback if no icon/label

                    if (buttonDef.onclick) {
                        try {
                            const clickFunction = new Function("rowData", "event", buttonDef.onclick);
                            button.onclick = (event) => {
                                try {
                                    clickFunction.call(button, row, event);
                                } catch (e) {
                                    console.error(`Error executing onclick for button (id: ${buttonDef.id || "N/A"}):`, e, "Row data:", row);
                                }
                            };
                        } catch (e) {
                            console.error(`Error creating onclick function for button (id: ${buttonDef.id || "N/A"}):`, e, "Function body:", buttonDef.onclick);
                            button.disabled = true;
                            button.title = "Action disabled due to error. Check console.";
                        }
                    }
                    buttonGroup.appendChild(button);
                });
                actionTd.appendChild(buttonGroup);
            }
        });
    }

    _renderPagination() {
        const paginationBar = this.shadowRoot.querySelector(".pagination-bar");
        if (!paginationBar) return;

        const totalItems = this._data.length;
        const totalPages = Math.ceil(totalItems / this._rowsPerPage);

        if (totalPages <= 1) {
            paginationBar.innerHTML = totalItems > 0 ? `<div class="page-info">${totalItems} item${totalItems !== 1 ? "s" : ""}</div>` : "";
            return;
        }

        const startItem = totalItems > 0 ? (this._currentPage - 1) * this._rowsPerPage + 1 : 0;
        const endItem = Math.min(this._currentPage * this._rowsPerPage, totalItems);

        paginationBar.innerHTML = `
        <div class="pagination-nav-controls">
            <button id="firstPageBtn" aria-label="First Page" data-tooltip-text="First Page" ${this._currentPage === 1 ? "disabled" : ""}><span class="material-icons">first_page</span></button>
            <button id="prevPageBtn" aria-label="Previous Page" data-tooltip-text="Previous Page" ${this._currentPage === 1 ? "disabled" : ""}><span class="material-icons">chevron_left</span></button>
            <span class="page-info">
                Page
                <span class="page-input-container"><input type="number" class="page-input" value="${this._currentPage}" min="1" max="${totalPages}" aria-label="Current Page"></span>
                of ${totalPages}
            </span>
            <button id="nextPageBtn" aria-label="Next Page" data-tooltip-text="Next Page" ${this._currentPage === totalPages ? "disabled" : ""}><span class="material-icons">chevron_right</span></button>
            <button id="lastPageBtn" aria-label="Last Page" data-tooltip-text="Last Page" ${this._currentPage === totalPages ? "disabled" : ""}><span class="material-icons">last_page</span></button>
        </div>
        <div class="pagination-summary">
            ${totalItems > 0 ? `${startItem}-${endItem} of ${totalItems} items` : "No items"}
        </div>
        <div class="pagination-rows-options">
            <select class="rows-per-page-select" aria-label="Rows per page" data-tooltip-text="Rows per page">
                ${this.rowsPerPageOptionsArray.map((option) => `<option value="${option}" ${option === this._rowsPerPage ? "selected" : ""}>${option} per page</option>`).join("")}
            </select>
        </div>
    `;

        const firstPageBtn = paginationBar.querySelector("#firstPageBtn");
        if (firstPageBtn) {
            firstPageBtn.addEventListener("click", () => this._changePage(1));
            firstPageBtn.addEventListener("mouseenter", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
            firstPageBtn.addEventListener("mouseleave", () => this._hideTooltip());
            firstPageBtn.addEventListener("focus", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
            firstPageBtn.addEventListener("blur", () => this._hideTooltip());
        }

        const prevPageBtn = paginationBar.querySelector("#prevPageBtn");
        if (prevPageBtn) {
            prevPageBtn.addEventListener("click", () => this._changePage(this._currentPage - 1));
            prevPageBtn.addEventListener("mouseenter", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
            prevPageBtn.addEventListener("mouseleave", () => this._hideTooltip());
            prevPageBtn.addEventListener("focus", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
            prevPageBtn.addEventListener("blur", () => this._hideTooltip());
        }

        const nextPageBtn = paginationBar.querySelector("#nextPageBtn");
        if (nextPageBtn) {
            nextPageBtn.addEventListener("click", () => this._changePage(this._currentPage + 1));
            nextPageBtn.addEventListener("mouseenter", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
            nextPageBtn.addEventListener("mouseleave", () => this._hideTooltip());
            nextPageBtn.addEventListener("focus", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
            nextPageBtn.addEventListener("blur", () => this._hideTooltip());
        }

        const lastPageBtn = paginationBar.querySelector("#lastPageBtn");
        if (lastPageBtn) {
            lastPageBtn.addEventListener("click", () => this._changePage(totalPages));
            lastPageBtn.addEventListener("mouseenter", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
            lastPageBtn.addEventListener("mouseleave", () => this._hideTooltip());
            lastPageBtn.addEventListener("focus", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
            lastPageBtn.addEventListener("blur", () => this._hideTooltip());
        }

        const pageInput = paginationBar.querySelector(".page-input");
        pageInput.addEventListener("change", (e) => {
            let newPage = parseInt(e.target.value, 10);
            if (isNaN(newPage) || newPage < 1) newPage = 1;
            if (newPage > totalPages) newPage = totalPages;
            this._changePage(newPage);
        });
        pageInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter") {
                pageInput.blur(); // Trigger change event
            }
        });

        const rowsPerPageSelect = paginationBar.querySelector(".rows-per-page-select");
        if (rowsPerPageSelect) {
            // Check if we've already attached a listener to this specific select element instance
            // This is a simple way to avoid multiple listeners if _renderPagination is called on the same elements.
            // A more robust solution might involve storing and removing listeners in disconnectedCallback.
            if (!rowsPerPageSelect._listenerAttached) {
                rowsPerPageSelect.addEventListener("change", (e) => {
                    const newRowsPerPage = parseInt(e.target.value, 10);
                    // Set the attribute; attributeChangedCallback for 'rows-per-page' will handle logic.
                    this.setAttribute("rows-per-page", String(newRowsPerPage));
                });
                rowsPerPageSelect._listenerAttached = true;
                rowsPerPageSelect.addEventListener("mouseenter", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
                rowsPerPageSelect.addEventListener("mouseleave", () => this._hideTooltip());
                rowsPerPageSelect.addEventListener("focus", (e) => this._showTooltip(e, e.target.dataset.tooltipText));
                rowsPerPageSelect.addEventListener("blur", () => this._hideTooltip());
            }
        }
    }

    _showTooltip(event, text) {
        const tooltipElement = this.shadowRoot.getElementById("customTooltip");
        if (!tooltipElement || !text) return;

        tooltipElement.textContent = text;
        tooltipElement.classList.add("visible");

        const targetRect = event.target.getBoundingClientRect();
        const tooltipRect = tooltipElement.getBoundingClientRect();

        // Position above the target element, centered
        let top = targetRect.top - tooltipRect.height - 5; // 5px offset
        let left = targetRect.left + targetRect.width / 2 - tooltipRect.width / 2;

        // Adjust if tooltip goes off-screen
        if (top < 0) {
            // If too high, position below
            top = targetRect.bottom + 5;
        }
        if (left < 0) {
            left = 5; // 5px from left edge
        }
        if (left + tooltipRect.width > window.innerWidth) {
            left = window.innerWidth - tooltipRect.width - 5; // 5px from right edge
        }

        tooltipElement.style.left = `${left}px`;
        tooltipElement.style.top = `${top}px`;
    }

    _hideTooltip() {
        const tooltipElement = this.shadowRoot.getElementById("customTooltip");
        if (tooltipElement) {
            tooltipElement.classList.remove("visible");
        }
    }

    _handleSort(key) {
        if (this._sortColumn === key) {
            this._sortDirection = this._sortDirection === "asc" ? "desc" : "asc";
        } else {
            this._sortColumn = key;
            this._sortDirection = "asc";
        }
        this._currentPage = 1;
        this._render();
    }

    _getSortedData() {
        if (!this._sortColumn) {
            return [...this._data];
        }
        return [...this._data].sort((a, b) => {
            let valA = a[this._sortColumn];
            let valB = b[this._sortColumn];

            // Type-aware sorting (basic)
            if (typeof valA === "string" && typeof valB === "string") {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            } else if (typeof valA === "number" && typeof valB === "number") {
                // standard comparison
            } else {
                // Fallback for mixed types or other types: convert to string
                valA = String(valA).toLowerCase();
                valB = String(valB).toLowerCase();
            }

            if (valA < valB) return this._sortDirection === "asc" ? -1 : 1;
            if (valA > valB) return this._sortDirection === "asc" ? 1 : -1;
            return 0;
        });
    }

    _getPaginatedData(data) {
        if (this._rowsPerPage <= 0) return data; // No pagination if rowsPerPage is 0 or less
        const start = (this._currentPage - 1) * this._rowsPerPage;
        const end = start + this._rowsPerPage;
        return data.slice(start, end);
    }

    _changePage(newPage) {
        const totalPages = Math.ceil(this._data.length / this._rowsPerPage);
        if (newPage >= 1 && newPage <= totalPages && newPage !== this._currentPage) {
            this._currentPage = newPage;
            this._render();
        } else if (newPage === this._currentPage) {
            // If the input page is the same, ensure the input field reflects the current page
            const pageInput = this.shadowRoot.querySelector(".page-input");
            if (pageInput) pageInput.value = this._currentPage;
        }
    }

    _applyHeaderStyle(styleName) {
        const thead = this.shadowRoot.querySelector("thead");
        if (!thead) return;

        // Remove any existing style classes
        thead.classList.remove("header-info", "header-warning", "header-success", "header-danger", "header-black", "header-light");

        if (styleName) {
            thead.classList.add(`header-${styleName.toLowerCase()}`);
        }
    }

    _applyBorderStyle(value) {
        if (value === "1") {
            this.classList.add("table-bordered");
        } else {
            this.classList.remove("table-bordered");
        }
    }
}

customElements.define("modern-table", ModernTable);
