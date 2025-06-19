document.addEventListener("DOMContentLoaded", e => {
    console.log("moveTop script loaded");
    const toolbarMenu = document.querySelector(".toolbar-menu");
    const titleRow = document.querySelector(".header-toolbar .container-fluid");

    if (toolbarMenu && titleRow) {
        titleRow.appendChild(toolbarMenu);
    }
});
