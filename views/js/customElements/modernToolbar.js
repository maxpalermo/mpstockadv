class ModernToolbar extends HTMLElement {
    constructor() {
        super();
        this.attachShadow({ mode: "open" });
        this._menuData = [];
        this._actionButtonsData = [];
        this._logoSrc = "";
    }

    static get observedAttributes() {
        return ["logo-src"];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (name === "logo-src" && oldValue !== newValue) {
            this._logoSrc = newValue;
            this._render();
        }
    }

    connectedCallback() {
        this._render();
    }

    setMenuData(menuData) {
        this._menuData = menuData || [];
        this._render();
    }

    setActionButtonsData(actionButtonsData) {
        this._actionButtonsData = actionButtonsData || [];
        this._render();
    }

    _render() {
        this.shadowRoot.innerHTML = `
            <style>
                @import url('https://fonts.googleapis.com/icon?family=Material+Icons');
                :host {
                    display: block;
                    background-color: #ffffff;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    font-family: 'Segoe UI', Arial, sans-serif;
                    color: #333;
                }
                .toolbar-container {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 0 16px;
                    height: 60px;
                }
                .logo-area {
                    height: 100%;
                    display: flex;
                    align-items: center;
                }
                .logo-area img {
                    height: 60%;
                    max-height: 40px;
                    object-fit: contain;
                }
                .navigation-area {
                    flex-grow: 1;
                    display: flex;
                    align-items: center;
                    height: 100%;
                    margin-left: 24px;
                }
                .menu-container {
                    display: flex;
                    list-style: none;
                    margin: 0;
                    padding: 0;
                    height: 100%;
                }
                .menu-item {
                    position: relative;
                    display: flex;
                    align-items: center;
                    padding: 0 15px; /* Adjusted for better spacing */
                    cursor: pointer;
                    height: 100%;
                    transition: background-color 0.2s ease, color 0.2s ease;
                    border-bottom: 3px solid transparent; /* For active/hover indication */
                    color: #555; /* Default menu item color */
                }
                .menu-item:hover {
                    background-color: #f7f7f7; /* Lighter hover for menu */
                    color: #007bff;
                    border-bottom-color: #007bff;
                }
                .menu-item.active { /* Example for active state */
                    color: #007bff;
                    border-bottom-color: #007bff;
                }
                .menu-item a, .menu-item > span:not(.material-icons):not(.submenu-toggle) { /* Ensure span also behaves like link */
                    text-decoration: none;
                    color: inherit; /* Inherits from .menu-item */
                    display: flex;
                    align-items: center;
                    height: 100%;
                    width: 100%;
                }
                .menu-item .material-icons {
                    margin-right: 8px;
                    font-size: 20px;
                    /* color will be inherited or can be set specifically */
                }
                .submenu-toggle::after {
                    content: 'arrow_drop_down'; /* Default for top-level menu items */
                    font-family: 'Material Icons';
                    font-size: 20px;
                    margin-left: 4px;
                    transition: transform 0.2s ease;
                }
                .menu-item:hover > span > .submenu-toggle::after {
                    transform: rotate(180deg);
                }
                /* Style for toggle on items already in a submenu (for side-opening submenus) */
                .submenu .menu-item.has-submenu > span > .submenu-toggle::after,
                .submenu .menu-item.has-submenu > a > .submenu-toggle::after {
                    content: 'arrow_right';
                    transform: rotate(0deg); /* Reset rotation */
                }
                .submenu .menu-item.has-submenu:hover > span > .submenu-toggle::after,
                .submenu .menu-item.has-submenu:hover > a > .submenu-toggle::after {
                    transform: rotate(0deg); /* No rotation for side arrows on hover */
                }

                .submenu {
                    display: none;
                    position: absolute;
                    top: 100%; /* Default for first-level dropdown */
                    left: 0;
                    background-color: #ffffff;
                    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                    list-style: none;
                    padding: 0;
                    margin: 0;
                    min-width: 200px;
                    z-index: 1000;
                }

                /* Positioning for nested submenus (side opening) */
                .submenu .menu-item > .submenu {
                    top: 0;
                    left: 100%;
                    margin-top: -1px; /* Adjust to align with parent border if any, or just for tighter fit */
                }

                .menu-item:hover > .submenu,
                .menu-item > .submenu:hover { /* Keep submenu open if hovering over it */
                    display: block;
                }

                .submenu .menu-item {
                    width: 100%;
                    box-sizing: border-box; /* Ensure padding doesn't expand beyond 100% width */
                    height: auto; /* adjust height for submenu items */
                    padding: 12px 18px; /* More padding for submenu items */
                    border-bottom: none; /* No bottom border for submenu items */
                    color: #333; /* Submenu item color */
                }
                .submenu .menu-item:hover {
                    background-color: #e9e9e9;
                    color: #007bff;
                }
                /* Ensure icons in submenu items are also spaced correctly */
                .submenu .menu-item .material-icons {
                    margin-right: 10px; 
                    font-size: 18px;
                }

                .action-buttons-container {
                    display: flex;
                    align-items: center;
                    margin-left: 20px;
                    height: 100%;
                }
                .action-button {
                    display: flex;
                    align-items: center;
                    padding: 7px 15px; /* Slightly adjusted padding */
                    margin-left: 10px; /* Increased margin */
                    border-radius: 5px;
                    cursor: pointer;
                    font-size: 0.9rem;
                    transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
                    border: 1px solid #ccc; /* Default border */
                    background-color: #f9f9f9; /* Light background */
                    color: #333;
                }
                .action-button .material-icons {
                    margin-right: 6px;
                    font-size: 18px;
                }
                .action-button:hover {
                    background-color: #007bff;
                    border-color: #007bff;
                    color: white;
                }
                /* Style for action buttons with custom background/color from JSON */
                .action-button[style*="background-color"]:hover {
                    filter: brightness(110%); /* Make custom colored buttons slightly brighter on hover */
                }


                .search-area {
                    display: flex;
                    align-items: center;
                    margin-left: 24px;
                }
                .search-area input[type='search'] {
                    padding: 8px 12px;
                    border: 1px solid #ccc;
                    border-radius: 4px 0 0 4px;
                    font-size: 0.9rem;
                    outline: none;
                }
                .search-area input[type='search']:focus {
                    border-color: #007bff;
                }
                .search-area button {
                    padding: 8px 12px;
                    border: 1px solid #007bff;
                    background-color: #007bff;
                    color: white;
                    border-radius: 0 4px 4px 0;
                    cursor: pointer;
                    font-size: 0.9rem;
                    margin-left: -1px; /* Overlap borders */
                    display: flex;
                    align-items: center;
                }
                 .search-area button .material-icons {
                    font-size: 18px;
                }
                .search-area button:hover {
                    background-color: #0056b3;
                }
            </style>
            
            <div class="toolbar-container">
                <div class="logo-area">
                    ${this._logoSrc ? `<img src="${this._logoSrc}" alt="Logo">` : ""}
                </div>
                <div class="navigation-area">
                    <ul class="menu-container">
                        ${this._renderMenuItems(this._menuData)}
                    </ul>
                    <div class="action-buttons-container">
                        ${this._renderActionButtons(this._actionButtonsData)}
                    </div>
                </div>
                <div class="search-area">
                    <input type="search" placeholder="Cerca...">
                    <button title="Cerca"><span class="material-icons">search</span></button>
                </div>
            </div>
        `;

        this._attachEventListeners();
    }

    _renderMenuItems(items, isSubmenu = false) {
        if (!items) return "";
        return items
            .map(
                (item) => `
            <li class="menu-item ${item.children && item.children.length > 0 ? "has-submenu" : ""}"
                style="${item.background ? `background-color:${item.background};` : ""} ${item.color ? `color:${item.color};` : ""}"
                data-item='${JSON.stringify(item)}'>
                ${item.href ? `<a href="${item.href}">${item.icon ? `<span class="material-icons">${item.icon}</span>` : ""}${item.label}${item.children && item.children.length > 0 ? '<span class="submenu-toggle"></span>' : ""}</a>` : `<span>${item.icon ? `<span class="material-icons">${item.icon}</span>` : ""}${item.label}${item.children && item.children.length > 0 ? '<span class="submenu-toggle"></span>' : ""}</span>`}
                ${item.children && item.children.length > 0 ? `<ul class="submenu">${this._renderMenuItems(item.children, true)}</ul>` : ""}
            </li>
        `
            )
            .join("");
    }

    _renderActionButtons(buttons) {
        if (!buttons) return "";
        return buttons
            .map(
                (button) => `
            <div class="action-button"
                 style="${button.background ? `background-color:${button.background};` : ""} ${button.color ? `color:${button.color};` : ""}"
                 data-button='${JSON.stringify(button)}'>
                ${button.icon ? `<span class="material-icons">${button.icon}</span>` : ""}
                ${button.label}
            </div>
        `
            )
            .join("");
    }

    _attachEventListeners() {
        this.shadowRoot.querySelectorAll(".menu-item").forEach((menuItemElement) => {
            // Prevent click on parent if it's just a container for submenu and has no href/direct action
            const itemData = JSON.parse(menuItemElement.dataset.item);
            menuItemElement.addEventListener("click", (event) => {
                event.stopPropagation(); // Prevent multiple events if nested
                this.dispatchEvent(
                    new CustomEvent("menu-item-click", {
                        detail: itemData,
                        bubbles: true,
                        composed: true
                    })
                );
            });
        });

        this.shadowRoot.querySelectorAll(".action-button").forEach((actionButtonElement) => {
            actionButtonElement.addEventListener("click", (event) => {
                this.dispatchEvent(
                    new CustomEvent("action-button-click", {
                        detail: JSON.parse(actionButtonElement.dataset.button),
                        bubbles: true,
                        composed: true
                    })
                );
            });
        });

        const searchInput = this.shadowRoot.querySelector('.search-area input[type="search"]');
        const searchButton = this.shadowRoot.querySelector(".search-area button");

        const performSearch = () => {
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                this.dispatchEvent(
                    new CustomEvent("toolbar-search", {
                        detail: { query: searchTerm },
                        bubbles: true,
                        composed: true
                    })
                );
            }
        };

        searchButton.addEventListener("click", performSearch);
        searchInput.addEventListener("keypress", (event) => {
            if (event.key === "Enter") {
                performSearch();
            }
        });
    }
}

customElements.define("modern-toolbar", ModernToolbar);

export class ModernToolbarManager {
    constructor({ targetElement, logoSrc, menuItems, actionButtons } = {}) {
        if (!targetElement) {
            console.error("ModernToolbarManager: targetElement is required.");
            return;
        }
        this.toolbar = document.createElement("modern-toolbar");
        if (logoSrc) {
            this.toolbar.setAttribute("logo-src", logoSrc);
        }
        if (menuItems) {
            this.toolbar.setMenuData(menuItems);
        }
        if (actionButtons) {
            this.toolbar.setActionButtonsData(actionButtons);
        }

        const target = typeof targetElement === "string" ? document.querySelector(targetElement) : targetElement;
        if (target) {
            target.appendChild(this.toolbar);
        } else {
            console.error("ModernToolbarManager: targetElement not found.");
        }
    }

    getToolbarInstance() {
        return this.toolbar;
    }
}
