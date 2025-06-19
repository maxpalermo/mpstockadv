class ModernSwitch extends HTMLElement {
    constructor() {
        super();
        
        // Definizione dei colori per gli stili predefiniti
        this.styleColors = {
            success: '#4CAF50',
            info: '#2196F3',
            warning: '#FFC107',
            error: '#F44336'
        };
        this.attachShadow({ mode: 'open' });
        
        // Stato iniziale dello switch
        this._checked = false;
        
        // Creazione della struttura base
        const style = document.createElement('style');
        style.textContent = `
            :host {
                display: inline-block;
                cursor: pointer;
            }

            .container {
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .container[data-position='top'] {
                flex-direction: column-reverse;
            }

            .container[data-position='bottom'] {
                flex-direction: column;
            }

            .container[data-position='left'] {
                flex-direction: row-reverse;
            }

            .container[data-position='right'] {
                flex-direction: row;
            }

            .label {
                font-family: system-ui, -apple-system, sans-serif;
                font-size: 14px;
                transition: color 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .switch {
                width: 60px;
                height: 34px;
                background-color: #ccc;
                border-radius: 34px;
                position: relative;
                transition: background-color 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            
            .switch.checked {
                background-color: var(--switch-color, #2196F3);
            }
            
            .knob {
                width: 26px;
                height: 26px;
                background-color: white;
                border-radius: 50%;
                position: absolute;
                top: 4px;
                left: 4px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                will-change: transform;
            }
            
            .switch.checked .knob {
                transform: translateX(26px);
            }
            
            .switch:active .knob {
                transform: scale(0.9);
            }

            .switch.checked:active .knob {
                transform: translateX(26px) scale(0.9);
            }
            
            :host([disabled]) {
                opacity: 0.6;
                cursor: not-allowed;
            }
        `;

        this.containerElement = document.createElement('div');
        this.containerElement.className = 'container';

        this.labelElement = document.createElement('div');
        this.labelElement.className = 'label';

        this.switchElement = document.createElement('div');
        this.switchElement.className = 'switch';
        
        this.knobElement = document.createElement('div');
        this.knobElement.className = 'knob';
        
        this.switchElement.appendChild(this.knobElement);
        this.containerElement.appendChild(this.switchElement);
        this.containerElement.appendChild(this.labelElement);
        
        this.shadowRoot.appendChild(style);
        this.shadowRoot.appendChild(this.containerElement);
        
        this.updateState();
    }
    
    static get observedAttributes() {
        return ['checked', 'disabled', 'data-label-on', 'data-label-off', 'data-color-on', 'data-color-off', 'data-label-position', 'data-style']
    }
    
    get checked() {
        return this._checked;
    }

    getValue() {
        return this._checked ? 1 : 0;
    }

    setState(value) {
        // Converti il valore in booleano
        const newState = value === 1 || value === true || value === 'true' || value === '1';
        if (this._checked !== newState) {
            this._checked = newState;
            this.updateState();
            this.dispatchEvent(new CustomEvent('change', {
                detail: { checked: this._checked },
                bubbles: true,
                composed: true
            }));
        }
        return this;
    }
    
    set checked(value) {
        this._checked = value;
        this.updateState();
    }
    
    updateState() {
        // Gestione dello stile dello switch
        const style = this.getAttribute('data-style');
        if (style) {
            const color = this.styleColors[style] || style;
            this.switchElement.style.setProperty('--switch-color', color);
        } else {
            this.switchElement.style.removeProperty('--switch-color');
        }

        if (this._checked) {
            this.switchElement.classList.add('checked');
            const labelOn = this.getAttribute('data-label-on');
            const colorOn = this.getAttribute('data-color-on');
            if (labelOn) {
                this.labelElement.textContent = labelOn;
                this.labelElement.style.color = colorOn || '';
            }
            // chiama l'evento change
            this.dispatchEvent(new CustomEvent('change', {
                detail: { checked: this._checked },
                bubbles: true,
                composed: true
            }));
        } else {
            this.switchElement.classList.remove('checked');
            const labelOff = this.getAttribute('data-label-off');
            const colorOff = this.getAttribute('data-color-off');
            if (labelOff) {
                this.labelElement.textContent = labelOff;
                this.labelElement.style.color = colorOff || '';
            }
            // chiama l'evento change
            this.dispatchEvent(new CustomEvent('change', {
                detail: { checked: this._checked },
                bubbles: true,
                composed: true
            }));
        }

        // Aggiorna la posizione della label
        const position = this.getAttribute('data-label-position') || 'right';
        this.containerElement.setAttribute('data-position', position);
    }
    
    connectedCallback() {
        // Binding corretto del metodo
        this._handleClick = this._handleClick.bind(this);
        this.addEventListener('click', this._handleClick);
        console.log('Switch connected, adding click listener');
    }
    
    disconnectedCallback() {
        this.removeEventListener('click', this._handleClick);
    }
    
    _handleClick(event) {
        if (!this.hasAttribute('disabled')) {
            this._checked = !this._checked;
            this.updateState();
            // Emetti l'evento change
            const changeEvent = new CustomEvent('change', {
                detail: { checked: this._checked },
                bubbles: true,
                composed: true
            });
            this.dispatchEvent(changeEvent);
            console.log('Switch clicked, new state:', this._checked);
        }
    }
    
    attributeChangedCallback(name, oldValue, newValue) {
        if (name === 'checked') {
            this._checked = newValue !== null;
            this.updateState();
        } else if (name.startsWith('data-')) {
            this.updateState();
        }
    }
}

customElements.define('modern-switch', ModernSwitch);
