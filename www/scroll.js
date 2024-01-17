var screenWidth =
    window.innerWidth ||
    document.documentElement.clientWidth ||
    document.body.clientWidth;
var porcentajeEnPixeles = (30 / 100) * screenWidth;
var isDragging = false;
var draggedElement = null;
var firstx = false;
var scroll_tag = document.querySelectorAll(".scroll_tag");

function handleStart(element) {
    if (element.classList.contains("scroll_tag")) {
        isDragging = true;
        draggedElement = element;
    }
}

document.addEventListener("mouseup", handleEnd);
document.addEventListener("touchend", handleEnd);
document.addEventListener("mousemove", handleMove);
document.addEventListener("touchmove", handleMove);

function handleEnd() {
    draggedElement.style.transition = "all 0.6s ease";
    isDragging = false;
    draggedElement = null;
    firstx = false;
    scroll_tag.forEach((element) => {
        element.style.transform = "translateX(-30%)";
    });
}

var done = true;

function handleMove(event) {
    if (isDragging && draggedElement) {
        let x;
        draggedElement.style.transition = "all 0s ease";

        if (event.type === "mousemove") {
            x = event.clientX;
        } else if (event.type === "touchmove") {
            // event.preventDefault(); // Prevent scrolling
            var touches = event.touches;
            if (touches.length > 0) {
                var touch = touches[0];
                x = touch.clientX || touch.pageX;
            }
        }

        if (x !== undefined) {
            if (!firstx) {
                firstx = x;
            }
            if (x > firstx) {
                var translateXValue = x - firstx;
                if (translateXValue > porcentajeEnPixeles - 30) {
                    var doaction = draggedElement.getAttribute("action");
                    var id = draggedElement.getAttribute("id");
                    setTimeout(() => {
                        if (done) {
                            switch (doaction) {
                                case "goProject":
                                    goProject(id);
                                    break;
                                case "goSkills":
                                    action("goSkills");
                                    break;
                                case "goAct":
                                    goActivity(id);
                                    break;
                                case "goTeams":
                                    action("goTeams");
                                    break;
                                case "goUsers":
                                    action("goUsers");
                                    break;
                                case "goOverall":
                                    action("goOverall");
                                    break;
                                case "goLeaderboard":
                                    action("goLeaderboard");
                                    break;
                                default:
                                    break;
                            }
                            done = false;
                        }
                    }, 300);

                } else {
                    draggedElement.style.transform =
                        "translateX(calc(" + translateXValue + "px - 30%))";
                }
            }
        }
    }
}