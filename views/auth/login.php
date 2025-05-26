<?php
session_start();
include '../../views/partials/header.php';
include '../../views/partials/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h2>Login to Your Account</h2>
                <p>Enter your credentials to access your account</p>
            </div>
            
            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                        echo $_SESSION['login_error']; 
                        unset($_SESSION['login_error']);
                    ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" action="login_process.php">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username or email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-login">Login</button>
                </div>
                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../../views/partials/footer.php'; ?>