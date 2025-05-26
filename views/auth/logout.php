<?php
// Start the session (if not already started)
session_start();

// Clear all session variables
$_SESSION = array();

// If you're using session cookies, destroy the cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set a logout message (optional)
session_start(); // Start a new session to store the message
$_SESSION['logout_message'] = "You have been successfully logged out.";

// Redirect to login page
header("Location: /Cineplex21/index.php");
exit;
?>