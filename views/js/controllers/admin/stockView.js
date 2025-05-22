// Fix per toggle combinazioni su righe tabella
// Gestione AJAX per impostare la combinazione di default
function showFlashMsg(type, msg) {
    const alert = document.createElement("div");
    alert.className = `alert alert-${type} alert-dismissible fade show my-2`;
    alert.role = "alert";
    alert.innerHTML = msg + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    document.querySelector(".card-header").prepend(alert);
    setTimeout(() => alert.classList.remove("show"), 3500);
}

function setupDefaultBtns() {
    document.querySelectorAll(".set-default-btn").forEach((btn) => {
        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const btn = e.target.closest("button");
            const id = btn.dataset.id;
            const url = window.pathSetDefaultCombination.replace("ID_REPLACE", id);
            const oldDefaultBtn = btn.closest("tbody").querySelector(".isDefaultComboBtn");

            if (!btn) {
                popupMessage("warning", "Elemento non trovato");
                return false;
            }

            if (!oldDefaultBtn) {
                popupMessage("warning", "Elemento non trovato");
                return false;
            }

            //cambio icona con spinner
            btn.innerHTML = '<i class="material-icons spin">autorenew</i>';
            btn.disabled = true;

            try {
                const response = await fetch(url, {
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json"
                    }
                });
                const data = await response.json();
                if (data.success) {
                    // Scambia le icone
                    const iOld = oldDefaultBtn.querySelector("i");
                    iOld.innerHTML = '<i class="material-icons text-secondary" style="cursor:pointer;">radio_button_unchecked</i>';
                    oldDefaultBtn.disabled = false;
                    oldDefaultBtn.classList.remove("isDefaultComboBtn");
                    oldDefaultBtn.classList.add("set-default-btn");

                    const iNew = btn.querySelector("i");
                    iNew.innerHTML = '<i class="material-icons text-success" title="Quantità di default">check_circle</i>';
                    iNew.classList.remove("spin");
                    btn.disabled = true;
                    btn.classList.remove("set-default-btn");
                    btn.classList.add("isDefaultComboBtn");

                    popupMessage("success", data.message);
                } else {
                    popupMessage("danger", data.message);
                }
            } catch (error) {
                popupMessage(
                    "danger",
                    `
                    <div>
                        <h4>Errore di comunicazione con il server.</h4>
                        <p>${error.message}</p>
                    </div>
                    `
                );
                const iOld = oldDefaultBtn.querySelector("i");
                iOld.innerHTML = '<i class="material-icons text-secondary" style="cursor:pointer;">radio_button_unchecked</i>';
                oldDefaultBtn.disabled = false;
                oldDefaultBtn.classList.remove("isDefaultComboBtn");
                oldDefaultBtn.classList.add("set-default-btn");
                throw error;
            }
        });
    });
}

function setupCollapseBtns() {
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function (btn) {
        btn.addEventListener("click", function () {
            var target = document.querySelector(btn.getAttribute("data-bs-target"));
            if (target) target.classList.toggle("show");
        });
    });
}

function popupMessage(type = "success", msg = "") {
    //creo un elemento dialog e lo mostro
    const popup = document.getElementById("dialogPopUp");
    let dialog;
    if (!popup) {
        dialog = document.createElement("dialog");
    } else {
        dialog = popup;
    }

    dialog.id = "dialogPopUp";
    dialog.className = `bootstrap swal-anim`;
    dialog.role = "alert";
    dialog.innerHTML = `
        <div class="card">
            <div class="card-body">
                <div class="alert alert-${type} alert-dismissible fade show my-2" role="alert">
                    ${msg}
                </div>
            </div>
        </div>`;
    document.body.appendChild(dialog);
    dialog.showModal();
    setTimeout(() => dialog.close(), 4000);
}

async function reloadProductsTable({ sort, direction, search, page, limit }) {
    const url = new URL(window.location.href);
    if (sort) url.searchParams.set("sort", sort);
    if (direction) url.searchParams.set("direction", direction);
    if (search !== undefined) url.searchParams.set("search", search);
    if (page !== undefined) url.searchParams.set("page", page);
    if (limit !== undefined) url.searchParams.set("limit", limit);
    url.searchParams.set("ajax", "1");

    const response = await fetch(url, {
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json"
        }
    });
    const data = await response.json();
    if (data.page !== undefined) {
        document.getElementById("products-table-wrapper").innerHTML = data.page;
    }
    if (data.pagination !== undefined && document.getElementById("formPagination")) {
        document.getElementById("formPagination").innerHTML = data.pagination;
    }
    // Re-inizializza bottoni, sorting e paginazione dopo il refresh
    setupDefaultBtns();
    setupCollapseBtns();
    setupTableSorting();
    setupPaginationLinks();
}

