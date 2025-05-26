<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize session and database connection
session_start();

// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}

// Include required files
require_once '../partials/header.php';
require_once '../../config/db.php';

// Page title for header
$pageTitle = "Edit Cinema - Cineplex21";

// Check if ID is valid
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_cinemas.php?error=Invalid+Cinema+ID');
    exit;
}

$id = intval($_GET['id']);

// Get cinema data from database
$stmt = $conn->prepare("SELECT * FROM theaters WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// If cinema not found, redirect
if ($result->num_rows === 0) {
    $stmt->close();
    header('Location: manage_cinemas.php?error=Cinema+not+found');
    exit;
}

// Get cinema data
$cinema = $result->fetch_assoc();
$stmt->close();

// Initialize variables for form
$name = $cinema['name'];
$city = $cinema['city'];
$address = $cinema['address'];
$phone = $cinema['phone'];
$special_tag = $cinema['special_tag'];
$total_seats = $cinema['total_seats'];
$status = $cinema['status'];
$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = trim($_POST['name']);
    $city = trim($_POST['city']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $special_tag = trim($_POST['special_tag']);
    $total_seats = intval($_POST['total_seats']);
    $status = $_POST['status'];
    
    // Validate name (required)
    if (empty($name)) {
        $errors[] = "Cinema name is required";
    }
    
    // Validate city (required)
    if (empty($city)) {
        $errors[] = "City is required";
    }
    
    // Validate seats (must be positive number)
    if ($total_seats <= 0) {
        $errors[] = "Total seats must be greater than 0";
    }
    
    // If no errors, update database
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE theaters SET name = ?, location = ?, city = ?, address = ?, phone = ?, special_tag = ?, total_seats = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("ssssssssi", $name, $city, $city, $address, $phone, $special_tag, $total_seats, $status, $id);
        
        if ($stmt->execute()) {
            $success = "Cinema successfully updated!";
        } else {
            $errors[] = "Failed to update cinema: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Count number of showtimes for this cinema
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM showtimes WHERE theater_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$showtimeCount = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* Custom styles for edit cinema page */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-content {
            flex-grow: 1;
            padding: 2rem;
            background-color: #f8f9fa;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .cinema-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .cinema-badge {
            padding: 0.35rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            border-radius: 50px;
            color: white;
        }
        
        .badge-active {
            background-color: #10b981;
        }
        
        .badge-inactive {
            background-color: #ef4444;
        }
        
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
        
        .form-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .form-subtitle {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1f2937;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        
        .required-label::after {
            content: " *";
            color: #ef4444;
        }
        
        .form-control {
            display: block;
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #1f2937;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            transition: border-color 0.15s ease-in-out;
        }
        
        .form-control:focus {
            border-color: #4361ee;
            outline: 0;
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.25);
        }
        
        .form-text {
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #6b7280;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border-left: 4px solid #b91c1c;
            color: #b91c1c;
        }
        
        .alert-success {
            background-color: #dcfce7;
            border-left: 4px solid #14532d;
            color: #14532d;
        }
        
        .error-list {
            padding-left: 1.5rem;
            margin: 0.5rem 0 0;
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
            gap: 1rem;
        }
        
        .btn-primary {
            padding: 0.75rem 1.5rem;
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
        }
        
        .info-box {
            background-color: #f3f4f6;
            border-left: 4px solid #4b5563;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .info-title {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../partials/sidebar.php'; ?>
        
        <div class="admin-content">
            <div class="admin-header">
                <div class="cinema-info">
                    <h2><i class="fas fa-edit"></i> Edit Cinema</h2>
                    <span class="cinema-badge <?php echo $status === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                        <?php echo $status === 'active' ? 'Active' : 'Inactive'; ?>
                    </span>
                </div>
                <a href="manage_cinemas.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong><i class="fas fa-exclamation-circle"></i> There are errors in the form:</strong>
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($showtimeCount > 0): ?>
                <div class="info-box">
                    <div class="info-title">
                        <i class="fas fa-info-circle"></i> Information
                    </div>
                    <p>This cinema has <?php echo $showtimeCount; ?> active showtimes. Changes to status or other important information may affect schedules displayed to users.</p>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $id; ?>" class="form-container">
                <h3 class="form-subtitle">Basic Information</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name" class="required-label">Cinema Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                        <div class="form-text">Example: Cineplex 21 Grand Indonesia</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="city" class="required-label">City</label>
                        <input type="text" id="city" name="city" class="form-control" value="<?php echo htmlspecialchars($city); ?>" required>
                        <div class="form-text">Example: Jakarta, Surabaya, Bandung</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                        <div class="form-text">Complete address of the cinema</div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($phone); ?>">
                        <div class="form-text">Format: 021-1234567 or 08123456789</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="special_tag">Special Tag</label>
                        <input type="text" id="special_tag" name="special_tag" class="form-control" value="<?php echo htmlspecialchars($special_tag); ?>">
                        <div class="form-text">Example: IMAX, 4DX, Premiere, etc (optional)</div>
                    </div>
                </div>
                
                <h3 class="form-subtitle">Configuration</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="total_seats" class="required-label">Total Seats</label>
                        <input type="number" id="total_seats" name="total_seats" class="form-control" value="<?php echo $total_seats; ?>" min="1" required>
                        <div class="form-text">Total number of seats in the cinema</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="required-label">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <div class="form-text">Inactive cinemas won't be displayed on public pages</div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="manage_cinemas.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
            
            <!-- Other sections -->
            <div class="form-container">
                <h3 class="form-subtitle">Other Actions</h3>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="view_cinema.php?id=<?php echo $id; ?>" class="btn-secondary">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                    
                    <a href="manage_screens.php?cinema_id=<?php echo $id; ?>" class="btn-secondary">
                        <i class="fas fa-tv"></i> Manage Screens/Studios
                    </a>
                    
                    <a href="theater_showtimes.php?id=<?php echo $id; ?>" class="btn-secondary">
                        <i class="fas fa-calendar"></i> View Showtimes
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation script
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            
            form.addEventListener('submit', function(event) {
                let hasErrors = false;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        hasErrors = true;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                const totalSeats = document.getElementById('total_seats');
                if (totalSeats.value <= 0) {
                    totalSeats.classList.add('is-invalid');
                    hasErrors = true;
                }
                
                if (hasErrors) {
                    event.preventDefault();
                    alert('Please check the form. Some required fields have not been filled in correctly.');
                }
            });
        });
    </script>
    
    <?php include '../partials/footer.php'; ?>
</body>
</html>