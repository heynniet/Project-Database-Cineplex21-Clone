<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection and session initialization
session_start();

// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}

include '../partials/header.php';
include '../../config/db.php';

// Page title for header
$pageTitle = "Add New Showtime - Cineplex21";

// Pre-select movie if provided in URL
$preselected_movie = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;

// Get all movies including coming soon
$moviesQuery = $conn->query("SELECT id, title, status FROM movies ORDER BY title");
$movies = [];
while ($movie = $moviesQuery->fetch_assoc()) {
    $movies[] = $movie;
}

// Get all theaters
$theatersQuery = $conn->query("SELECT id, name, total_seats FROM theaters ORDER BY name");
$theaters = [];
while ($theater = $theatersQuery->fetch_assoc()) {
    $theaters[] = $theater;
}

$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate data
    $movie_id = intval($_POST['movie_id']);
    $theater_id = intval($_POST['theater_id']);
    $show_date = trim($_POST['showdate']);
    $show_time = trim($_POST['showtime']);
    $price = floatval($_POST['price']);
    
    // Input validation
    if ($movie_id <= 0) {
        $errors[] = "Please select a movie";
    }
    
    if ($theater_id <= 0) {
        $errors[] = "Please select a theater";
    }
    
    if (empty($show_date)) {
        $errors[] = "Show date cannot be empty";
    } elseif (strtotime($show_date) < strtotime(date('Y-m-d'))) {
        $errors[] = "Show date cannot be in the past";
    }
    
    if (empty($show_time)) {
        $errors[] = "Show time cannot be empty";
    }
    
    if ($price <= 0) {
        $errors[] = "Ticket price must be greater than 0";
    }
    
    // Check scheduling conflicts for the same theater
    if (empty($errors)) {
        $checkConflict = $conn->prepare("
            SELECT COUNT(*) as conflict_count FROM showtimes 
            WHERE theater_id = ? AND showdate = ? AND 
            (TIME_TO_SEC(TIMEDIFF(?, showtime)) < 7200 OR 
             TIME_TO_SEC(TIMEDIFF(showtime, ?)) < 7200)
        ");
        $checkConflict->bind_param("isss", $theater_id, $show_date, $show_time, $show_time);
        $checkConflict->execute();
        $conflictResult = $checkConflict->get_result();
        $conflict = $conflictResult->fetch_assoc();
        
        if ($conflict['conflict_count'] > 0) {
            $errors[] = "There is a scheduling conflict in this theater (within 2 hours range)";
        }
    }
    
    // Save data if no errors
    if (empty($errors)) {
        // Get theater capacity to set available seats
        $capacity = 0;
        foreach ($theaters as $theater) {
            if ($theater['id'] == $theater_id) {
                $capacity = $theater['total_seats'];
                break;
            }
        }
        
        $insertQuery = $conn->prepare("
            INSERT INTO showtimes 
            (movie_id, theater_id, showdate, showtime, price, available_seats, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $insertQuery->bind_param("iissdi", $movie_id, $theater_id, $show_date, $show_time, $price, $capacity);
        
        if ($insertQuery->execute()) {
            $success = "Showtime schedule successfully added!";
            // Reset form after successful submission
            $_POST = [];
        } else {
            $errors[] = "Failed to save schedule: " . $conn->error;
        }
    }
}
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
        /* Base Styles */
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3a56d4;
            --secondary-color: #f3f4f6;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #3b82f6;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --gray-color: #6b7280;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            font-size: 16px;
        }

        /* Layout */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-content {
            flex-grow: 1;
            padding: 2rem;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Header Styles */
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-header h2 {
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
        }

        .admin-header h2 i {
            color: var(--primary-color);
        }

        .admin-actions {
            display: flex;
            gap: 1rem;
        }

        /* Button Styles */
        .btn-primary, .btn-secondary {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.2s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--dark-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary i, .btn-secondary i {
            font-size: 0.9rem;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            position: relative;
            box-shadow: var(--shadow-sm);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert-success {
            background-color: #dcfce7;
            border-left: 4px solid var(--success-color);
            color: #166534;
        }

        .alert-danger {
            background-color: #fee2e2;
            border-left: 4px solid var(--danger-color);
            color: #991b1b;
        }

        .alert ul {
            margin: 0.5rem 0 0 0;
            padding-left: 1.5rem;
        }

        .alert ul li:not(:last-child) {
            margin-bottom: 0.25rem;
        }

        /* Form Styles */
        .admin-form-container {
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            max-width: 800px;
            margin: 0 auto;
        }

        .admin-form {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--dark-color);
            font-size: 1rem;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            width: 100%;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
        }

        select.form-control {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236B7280'%3E%3Cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' clip-rule='evenodd'/%3E%3C/svg%3E");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
        }

        optgroup {
            font-weight: 600;
            color: var(--dark-color);
        }

        optgroup option {
            font-weight: normal;
            padding: 0.5rem;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .admin-actions {
                width: 100%;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .form-actions .btn-primary, 
            .form-actions .btn-secondary {
                width: 100%;
            }
        }

        /* Enhancements */
        .field-hint {
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-top: 0.25rem;
        }

        .form-section {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        /* Toast animation for success */
        @keyframes toastIn {
            0% { transform: translateY(100%); opacity: 0; }
            10% { transform: translateY(0); opacity: 1; }
            90% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(100%); opacity: 0; }
        }

        .toast-container {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            z-index: 1000;
        }

        .toast {
            padding: 1rem 1.5rem;
            background-color: white;
            border-left: 4px solid var(--success-color);
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: toastIn 4s ease-in-out forwards;
            margin-top: 1rem;
        }

        .toast-success i {
            color: var(--success-color);
            font-size: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../partials/sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header">
                <h2><i class="fas fa-calendar-plus"></i> Add New Showtime</h2>
                <div class="admin-actions">
                    <a href="manage_schedules.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Schedule List
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h4 style="margin-top: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-circle"></i> Form is not valid
                </h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <h4 style="margin-top: 0; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle"></i> Success
                </h4>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            
            <div class="admin-form-container">
                <form method="POST" action="" class="admin-form">
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-film"></i> Movie Information
                        </div>
                        <div class="form-group">
                            <label for="movie_id">Movie <span style="color: var(--danger-color);">*</span></label>
                            <select name="movie_id" id="movie_id" class="form-control" required>
                                <option value="">-- Select Movie --</option>
                                <optgroup label="Now Showing">
                                    <?php foreach ($movies as $movie): ?>
                                        <?php if (strtolower($movie['status']) == 'now-showing'): ?>
                                        <option value="<?php echo $movie['id']; ?>" <?php echo ($preselected_movie == $movie['id'] || (isset($_POST['movie_id']) && $_POST['movie_id'] == $movie['id'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($movie['title']); ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="Coming Soon">
                                    <?php foreach ($movies as $movie): ?>
                                        <?php if (strtolower($movie['status']) == 'coming-soon'): ?>
                                        <option value="<?php echo $movie['id']; ?>" <?php echo ($preselected_movie == $movie['id'] || (isset($_POST['movie_id']) && $_POST['movie_id'] == $movie['id'])) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($movie['title']); ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                            <div class="field-hint">Select the movie to be scheduled for screening</div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-tv"></i> Theater Information
                        </div>
                        <div class="form-group">
                            <label for="theater_id">Theater <span style="color: var(--danger-color);">*</span></label>
                            <select name="theater_id" id="theater_id" class="form-control" required>
                                <option value="">-- Select Theater --</option>
                                <?php foreach ($theaters as $theater): ?>
                                <option value="<?php echo $theater['id']; ?>" <?php echo (isset($_POST['theater_id']) && $_POST['theater_id'] == $theater['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($theater['name']) . ' (Capacity: ' . $theater['total_seats'] . ' seats)'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="field-hint">Select the theater where the movie will be shown</div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fas fa-calendar-alt"></i> Screening Schedule
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div class="form-group">
                                <label for="showdate">Show Date <span style="color: var(--danger-color);">*</span></label>
                                <input type="date" name="showdate" id="showdate" class="form-control" 
                                    min="<?php echo date('Y-m-d'); ?>" 
                                    value="<?php echo isset($_POST['showdate']) ? $_POST['showdate'] : date('Y-m-d'); ?>" 
                                    required>
                                <div class="field-hint">Select the date of the movie screening (minimum today)</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="showtime">Show Time <span style="color: var(--danger-color);">*</span></label>
                                <input type="time" name="showtime" id="showtime" class="form-control" 
                                    value="<?php echo isset($_POST['showtime']) ? $_POST['showtime'] : '18:00'; ?>" 
                                    required>
                                <div class="field-hint">Select the start time of the movie screening (24-hour format)</div>
                            </div>
                        </div>
                        <div class="field-hint" style="margin-top: 0.5rem; color: var(--warning-color);">
                            <i class="fas fa-info-circle"></i> Note: The system will check for schedule conflicts within a 2-hour range before and after the selected time.
                        </div>
                    </div>
                    
                    <div class="form-section" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                        <div class="form-section-title">
                            <i class="fas fa-tag"></i> Price Information
                        </div>
                        <div class="form-group">
                            <label for="price">Ticket Price ($) <span style="color: var(--danger-color);">*</span></label>
                            <input type="number" name="price" id="price" class="form-control" 
                                min="10000" step="1000" 
                                value="<?php echo isset($_POST['price']) ? $_POST['price'] : '50000'; ?>" 
                                required>
                            <div class="field-hint">Enter the ticket price in IDR (minimum Rp 10,000)</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Schedule
                        </button>
                        <a href="manage_schedules.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Optional: JavaScript for enhanced interactions -->
    <script>
        // Display toast notification when form is successfully saved
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($success): ?>
            // Scroll to top to show success message
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Auto-hide success alert after 4 seconds
            setTimeout(function() {
                const successAlert = document.querySelector('.alert-success');
                if (successAlert) {
                    successAlert.style.transition = 'opacity 0.5s ease';
                    successAlert.style.opacity = '0';
                    setTimeout(() => successAlert.remove(), 500);
                }
            }, 4000);
            <?php endif; ?>
            
            // Check for conflicts when theater or date/time changes
            const theaterSelect = document.getElementById('theater_id');
            const showDateInput = document.getElementById('showdate');
            const showTimeInput = document.getElementById('showtime');
            
            // Optional: Add real-time validation for future enhancements
        });
    </script>
</body>
</html>