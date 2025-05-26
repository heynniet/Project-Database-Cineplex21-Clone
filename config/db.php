<?php
// Database configuration
try {
    // Define database connection parameters
    $servername = "localhost";
    $dbname = "Cineplex21";
    $username = "root"; 
    $password = ""; 

    // Create PDO instance for advanced queries
    $db = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set the PDO error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set default fetch mode to associative array
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create mysqli connection for legacy code compatibility
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check mysqli connection
    if ($conn->connect_error) {
        throw new Exception("MySQLi Connection failed: " . $conn->connect_error);
    }
    
} catch(Exception $e) {
    // Log the error to a file
    error_log("Database Connection Error: " . $e->getMessage(), 0);
    
    // For development only - comment this out in production
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>