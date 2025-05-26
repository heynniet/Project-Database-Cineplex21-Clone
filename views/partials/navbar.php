<?php
// Pastikan session sudah dimulai di file yang menginclude navbar ini
// Cek status login
$isLoggedIn = isset($_SESSION['user_id']);
?>

<header class="site-header">
  <div class="container">
    <nav class="navbar">
      <div class="navbar-logo">
          <img src="../../assets/images/logo.png" alt="SimpleCinema" onerror="this.src='https://via.placeholder.com/150x50?text=SimpleCinema'">
        </a>
      </div>
      <div class="navbar-toggle" id="navbar-toggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <ul class="navbar-menu" id="navbar-menu">
        <li><a href="/Cineplex21/index.php">Home</a></li>
        <li><a href="/Cineplex21/models/Movies.php">Movies</a></li>
        <li><a href="/Cineplex21/models/Cinemas.php">Cinemas</a></li>
        <?php if($isLoggedIn): ?>
        <li><a href="/Cineplex21/views/customer/my_tickets.php">My Tickets</a></li>
        <?php endif; ?>
        
        <?php if(!$isLoggedIn && isset($responsiveMode)): ?>
        <div class="navbar-actions">
          <a href="/Cineplex21/views/auth/login.php" class="btn-login">Login</a>
          <a href="/Cineplex21/views/auth/register.php" class="btn-register">Make an account</a>
        </div>
        <?php endif; ?>
      </ul>
      
      <?php if(!$isLoggedIn): ?>
      <div class="navbar-actions">
        <a href="/Cineplex21/views/auth/login.php" class="btn-login">Login</a>
        <a href="/Cineplex21/views/auth/register.php" class="btn-register">Make an account</a>
      </div>
      <?php else: ?>
      <!-- Tampilkan menu user jika sudah login -->
      <div class="user-dropdown">
        <button class="user-dropdown-btn">
          <a href="/Cineplex21/views/customer/dashboard.php" class="username-link">
            <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>
          </a>
        </button>
        <div class="user-dropdown-content">
          <a href="/Cineplex21/views/customer/profile.php">My Profile</a>
          <a href="/Cineplex21/views/auth/logout.php">Logout</a>
        </div>
      </div>
      <?php endif; ?>
    </nav>
  </div>
</header>

<!-- JavaScript for mobile menu toggle -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const navbarToggle = document.getElementById('navbar-toggle');
    const navbarMenu = document.getElementById('navbar-menu');
    
    if (navbarToggle) {
      navbarToggle.addEventListener('click', function() {
        navbarMenu.classList.toggle('active');
        navbarToggle.classList.toggle('active');
      });
    }
  });
</script>