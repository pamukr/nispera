var skillPercentInputs = document.querySelectorAll('.skill-percent');

function updateTotalPercent() {
    var totalPercentDisplay = document.getElementById('totalPercent');
    var totalPercentage = Array.from(skillPercentInputs).reduce((sum, input) => sum + parseInt(input.value), 0);
    totalPercentDisplay.textContent = totalPercentage;
}

function limitMaxValue(input) {
    var maxValue = 100;

    input.addEventListener('input', function () {
        if (this.value > maxValue) {
            this.value = maxValue;
        }
        var totalPercentage = Array.from(skillPercentInputs).reduce((sum, input) => sum + parseInt(input.value), 0);

        if (totalPercentage > 100) {
            this.value = this.value - (totalPercentage - 100);
        }
    });
    updateTotalPercent()
}

function paintPercentage(input) {
    var parentDiv = input.closest('.edit-percent');
    var percentageDisplay = parentDiv.querySelector('.percentage-display');

    input.addEventListener('input', function () {
        percentageDisplay.textContent = input.value + '%';
        let fillPercentage = (input.value / 100) * 100;
        input.style.background = `linear-gradient(to right, var(--skill-color) 0%, var(--skill-color) ${fillPercentage}%, #ddd ${fillPercentage}%, #ddd 100%)`;
        updateTotalPercent();
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