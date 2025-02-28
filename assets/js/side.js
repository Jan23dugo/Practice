const sidebar = document.querySelector('.sidebar');
const sidebarToggler = document.querySelector('.sidebar-toggler');
const menuToggler = document.querySelector('.menu-toggler');

const collapsedSidebarHeight = '56px';
const fullSidebarHeight = 'calc(100vh - 32px)';

// Toggle sidebar's collapsed state
sidebarToggler.addEventListener("click", () => {
    sidebar.classList.toggle("collapsed");
});

const toggleMenu = (isMenuActive) => {
    sidebar.style.height = isMenuActive ? `${sidebar.scrollHeight}px` : collapsedSidebarHeight;
    menuToggler.querySelector("span").innerText = isMenuActive ? "close" : "menu";
}

// Toggler menu-active class and adjust height
menuToggler.addEventListener("click", () => {
    toggleMenu(sidebar.classList.toggle("menu-active"));
})

document.addEventListener("DOMContentLoaded", function () {
    const menuToggler = document.querySelector(".menu-toggler");

    menuToggler.addEventListener("click", function () {
        sidebar.classList.toggle("open");
    });
});
// Function to toggle dropdown menu and expand sidebar if collapsed
function toggleDropdown(event) {
    event.preventDefault();
    const parentItem = event.currentTarget.closest('.has-dropdown');
    const dropdown = parentItem.querySelector('.dropdown-menu');
    const chevron = parentItem.querySelector('.dropdown-arrow'); // Select the chevron

    // Expand the sidebar if it is collapsed
    if (sidebar.classList.contains('collapsed')) {
        sidebar.classList.remove('collapsed'); // Expand sidebar
    }

    // Toggle current dropdown
    dropdown.classList.toggle('show');
    parentItem.classList.toggle('expanded');

    // Rotate the chevron when dropdown is expanded
    if (parentItem.classList.contains('expanded')) {
        chevron.style.transform = "rotate(90deg)";
    } else {
        chevron.style.transform = "rotate(0deg)";
    }

    // Close other dropdowns if open
    document.querySelectorAll('.has-dropdown .dropdown-menu').forEach(function (menu) {
        if (menu !== dropdown) {
            menu.classList.remove('show');
            let otherParent = menu.closest('.has-dropdown');
            otherParent.classList.remove('expanded');

            // Reset chevron rotation for other dropdowns
            let otherChevron = otherParent.querySelector('.dropdown-arrow');
            if (otherChevron) {
                otherChevron.style.transform = "rotate(0deg)";
            }
        }
    });
}

// Add event listeners to dropdown toggle buttons
document.querySelectorAll('.dropdown-toggle').forEach(function (dropdownToggle) {
    dropdownToggle.addEventListener('click', toggleDropdown);
});

// Close dropdowns when clicking outside
document.addEventListener('click', function (event) {
    if (!event.target.closest('.has-dropdown')) {
        document.querySelectorAll('.has-dropdown .dropdown-menu').forEach(function (menu) {
            menu.classList.remove('show');
            let parentItem = menu.closest('.has-dropdown');
            parentItem.classList.remove('expanded');

            // Reset chevron rotation when closing dropdowns
            let chevron = parentItem.querySelector('.dropdown-arrow');
            if (chevron) {
                chevron.style.transform = "rotate(0deg)";
            }
        });
    }
});

// Prevent event propagation for clicks inside the dropdown
document.querySelectorAll('.dropdown-menu').forEach(function (dropdown) {
    dropdown.addEventListener('click', function (event) {
        event.stopPropagation();
    });
});
