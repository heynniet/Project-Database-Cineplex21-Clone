<?php
include '../config/db.php';
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if there's an active booking with selected seats
if (!isset($_SESSION['booking']) || !isset($_SESSION['seats']) || !isset($_SESSION['showtime_id'])) {
    // Redirect to movie listing page if no booking is in progress
    header("Location: movies.php");
    exit();
}

// Get booking details from session
$movieId = $_SESSION['booking']['movie_id'];
$theaterId = $_SESSION['booking']['theater_id'];
$selectedDate = $_SESSION['booking']['date'];
$selectedTime = $_SESSION['booking']['showtime'];
$selectedFormat = $_SESSION['booking']['format'];
$selectedSeats = $_SESSION['seats'];
$showtimeId = $_SESSION['showtime_id'];
$totalAmount = $_SESSION['total_amount'];

// Get movie details
$movieQuery = "SELECT * FROM movies WHERE id = ?";
$stmt = $conn->prepare($movieQuery);
$stmt->bind_param("i", $movieId);
$stmt->execute();
$movieResult = $stmt->get_result();
$movie = $movieResult->fetch_assoc();
$stmt->close();

// Get theater details
$theaterQuery = "SELECT * FROM theaters WHERE id = ?";
$stmt = $conn->prepare($theaterQuery);
$stmt->bind_param("i", $theaterId);
$stmt->execute();
$theaterResult = $stmt->get_result();
$theater = $theaterResult->fetch_assoc();
$stmt->close();

// Get showtime details
$showtimeQuery = "SELECT * FROM showtimes WHERE id = ?";
$stmt = $conn->prepare($showtimeQuery);
$stmt->bind_param("i", $showtimeId);
$stmt->execute();
$showtimeResult = $stmt->get_result();
$showtime = $showtimeResult->fetch_assoc();
$stmt->close();

// Get seat details
$seatInfo = [];
if (!empty($selectedSeats)) {
    $placeholders = implode(',', array_fill(0, count($selectedSeats), '?'));
    $seatQuery = "SELECT * FROM theater_seats WHERE id IN ($placeholders)";
    $stmt = $conn->prepare($seatQuery);
    
    // Bind parameters dynamically
    $types = str_repeat('i', count($selectedSeats));
    $stmt->bind_param($types, ...$selectedSeats);
    
    $stmt->execute();
    $seatsResult = $stmt->get_result();
    while ($seat = $seatsResult->fetch_assoc()) {
        $seatInfo[] = $seat;
    }
    $stmt->close();
}

// Process payment form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'process_payment') {
    // Validate payment details
    $cardNumber = $_POST['card_number'];
    $expiryDate = $_POST['expiry_date'];
    $cvv = $_POST['cvv'];
    $cardName = $_POST['card_name'];
    
    // Simple validation
    $errors = [];
    if (empty($cardNumber) || strlen(preg_replace('/\D/', '', $cardNumber)) != 16) {
        $errors[] = "Please enter a valid card number";
    }
    if (empty($expiryDate) || !preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiryDate)) {
        $errors[] = "Please enter a valid expiry date (MM/YY)";
    }
    if (empty($cvv) || !preg_match('/^[0-9]{3,4}$/', $cvv)) {
        $errors[] = "Please enter a valid CVV";
    }
    if (empty($cardName)) {
        $errors[] = "Please enter the cardholder name";
    }
    
    // If validation passes, process the booking
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // In a real application, payment processing would happen here
            // For this example, we'll assume payment is successful
            
            // Check if user is logged in, if not create a guest booking
            // For this example, we'll use a hardcoded user ID
            $userId = 1; // In a real app, this would come from the session after login
            
            // Create booking record
            $bookingQuery = "INSERT INTO bookings (user_id, showtime_id, total_amount, status) VALUES (?, ?, ?, 'confirmed')";
            $stmt = $conn->prepare($bookingQuery);
            $stmt->bind_param("iid", $userId, $showtimeId, $totalAmount);
            $stmt->execute();
            $bookingId = $conn->insert_id;
            $stmt->close();
            
            // Add booked seats
            foreach ($selectedSeats as $seatId) {
                $seatPrice = $showtime['price']; // In a real app, prices might vary by seat
                
                $bookedSeatQuery = "INSERT INTO booked_seats (booking_id, seat_id, price) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($bookedSeatQuery);
                $stmt->bind_param("iid", $bookingId, $seatId, $seatPrice);
                $stmt->execute();
                $stmt->close();
            }
            
            // Commit the transaction
            $conn->commit();
            
            // Store booking ID in session for confirmation
            $_SESSION['booking_id'] = $bookingId;
            
            // Clear booking-related session data
            unset($_SESSION['booking']);
            unset($_SESSION['seats']);
            unset($_SESSION['showtime_id']);
            unset($_SESSION['total_amount']);
            
            // Redirect to confirmation page
            header("Location: booking_confirmation.php");
            exit();
            
        } catch (Exception $e) {
            // Rollback the transaction in case of error
            $conn->rollback();
            $errors[] = "An error occurred while processing your booking. Please try again.";
        }
    }
}

