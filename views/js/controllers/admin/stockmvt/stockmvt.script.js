document.addEventListener("DOMContentLoaded", async (e) => {
    console.log("script loaded");
    const btnNewMvt = document.querySelector("#stock-mvt-new-mvt");
    if (btnNewMvt) {
        btnNewMvt.addEventListener("click", async (e) => {
            e.preventDefault();
            const response = await fetch(window.APP_ROUTES.fetchDialogFormUrl);
            const data = await response.json();
            const formHtml = data.formHtml;
            const idDialog = data.idDialog;
            const dialog = document.getElementById(idDialog);
            if (dialog) {
                dialog.remove();
            }
            document.body.insertAdjacentHTML("beforeend", formHtml);
            const dialogElement = document.getElementById(idDialog);
            dialogElement.showModal();
        });
    }
});
