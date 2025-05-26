<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    echo "Login process started<br>";

    // Prepare query to find user
    $query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            echo "User role: " . $user['role'] . "<br>";
            echo "Redirecting to: " . ($user['role'] == 'admin' ? "/Cineplex21/views/admin/dashboard.php" : "/Cineplex21/views/customer/dashboard.php") . "<br>";
            // Redirect based on role
            if ($user['role'] == 'admin') {
                header('Location: /Cineplex21/views/admin/dashboard.php');
            } else {
                header('Location: /Cineplex21/views/customer/dashboard.php');
            }
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid password!";
        }
    } else {
        $_SESSION['login_error'] = "Username or email not found!";
    }
    
    // Redirect back to login if authentication failed
    header('Location: login.php');
    exit();
}
?>