include '../views/partials/header.php';
include '../views/partials/navbar.php';
?>

<main class="main-content">
    <div class="container">
        <h1>Payment</h1>
        
        <div class="booking-summary">
            <div class="movie-summary">
                <div class="movie-poster-small">
                    <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
                </div>
                <div class="movie-details-small">
                    <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                    <p><strong>Theater:</strong> <?php echo htmlspecialchars($theater['name']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('l, F j, Y', strtotime($selectedDate)); ?></p>
                    <p><strong>Time:</strong> <?php echo date('g:i A', strtotime($selectedTime)); ?> (<?php echo htmlspecialchars($selectedFormat); ?>)</p>
                    <p><strong>Seats:</strong> 
                        <?php 
                        $seatLabels = [];
                        foreach ($seatInfo as $seat) {
                            $seatLabels[] = $seat['row'] . $seat['number'];
                        }
                        echo implode(', ', $seatLabels);
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="price-summary">
                <div class="price-item">
                    <span>Tickets (<?php echo count($selectedSeats); ?>)</span>
                    <span>$<?php echo number_format($totalAmount, 2); ?></span>
                </div>
                <div class="price-item">
                    <span>Booking Fee</span>
                    <span>$<?php echo number_format(1.50, 2); ?></span>
                </div>
                <div class="price-item total">
                    <span>Total</span>
                    <span>$<?php echo number_format($totalAmount + 1.50, 2); ?></span>
                </div>
            </div>
        </div>
        
        <div class="booking-steps">
            <div class="step completed">
                <span class="step-number">1</span>
                <span class="step-text">Select Date</span>
            </div>
            <div class="step completed">
                <span class="step-number">2</span>
                <span class="step-text">Select Theater</span>
            </div>
            <div class="step completed">
                <span class="step-number">3</span>
                <span class="step-text">Select Seats</span>
            </div>
            <div class="step active">
                <span class="step-number">4</span>
                <span class="step-text">Payment</span>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="payment-form-container">
            <h2>Payment Details</h2>
            
            <form method="post" id="paymentForm">
                <input type="hidden" name="action" value="process_payment">
                
                <div class="form-group">
                    <label for="card_number">Card Number</label>
                    <input type="text" id="card_number" name="card_number" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="text" id="expiry_date" name="expiry_date" class="form-control" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" class="form-control" placeholder="123" maxlength="4">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="card_name">Cardholder Name</label>
                    <input type="text" id="card_name" name="card_name" class="form-control" placeholder="John Doe">
                </div>
                
                <div class="payment-actions">
                    <a href="seat_selection.php" class="btn btn-secondary">Back to Seats</a>
                    <button type="submit" class="btn btn-primary">Pay $<?php echo number_format($totalAmount + 1.50, 2); ?></button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.booking-summary {
    margin-bottom: 30px;
}

.price-summary {
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 5px;
    margin-top: 20px;
}

.price-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.price-item.total {
    font-weight: bold;
    font-size: 1.1em;
    border-top: 1px solid #ddd;
    padding-top: 10px;
    margin-top: 5px;
}

.payment-form-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background-color: #fff;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
}

.form-row {
    display: flex;
    gap: 15px;
}

.form-row .form-group {
    flex: 1;
}

.payment-actions {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.alert-danger {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format card number with spaces
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = '';
        
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        
        e.target.value = formattedValue;
    });
    
    // Format expiry date
    document.getElementById('expiry_date').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        let formattedValue = '';
        
        if (value.length > 0) {
            formattedValue = value.substring(0, 2);
            if (value.length > 2) {
                formattedValue += '/' + value.substring(2, 4);
            }
        }
        
        e.target.value = formattedValue;
    });
    
    // Allow only numbers in CVV
    document.getElementById('cvv').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
});
</script>

<?php include '../views/partials/footer.php'; ?>
<?php
// Close the database connection
$conn->close();
?>