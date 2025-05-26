<?php
// Enable error reporting for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include header files
include '../views/partials/header.php';

// Check login status before showing navbar
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Include navbar with login status
include '../config/db.php';

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no booking ID, redirect to home
if ($booking_id === 0) {
    header('Location: index.php');
    exit;
}

// Get booking details
$bookingQuery = $db->prepare("SELECT b.*, s.movie_id, s.theater_id, s.showdate, s.showtime, s.price as base_price,
    m.title as movie_title, m.poster_path, m.rating, m.genre, m.duration,
    t.name as theater_name, t.location as theater_location
    FROM bookings b
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN theaters t ON s.theater_id = t.id
    WHERE b.id = :id");
$bookingQuery->execute([':id' => $booking_id]);
$booking = $bookingQuery->fetch(PDO::FETCH_ASSOC);

// If booking not found, redirect to home
if (!$booking) {
    header('Location: index.php');
    exit;
}

// Get booked seats
$seatsQuery = $db->prepare("SELECT seat_number FROM booking_seats WHERE booking_id = :booking_id");
$seatsQuery->execute([':booking_id' => $booking_id]);
$seats = $seatsQuery->fetchAll(PDO::FETCH_COLUMN);

// Sort seats alphabetically
sort($seats);

// Generate QR code data
$qrData = json_encode([
    'booking_id' => $booking_id,
    'movie' => $booking['movie_title'],
    'date' => $booking['showdate'],
    'time' => $booking['showtime'],
    'theater' => $booking['theater_name'],
    'seats' => implode(', ', $seats)
]);
$qrCode = urlencode($qrData);

// Generate a unique booking reference
$bookingReference = strtoupper(substr(md5($booking_id . $booking['booking_date']), 0, 8));

// Format date and time
$showDate = date('l, F j, Y', strtotime($booking['showdate']));
$showTime = date('h:i A', strtotime($booking['showtime']));
?>

<div class="main-content">
    <div class="container">
        <!-- Booking Confirmation Header -->
        <div class="booking-header">
            <h1>Booking Confirmation</h1>
            <div class="booking-status success">
                <i class="fas fa-check-circle"></i>
                <span>Your booking has been confirmed!</span>
            </div>
        </div>

        <!-- Booking steps indicator -->
        <div class="booking-steps">
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <div class="step-label">Select Showtime</div>
            </div>
            <div class="step-connector"></div>
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <div class="step-label">Choose Seats</div>
            </div>
            <div class="step-connector"></div>
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <div class="step-label">Payment</div>
            </div>
        </div>

        <!-- Confirmation Content -->
        <div class="confirmation-container">
            <!-- Booking Details Card -->
            <div class="confirmation-card">
                <div class="card-header">
                    <h2>Booking Details</h2>
                    <div class="booking-reference">
                        <span>Booking Reference:</span>
                        <strong><?= $bookingReference ?></strong>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="movie-info-banner">
                        <div class="movie-poster-small">
                            <?php
                            // Fix poster path to match structure used in add_movie
                            $posterPath = '../uploads/posters/' . htmlspecialchars($booking['poster_path']);
                            if (file_exists($posterPath)): ?>
                                <img src="<?= $posterPath ?>" alt="<?= htmlspecialchars($booking['movie_title']) ?> Poster">
                            <?php else: ?>
                                <img src="../../assets/images/default-poster.jpg" alt="Default Poster">
                            <?php endif; ?>
                        </div>
                        <div class="movie-details">
                            <h2><?= htmlspecialchars($booking['movie_title']) ?></h2>
                            <div class="movie-meta">
                                <span class="rating"><?= htmlspecialchars($booking['rating']) ?></span>
                                <span class="duration"><i class="fas fa-clock"></i> <?= htmlspecialchars($booking['duration']) ?> min</span>
                            </div>
                            <div class="booking-details">
                                <div class="detail-item">
                                    <i class="fas fa-calendar-alt"></i> <?= $showDate ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-clock"></i> <?= $showTime ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-building"></i> <?= htmlspecialchars($booking['theater_name']) ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($booking['theater_location']) ?>
                                </div>
                                <div class="detail-item">
                                    <i class="fas fa-couch"></i> Seats: <?= htmlspecialchars(implode(', ', $seats)) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- QR Code and Ticket Card -->
            <div class="confirmation-card ticket-card">
                <div class="card-header">
                    <h2>Your E-Ticket</h2   >
                    <div class="ticket-actions">
                        <a href="/Cineplex21/views/customer/download_ticket.php?id=<?= $booking_id ?>" class="btn-action" target="_blank">
                        <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Payment Summary Card -->
            <div class="confirmation-card payment-card">
                <div class="card-header">
                    <h2>Payment Summary</h2>
                    <div class="payment-status">
                        <span class="status-indicator paid">Paid</span>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="payment-details">
                        <?php
                        // Calculate prices based on seats
                        $regularSeats = count(array_filter($seats, function($seat) {
                            return in_array(substr($seat, 0, 1), ['A', 'B', 'C', 'D']);
                        }));
                        
                        $premiumSeats = count(array_filter($seats, function($seat) {
                            return in_array(substr($seat, 0, 1), ['E', 'F', 'G', 'H']);
                        }));
                        
                        $vipSeats = count(array_filter($seats, function($seat) {
                            return in_array(substr($seat, 0, 1), ['I', 'J', 'K']);
                        }));
                        
                        $basePrice = $booking['base_price'] ?? 50000;
                        $premiumPrice = $basePrice * 1.25;
                        $vipPrice = $basePrice * 1.5;
                        
                        $regularTotal = $regularSeats * $basePrice;
                        $premiumTotal = $premiumSeats * $premiumPrice;
                        $vipTotal = $vipSeats * $vipPrice;
                        
                        $subtotal = $regularTotal + $premiumTotal + $vipTotal;
                        $tax = $subtotal * 0.1; // 10% tax
                        $total = $subtotal + $tax;
                        ?>
                        
                        <div class="payment-item">
                            <span class="item-name">Regular Seats (<?= $regularSeats ?>)</span>
                            <span class="item-price">Rp <?= number_format($regularTotal, 0, ',', '.') ?></span>
                        </div>
                        
                        <?php if ($premiumSeats > 0): ?>
                        <div class="payment-item">
                            <span class="item-name">Premium Seats (<?= $premiumSeats ?>)</span>
                            <span class="item-price">Rp <?= number_format($premiumTotal, 0, ',', '.') ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($vipSeats > 0): ?>
                        <div class="payment-item">
                            <span class="item-name">VIP Seats (<?= $vipSeats ?>)</span>
                            <span class="item-price">Rp <?= number_format($vipTotal, 0, ',', '.') ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="payment-item subtotal">
                            <span class="item-name">Subtotal</span>
                            <span class="item-price">Rp <?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="payment-item">
                            <span class="item-name">Tax (10%)</span>
                            <span class="item-price">Rp <?= number_format($tax, 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="payment-item total">
                            <span class="item-name">Total</span>
                            <span class="item-price">Rp <?= number_format($total, 0, ',', '.') ?></span>
                        </div>
                        
                        <div class="payment-date">
                            <span class="date-label">Payment Date:</span>
                            <span class="date-value"><?= date('F j, Y H:i', strtotime($booking['booking_date'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions Footer -->
            <div class="confirmation-actions">
                <a href="../index.php" class="btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
                <a href="Movies.php" class="btn-primary">
                    <i class="fas fa-film"></i> Browse More Movies
                </a>
            </div>
        </div>
    </div>
</div>