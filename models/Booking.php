<?php
include '../config/db.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if there's a booking ID in the session
if (!isset($_SESSION['booking_id'])) {
    // Redirect to movie listing page if no booking was made
    header("Location: movies.php");
    exit();
}

$bookingId = $_SESSION['booking_id'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get booking details
$bookingQuery = "SELECT b.*, s.movie_id, s.theater_id, s.date, s.time, s.format 
                FROM bookings b
                JOIN showtimes s ON b.showtime_id = s.id
                WHERE b.id = ?";
$stmt = $conn->prepare($bookingQuery);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$bookingResult = $stmt->get_result();
$booking = $bookingResult->fetch_assoc();
$stmt->close();

if (!$booking) {
    // Booking not found
    header("Location: movies.php");
    exit();
}

// Get movie details
$movieId = $booking['movie_id'];
$movieQuery = "SELECT * FROM movies WHERE id = ?";
$stmt = $conn->prepare($movieQuery);
$stmt->bind_param("i", $movieId);
$stmt->execute();
$movieResult = $stmt->get_result();
$movie = $movieResult->fetch_assoc();
$stmt->close();

// Get theater details
$theaterId = $booking['theater_id'];
$theaterQuery = "SELECT * FROM theaters WHERE id = ?";
$stmt = $conn->prepare($theaterQuery);
$stmt->bind_param("i", $theaterId);
$stmt->execute();
$theaterResult = $stmt->get_result();
$theater = $theaterResult->fetch_assoc();
$stmt->close();

// Get booked seats
$seatQuery = "SELECT ts.row, ts.number, ts.seat_type, bs.price 
             FROM booked_seats bs
             JOIN theater_seats ts ON bs.seat_id = ts.id
             WHERE bs.booking_id = ?";
$stmt = $conn->prepare($seatQuery);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$seatsResult = $stmt->get_result();
$seats = [];
while ($seat = $seatsResult->fetch_assoc()) {
    $seats[] = $seat;
}
$stmt->close();

// Generate a confirmation code
$confirmationCode = strtoupper(substr(md5($bookingId), 0, 6));

// Clear the booking ID from session as we're done with it
unset($_SESSION['booking_id']);

include '../views/partials/header.php';
include '../views/partials/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <div class="confirmation-container">
            <div class="confirmation-header">
                <i class="fas fa-check-circle"></i>
                <h1>Booking Confirmed!</h1>
                <p>Your tickets have been booked successfully.</p>
            </div>
            
            <div class="confirmation-details">
                <div class="confirmation-code">
                    <h3>Confirmation Code</h3>
                    <div class="code"><?php echo $confirmationCode; ?></div>
                </div>
                
                <div class="booking-info">
                    <div class="movie-poster-small">
                        <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                    </div>
                    <div class="booking-details">
                        <h2><?php echo htmlspecialchars($movie['title']); ?></h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="label">Date</span>
                                <span class="value"><?php echo date('l, F j, Y', strtotime($booking['date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Time</span>
                                <span class="value"><?php echo date('g:i A', strtotime($booking['time'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Format</span>
                                <span class="value"><?php echo htmlspecialchars($booking['format']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Theater</span>
                                <span class="value"><?php echo htmlspecialchars($theater['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Seats</span>
                                <span class="value">
                                    <?php 
                                    $seatLabels = [];
                                    foreach ($seats as $seat) {
                                        $seatLabels[] = $seat['row'] . $seat['number'];
                                    }
                                    echo implode(', ', $seatLabels);
                                    ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="label">Total Amount</span>
                                <span class="value">$<?php echo number_format($booking['total_amount'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="barcode-container">
                    <div class="barcode">
                        <img src="/api/placeholder/300/80" alt="Barcode">
                    </div>
                    <p class="barcode-text">Please show this confirmation or provide your confirmation code at the theater</p>
                </div>
                
                <div class="confirmation-actions">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print"></i> Print Tickets
                    </button>
                    <a href="movies.php" class="btn btn-secondary">
                        <i class="fas fa-film"></i> Browse More Movies
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
.confirmation-container {
    max-width: 800px;
    margin: 30px auto;
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.confirmation-header {
    background-color: #4CAF50;
    color: white;
    padding: 30px;
    text-align: center;
}

.confirmation-header i {
    font-size: 48px;
    margin-bottom: 15px;
}

.confirmation-header h1 {
    margin: 0 0 10px;
}

.confirmation-header p {
    margin: 0;
    opacity: 0.9;
}

.confirmation-details {
    padding: 30px;
}

.confirmation-code {
    text-align: center;
    margin-bottom: 30px;
}

.confirmation-code h3 {
    margin-bottom: 10px;
    color: #555;
}

.confirmation-code .code {
    font-size: 32px;
    font-weight: bold;
    letter-spacing: 5px;
    color: #333;
}

.booking-info {
    display: flex;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.movie-poster-small {
    width: 120px;
    margin-right: 20px;
}

.movie-poster-small img {
    width: 100%;
    border-radius: 5px;
}

.booking-details {
    flex: 1;
}

.booking-details h2 {
    margin-top: 0;
    margin-bottom: 15px;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-item .label {
    color: #777;
    font-size: 14px;
    margin-bottom: 5px;
}

.info-item .value {
    font-weight: bold;
}

.barcode-container {
    text-align: center;
    margin-bottom: 30px;
}

.barcode img {
    max-width: 300px;
    height: auto;
}

.barcode-text {
    margin-top: 10px;
    color: #666;
    font-size: 14px;
}

.confirmation-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
}

@media print {
    .navbar, .footer, .confirmation-actions {
        display: none;
    }
    
    body {
        background-color: white;
    }
    
    .confirmation-container {
        box-shadow: none;
    }
}
</style>

<?php include '../views/partials/footer.php'; ?>
<?php
// Close the database connection
$conn->close();
?>