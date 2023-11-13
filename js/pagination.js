document.addEventListener('DOMContentLoaded', function () {
    const content = document.querySelector('.pkpStats__panel'); 
    const itemsPerPage = 25;
    let currentPage = 0;
    const items = Array.from(content.getElementsByTagName('tr')).slice(1);

    function showPage(page) {
        const startIndex = page * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        items.forEach((item, index) => {
            item.classList.toggle('hiddenReviewerReport', index < startIndex || index >= endIndex);
        });
        updateActiveButtonStates();
    }

    function createPageButtons() {
        const totalPages = Math.ceil(items.length / itemsPerPage);
        const paginationContainer = document.createElement('div');
        const paginationDiv = document.body.appendChild(paginationContainer);
        paginationContainer.classList.add('pagination');

        for (let i = 0; i < totalPages; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i + 1;
            pageButton.addEventListener('click', () => {
                currentPage = i;
                showPage(currentPage);
                updateActiveButtonStates();
            });

            content.appendChild(paginationContainer);
            paginationDiv.appendChild(pageButton);
        }
    }

    function updateActiveButtonStates() {
        const pageButtons = document.querySelectorAll('.pagination button');
        pageButtons.forEach((button, index) => {
            if (index === currentPage) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }
        
    createPageButtons();
    showPage(currentPage);
});

document.addEventListener('DOMContentLoaded', function () {
    var expandButtons = document.querySelectorAll('.expand-button');

    expandButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var expandable = this.closest('tr');
            var expandContent = expandable.nextElementSibling;

            if (expandContent && expandContent.classList.contains('expand-content')) {
                expandContent.style.display = (expandContent.style.display === 'none' || !expandContent.style.display) ? 'block' : 'none';
            }
        });
    });
});
