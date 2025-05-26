document.addEventListener('DOMContentLoaded', function() {
    // Toggle mobile menu
    const navbarToggle = document.getElementById('navbar-toggle');
    const navbarMenu = document.getElementById('navbar-menu');
    
    if (navbarToggle && navbarMenu) {
        navbarToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('active');
            navbarToggle.classList.toggle('active');
        });
    }
    
    // Search functionality
    const searchIcon = document.querySelector('.search-icon');
    const searchOverlay = document.getElementById('search-overlay');
    const closeSearch = document.querySelector('.close-search');
    
    if (searchIcon && searchOverlay && closeSearch) {
        searchIcon.addEventListener('click', function() {
            searchOverlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when search is active
            document.querySelector('.search-container input').focus();
        });
        
        closeSearch.addEventListener('click', function() {
            searchOverlay.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close search on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && searchOverlay && searchOverlay.classList.contains('active')) {
            searchOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Initialize carousels or sliders if needed
    // This is where you could add third-party carousel initialization
    
    // Add active class to current page in navbar
    const currentLocation = location.pathname.split('/').slice(-1)[0] || 'index.php';
    const menuItems = document.querySelectorAll('.navbar-menu a');
    
    menuItems.forEach(item => {
        const itemHref = item.getAttribute('href');
        if (itemHref === currentLocation) {
            item.classList.add('active');
        }
    });
});

// Add this to your existing main.js

// Admin sidebar toggle for responsive design
document.addEventListener('DOMContentLoaded', function() {
    // Create sidebar toggle button for mobile
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (adminSidebar) {
        // Create toggle button
        const sidebarToggle = document.createElement('div');
        sidebarToggle.className = 'sidebar-toggle';
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(sidebarToggle);
        
        // Add toggle functionality
        sidebarToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('active');
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.admin-sidebar') && 
                !event.target.closest('.sidebar-toggle') && 
                adminSidebar.classList.contains('active')) {
                adminSidebar.classList.remove('active');
            }
        });
    }
});