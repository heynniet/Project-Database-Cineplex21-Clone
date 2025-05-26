<?php
// Get current page filename for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Define menu items
$menu_items = [
    'main' => [
        ['title' => 'Dashboard', 'icon' => 'tachometer-alt', 'link' => 'dashboard.php'],
    ],
    'system' => [
        ['title' => 'Logout', 'icon' => 'sign-out-alt', 'link' => '../auth/logout.php']
    ]
];
?>

<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="../../public/images/logo-light.png" alt="SimpleCinema" 
                 onerror="this.src='https://via.placeholder.com/150x50?text=SimpleCinema'">
        </div>
        <div class="sidebar-title">Admin Panel</div>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Main Section -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Main</div>
            <ul>
                <?php foreach ($menu_items['main'] as $item): ?>
                <li class="<?= $current_page == $item['link'] ? 'active' : '' ?>">
                    <a href="<?= $item['link'] ?>">
                        <i class="fas fa-<?= $item['icon'] ?>"></i>
                        <span><?= $item['title'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- System Section -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">System</div>
            <ul>
                <?php foreach ($menu_items['system'] as $item): ?>
                <li class="<?= $current_page == $item['link'] ? 'active' : '' ?>">
                    <a href="<?= $item['link'] ?>">
                        <i class="fas fa-<?= $item['icon'] ?>"></i>
                        <span><?= $item['title'] ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
</div>

<!-- Mobile Toggle Button -->
<div class="sidebar-toggle d-lg-none">
    <i class="fas fa-bars"></i>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
            if (window.innerWidth <= 992 && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        }
    });
});
</script>