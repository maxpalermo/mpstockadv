class ProductAutocomplete extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: 'open' });
        this._results = [];
        this._selected = null;
        this._dropdownOpen = false;
        this._fetchController = null;
        this._maxResults = parseInt(this.getAttribute('max-results')) || 10;
        this._placeholder = this.getAttribute('placeholder') || 'Cerca...';
        this._endpoint = this.getAttribute('endpoint') || '';
        this._minLength = parseInt(this.getAttribute('min-length')) || 2;
        this._multiple = this.hasAttribute('multiple');
        this.shadowRoot.innerHTML = `
            <style>
                :host {
                    display: inline-block;
                    position: relative;
                    font-family: 'Segoe UI', Arial, sans-serif;
                    min-width: 320px;
                }
                .autocomplete {
                    width: 100%;
                    position: relative;
                }
                .input {
                    width: 100%;
                    padding: 12px 16px;
                    border-radius: 10px;
                    border: 1.5px solid #d1d5db;
                    font-size: 1.08rem;
                    outline: none;
                    transition: border-color 0.18s;
                    background: #fff;
                }
                .input:focus {
                    border-color: #4f8cff;
                }
                .dropdown {
                    position: absolute;
                    left: 0; right: 0;
                    top: calc(100% + 3px);
                    background: #fff;
                    border-radius: 10px;
                    box-shadow: 0 8px 24px rgba(0,0,0,0.13);
                    z-index: 10;
                    margin-top: 3px;
                    max-height: 260px;
                    overflow-y: auto;
                    animation: fadeIn 0.18s;
                }
                @keyframes fadeIn {
                    from { opacity: 0; transform: translateY(-8px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .result {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    padding: 10px 16px;
                    cursor: pointer;
                    transition: background 0.13s;
                    border-bottom: 1px solid #f3f4f6;
                }
                .result:last-child {
                    border-bottom: none;
                }
                .result:hover, .result.active {
                    background: #e0e7ff;
                }
                .result-img {
                    width: 42px; height: 42px;
                    border-radius: 7px;
                    object-fit: cover;
                    background: #f1f5f9;
                    border: 1px solid #e5e7eb;
                }
                .result-labels {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                }
                .label-main {
                    font-size: 1.05rem;
                    font-weight: 600;
                    color: #222b45;
                }
                .label-secondary {
                    font-size: 0.98rem;
                    color: #64748b;
                }
                .label-price {
                    font-size: 1.02rem;
                    color: #22c55e;
                    font-weight: 600;
                }
                .no-results {
                    padding: 14px;
                    color: #b91c1c;
                    text-align: center;
                }
                .selected-list {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 6px;
                    margin-bottom: 6px;
                }
                .selected-item {
                    background: #f1f5f9;
                    border-radius: 6px;
                    padding: 5px 12px 5px 8px;
                    display: flex;
                    align-items: center;
                    gap: 4px;
                    font-size: 0.95rem;
                }
                .remove-btn {
                    background: none;
                    border: none;
                    color: #e53e3e;
                    font-size: 1.1rem;
                    cursor: pointer;
                    margin-left: 2px;
                }
            </style>
            <div class="autocomplete">
                <div class="selected-list" style="display:none"></div>
                <input class="input" type="text" placeholder="${this._placeholder}" autocomplete="off" />
                <div class="dropdown" style="display:none"></div>
            </div>
        `;
        this.$input = this.shadowRoot.querySelector('.input');
        this.$dropdown = this.shadowRoot.querySelector('.dropdown');
        this.$selectedList = this.shadowRoot.querySelector('.selected-list');
        this._selectedItems = [];
        this._bindEvents();
    }

    _bindEvents() {
        this.$input.addEventListener('input', e => this._onInput(e));
        this.$input.addEventListener('focus', () => this._onFocus());
        this.$input.addEventListener('keydown', e => this._onKeyDown(e));
        document.addEventListener('click', e => this._onDocumentClick(e));
        this.$dropdown.addEventListener('mousedown', e => e.preventDefault());
    }

    _onInput(e) {
        const val = this.$input.value.trim();
        if (val.length < this._minLength) {
            this._hideDropdown();
            return;
        }
        this._fetchResults(val);
    }

    _onFocus() {
        if (this.$input.value.trim().length >= this._minLength) {
            this._fetchResults(this.$input.value.trim());
        }
    }

    _onKeyDown(e) {
        if (!this._dropdownOpen) return;
        const results = Array.from(this.$dropdown.querySelectorAll('.result'));
        let idx = results.findIndex(r => r.classList.contains('active'));
        if (e.key === 'ArrowDown') {
            if (idx < results.length - 1) idx++;
            else idx = 0;
            results.forEach(r => r.classList.remove('active'));
            if (results[idx]) results[idx].classList.add('active');
            e.preventDefault();
        } else if (e.key === 'ArrowUp') {
            if (idx > 0) idx--;
            else idx = results.length - 1;
            results.forEach(r => r.classList.remove('active'));
            if (results[idx]) results[idx].classList.add('active');
            e.preventDefault();
        } else if (e.key === 'Enter') {
            if (results[idx]) {
                results[idx].click();
                e.preventDefault();
            }
        } else if (e.key === 'Escape') {
            this._hideDropdown();
        }
    }

    _onDocumentClick(e) {
        if (!this.contains(e.target) && !this.shadowRoot.contains(e.target)) {
            this._hideDropdown();
        }
    }

    _fetchResults(query) {
        if (!this._endpoint) return;
        if (this._fetchController) this._fetchController.abort();
        this._fetchController = new AbortController();
        fetch(`${this._endpoint}?q=${encodeURIComponent(query)}`, { signal: this._fetchController.signal })
            .then(r => r.json())
            .then(data => {
                if (!Array.isArray(data)) data = [];
                this._results = data.slice(0, this._maxResults); // Store raw results
                this._displayDropdownResults();
            })
            .catch(() => {
                this._results = [];
                this._displayDropdownResults();
            });
    }

    _displayDropdownResults() {
        this.$dropdown.innerHTML = '';
        let displayableResults = this._results;

        if (this._multiple) {
            const selectedIds = this._selectedItems.map(si => si.id);
            displayableResults = this._results.filter(r => !selectedIds.includes(r.id));
        }

        if (!displayableResults.length) {
            const message = this._results.length > 0 && this._multiple ? 'Tutti i risultati corrispondenti sono già selezionati' : 'Nessun risultato';
            this.$dropdown.innerHTML = `<div class="no-results">${message}</div>`;
            this.$dropdown.style.display = 'block';
            this._dropdownOpen = true;
            return;
        }

        displayableResults.forEach(item => {
            const row = document.createElement('div');
            row.className = 'result';
            row.tabIndex = 0;
            row.innerHTML = this._renderResult(item);
            row.addEventListener('click', () => this._selectItem(item));
            this.$dropdown.appendChild(row);
        });
        this.$dropdown.style.display = 'block';
        this._dropdownOpen = true;
    }

    _hideDropdown() {
        this.$dropdown.style.display = 'none';
        this._dropdownOpen = false;
    }

    _renderResult(item) {
        // item: {id, label: string|array}
        if (typeof item.label === 'string') {
            return `<div class="label-main">${item.label}</div>`;
        }
        if (Array.isArray(item.label)) {
            let img = item.label.find(l => l.type === 'image');
            let name = item.label.find(l => !l.type || l.type === 'name');
            let price = item.label.find(l => l.type === 'price');
            // Filter out hidden types for rendering, and also img, name, price as they are handled separately
            let others = item.label.filter(l => l.type !== 'hidden' && l !== img && l !== name && l !== price);
            return `
                ${img ? `<img class="result-img" src="${img.src}" alt="">` : ''}
                <div class="result-labels">
                    ${name ? `<div class="label-main">${name.value}</div>` : ''}
                    ${others.map(o => `<div class="label-secondary">${o.value}</div>`).join('')}
                    ${price ? `<div class="label-price">${price.value}€</div>` : ''}
                </div>
            `;
        }
        return `<div class="label-main">${item.id}</div>`;
    }

    _selectItem(item) {
        if (this._multiple) {
            if (!this._selectedItems.find(si => si.id === item.id)) {
                this._selectedItems.push(item);
            }
            this._renderSelectedList();
            this._displayDropdownResults(); // Refresh dropdown to remove selected item
            this.$input.value = ''; // Clear input after selection in multiple mode
        } else {
            this._selectedItems = [item];
            this.$input.value = this._getLabelString(item);
            this._hideDropdown();
        }
        this.dispatchEvent(new CustomEvent('selected', { detail: this._multiple ? this._selectedItems : item }));
    }

    _renderSelectedList() {
        if (!this._multiple) return;
        this.$selectedList.innerHTML = '';
        if (!this._selectedItems.length) {
            this.$selectedList.style.display = 'none';
            return;
        }
        this.$selectedList.style.display = 'flex';
        this._selectedItems.forEach(item => {
            const el = document.createElement('div');
            el.className = 'selected-item';
            el.innerHTML = `${this._getLabelString(item)} <button class="remove-btn" title="Rimuovi">&times;</button>`;
            el.querySelector('.remove-btn').addEventListener('click', () => {
                this._selectedItems = this._selectedItems.filter(si => si.id !== item.id);
                this._renderSelectedList();
                this._displayDropdownResults(); // Refresh dropdown to potentially re-add item
                this.dispatchEvent(new CustomEvent('deselected', { detail: item }));
                this.dispatchEvent(new CustomEvent('selected', { detail: this._selectedItems })); // Also dispatch current full selection
            });
            this.$selectedList.appendChild(el);
        });
    }

    _getLabelString(item) {
        if (typeof item.label === 'string') return item.label;
        if (Array.isArray(item.label)) {
            // Find the primary display label, excluding hidden ones
            let name = item.label.find(l => l.type !== 'hidden' && (!l.type || l.type === 'name'));
            return name ? name.value : item.id.toString(); // Ensure string conversion for ID if no name
        }
        return item.id.toString(); // Ensure string conversion for ID
    }

    // API access
    get value() {
        return this._multiple ? this._selectedItems : this._selectedItems[0] || null;
    }
    set value(val) {
        if (this._multiple && Array.isArray(val)) {
            this._selectedItems = val;
            this._renderSelectedList();
        } else if (!this._multiple && val) {
            this._selectedItems = [val];
            this.$input.value = this._getLabelString(val);
        }
    }
    get selected() {
        return this.value;
    }
    reset() {
        this._selectedItems = [];
        this.$input.value = '';
        this._renderSelectedList();
    }
}

customElements.define('product-autocomplete', ProductAutocomplete);

export { ProductAutocomplete };
