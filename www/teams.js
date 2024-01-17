function openTeam(id, icon) {
    let div = document.getElementById(`team${id}`);
    icon = icon.querySelector('.bx-chevron-right');

    if (div.classList.contains("height-0")) {
        div.classList.remove("height-0");
        div.classList.add("fit-content");
        icon.style.transform = "rotate(90deg)";
    } else {
        div.classList.remove("fit-content");
        div.classList.add("height-0");
        icon.style.transform = "rotate(0deg)";
    }
}

function openUser(userId, user) {
    let div = document.getElementById(`${userId}`);
    let icon = user.querySelector('.bx-chevron-right');

    if (div.classList.contains("height-0")) {
        div.style.padding = "15px";
        div.classList.remove("height-0");
        div.classList.add("fit-content");
        icon.style.transform = "rotate(90deg)";
    } else {
        div.style.padding = "0px";
        div.classList.remove("fit-content");
        div.classList.add("height-0");
        icon.style.transform = "rotate(0deg)";
    }
}