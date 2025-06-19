function doSearch(searchValue) {
    console.log(searchValue);
}

function editWarehouse(id) {
    const countrySelect = document.getElementById("warehouse-country");
    const warehouseCard = document.getElementById("warehouse-info");
    warehouseCard.classList.add("active");
    warehouseCard.querySelector(".card-title").textContent = "Modifica Magazzino";
    initStateSelect(countrySelect.value);
    setDefaultActive();
}

function disableWarehouse(id) {
    console.log("DISABLE", id);
}

function closeWarehouseInfo() {
    const warehouseCard = document.getElementById("warehouse-info");
    warehouseCard.classList.remove("active");
}

function hideSelect2(selector) {
    $(selector).hide();
    try {
        $(selector).select2("close");
        $(selector).next(".select2-container").hide();
    } catch (error) {
        console.log(error);
    }
}

function showSelect2(selector) {
    $(selector).show();
    $(selector).next(".select2-container").show();
}

async function fillStates(countryId) {
    const response = await fetch(`${ControllerURLS.getStatesUrl}&countryId=${countryId}`);
    const result = await response.json();
    const states = result.states || [];
    const stateSelect = document.getElementById("warehouse-state");

    stateSelect.innerHTML = "";
    states.forEach((state) => {
        const option = document.createElement("option");
        option.value = state.id_state;
        option.textContent = state.name;
        stateSelect.appendChild(option);
    });
}

async function setDefaultActive() {
    const activeState = document.querySelector("#warehouse-status-on");
    activeState.checked = true;
}

async function initStateSelect(countryId = null) {
    const stateSelect = document.getElementById("warehouse-state");
    const stateSpinner = document.getElementById("stateSpinner");
    hideSelect2(stateSelect);
    stateSpinner.style.display = "block";
    if (stateSelect) {
        if (countryId) {
            await fillStates(countryId);
            showSelect2(stateSelect);
            stateSpinner.style.display = "none";
        }

        $(stateSelect)
            .select2({
                placeholder: "Nessuna Provincia",
                allowClear: true,
                width: "100%",
                dropdownAutoWidth: true,
                dropdownParent: $(stateSelect).parent(),
                minimumResultsForSearch: 0,
                height: "38px",
                templateResult: function (state) {
                    return $('<span style="line-height:38px;">' + state.text + "</span>");
                }
            })
            .on("select2:open", function () {
                setTimeout(function () {
                    document.querySelector(".select2-container--open .select2-search__field").focus();
                }, 0);
            });
    } else {
        console.error("Elemento stateSelect non trovato");
    }
}

function initCountrySelect() {
    const countrySelect = document.getElementById("warehouse-country");
    $(countrySelect)
        .select2({
            placeholder: "Seleziona paese",
            allowClear: true,
            width: "100%",
            dropdownAutoWidth: true,
            dropdownParent: $(countrySelect).parent(),
            minimumResultsForSearch: 0,
            height: "38px",
            templateResult: function (state) {
                return $('<span style="line-height:38px; padding-left: 8px;">' + state.text + "</span>");
            }
        })
        .on("select2:open", function () {
            setTimeout(function () {
                document.querySelector(".select2-container--open .select2-search__field").focus();
            }, 0);
        });
}

document.addEventListener("DOMContentLoaded", async () => {
    const countrySelect = document.getElementById("warehouse-country");

    initCountrySelect();

    if (countrySelect) {
        $(countrySelect).on("change", async (e) => {
            e.preventDefault();
            const that = e.target;
            const countryId = that.value;
            initStateSelect(countryId);
        });
    }
});
