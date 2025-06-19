// Placeholder for ModernSelect component
// Please copy the content of productAutocomplete.js here.
class ModernSelect extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._dropdownOpen = false;
        this._fetchController = null;
        this._allOptions = [];
        this._isDataLoaded = false;
        this._activeDescendant = null;
        this._selectedItems = []; // Initialize _selectedItems

        this._maxResults = parseInt(this.getAttribute("max-results")) || 1000;
        this._placeholder = this.getAttribute("placeholder") || "Seleziona o cerca...";
        this._endpoint = this.getAttribute("endpoint") || "";
        this._multiple = this.hasAttribute("multiple");
        this._displayType = this.getAttribute("data-display-type") || "standard";
        // _results, _selected, and _minLength are removed or their roles will be handled differently.
        this.shadowRoot.innerHTML = `
            <div id="options-slot-wrapper"><slot id="options-slot"></slot></div>
            <style>
                :host {
                    display: inline-block;
                    position: relative;
                    font-family: 'Segoe UI', Arial, sans-serif;
                    min-width: 200px;
                    width: 100%;
                    box-sizing: border-box;
                }
                :host([hidden]) {
                    display: none;
                }
                #options-slot-wrapper {
                    display: none;
                }
                .modern-select-container {
                    width: 100%;
                    position: relative;
                }
                .selected-list {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px;
                    margin-bottom: 6px;
                }
                .selected-item {
                    background: #e0e7ff;
                    color: #3730a3;
                    border-radius: 6px;
                    padding: 5px 8px;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 0.9rem;
                    border: 1px solid #c7d2fe;
                    line-height: 1.2;
                }
                .selected-item.colorpicker-item {
                    /* Specific styles if needed, already covered by .selected-item and color-swatch logic */
                }
                .remove-btn {
                    background: none;
                    border: none;
                    color: #be123c;
                    font-size: 1rem;
                    cursor: pointer;
                    padding: 2px;
                    margin-left: 4px;
                    line-height: 1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                .remove-btn:hover {
                    color: #9f1239;
                }
                .select-display {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    width: 100%;
                    padding: 10px 12px;
                    border-radius: 8px;
                    border: 1.5px solid #d1d5db;
                    font-size: 1rem;
                    background: #fff;
                    cursor: pointer;
                    transition: border-color 0.18s, box-shadow 0.18s;
                    box-sizing: border-box;
                }
                :host([open]) .select-display, .select-display:focus {
                    border-color: #4f8cff;
                    box-shadow: 0 0 0 2px rgba(79, 140, 255, 0.2);
                    outline: none;
                }
                .display-text {
                    flex-grow: 1;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    display: flex; /* For colorpicker swatch alignment */
                    align-items: center; /* For colorpicker swatch alignment */
                }
                .display-text.placeholder {
                    color: #6b7280;
                }
                .dropdown-arrow {
                    margin-left: 10px;
                    font-size: 0.9em;
                    color: #6b7280;
                    transition: transform 0.2s ease;
                }
                :host([open]) .dropdown-arrow {
                    transform: rotate(180deg);
                }
                .dropdown {
                    position: absolute;
                    left: 0; right: 0;
                    top: calc(100% + 4px);
                    background: #fff;
                    border-radius: 8px;
                    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
                    z-index: 1000;
                    max-height: 280px;
                    display: flex;
                    flex-direction: column;
                    animation: fadeIn 0.15s ease-out;
                    border: 1.5px solid #c7d2fe;
                    overflow: hidden;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-6px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .search-input-wrapper {
                    padding: 8px;
                    border-bottom: 1.5px solid #e5e7eb;
                }
                .search-input {
                    width: 100%;
                    padding: 8px 10px;
                    border-radius: 6px;
                    border: 1.5px solid #d1d5db;
                    font-size: 0.95rem;
                    outline: none;
                    box-sizing: border-box;
                }
                .search-input:focus {
                    border-color: #4f8cff;
                    box-shadow: 0 0 0 2px rgba(79, 140, 255, 0.2);
                }
                .results-list {
                    list-style-type: none; /* Ensure no bullets */
                    padding: 0;
                    margin: 0;
                    flex-grow: 1;
                    overflow-y: auto;
                }
                .results-list li { /* General styling for all list items */
                    display: flex;
                    align-items: center;
                    gap: 8px; 
                    padding: 10px 12px;
                    cursor: pointer;
                    transition: background-color 0.15s;
                    border-bottom: 1px solid #f3f4f6; /* Light separator */
                    font-size: 0.95rem;
                }
                .results-list li:last-child {
                    border-bottom: none;
                }
                .results-list li:hover,
                .results-list li.active-descendant { /* For keyboard navigation */
                    background-color: #f0f5ff; /* Light blue hover */
                    color: #333;
                }
                .results-list li.selected { /* For already selected items in the list */
                    background-color: #e0e7ff;
                    color: #3730a3;
                    font-weight: 500;
                }
                .results-list li.colorpicker-option {
                    /* display: flex; align-items: center; already in general li */
                }
                .color-swatch {
                    display: inline-block;
                    width: 14px;
                    height: 14px;
                    border: 1px solid #ccc;
                    margin-right: 8px; /* Kept for spacing if text is direct child */
                    vertical-align: middle; /* Useful if not flex parent */
                    flex-shrink: 0; /* Prevent swatch from shrinking */
                }
                .result-labels { /* Used if options have complex structure like name/description */
                    display: flex;
                    flex-direction: column;
                    gap: 1px;
                    overflow: hidden;
                }
                .result-name {
                    font-weight: 500;
                    color: #1f2937;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .result-description {
                    font-size: 0.85rem;
                    color: #4b5563;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .no-results {
                    padding: 12px;
                    text-align: center;
                    color: #6b7280;
                    font-style: italic;
                }
            </style>
            <div class="modern-select-container">
                <div class="selected-list" part="selected-list">
                    <!-- Selected items will be rendered here by JS -->
                </div>
                <div class="select-display" part="display" role="combobox" aria-haspopup="listbox" aria-expanded="false" tabindex="0">
                    <span class="display-text placeholder" part="placeholder"></span>
                    <span class="dropdown-arrow" part="arrow">▼</span>
                </div>
                <div class="dropdown" part="dropdown" style="display: none;">
                    <div class="search-input-wrapper" part="search-wrapper">
                        <input type="text" class="search-input" part="search-input" placeholder="Cerca..." aria-label="Search options" autocomplete="off">
                    </div>
                    <ul class="results-list" part="results-list" role="listbox" aria-live="polite">
                        <!-- Filtered results will be rendered here by JS -->
                    </ul>
                </div>
            </div>
        `;

        // Query for shadow DOM elements right after innerHTML is set
        this.$selectDisplay = this.shadowRoot.querySelector(".select-display");
        this.$displayText = this.shadowRoot.querySelector(".display-text");
        this.$dropdown = this.shadowRoot.querySelector(".dropdown");
        this.$searchInput = this.shadowRoot.querySelector(".search-input");
        this.$resultsList = this.shadowRoot.querySelector(".results-list");
        this.$selectedItemsList = this.shadowRoot.querySelector(".selected-list");
        this._optionsSlotElement = this.shadowRoot.getElementById("options-slot");
    }

    connectedCallback() {
        this._bindEvents();
        this._updateDisplayText(); // Initial display text

        let slottedOptionsProcessed = false;
        if (this._optionsSlotElement) {
            slottedOptionsProcessed = this._loadSlottedOptions(); // Initial load
            this._optionsSlotElement.addEventListener("slotchange", () => {
                this._loadSlottedOptions();
                // Re-evaluate selection with new options
                // If a value was programmatically set before options were fully parsed, or if options change,
                // this ensures the selection remains consistent.
                const currentValue = this.getValue();
                this.selected = currentValue;
            });
        }

        // If no slotted options were processed AND an endpoint exists, fetch remote data.
        if (!slottedOptionsProcessed && this._endpoint) {
            this._loadRemoteOptions();
        } else if (!slottedOptionsProcessed && !this._endpoint && !this._optionsSlotElement) {
            // Case: No slot element found (template error?) and no endpoint either.
            this._allOptions = [];
            this._isDataLoaded = true;
            this._renderResults([]);
        }
        // If _loadSlottedOptions was called but found no elements, and there's no endpoint,
        // it already handles clearing options and rendering the 'no results' state.
    }

    _loadSlottedOptions() {
        const optionsSlot = this._optionsSlotElement;
        if (!optionsSlot) {
            if (!this._endpoint) {
                this._allOptions = [];
                this._isDataLoaded = true;
                this._renderResults([]);
            }
            return false;
        }

        const slottedElements = optionsSlot.assignedElements({ flatten: true });

        if (slottedElements.length > 0) {
            this._allOptions = slottedElements
                .filter((node) => node.matches("div, option"))
                .map((node, index) => {
                    let optionData = {};
                    if (node.tagName.toLowerCase() === "option") {
                        optionData = {
                            id: node.getAttribute("data-id") || node.value || `slotted-opt-${index}-${Math.random().toString(36).substring(2, 7)}`,
                            value: node.value,
                            name: node.label || node.textContent.trim()
                        };
                    } else {
                        optionData = {
                            id: node.dataset.id || node.dataset.value || `slotted-opt-${index}-${Math.random().toString(36).substring(2, 7)}`,
                            value: node.dataset.value !== undefined ? node.dataset.value : node.textContent.trim(),
                            name: node.dataset.label || node.textContent.trim()
                        };
                    }

                    const finalOption = {
                        ...optionData,
                        originalElement: node
                    };

                    Object.keys(node.dataset).forEach((key) => {
                        if (!(key in finalOption)) {
                            finalOption[key] = node.dataset[key];
                        }
                    });

                    if (this._displayType === "colorpicker" && node.dataset.code) {
                        finalOption.code = node.dataset.code;
                    } else if (this._displayType === "colorpicker" && node.dataset.colorCode) {
                        finalOption.code = node.dataset.colorCode;
                    }

                    return finalOption;
                });
            this._isDataLoaded = true;
            this._updateDisplayText();
            this._renderResults(this._getFilteredOptions(this.$searchInput && this.$searchInput.value ? this.$searchInput.value : ""));
            return true;
        }

        if (!this._endpoint) {
            this._allOptions = [];
            this._isDataLoaded = true;
            this._renderResults([]);
        }
        return false;
    }

    _bindEvents() {
        this.$selectDisplay.addEventListener("click", (e) => this._onSelectDisplayClick(e));
        this.$selectDisplay.addEventListener("focus", () => this._onSelectDisplayFocus());
        this.$selectDisplay.addEventListener("keydown", (e) => this._onSelectDisplayKeyDown(e));

        this.$searchInput.addEventListener("input", (e) => this._onSearchInput(e));
        this.$searchInput.addEventListener("keydown", (e) => this._onSearchInputKeyDown(e));
        this.$searchInput.addEventListener("focus", () => this._onSearchInputFocus());
        this.$searchInput.addEventListener("blur", () => this._onSearchInputBlur());
        this.$searchInput.addEventListener("click", (e) => {
            console.log("[ModernSelect] Search input clicked, stopping propagation.");
            e.stopPropagation();
        });

        this.$resultsList.addEventListener("click", (e) => this._onResultsListClick(e));
        this.$resultsList.addEventListener("mousedown", (e) => {
            // Prevent mousedown on a result item from immediately blurring the search input.
            // This allows the 'click' event on the item to properly handle focus and selection
            // without the dropdown closing prematurely due to the search input's blur event.
            console.log("[ModernSelect] Results list mousedown, preventing default to keep search input focused.");
            e.preventDefault();
        });
        // Prevent mousedown on dropdown from blurring the search input if it's focused, or select display
        // TEMP: Commenting out to debug non-selectable items in dropdown
        // this.$dropdown.addEventListener("mousedown", (e) => e.preventDefault());

        document.addEventListener("click", (e) => this._onDocumentClick(e));
    }

    _onSelectDisplayClick(e) {
        console.log(`[ModernSelect] _onSelectDisplayClick called. Event target:`, e.target, `Dropdown open: ${this._dropdownOpen}`);
        console.log(`[ModernSelect] _onSelectDisplayClick: e.target is $searchInput? ${e.target === this.$searchInput}. $searchInput is:`, this.$searchInput);
        // If the click target is the search input itself, do nothing here.
        // The search input's focus handler (_onSearchInputFocus) is responsible for opening the dropdown.
        if (e.target === this.$searchInput) {
            console.log("[ModernSelect] Click target IS searchInput, _onSelectDisplayClick will not toggle.");
            // Ensure search input gets focus if it was clicked, which should trigger _onSearchInputFocus if not already active
            if (document.activeElement !== this.$searchInput) {
                this.$searchInput.focus();
            }
            return;
        }
        console.log("[ModernSelect] Click target is NOT searchInput, _onSelectDisplayClick will proceed to toggle.");
        this._toggleDropdown();
    }

    _onSelectDisplayFocus() {
        // Open dropdown on focus, but only if not already open via a click
        // This could also be configured via an attribute if more nuanced behavior is needed
        if (!this._dropdownOpen) {
            this._toggleDropdown(true);
        }
    }

    _toggleDropdown(forceState) {
        console.log(`[ModernSelect] _toggleDropdown called. Current open state: ${this._dropdownOpen}, forceState: ${forceState}`, this.$selectDisplay);
        const shouldOpen = forceState !== null ? forceState : !this._dropdownOpen;

        if (shouldOpen) {
            if (!this._isDataLoaded && this._endpoint) {
                this._loadAllOptions(); // We'll implement this next
            } else {
                // If data is loaded, or no endpoint, just show current/filtered options
                // Ensure results are rendered if they were cleared or filtered
                this._renderResults(this._getFilteredOptions(this.$searchInput.value));
            }
            this._dropdownOpen = true;
            this.setAttribute("open", "");
            this.$dropdown.style.display = "flex";
            this.$searchInput.focus(); // Focus the search input when dropdown opens
            this._updateActiveDescendant(); // Ensure correct ARIA active descendant
        } else {
            this._dropdownOpen = false;
            this.removeAttribute("open");
            this.$dropdown.style.display = "none";
            // Optional: clear search and reset list to all items when closing
            // this.$searchInput.value = '';
            // this._renderResults(this._allOptions);
        }
    }

    async _fetchData(url) {
        if (this._fetchController) {
            this._fetchController.abort();
        }
        this._fetchController = new AbortController();
        const signal = this._fetchController.signal;

        try {
            const response = await fetch(url, { signal });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return await response.json();
        } catch (error) {
            if (error.name === "AbortError") {
                console.log("Fetch aborted");
            } else {
                console.error("Fetch error:", error);
            }
            // In case of a real error, or if not aborted, return null or rethrow
            if (error.name !== "AbortError") throw error;
            return null;
        }
    }

    async _loadRemoteOptions() {
        if (!this._endpoint) {
            console.warn("ModernSelect: No endpoint provided.");
            this._allOptions = [];
            this._isDataLoaded = true;
            this._renderResults([]); // Render empty or a message
            return;
        }

        // Show loading state
        this.$resultsList.innerHTML = `<div class="loading-message no-results">${this.getAttribute("loading-text") || "Loading..."}</div>`;

        try {
            let rawData = await this._fetchData(this._endpoint);
            let optionsArray = [];

            if (rawData) {
                if (this._displayType === "colorpicker" && typeof rawData === "object" && rawData.hasOwnProperty("options") && Array.isArray(rawData.options)) {
                    if (rawData.type === "colorpicker") {
                        // Optional: check type property in data
                        optionsArray = rawData.options;
                    }
                } else if (Array.isArray(rawData)) {
                    optionsArray = rawData;
                }
            }

            if (optionsArray.length > 0) {
                this._allOptions = optionsArray.map((item) => {
                    const baseOption = {
                        id: item.id || item.value || item.key,
                        name: item.name || item.text || item.label,
                        value: item.value || item.id,
                        image: item.image || item.imageUrl || item.icon,
                        description: item.description,
                        reference: item.reference,
                        originalItem: item
                    };
                    if (this._displayType === "colorpicker" && item.code) {
                        baseOption.code = item.code;
                    }
                    return baseOption;
                });
                this._isDataLoaded = true;
                this._renderResults(this._getFilteredOptions(this.$searchInput.value)); // Render based on current search
            } else if (rawData === null && this._fetchController && this._fetchController.signal.aborted) {
                // Fetch was aborted, do nothing further here.
                return;
            } else {
                console.error("ModernSelect: Data from endpoint is not a valid array or fetch failed.", data);
                this._allOptions = [];
                this._isDataLoaded = true;
                this.$resultsList.innerHTML = `<div class="error-message no-results">${this.getAttribute("error-text") || "Error loading options."}</div>`;
            }
        } catch (error) {
            // Don't show error if it was an abort
            if (error.name !== "AbortError") {
                console.error("ModernSelect: Error in _loadAllOptions:", error);
                this._allOptions = [];
                this._isDataLoaded = true;
                this.$resultsList.innerHTML = `<div class="error-message no-results">${this.getAttribute("error-text") || "Error loading options."}</div>`;
            }
        }
    }
    _getFilteredOptions(searchTerm = "") {
        const term = searchTerm.toLowerCase().trim();
        if (!term) {
            return [...this._allOptions]; // Return all options if search term is empty
        }
        return this._allOptions.filter((option) => {
            return (option.name && option.name.toLowerCase().includes(term)) || (option.reference && option.reference.toLowerCase().includes(term)) || (option.description && option.description.toLowerCase().includes(term)) || (option.id && String(option.id).toLowerCase().includes(term));
        });
    }

    _renderResults(options) {
        this.$resultsList.innerHTML = ""; // Clear previous results

        if (!options || options.length === 0) {
            const noResultsMessage = this.getAttribute("no-results-text") || "No options found.";
            this.$resultsList.innerHTML = `<div class="no-results">${noResultsMessage}</div>`;
            this._updateActiveDescendant(); // Clear active descendant
            return;
        }

        options.forEach((option) => {
            const li = document.createElement("li");
            li.dataset.id = option.id;
            li.setAttribute("role", "option");
            li.setAttribute("aria-selected", "false"); // Default to not selected
            li.setAttribute('tabindex', '-1'); // Make item programmatically focusable via JS

            if (this._displayType === "colorpicker" && option.code) {
                li.classList.add("colorpicker-option");
                const swatch = document.createElement("span");
                swatch.className = "color-swatch";
                swatch.style.backgroundColor = option.code;
                li.appendChild(swatch);
            }

            const textNode = document.createTextNode(option.name || option.value);
            li.appendChild(textNode);

            // Check if this option is already selected (for visual indication if needed)
            if (this._selectedItems.find((selected) => selected.id === option.id)) {
                li.classList.add("selected");
                li.setAttribute("aria-selected", "true");
            }
            this.$resultsList.appendChild(li);
        });

        if (options.length === 0) {
            const noResultsMessage = this.getAttribute("no-results-text") || "No options found.";
            this.$resultsList.innerHTML = `<div class="no-results">${noResultsMessage}</div>`;
            this._updateActiveDescendant(); // Clear active descendant
            return;
        }
    }

    _onSearchInput(e) {
        const searchTerm = e.target.value;
        const filteredOptions = this._getFilteredOptions(searchTerm);
        this._renderResults(filteredOptions);
    }

    _onResultsListClick(e) {
        console.log("[ModernSelect] _onResultsListClick triggered. Event target:", e.target);
        const listItem = e.target.closest('li[role="option"]');
        console.log("[ModernSelect] _onResultsListClick: listItem found:", listItem);
        if (listItem && listItem.dataset.id) {
            listItem.focus(); // Set focus to the clicked item
            const optionId = listItem.dataset.id;
            const selectedOption = this._allOptions.find((opt) => String(opt.id) === optionId);
            console.log(`[ModernSelect] _onResultsListClick: optionId: ${optionId}, selectedOption found:`, selectedOption);
            if (selectedOption) {
                this._selectOption(selectedOption);
            }
        } else {
            console.log("[ModernSelect] _onResultsListClick: No valid list item or item ID found for target:", e.target);
        }
    }

    _selectOption(option) {
        if (!option) return;

        if (this._multiple) {
            const index = this._selectedItems.findIndex((item) => item.id === option.id);
            if (index > -1) {
                // Option already selected, potentially remove it (toggle behavior)
                // For now, let's assume selecting an already selected item does nothing or is handled by a separate deselect click.
                // To implement toggle: this._selectedItems.splice(index, 1);
            } else {
                this._selectedItems.push(option);
            }
            this._renderSelectedItemsList();
        } else {
            this._selectedItems = [option];
            this._toggleDropdown(false); // Close dropdown on selection for single-select
        }

        this._updateDisplayText();
        this.dispatchEvent(
            new CustomEvent("modern-select-change", {
                detail: {
                    selected: this._multiple ? [...this._selectedItems] : this._selectedItems[0] || null
                },
                bubbles: true,
                composed: true
            })
        );
    }

    _deselectOption(optionId) {
        if (!this._multiple) return; // Deselection primarily for multiple mode via UI

        this._selectedItems = this._selectedItems.filter((item) => String(item.id) !== String(optionId));
        this._renderSelectedItemsList();
        this._updateDisplayText();

        this.dispatchEvent(
            new CustomEvent("modern-select-change", {
                detail: {
                    selected: [...this._selectedItems]
                },
                bubbles: true,
                composed: true
            })
        );
    }

    _updateDisplayText() {
        if (this._selectedItems.length > 0) {
            if (this._multiple) {
                const displayTexts = this._selectedItems.map((item) => item.name);
                // Show number of items if more than 2, else show names
                if (displayTexts.length > 2) {
                    this.$displayText.textContent = `${displayTexts.length} ${this.getAttribute("items-selected-text") || "items selected"}`;
                } else {
                    this.$displayText.textContent = displayTexts.join(", ");
                }
            } else {
                this.$displayText.textContent = this._selectedItems[0].name;
            }
            this.$displayText.classList.remove("placeholder");
        } else {
            this.$displayText.innerHTML = ""; // Clear previous content
            if (this._displayType === "colorpicker" && !this._multiple && this._selectedItems.length > 0 && this._selectedItems[0].code) {
                const swatch = document.createElement("span");
                swatch.className = "color-swatch";
                swatch.style.backgroundColor = this._selectedItems[0].code;
                this.$displayText.appendChild(swatch);
                this.$displayText.appendChild(document.createTextNode(this._selectedItems[0].name || this._selectedItems[0].value));
                this.$displayText.classList.remove("placeholder");
            } else {
                this.$displayText.textContent = this._placeholder;
                this.$displayText.classList.add("placeholder");
            }
        }
    }

    _renderSelectedItemsList() {
        this.$selectedItemsList.innerHTML = "";
        if (this._multiple && this._selectedItems.length > 0) {
            this.$selectedItemsList.style.display = "flex";
            const fragment = document.createDocumentFragment();
            this._selectedItems.forEach((item) => {
                const itemEl = document.createElement("div");
                itemEl.className = "selected-item";
                // itemDiv.textContent = item.name || item.value; // Replaced by more specific content setting
                if (this._displayType === "colorpicker" && item.code) {
                    itemDiv.classList.add("colorpicker-item");
                    const swatch = document.createElement("span");
                    swatch.className = "color-swatch";
                    swatch.style.backgroundColor = item.code;
                    itemDiv.appendChild(swatch);
                }
                itemDiv.appendChild(document.createTextNode(item.name || item.value));

                const removeBtn = document.createElement("button");
                removeBtn.className = "remove-btn";
                removeBtn.innerHTML = "&times;"; // Multiplication sign X
                removeBtn.setAttribute("aria-label", `Remove ${item.name}`);
                removeBtn.dataset.id = item.id;
                removeBtn.addEventListener("click", (e) => {
                    e.stopPropagation(); // Prevent select display click
                    this._deselectOption(item.id);
                });
                itemEl.appendChild(removeBtn);
                fragment.appendChild(itemEl);
            });
            this.$selectedItemsList.appendChild(fragment);
        } else {
            this.$selectedItemsList.style.display = "none";
        }
    }

    // Placeholder for ARIA updates
    _updateActiveDescendant(elementId = null) {
        if (elementId) {
            this.$searchInput.setAttribute("aria-activedescendant", elementId);
        } else {
            this.$searchInput.removeAttribute("aria-activedescendant");
        }
        // Ensure all .result items are not aria-selected, then mark the active one
        this.shadowRoot.querySelectorAll(".result").forEach((el) => el.removeAttribute("aria-selected"));
        if (elementId) {
            const activeEl = this.shadowRoot.getElementById(elementId);
            if (activeEl) activeEl.setAttribute("aria-selected", "true");
        }
    }

    _onSelectDisplayKeyDown(e) {
        if (this._dropdownOpen && ["ArrowDown", "ArrowUp", "Enter", "Escape"].includes(e.key)) {
            // If dropdown is open and selectDisplay somehow gets keydown, redirect to searchInput's handler
            this.$searchInput.focus();
            this._onSearchInputKeyDown(e); // Pass the event to the search input's handler
            return;
        }

        switch (e.key) {
            case "Enter":
            case " ": // Space key
            case "ArrowDown":
            case "ArrowUp":
                e.preventDefault();
                this._toggleDropdown(true);
                // For ArrowDown/Up, _highlightOption will be called by _onSearchInputKeyDown if needed after focus
                break;
            case "Escape":
                if (this._dropdownOpen) {
                    e.preventDefault();
                    this._toggleDropdown(false);
                }
                break;
            // Tab key will follow default browser behavior
        }
    }

    _onSearchInputKeyDown(e) {
        if (!this._dropdownOpen) return;

        const results = Array.from(this.$resultsList.querySelectorAll('.result[role="option"]'));
        // Allow Escape and Tab even if no results
        if (results.length === 0 && !["Escape", "Tab"].includes(e.key)) return;

        let currentIndex = results.findIndex((r) => r.id === this.$searchInput.getAttribute("aria-activedescendant"));

        switch (e.key) {
            case "ArrowDown":
                e.preventDefault();
                currentIndex = currentIndex < results.length - 1 ? currentIndex + 1 : 0;
                if (results[currentIndex]) this._highlightOption(results[currentIndex]);
                break;
            case "ArrowUp":
                e.preventDefault();
                currentIndex = currentIndex > 0 ? currentIndex - 1 : results.length - 1;
                if (results[currentIndex]) this._highlightOption(results[currentIndex]);
                break;
            case "Enter":
                e.preventDefault();
                if (currentIndex > -1 && results[currentIndex]) {
                    const optionId = results[currentIndex].dataset.id;
                    const selectedOption = this._allOptions.find((opt) => String(opt.id) === optionId);
                    if (selectedOption) {
                        this._selectOption(selectedOption);
                    }
                } else if (this.$searchInput.value && results.length > 0 && this._multiple === false) {
                    // If single select, text in input, and results exist, select first result on Enter
                    // This is a common UX pattern for autocompletes that behave like selects
                    const firstOptionId = results[0].dataset.id;
                    const firstSelectedOption = this._allOptions.find((opt) => String(opt.id) === firstOptionId);
                    if (firstSelectedOption) {
                        this._selectOption(firstSelectedOption);
                    }
                }
                break;
            case "Escape":
                e.preventDefault();
                this._toggleDropdown(false);
                this.$selectDisplay.focus(); // Return focus to the main display element
                break;
            case "Tab":
                // Allow tab to move focus out. If dropdown is open, close it.
                if (this._dropdownOpen) {
                    this._toggleDropdown(false);
                }
                // Default tab behavior will then move to the next focusable element.
                break;
        }
    }

    _onSearchInputFocus() {
        // Ensure the dropdown opens when the search input is focused.
        // This is important if the user tabs into the search field or clicks it directly.
        if (!this._dropdownOpen) {
            this._toggleDropdown(true);
        }
    }

    _onSearchInputBlur() {
        console.log("[ModernSelect] Search input blur triggered.");
        if (this._blurTimeout) {
            clearTimeout(this._blurTimeout);
        }
        this._blurTimeout = setTimeout(() => {
            const activeInShadow = this.shadowRoot.activeElement;
            console.log("[ModernSelect] Inside blur setTimeout. Shadow activeElement:", activeInShadow);
            if (activeInShadow) {
                console.log("[ModernSelect] Shadow activeElement tagName:", activeInShadow.tagName, "id:", activeInShadow.id, "className:", activeInShadow.className);
            }

            const isSearchInputFocused = activeInShadow === this.$searchInput;
            // Ensure activeInShadow is not null before calling .contains
            const isResultsListFocused = this.$resultsList && activeInShadow && this.$resultsList.contains(activeInShadow);
            const isSelectDisplayFocused = activeInShadow === this.$selectDisplay;

            const isFocusStillInsideComponentInteractiveElements = isSearchInputFocused || isResultsListFocused || isSelectDisplayFocused;

            // New detailed log block before the if condition
            console.log(`[ModernSelect] BLUR DECISION: activeInShadow:`, activeInShadow ? activeInShadow.outerHTML.substring(0, 100) + '...' : null);
            console.log(`[ModernSelect] BLUR DECISION: isSearchInputFocused: ${isSearchInputFocused}`);
            console.log(`[ModernSelect] BLUR DECISION: isResultsListFocused: ${isResultsListFocused} (target: $resultsList, active: ${activeInShadow ? activeInShadow.tagName : 'null'})`);
            console.log(`[ModernSelect] BLUR DECISION: isSelectDisplayFocused: ${isSelectDisplayFocused}`);
            console.log(`[ModernSelect] BLUR DECISION: isFocusStillInsideComponentInteractiveElements: ${isFocusStillInsideComponentInteractiveElements}`);
            console.log(`[ModernSelect] BLUR DECISION: _dropdownOpen: ${this._dropdownOpen}`);

            if (!isFocusStillInsideComponentInteractiveElements && this._dropdownOpen) {
                console.log("[ModernSelect] BLUR DECISION: Closing dropdown.");
                this._toggleDropdown(false);
            } else {
                console.log("[ModernSelect] BLUR DECISION: Not closing dropdown. Reason:", 
                    isFocusStillInsideComponentInteractiveElements ? "Focus inside" : "Dropdown not open or other");
            }
        }, 150); // Small delay (e.g., 150ms) - adjust as needed
    }

    _highlightOption(optionElement) {
        if (!optionElement) {
            this._updateActiveDescendant(null);
            return;
        }
        // Remove 'active' class from any currently active option
        const currentActive = this.shadowRoot.querySelector(".result.active");
        if (currentActive) currentActive.classList.remove("active");

        optionElement.classList.add("active");
        optionElement.scrollIntoView({ block: "nearest" });
        this._updateActiveDescendant(optionElement.id);
    }

    _onDocumentClick(e) {
        console.log("[ModernSelect] _onDocumentClick triggered. Event target:", e.target);
        const path = e.composedPath();
        const isClickInsideComponent = path.includes(this);

        console.log(`[ModernSelect] _onDocumentClick: isClickInsideComponent = ${isClickInsideComponent}, _dropdownOpen = ${this._dropdownOpen}`);
        if (!isClickInsideComponent && this._dropdownOpen) {
            console.log("[ModernSelect] Click was outside component, closing dropdown.");
            this._toggleDropdown(false);
        } else {
            console.log("[ModernSelect] Click was inside component or dropdown not open, not closing.");
        }
    }

    // --- Public API ---

    /**
     * Gets the selected option(s).
     * @returns {object|object[]|null} The selected option object or an array of option objects if multiple, null if none.
     */
    get selected() {
        return this._multiple ? [...this._selectedItems] : this._selectedItems[0] || null;
    }

    /**
     * Sets the selected option(s).
     * @param {any|any[]} value - The value(s) to set. Can be a single value or an array for multiple select.
     *                            The component will try to match against option.value, option.id, or option.name.
     */
    set selected(value) {
        const newSelectedItems = [];
        const valuesToSelect = Array.isArray(value) ? value : value !== null && typeof value !== "undefined" ? [value] : [];

        if (this._multiple) {
            valuesToSelect.forEach((val) => {
                const option = this._allOptions.find((opt) => (opt.value !== undefined && String(opt.value) === String(val)) || (opt.id !== undefined && String(opt.id) === String(val)) || (opt.name !== undefined && String(opt.name) === String(val)));
                if (option && !newSelectedItems.find((si) => si.id === option.id)) {
                    newSelectedItems.push(option);
                }
            });
            this._selectedItems = newSelectedItems;
        } else {
            // Single select
            if (valuesToSelect.length > 0) {
                const valToSelect = valuesToSelect[0];
                const option = this._allOptions.find((opt) => (opt.value !== undefined && String(opt.value) === String(valToSelect)) || (opt.id !== undefined && String(opt.id) === String(valToSelect)) || (opt.name !== undefined && String(opt.name) === String(valToSelect)));
                this._selectedItems = option ? [option] : [];
            } else {
                this._selectedItems = [];
            }
        }
        this._renderSelectedItemsList(); // Update UI for multiple selections tag list
        this._updateDisplayText(); // Update main display text
        // Note: Programmatic changes typically don't dispatch 'change' events unless desired.
        // If needed, a 'modern-select-programmatic-change' could be dispatched or an option added.
    }

    /**
     * Clears all selected items and updates the display.
     */
    clearSelection() {
        this._selectedItems = [];
        this._renderSelectedItemsList();
        this._updateDisplayText();
        this.dispatchEvent(
            new CustomEvent("modern-select-change", {
                detail: { selected: this._multiple ? [] : null },
                bubbles: true,
                composed: true
            })
        );
    }

    /**
     * Programmatically sets the value of the component by finding matching options.
     * For single select, `value` is a single id/value/name.
     * For multiple select, `value` can be an array of ids/values/names.
     */
    setValue(value) {
        this.selected = value; // Uses the 'selected' setter
    }

    /**
     * Returns the current value of the component.
     * For single select, it's the `value` property of the selected option.
     * For multiple select, it's an array of `value` properties from selected options.
     * @returns {any|any[]|null} The value or array of values, null if no selection or no value property.
     */
    getValue() {
        if (this._multiple) {
            return this._selectedItems.map((item) => (item.value !== undefined ? item.value : item.id));
        } else {
            return this._selectedItems.length > 0 && this._selectedItems[0].value !== undefined ? this._selectedItems[0].value : this._selectedItems.length > 0 ? this._selectedItems[0].id : null;
        }
    }
}

customElements.define("modern-select", ModernSelect);

export { ModernSelect };
