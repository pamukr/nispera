var skillPercentInputs = document.querySelectorAll('.skill-percent');

function limitMaxValue(input) {

    input.addEventListener('input', function () {
        var maxValue = parseInt(this.getAttribute('block'));
        if (this.value > maxValue) {
            this.value = maxValue;
        }
    });
}

function paintPercentage(input) {
    var parentDiv = input.closest('.edit-percent');
    var percentageDisplay = parentDiv.querySelector('.percentage-display');

    input.addEventListener('input', function () {
        percentageDisplay.textContent = input.value + '%';
        var fillPercentage = (input.value / 100) * 100;
        input.style.background = `linear-gradient(to right, var(--skill-color) 0%, var(--skill-color) ${fillPercentage}%, #ddd ${fillPercentage}%, #ddd 100%)`;
    });

}

function paintOnLoading(input) {
    var parentDiv = input.closest('.edit-percent');
    var percentageDisplay = parentDiv.querySelector('.percentage-display');
    percentageDisplay.textContent = input.value + '%';
    var fillPercentage = (input.value / 100) * 100;
    input.style.background = `linear-gradient(to right, var(--skill-color) 0%, var(--skill-color) ${fillPercentage}%, #ddd ${fillPercentage}%, #ddd 100%)`;
}

document.addEventListener('DOMContentLoaded', function () {
    skillPercentInputs.forEach(function (input) {
        limitMaxValue(input);
        paintPercentage(input);
        paintOnLoading(input);
    });
});