function setupTableSorting() {
    document.querySelectorAll("th.sortable").forEach((th) => {
        th.style.cursor = "pointer";
        th.addEventListener("click", function () {
            const sort = th.dataset.sortKey;
            const currentDir = th.dataset.sortDir || "asc";
            const newDir = currentDir === "asc" ? "desc" : "asc";
            const searchInput = document.querySelector('input[name="search"]');
            const search = searchInput ? searchInput.value : "";
            const pageInput = document.querySelector('input[name="page"]');
            const page = pageInput ? parseInt(pageInput.value) || 1 : 1;
            const limitSelect = document.querySelector('select[name="limit"]');
            const limit = limitSelect ? parseInt(limitSelect.value) || 20 : 20;
            reloadProductsTable({ sort, direction: newDir, search, page, limit });
        });
    });
}

// Gestione paginazione AJAX
function setupPaginationLinks() {
    const formPagination = document.getElementById("formPagination");
    if (formPagination) {
        formPagination.addEventListener("submit", function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const pageInput = document.querySelector('input[name="page"]');
            const page = pageInput ? parseInt(pageInput.value) || 1 : 1;
            const searchInput = document.querySelector('input[name="search"]');
            const search = searchInput ? searchInput.value : "";
            const limitSelect = document.querySelector('select[name="limit"]');
            const limit = limitSelect ? parseInt(limitSelect.value) || 20 : 20;
            const sortTh = document.querySelector("th.sortable[data-sort-dir][data-sort-key]");
            const sort = sortTh ? sortTh.dataset.sortKey : "name";
            const direction = sortTh ? sortTh.dataset.sortDir : "asc";
            reloadProductsTable({ sort, direction, search, page, limit });
        });

        formPagination.querySelectorAll(".pagination a.page-link").forEach((link) => {
            link.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const url = new URL(window.location.href);
                const sort = url.searchParams.get("sort");
                const direction = url.searchParams.get("direction");
                const search = url.searchParams.get("search");
                const page = link.dataset.page || 1;
                const limit = document.getElementById("limit-select").value || 20;

                console.log("CLICK PAGINATION");
                console.log(sort, direction, search, page, limit);

                reloadProductsTable({ sort, direction, search, page, limit });
            });
        });

        // Cambio pagina diretta tramite input
        const pageInput = document.querySelector(".page-input");
        if (pageInput) {
            pageInput.addEventListener("change", function (e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const page = parseInt(pageInput.value) || 1;
                const searchInput = document.querySelector('input[name="search"]');
                const search = searchInput ? searchInput.value : "";
                const limitSelect = document.querySelector('select[name="limit"]');
                const limit = limitSelect ? parseInt(limitSelect.value) || 20 : 20;
                const sortTh = document.querySelector("th.sortable[data-sort-dir][data-sort-key]");
                const sort = sortTh ? sortTh.dataset.sortKey : undefined;
                const direction = sortTh ? sortTh.dataset.sortDir : undefined;
                reloadProductsTable({ sort, direction, search, page, limit });
            });
        }

        // Cambio righe per pagina AJAX
        const limitSelect = document.querySelector('select[name="limit"]');
        if (limitSelect) {
            limitSelect.addEventListener("change", function (e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();

                const page = 1;
                const searchInput = document.querySelector('input[name="search"]');
                const search = searchInput ? searchInput.value : "";
                const limit = parseInt(this.value) || 20;
                const sortTh = document.querySelector("th.sortable[data-sort-dir][data-sort-key]");
                const sort = sortTh ? sortTh.dataset.sortKey : "name";
                const direction = sortTh ? sortTh.dataset.sortDir : "asc";
                reloadProductsTable({ sort, direction, search, page, limit });
            });
        }
    }
}

async function setupFormSearch() {
    const formStockSearch = document.getElementById("formStockSearch");
    if (formStockSearch) {
        formStockSearch.addEventListener("submit", function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const searchInput = document.querySelector('input[name="search"]');
            const search = searchInput ? searchInput.value : "";
            const page = 1;
            const limit = 20;
            const sortTh = document.querySelector("th.sortable[data-sort-dir][data-sort-key]");
            const sort = sortTh ? sortTh.dataset.sortKey : "name";
            const direction = sortTh ? sortTh.dataset.sortDir : "asc";
            reloadProductsTable({ sort, direction, search, page, limit });
        });
    }
}

document.addEventListener("DOMContentLoaded", () => {
    console.log("script loaded");

    setupCollapseBtns();
    setupDefaultBtns();
    setupTableSorting();
    setupPaginationLinks();
    setupFormSearch();
});
