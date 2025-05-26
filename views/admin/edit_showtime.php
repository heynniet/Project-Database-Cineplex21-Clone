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
$pageTitle = "Edit Showtime Schedule - Cineplex21";

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_schedules.php');
    exit;
}

$id = intval($_GET['id']);

// Get showtime data
$showtimeQuery = $conn->prepare("
    SELECT s.*, m.title as movie_title, t.name as theater_name, t.total_seats
    FROM showtimes s
    JOIN movies m ON s.movie_id = m.id
    JOIN theaters t ON s.theater_id = t.id
    WHERE s.id = ?
");
$showtimeQuery->bind_param("i", $id);
$showtimeQuery->execute();
$result = $showtimeQuery->get_result();

if ($result->num_rows === 0) {
    header('Location: manage_schedules.php');
    exit;
}

$showtime = $result->fetch_assoc();

// Get all movies
$moviesQuery = $conn->query("SELECT id, title FROM movies WHERE status = 1 ORDER BY title");
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
    $showdate = trim($_POST['showdate']);
    $showtime_input = trim($_POST['showtime']);
    $price = floatval($_POST['price']);
    $available_seats = intval($_POST['available_seats']);
    
    if ($movie_id <= 0) {
        $errors[] = "Please select a movie";
    }
    
    if ($theater_id <= 0) {
        $errors[] = "Please select a theater";
    }
    
    if (empty($showdate)) {
        $errors[] = "Show date cannot be empty";
    }
    
    if (empty($showtime_input)) { // Bug fix: using showtime_input, not showtime
        $errors[] = "Show time cannot be empty";
    }
    
    if ($price <= 0) {
        $errors[] = "Ticket price must be greater than 0";
    }

    // Check schedule conflicts if there are changes to theater, date, or time
    if (empty($errors) && ($theater_id != $showtime['theater_id'] || $showdate != $showtime['showdate'] || $showtime_input != $showtime['showtime'])) {
        $checkConflict = $conn->prepare("
            SELECT COUNT(*) as conflict_count FROM showtimes 
            WHERE theater_id = ? AND showdate = ? AND 
            (TIME_TO_SEC(TIMEDIFF(?, showtime)) < 7200 OR 
             TIME_TO_SEC(TIMEDIFF(showtime, ?)) < 7200) AND
            id != ?
        ");
        $checkConflict->bind_param("isssi", $theater_id, $showdate, $showtime_input, $showtime_input, $id); // Bug fix: using showtime_input
        $checkConflict->execute();
        $conflictResult = $checkConflict->get_result();
        $conflict = $conflictResult->fetch_assoc();
        
        if ($conflict['conflict_count'] > 0) {
            $errors[] = "There is a scheduling conflict in this theater (within 2 hours range)";
        }
    }
    
    // Get theater capacity if theater changed
    $capacity = $showtime['total_seats'];
    if ($theater_id != $showtime['theater_id']) {
        foreach ($theaters as $theater) {
            if ($theater['id'] == $theater_id) {
                $capacity = $theater['total_seats'];
                break;
            }
        }
        
        // If new theater capacity is less than sold seats
        $sold_seats = $capacity - $available_seats;
        if ($capacity < $sold_seats) {
            $errors[] = "The new theater has a smaller capacity than the number of seats already sold";
        }
    }
    
    // Update data if no errors
    if (empty($errors)) {
        $updateQuery = $conn->prepare("
            UPDATE showtimes 
            SET movie_id = ?, theater_id = ?, showdate = ?, showtime = ?, 
                price = ?, available_seats = ?, created_at = NOW() 
            WHERE id = ?
        ");
        $updateQuery->bind_param("iissdii", $movie_id, $theater_id, $showdate, $showtime_input, $price, $available_seats, $id);
        
        if ($updateQuery->execute()) {
            $success = "Showtime schedule successfully updated!";
            
            // Refresh showtime data
            $showtimeQuery->execute();
            $showtime = $showtimeQuery->get_result()->fetch_assoc();
        } else {
            $errors[] = "Failed to update schedule: " . $conn->error;
        }
    }
}

// Calculate sold seats
$soldSeats = $showtime['total_seats'] - $showtime['available_seats'];
$maxAvailable = $showtime['total_seats'] - $soldSeats;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #e74c3c;
            --accent-color: #3498db;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --info-color: #3498db;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background-color: #f5f7fa;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e6ed;
        }
        
        .admin-header h2 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 600;
            font-size: 24px;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-primary, .btn-secondary, .btn-danger {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }
        
        .btn-secondary {
            background-color: #95a5a6;
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-primary:hover, .btn-secondary:hover, .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary i, .btn-secondary i, .btn-danger i {
            margin-right: 8px;
        }
        
        .admin-form-container {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .admin-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }
        
        .form-text {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border-left: 4px solid transparent;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: var(--success-color);
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: var(--danger-color);
            color: #721c24;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: var(--info-color);
            color: #0c5460;
        }
        
        .alert ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .admin-section {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .admin-section h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: var(--dark-color);
            font-size: 20px;
            font-weight: 600;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .admin-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .admin-table tr:hover {
            background-color: #f9f9f9;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .mt-4 {
            margin-top: 1.5rem;
        }
        
        .movie-details {
            display: flex;
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 25px;
        }
        
        .movie-info {
            flex: 1;
            padding: 20px;
        }
        
        .movie-info h3 {
            margin-top: 0;
            color: var(--dark-color);
            font-size: 22px;
            font-weight: 600;
        }
        
        .movie-meta {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .movie-meta-item {
            margin-right: 20px;
            display: flex;
            align-items: center;
            color: #636e72;
            font-size: 14px;
        }
        
        .movie-meta-item i {
            margin-right: 6px;
            color: var(--accent-color);
        }
        
        .seats-indicator {
            display: flex;
            align-items: center;
            margin-top: 15px;
        }
        
        .seats-progress {
            flex: 1;
            height: 10px;
            background-color: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-right: 15px;
        }
        
        .seats-progress-bar {
            height: 100%;
            background-color: var(--accent-color);
        }
        
        .seats-info {
            white-space: nowrap;
            font-size: 14px;
            color: #636e72;
        }
        
        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h3 {
            margin: 0;
            font-size: 18px;
            color: var(--dark-color);
        }
        
        .booking-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .booking-detail {
            flex: 1;
        }
        
        .booking-detail-title {
            font-size: 13px;
            color: #95a5a6;
            margin-bottom: 5px;
        }
        
        .booking-detail-value {
            font-size: 15px;
            color: var(--dark-color);
            font-weight: 500;
        }
        
        /* Form width adjust */
        @media (max-width: 992px) {
            .admin-form {
                grid-template-columns: 1fr;
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .admin-layout {
                flex-direction: column;
            }
            
            .admin-content {
                padding: 20px;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .admin-actions {
                margin-top: 15px;
            }
            
            .movie-details {
                flex-direction: column;
            }
        }
    /* Animations */
    @keyframes fadeInOut {
        0% { background-color: #d4edda; }
        50% { background-color: #a3d7bb; }
        100% { background-color: #d4edda; }
    }
    
    /* Additional styles for better visual appeal */
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 500;
        text-align: center;
    }
    
    .status-available {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-limited {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-soldout {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>
</head>
<body>

<div class="admin-layout">
    <?php include '../partials/sidebar.php'; ?>
    <!-- Main Content -->
    <div class="admin-content">
        <div class="admin-header">
            <h2><i class="fas fa-edit"></i> Edit Showtime Schedule</h2>
            <div class="admin-actions">
                <a href="manage_schedules.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <!-- Movie Quick Info -->
        <div class="movie-details">
            <div class="movie-info">
                <h3><?php echo htmlspecialchars($showtime['movie_title']); ?></h3>
                <div class="movie-meta">
                    <div class="movie-meta-item">
                        <i class="fas fa-theater-masks"></i> <?php echo htmlspecialchars($showtime['theater_name']); ?>
                    </div>
                    <div class="movie-meta-item">
                        <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($showtime['showdate'])); ?>
                    </div>
                    <div class="movie-meta-item">
                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($showtime['showtime'])); ?>
                    </div>
                    <div class="movie-meta-item">
                        <i class="fas fa-money-bill"></i> Rp <?php echo number_format($showtime['price'], 0, ',', '.'); ?>
                    </div>
                </div>
                
                <div class="seats-indicator">
                    <div class="seats-progress">
                        <div class="seats-progress-bar" style="width: <?php echo ($soldSeats / $showtime['total_seats']) * 100; ?>%"></div>
                    </div>
                    <div class="seats-info">
                        <strong><?php echo $showtime['available_seats']; ?></strong> available out of <strong><?php echo $showtime['total_seats']; ?></strong> seats
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Form -->
        <div class="admin-form-container">
            <form method="POST" action="" class="admin-form">
                <div class="form-group">
                    <label for="movie_id"><i class="fas fa-film"></i> Movie</label>
                    <select name="movie_id" id="movie_id" class="form-control" required>
                        <option value="">-- Select Movie --</option>
                        <?php foreach ($movies as $movie): ?>
                        <option value="<?php echo $movie['id']; ?>" <?php echo ($showtime['movie_id'] == $movie['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($movie['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="theater_id"><i class="fas fa-theater-masks"></i> Theater</label>
                    <select name="theater_id" id="theater_id" class="form-control" required>
                        <option value="">-- Select Theater --</option>
                        <?php foreach ($theaters as $theater): ?>
                        <option value="<?php echo $theater['id']; ?>" <?php echo ($showtime['theater_id'] == $theater['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($theater['name']) . ' (Capacity: ' . $theater['total_seats'] . ')'; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="showdate"><i class="fas fa-calendar-alt"></i> Show Date</label>
                    <input type="date" name="showdate" id="showdate" class="form-control" 
                           value="<?php echo $showtime['showdate']; ?>" 
                           min="<?php echo date('Y-m-d'); ?>"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="showtime"><i class="fas fa-clock"></i> Show Time</label>
                    <input type="time" name="showtime" id="showtime" class="form-control" 
                           value="<?php echo $showtime['showtime']; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="price"><i class="fas fa-tag"></i> Ticket Price (Rp)</label>
                    <input type="number" name="price" id="price" class="form-control" 
                           min="10000" step="1000" 
                           value="<?php echo $showtime['price']; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="available_seats"><i class="fas fa-chair"></i> Available Seats</label>
                    <input type="number" name="available_seats" id="available_seats" class="form-control" 
                           min="<?php echo $soldSeats > 0 ? 0 : 1; ?>" 
                           max="<?php echo $showtime['total_seats']; ?>" 
                           value="<?php echo $showtime['available_seats']; ?>" 
                           required>
                    <p class="form-text">
                        <i class="fas fa-info-circle"></i> Theater capacity: <strong><?php echo $showtime['total_seats']; ?></strong> seats, 
                        Sold seats: <strong><?php echo $soldSeats; ?></strong> seats
                    </p>
                </div>
                
                <div class="form-actions">
                    <a href="manage_schedules.php" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Schedule</button>
                </div>
            </form>
        </div>
        
        <!-- Ticket Booking Information -->
        <?php if ($soldSeats > 0): ?>
        <div class="admin-section">
            <h3><i class="fas fa-ticket-alt"></i> Ticket Booking Information</h3>
            <p class="alert alert-info">
                <i class="fas fa-info-circle"></i> This schedule has <strong><?php echo $soldSeats; ?></strong> seats already booked. 
                Schedule changes will affect tickets that have been purchased.
            </p>
            
            <?php
            // Get booking information
            $bookingsQuery = $conn->prepare("
                SELECT t.id, t.booking_code, u.name as user_name, t.quantity, t.amount, t.booking_date
                FROM tickets t
                JOIN users u ON t.user_id = u.id
                WHERE t.showtime_id = ?
                ORDER BY t.booking_date DESC
            ");
            $bookingsQuery->bind_param("i", $id);
            $bookingsQuery->execute();
            $bookingsResult = $bookingsQuery->get_result();
            ?>
            
            <?php if ($bookingsResult->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> Booking Code</th>
                            <th><i class="fas fa-user"></i> User Name</th>
                            <th><i class="fas fa-ticket-alt"></i> Number of Tickets</th>
                            <th><i class="fas fa-money-bill"></i> Total Price</th>
                            <th><i class="fas fa-calendar-check"></i> Booking Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $bookingsResult->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $booking['booking_code']; ?></strong></td>
                            <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                            <td><?php echo $booking['quantity']; ?> tickets</td>
                            <td>Rp <?php echo number_format($booking['amount'], 0, ',', '.'); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($booking['booking_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No ticket booking data for this schedule yet.
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-highlight the form after successful update
    <?php if ($success): ?>
    setTimeout(function() {
        document.querySelector('.alert-success').style.animation = 'fadeInOut 2s';
    }, 300);
    <?php endif; ?>
    
    // Theater change handler - update maximum available seats
    const theaterSelect = document.getElementById('theater_id');
    const availableSeatsInput = document.getElementById('available_seats');
    
    theaterSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const theaterInfo = selectedOption.text;
        const capacityMatch = theaterInfo.match(/Capacity: (\d+)/);
        
        if (capacityMatch && capacityMatch[1]) {
            const maxCapacity = parseInt(capacityMatch[1]);
            const soldSeats = <?php echo $soldSeats; ?>;
            
            // Set max attribute
            availableSeatsInput.max = maxCapacity;
            
            // If sold seats > 0, we need to make sure available seats reflects that
            if (soldSeats > 0) {
                const maxAvailable = maxCapacity - soldSeats;
                if (maxAvailable < 0) {
                    // Alert user about insufficient capacity
                    alert('Warning: The selected theater has a smaller capacity than the number of seats already sold!');
                    availableSeatsInput.value = 0;
                } else {
                    availableSeatsInput.value = maxAvailable;
                }
            } else {
                availableSeatsInput.value = maxCapacity;
            }
        }
    });
});

// Date validation to prevent past dates
const showdateInput = document.getElementById('showdate');
const today = new Date().toISOString().split('T')[0];
showdateInput.min = today;