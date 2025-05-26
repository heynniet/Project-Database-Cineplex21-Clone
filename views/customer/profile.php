<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

include '../partials/header.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}

// Include necessary files
include '../partials/navbar.php';
include '../../config/db.php';

// Get current user data
$userId = $_SESSION['user_id'];

// Handle form submission for profile update
$updateMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lastName']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = is_numeric($_POST['phone_number']) ? $_POST['phone_number'] : 0;
    $birthdate = !empty($_POST['birthdate']) ? "'" . mysqli_real_escape_string($conn, $_POST['birthdate']) . "'" : "NULL";
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    $updateQuery = "UPDATE users SET 
        firstName = '$firstName', 
        lastName = '$lastName', 
        email = '$email', 
        phone_number = $phone, 
        birthdate = $birthdate,
        gender = '$gender', 
        address = '$address' 
        WHERE id = $userId";

    
    if (mysqli_query($conn, $updateQuery)) {
        $updateMessage = '<div class="alert alert-success">Profile updated successfully!</div>';
    } else {
        $updateMessage = '<div class="alert alert-danger">Error updating profile: ' . mysqli_error($conn) . '</div>';
    }
}

// Fetch user data - using the correct column names from phpMyAdmin
$userQuery = "SELECT * FROM users WHERE id = $userId";
$userResult = mysqli_query($conn, $userQuery);
$userData = mysqli_fetch_assoc($userResult);

// Fetch orders history
// Adjust query based on your actual database schema
// First, let's check if movie_id exists in bookings table or if it's named differently
$checkColumnsQuery = "SHOW COLUMNS FROM bookings";
$columnsResult = mysqli_query($conn, $checkColumnsQuery);
$movieIdColumn = 'movie_id'; // Default column name
$userIdColumn = 'user_id';   // Default column name
$bookingDateColumn = 'booking_date'; // Default column name

// Find the correct column names
$columns = [];
while ($column = mysqli_fetch_assoc($columnsResult)) {
    $columns[] = $column['Field'];
}

// Check if columns exist or find alternatives
if (!in_array('movie_id', $columns)) {
    // Try to find an alternative column that might connect to movies
    foreach ($columns as $column) {
        if (strpos($column, 'movie') !== false) {
            $movieIdColumn = $column;
            break;
        }
    }
}

if (!in_array('user_id', $columns)) {
    // Try to find an alternative column for user
    foreach ($columns as $column) {
        if (strpos($column, 'user') !== false || strpos($column, 'customer') !== false) {
            $userIdColumn = $column;
            break;
        }
    }
}

if (!in_array('booking_date', $columns)) {
    // Try to find an alternative date column
    foreach ($columns as $column) {
        if (strpos($column, 'date') !== false || strpos($column, 'created') !== false) {
            $bookingDateColumn = $column;
            break;
        }
    }
}

// Now build the query based on discovered column names
$ordersQuery = "SELECT b.id, m.title as movie_title, b.$bookingDateColumn as order_date, b.total_amount, b.status 
                FROM bookings b 
                JOIN movies m ON b.$movieIdColumn = m.id 
                WHERE b.$userIdColumn = $userId 
                ORDER BY b.$bookingDateColumn DESC";

// For debugging
//echo "<pre>DEBUG: $ordersQuery</pre>";

// Execute query with error handling
try {
    $ordersResult = mysqli_query($conn, $ordersQuery);
    if (!$ordersResult) {
        // Fallback: try a simpler query without joins
        $simpleQuery = "SELECT id, $bookingDateColumn as order_date, total_amount, status 
                        FROM bookings 
                        WHERE $userIdColumn = $userId 
                        ORDER BY $bookingDateColumn DESC";
        $ordersResult = mysqli_query($conn, $simpleQuery);
    }
} catch (Exception $e) {
    // If still fails, set to empty result
    $ordersResult = false;
}

