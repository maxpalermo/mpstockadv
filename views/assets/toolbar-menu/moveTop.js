document.addEventListener("DOMContentLoaded", e => {
    const toolbarMenu = document.querySelector(".toolbar-menu");
    const titleRow = document.querySelector(".header-toolbar .container-fluid");

    if (toolbarMenu && titleRow) {
        titleRow.appendChild(toolbarMenu);
    }

    console.log("moveTop script loaded");
});
