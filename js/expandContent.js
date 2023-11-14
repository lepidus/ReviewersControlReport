document.addEventListener('DOMContentLoaded', function () {
    var expandButtons = document.querySelectorAll('.expand-button');

    expandButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var expandable = this.closest('tr');
            var expandContent = expandable.nextElementSibling;

            if (expandContent && expandContent.classList.contains('expand-content')) {
                expandContent.style.display = (expandContent.style.display === 'none' || !expandContent.style.display) ? 'block' : 'none';

                var buttonText = this.querySelector('.button-text');
                if (buttonText) {
                    buttonText.textContent = expandContent.style.display === 'none' ? '▼' : '▲';
                }
            }
        });
    });
});