?>
<!-- Main Content -->
<div class="main-content">
    <div class="container">
        <?php if (!empty($updateMessage)) echo $updateMessage; ?>
        
        <div class="profile-container">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="user-info">
                    <h3 id="userName"><?php echo $userData['firstname'] . ' ' . $userData['lastname']; ?></h3>
                    <p id="userEmail"><?php echo $userData['email']; ?></p>
                </div>
                <div class="profile-menu">
                    <ul>
                        <li class="active"><a href="#personalInfo" data-section="personalInfo">
                            <i class="fas fa-user"></i> Personal Information
                        </a></li>
                        <li><a href="my_tickets.php" data-section="orderHistory">
                            <i class="fas fa-ticket-alt"></i> Order History
                        </a></li>
                    </ul>
                </div>
                <div class="profile-actions">
                    <a href="/Cineplex21/views/auth/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Personal Information Section -->
                <div class="profile-section active" id="personalInfo">
                    <div class="section-header">
                        <h2>Personal Information</h2>
                    </div>
                    <div class="section-content">
                        <form id="profileForm" method="POST" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="firstName">First Name</label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo $userData['firstname']; ?>" placeholder="First Name">
                                </div>
                                <div class="form-group">
                                    <label for="lastName">Last Name</label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo $userData['lastname']; ?>" placeholder="Last Name">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo $userData['email']; ?>" placeholder="Email Address">
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="tel" id="phone_number" name="phone_number" value="<?php echo isset($userData['phone_number']) ? $userData['phone_number'] : ''; ?>" placeholder="Phone Number">
                            </div>
                            <div class="form-group">
                                <label for="birthdate">Date of Birth</label>
                                <input type="date" id="birthdate" name="birthdate" value="<?php echo $userData['birthdate']; ?>">
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male" <?php echo ($userData['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($userData['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="prefer_not_to_say" <?php echo ($userData['gender'] == 'prefer_not_to_say') ? 'selected' : ''; ?>>Prefer not to say</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3" placeholder="Your Address"><?php echo $userData['address']; ?></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Order History Section -->
                <div class="profile-section" id="orderHistory">
                    <div class="section-header">
                        <h2>Order History</h2>
                    </div>
                    <div class="section-content">
                        <div class="order-list" id="orderListContainer">
                            <?php if (isset($ordersResult) && mysqli_num_rows($ordersResult) > 0): ?>
                                <table class="order-table">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Movie</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($order = mysqli_fetch_assoc($ordersResult)): ?>
                                            <tr>
                                                <td>#<?php echo $order['id']; ?></td>
                                                <td><?php echo isset($order['movie_title']) ? $order['movie_title'] : 'Movie info not available'; ?></td>
                                                <td><?php echo isset($order['order_date']) ? date('M d, Y', strtotime($order['order_date'])) : 'N/A'; ?></td>
                                                <td>$<?php echo isset($order['total_amount']) ? number_format($order['total_amount'], 2) : '0.00'; ?></td>
                                                <td><span class="status-badge status-<?php echo isset($order['status']) ? strtolower($order['status']) : 'unknown'; ?>"><?php echo isset($order['status']) ? $order['status'] : 'Unknown'; ?></span></td>
                                                <td>
                                                    <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-view">View Details</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-ticket-alt empty-icon"></i>
                                    <p>You haven't made any orders yet.</p>
                                    <a href="/Cineplex21/views/movies/index.php" class="btn-browse">Browse Movies</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle tab switching
    const menuItems = document.querySelectorAll('.profile-menu li a');
    const sections = document.querySelectorAll('.profile-section');
    
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all menu items and sections
            menuItems.forEach(mi => mi.parentElement.classList.remove('active'));
            sections.forEach(section => section.classList.remove('active'));
            
            // Add active class to clicked menu item
            this.parentElement.classList.add('active');
            
            // Show corresponding section
            const targetSection = this.getAttribute('data-section');
            document.getElementById(targetSection).classList.add('active');
        });
    });
    
    // Form validation
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone_number').value;
            
            let isValid = true;
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                isValid = false;
                alert('Please enter a valid email address');
            }
            
            // Phone validation - make sure it's a number if provided
            if (phone) {
                const phoneRegex = /^\d+$/;
                if (!phoneRegex.test(phone.replace(/\D/g, ''))) {
                    isValid = false;
                    alert('Please enter a valid phone number (digits only)');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php 
// Close database connection
mysqli_close($conn);

// Include footer
include '../partials/footer.php'; 
?>