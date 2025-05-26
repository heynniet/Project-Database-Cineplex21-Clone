<?php
// Include database connection correctly
include('../../config/db.php'); // Make sure this path is correct

session_start();

// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Variable to store registration errors
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $fullname     = mysqli_real_escape_string($conn, $_POST['fullname']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $password     = $_POST['password'];
    $phone_number = !empty($_POST['phone_number']) ? mysqli_real_escape_string($conn, $_POST['phone_number']) : NULL;

    // Basic validation
    if (empty($fullname) || empty($username) || empty($email) || empty($password)) {
        $error_message = "All fields are required except phone number.";
    } else {
        // Cek email sudah terdaftar atau belum
        $checkEmail = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
        if (!$checkEmail) {
            $error_message = "Database error: " . mysqli_error($conn);
        } elseif (mysqli_num_rows($checkEmail) > 0) {
            $error_message = "Email already registered. Please login.";
        } else {
            // Cek username sudah terdaftar atau belum
            $checkUsername = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
            if (!$checkUsername) {
                $error_message = "Database error: " . mysqli_error($conn);
            } elseif (mysqli_num_rows($checkUsername) > 0) {
                $error_message = "Username already registered. Please use another username.";
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // PERBAIKAN: Menambahkan kolom phone_number pada query INSERT
                $query = "INSERT INTO users (username, email, password, name, role, phone_number) 
                         VALUES ('$username', '$email', '$hashedPassword', '$fullname', 'customer', ";
                
                // Jika phone_number NULL, gunakan NULL dalam query
                if ($phone_number === NULL) {
                    $query .= "NULL)";
                } else {
                    $query .= "'$phone_number')";
                }

                if (mysqli_query($conn, $query)) {
                    // Berhasil register
                    $success_message = "Successful registration!";
                    // Redirect after 2 seconds
                    header("refresh:2;url=login.php?register=success");
                } else {
                    $error_message = "Registration failed: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<?php include '../../views/partials/header.php'; ?>
<?php include '../../views/partials/navbar.php'; ?>

<main class="main-content">
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h2>Create New Account</h2>
                <p>Please fill in the form below to register</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                    <p>Redirecting to login page...</p>
                </div>
            <?php else: ?>
                <form class="register-form" action="" method="POST">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email address" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <!-- PERBAIKAN: Menambahkan field phone_number pada form -->
                    <div class="form-group">
                        <label for="phone_number">Phone Number (Optional)</label>
                        <input type="text" id="phone_number" name="phone_number" placeholder="Enter your phone number" value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-register">Register</button>
                    </div>
                    
                    <div class="login-link">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include '../../views/partials/footer.php'; ?>