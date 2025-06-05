class Toolbar {
    options = {};
    buttons = [];

    constructor(options = {}) {
        this.options = options;
        this.buttons = options.buttons || [];
    }

    createToolbar() {
        const container = document.createElement("div");
        container.className = "bootstrap d-flex flex-wrap justify-content-between align-items-center w-100";

        const toolbar = document.createElement("div");
        toolbar.className = "d-flex justify-content-start flex-wrap gap-2 align-items-center";

        const btnGroup = document.createElement("div");
        btnGroup.className = "btn-group";
        for (const button of this.buttons) {
            btnGroup.appendChild(this.createButton(button));
        }
        toolbar.appendChild(btnGroup);

        const searchBar = document.createElement("form");
        searchBar.id = "toolbar-search-form";
        searchBar.className = "d-flex justify-content-end ms-auto mt-2 mt-md-0";
        searchBar.style.maxWidth = "500px";

        const searchInput = document.createElement("input");
        searchInput.id = "toolbar-search-input";
        searchInput.className = "form-control me-2";
        searchInput.type = "text";
        searchInput.placeholder = "Cerca...";
        searchBar.appendChild(searchInput);

        const searchButton = document.createElement("button");
        searchButton.id = "toolbar-search-button";
        searchButton.className = "btn btn-outline-primary";
        searchButton.type = "submit";
        searchButton.textContent = "Cerca";
        searchBar.appendChild(searchButton);

        container.appendChild(toolbar);
        container.appendChild(searchBar);
        return container;
    }

    createButton(buttonParams) {
        const button = document.createElement("button");
        button.id =
            buttonParams.id ||
            // eslint-disable-next-line no-bitwise
            Array.from({ length: 16 }, () => Math.floor(Math.random() * 16).toString(16)).join("");
        button.className = buttonParams.className;
        button.type = buttonParams.type || "button";
        if (buttonParams.icon) {
            const icon = document.createElement("i");
            icon.className = "material-icons";
            icon.textContent = buttonParams.icon;
            button.appendChild(icon);
        }
        if (buttonParams.label) {
            const span = document.createElement("span");
            span.className = "ml-2";
            span.textContent = buttonParams.label;
            button.appendChild(span);
        }

        if (buttonParams.action) {
            button.addEventListener("click", buttonParams.action);
        }

        return button;
    }

    init() {
        const toolbar = this.createToolbar();
        if (this.options.parent) {
            this.options.parent.appendChild(toolbar);
        }
    }
}

export default Toolbar;
