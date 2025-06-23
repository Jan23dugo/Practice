document.addEventListener('DOMContentLoaded', function() {
    // Profile Menu Toggle
    const profileMenu = document.querySelector('.profile-menu');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    const profileMenuTrigger = document.querySelector('#profile-menu');
    
    if (profileMenu && dropdownMenu && profileMenuTrigger) {
        document.addEventListener('click', function(event) {
            if (!profileMenu.contains(event.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
        
        profileMenuTrigger.addEventListener('click', function(event) {
            event.preventDefault();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });
    }
    
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    
    if (menuToggle && sidebar && overlay) {
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        }
        
        menuToggle.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
        
        // Close sidebar when clicking a menu item on mobile
        const menuItems = document.querySelectorAll('.sidebar-menu a');
        menuItems.forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth <= 768) {
                    toggleSidebar();
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    }
}); 