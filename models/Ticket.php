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
include '../views/partials/navbar.php';
include '../config/db.php';

// Get movie ID from URL
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$showtime_id = isset($_GET['showtime_id']) ? intval($_GET['showtime_id']) : 0;

// If no movie ID and not at step 2 with showtime_id, redirect to home
if ($movie_id === 0 && !($step === 2 && $showtime_id > 0)) {
    header('Location: index.php');
    exit;
}

// Step 1 - Get movie details for showtime selection
if ($step === 1) {
    // Get movie details
    $movieQuery = $db->prepare("SELECT * FROM movies WHERE id = :id");
    $movieQuery->execute([':id' => $movie_id]);
    $movie = $movieQuery->fetch(PDO::FETCH_ASSOC);

    // If movie not found, redirect to home
    if (!$movie) {
        header('Location: index.php');
        exit;
    }

    // Get theaters showing this movie
    $theaterQuery = $db->prepare("SELECT DISTINCT t.id, t.name, t.location
        FROM theaters t
        JOIN showtimes s ON t.id = s.theater_id
        WHERE s.movie_id = :movie_id AND s.status = 'active'");
    $theaterQuery->execute([':movie_id' => $movie_id]);
    $theaters = $theaterQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get dates for the next 7 days
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("+$i days"));
        $formattedDate = date('D, M j', strtotime("+$i days"));
        $dates[$date] = $formattedDate;
    }

    // Process form submission
    $selected_theater = isset($_POST['theater']) ? intval($_POST['theater']) : 0;
    $selected_date = isset($_POST['date']) ? $_POST['date'] : '';
    $selected_time = isset($_POST['time']) ? $_POST['time'] : '';

    // If form submitted and all selections made, move to seat selection
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selected_theater && $selected_date && $selected_time) {
        // Get showtimes based on selection
        $showtimeQuery = $db->prepare("SELECT id FROM showtimes
            WHERE movie_id = :movie_id
            AND theater_id = :theater_id
            AND showdate = :date
            AND showtime = :time
            AND status = 'active'");
        $showtimeQuery->execute([
            ':movie_id' => $movie_id,
            ':theater_id' => $selected_theater,
            ':date' => $selected_date,
            ':time' => $selected_time,
        ]);
        $showtime = $showtimeQuery->fetch(PDO::FETCH_ASSOC);
        
        if ($showtime) {
            $showtime_id = $showtime['id'];
            $redirectUrl = "Ticket.php?step=2&showtime_id=" . $showtime_id;
            header("Location: " . $redirectUrl);
            exit;
        }
    }
} else if ($step === 2) {
    // Step 2 - Get showtime details for seat selection
    if ($showtime_id === 0) {
        header('Location: index.php');
        exit;
    }

    // Get showtime details
    $showtimeQuery = $db->prepare("SELECT s.*, m.id as movie_id, m.title as movie_title, 
        m.poster_path, m.rating, m.genre, m.duration,
        t.name as theater_name, t.location as theater_location, t.total_seats,
        CONCAT(s.showdate, ' ', s.showtime) as full_showtime
        FROM showtimes s
        JOIN movies m ON s.movie_id = m.id
        JOIN theaters t ON s.theater_id = t.id
        WHERE s.id = :id AND s.status = 'active'");
    $showtimeQuery->execute([':id' => $showtime_id]);
    $showtime = $showtimeQuery->fetch(PDO::FETCH_ASSOC);

    // If showtime not found, redirect to home
    if (!$showtime) {
        header('Location: index.php');
        exit;
    }

    // Get occupied seats
    $occupiedSeatsQuery = $db->prepare("SELECT seat_number FROM booking_seats b
        JOIN bookings bs ON b.booking_id = bs.id
        WHERE bs.showtime_id = :showtime_id AND bs.status != 'cancelled'");
    $occupiedSeatsQuery->execute([':showtime_id' => $showtime_id]);
    $occupiedSeats = $occupiedSeatsQuery->fetchAll(PDO::FETCH_COLUMN);

    // Process seat selection and booking
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_seats'])) {
        $selected_seats = explode(',', $_POST['selected_seats']);

        // Get price info
        $price = $showtime['price'] ?? 50000; // Default price if not set
        $total_price = count($selected_seats) * $price;

        // Insert booking (in real app, you'd use transaction here)
        try {
            $db->beginTransaction();

            // Create booking
            $bookingQuery = $db->prepare("INSERT INTO bookings (showtime_id, user_id, booking_date, total_amount, status)
                VALUES (:showtime_id, :user_id, NOW(), :total, 'pending')");
            $bookingQuery->execute([
                ':showtime_id' => $showtime_id,
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':total' => $total_price
            ]);
            $booking_id = $db->lastInsertId();

            // Insert seat bookings
            foreach ($selected_seats as $seat) {
                $seatBookingQuery = $db->prepare("INSERT INTO booking_seats (booking_id, seat_number) VALUES (:booking_id, :seat)");
                $seatBookingQuery->execute([
                    ':booking_id' => $booking_id,
                    ':seat' => $seat
                ]);
            }

            // Update available seats in showtime
            $updateShowtimeQuery = $db->prepare("UPDATE showtimes SET available_seats = available_seats - :count 
                WHERE id = :showtime_id");
            $updateShowtimeQuery->execute([
                ':count' => count($selected_seats),
                ':showtime_id' => $showtime_id
            ]);

            $db->commit();

            // Redirect to confirmation page
            header("Location: booking-confirmation.php?id=$booking_id");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = "Booking failed: " . $e->getMessage();
        }
    }
}
?>

<div class="main-content">
    <div class="container">
        <?php if ($step === 1): ?>
        <!-- STEP 1: SHOWTIME SELECTION -->
        <!-- Booking Header with Movie Info -->
        <div class="booking-header">
            <h1>Book Tickets</h1>
            <div class="movie-info-banner">
                <div class="movie-poster-small">
                <?php
                $posterFilename = htmlspecialchars($movie['poster_path']);
                $posterPath = '../uploads/posters/' . $posterFilename;
                $posterURL = '/Cineplex21/uploads/posters/' . $posterFilename;

                if (file_exists($posterPath) && !empty($posterFilename)) {
                    echo "<img src='$posterURL' alt='Poster'>";
                } else {
                    echo "<img src='/Cineplex21/public/assets/images/default-poster.jpg' alt='Default Poster'>";
                }
                ?>
                </div>
                <div class="movie-details">
                    <h2><?= htmlspecialchars($movie['title']) ?></h2>
                    <div class="movie-meta">
                        <span class="rating"><?= htmlspecialchars($movie['rating']) ?></span>
                        <span class="duration"><i class="fas fa-clock"></i> <?= htmlspecialchars($movie['duration'] ?? '') ?> min</span>
                        <span class="genre"><i class="fas fa-film"></i> <?= htmlspecialchars($movie['genre']) ?></span>
                        <span class="release"><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($movie['release_date'] ?? '') ?></span>
                    </div>
                    <div class="movie-description">
                        <?= htmlspecialchars($movie['description'] ?? 'No description available.') ?>
                    </div>
                    <div class="movie-tags">
                        <?php 
                        $genres = explode(',', $movie['genre']);
                        foreach($genres as $genre): ?>
                        <span class="movie-tag"><?= trim(htmlspecialchars($genre)) ?></span>
                        <?php endforeach; ?>
                        <span class="movie-tag"><?= htmlspecialchars($movie['language'] ?? 'English') ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="booking-process">
            <!-- Booking steps indicator -->
            <div class="booking-steps">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Select Showtime</div>
                </div>
                <div class="step-connector"></div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Choose Seats</div>
                </div>
                <div class="step-connector"></div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Payment</div>
                </div>
            </div>

            <!-- Step 1: Select showtime -->
            <div class="booking-section">
                <form id="showtimeForm" method="POST" action="">
                    <div class="booking-selection">
                        <!-- Theater Selection -->
                        <div class="selection-group theater-selection">
                            <h3 id="select-theater-heading">Select Theater</h3>
                            <div class="selection-options theater-options">
                                <?php foreach($theaters as $theater): ?>
                                <div class="option">
                                    <input type="radio" name="theater" id="theater<?= $theater['id'] ?>" 
                                        value="<?= $theater['id'] ?>" 
                                        <?= ($selected_theater == $theater['id']) ? 'checked' : '' ?> required>
                                    <label for="theater<?= $theater['id'] ?>">
                                        <div class="option-title"><?= htmlspecialchars($theater['name']) ?></div>
                                        <div class="option-subtitle"><?= htmlspecialchars($theater['location']) ?></div>
                                        <div class="option-icon"><i class="fas fa-check-circle"></i></div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Date Selection -->
                        <div class="selection-group date-selection">
                            <h3 id="select-date-heading">Select Date</h3>
                            <div class="selection-options date-options">
                                <?php foreach($dates as $dateValue => $dateLabel): ?>
                                <div class="option">
                                    <input type="radio" name="date" id="date<?= $dateValue ?>" 
                                           value="<?= $dateValue ?>" 
                                           <?= ($selected_date == $dateValue) ? 'checked' : '' ?> required>
                                    <label for="date<?= $dateValue ?>">
                                        <div class="option-title"><?= $dateLabel ?></div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Time Selection -->
                        <div class="selection-group time-selection">
                        <h3 id="select-time-heading">Select Time</h3>
                            <div class="selection-options time-options">
                                <?php 
                                if ($selected_theater && $selected_date) {
                                    // Get available times for this theater and date
                                    $timeQuery = $db->prepare("SELECT DISTINCT showtime as time 
                                                            FROM showtimes 
                                                            WHERE movie_id = :movie_id 
                                                            AND theater_id = :theater_id 
                                                            AND showdate = :date
                                                            ORDER BY time");
                                    $timeQuery->execute([
                                        ':movie_id' => $movie_id,
                                        ':theater_id' => $selected_theater,
                                        ':date' => $selected_date
                                    ]);
                                    $times = $timeQuery->fetchAll(PDO::FETCH_COLUMN);
                                    
                                    if (count($times) > 0) {
                                        foreach($times as $time) {
                                            $formattedTime = date('H:i', strtotime($time));
                                            ?>
                                            <div class="option">
                                                <input type="radio" name="time" id="time<?= $formattedTime ?>" 
                                                       value="<?= $time ?>" 
                                                       <?= ($selected_time == $time) ? 'checked' : '' ?> required>
                                                <label for="time<?= $formattedTime ?>">
                                                    <div class="option-title"><?= $formattedTime ?></div>
                                                </label>
                                            </div>
                                            <?php
                                        }
                                    } else {
                                        echo "<div class='no-times'>No showtimes available for this selection.</div>";
                                    }
                                } else {
                                    echo "<div class='select-prompt'>Please select a theater and date first.</div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="booking-actions">
                        <button type="submit" class="btn-primary">Continue to Seat Selection</button>
                    </div>
                </form>
                
                <!-- Live update script for time options -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const theaterInputs = document.querySelectorAll('input[name="theater"]');
                    const dateInputs = document.querySelectorAll('input[name="date"]');
                    
                    function updateTimeOptions() {
                        const selectedTheater = document.querySelector('input[name="theater"]:checked')?.value;
                        const selectedDate = document.querySelector('input[name="date"]:checked')?.value;
                        
                        if (selectedTheater && selectedDate) {
                            // Use AJAX to get updated time options
                            const xhr = new XMLHttpRequest();
                            xhr.open('GET', `get-showtimes.php?movie_id=<?= $movie_id ?>&theater_id=${selectedTheater}&date=${selectedDate}`, true);
                            
                            xhr.onload = function() {
                                if (this.status === 200) {
                                    document.querySelector('.time-options').innerHTML = this.responseText;
                                }
                            };
                            
                            xhr.send();
                        }
                    }
                    
                    theaterInputs.forEach(input => {
                        input.addEventListener('change', updateTimeOptions);
                    });
                    
                    dateInputs.forEach(input => {
                        input.addEventListener('change', updateTimeOptions);
                    });
                });
                </script>
            </div>
            
        <?php elseif ($step === 2): ?>
        <!-- STEP 2: SEAT SELECTION -->
        <!-- Movie Info Header -->
        <div class="booking-header">
            <h1>Select Your Seats</h1>
            <div class="movie-info-banner">
                <div class="movie-poster-small">
                <?php
                $posterFilename = htmlspecialchars($movie['poster_path']);
                $posterPath = '../uploads/posters/' . $posterFilename;
                $posterURL = '/Cineplex21/uploads/posters/' . $posterFilename;

                if (file_exists($posterPath) && !empty($posterFilename)) {
                    echo "<img src='$posterURL' alt='Poster'>";
                } else {
                    echo "<img src='/Cineplex21/public/assets/images/default-poster.jpg' alt='Default Poster'>";
                }
                ?>
                </div>
                <div class="movie-details">
                    <h2><?= htmlspecialchars($showtime['movie_title']) ?></h2>
                    <div class="movie-meta">
                        <span class="rating"><?= htmlspecialchars($showtime['rating']) ?></span>
                        <span class="duration"><i class="fas fa-clock"></i> <?= htmlspecialchars($showtime['duration']) ?> min</span>
                        <span class="release"><i class="fas fa-calendar-alt"></i> <?= date('D, M j', strtotime($showtime['showdate'])) ?> at <?= date('H:i', strtotime($showtime['showtime'])) ?></span>
                    </div>
                    <div class="booking-details">
                        <div class="detail-item">
                            <i class="fas fa-building"></i> <?= htmlspecialchars($showtime['theater_name']) ?>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($showtime['theater_location']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking steps indicator -->
        <div class="booking-steps">
            <div class="step completed">
                <div class="step-number"><i class="fas fa-check"></i></div>
                <div class="step-label">Select Showtime</div>
            </div>
            <div class="step-connector"></div>
            <div class="step active">
                <div class="step-number">2</div>
                <div class="step-label">Choose Seats</div>
            </div>
            <div class="step-connector"></div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-label">Payment</div>
            </div>
        </div>

        <!-- Seat Selection Section -->
        <div class="seat-selection-container">
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?= $error_message ?></div>
            <?php endif; ?>
            
            <!-- Screen Area -->
            <div class="screen-container">
                <div class="screen">
                    <span>SCREEN</span>
                </div>
            </div>
            
            <!-- Seat Legend -->
            <div class="seat-legend">
                <div class="legend-item">
                    <div class="seat available"></div>
                    <span>Available</span>
                </div>
                <div class="legend-item">
                    <div class="seat selected"></div>
                    <span>Selected</span>
                </div>
                <div class="legend-item">
                    <div class="seat occupied"></div>
                    <span>Occupied</span>
                </div>
            </div>
            
            <!-- Seating Layout -->
            <div class="seating-area">
                <?php
                // Define seating layout
                $sections = [
                    'A' => ['rows' => ['A', 'B', 'C', 'D'], 'seats_per_row' => 14, 'class' => 'regular'],
                    'B' => ['rows' => ['E', 'F', 'G', 'H'], 'seats_per_row' => 16, 'class' => 'premium'],
                    'C' => ['rows' => ['I', 'J', 'K'], 'seats_per_row' => 18, 'class' => 'vip']
                ];
                
                foreach ($sections as $section_name => $section) {
                    echo "<div class='section section-{$section['class']}'>";
                    
                    foreach ($section['rows'] as $row) {
                        echo "<div class='seat-row'>";
                        echo "<div class='row-label'>{$row}</div>";
                        
                        echo "<div class='seats'>";
                        // Add left spacing for visual effect
                        $spacing = floor((18 - $section['seats_per_row']) / 2);
                        for ($i = 0; $i < $spacing; $i++) {
                            echo "<div class='seat-spacer'></div>";
                        }
                        
                        // Generate seats
                        for ($i = 1; $i <= $section['seats_per_row']; $i++) {
                            $seatNumber = $row . $i;
                            $seatClass = in_array($seatNumber, $occupiedSeats) ? 'occupied' : 'available';
                            
                            // Add a center aisle
                            if ($i === ceil($section['seats_per_row'] / 2)) {
                                echo "<div class='seat-spacer aisle'></div>";
                            }
                            
                            echo "<div class='seat {$seatClass} {$section['class']}' data-seat='{$seatNumber}'>{$i}</div>";
                        }
                        
                        // Add right spacing
                        for ($i = 0; $i < $spacing; $i++) {
                            echo "<div class='seat-spacer'></div>";
                        }
                        echo "</div>";
                        
                        echo "<div class='row-label'>{$row}</div>";
                        echo "</div>";
                    }
                    
                    echo "</div>";
                    
                    // Add spacing between sections
                    if ($section_name !== 'C') {
                        echo "<div class='section-divider'></div>";
                    }
                }
                ?>
            </div>
            
            <!-- Booking Form -->
            <form id="seatBookingForm" method="POST">
                <input type="hidden" name="selected_seats" id="selectedSeatsInput" value="">
                
                <div class="booking-summary">
                    <div class="summary-left">
                        <h3>Your Selection</h3>
                        <div class="selection-details">
                            <div class="detail-row">
                                <span class="label">Selected Seats:</span>
                                <span class="value" id="selectedSeatsDisplay">None</span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Seat Count:</span>
                                <span class="value" id="seatCount">0</span>
                            </div>
                            <div class="detail-row total">
                                <span class="label">Total Amount:</span>
                                <span class="value" id="totalAmount">Rp 0</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="summary-right">
                        <h3>Customer Details</h3>
                        <div class="form-group">
                            <label for="customerName">Name</label>
                            <input type="text" name="customer_name" id="customerName" required>
                        </div>
                        <div class="form-group">
                            <label for="customerEmail">Email</label>
                            <input type="email" name="customer_email" id="customerEmail" required>
                        </div>
                    </div>
                </div>
                
                <div class="booking-actions">
                    <a href="Ticket.php?id=<?= $showtime['movie_id'] ?>&step=1" class="btn-secondary">Back to Showtimes</a>
                    <button type="submit" id="bookButton" class="btn-primary" disabled>Proceed to Payment</button>
                </div>
            </form>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const seats = document.querySelectorAll('.seat.available');
            const selectedSeatsInput = document.getElementById('selectedSeatsInput');
            const selectedSeatsDisplay = document.getElementById('selectedSeatsDisplay');
            const seatCountDisplay = document.getElementById('seatCount');
            const totalAmountDisplay = document.getElementById('totalAmount');
            const bookButton = document.getElementById('bookButton');
            
            // Set ticket price based on section
            const prices = {
                'regular': <?= $showtime['price'] ?? 50000 ?>,
                'premium': <?= ($showtime['price'] ?? 50000) * 1.25 ?>,
                'vip': <?= ($showtime['price'] ?? 50000) * 1.5 ?>
            };
            
            let selectedSeats = [];
            let totalAmount = 0;
            
            seats.forEach(seat => {
                seat.addEventListener('click', function() {
                    const seatNumber = this.getAttribute('data-seat');
                    const seatClass = this.classList.contains('premium') ? 'premium' : 
                                    (this.classList.contains('vip') ? 'vip' : 'regular');
                    const seatPrice = prices[seatClass];
                    
                    if (this.classList.contains('selected')) {
                        // Deselect seat
                        this.classList.remove('selected');
                        selectedSeats = selectedSeats.filter(s => s !== seatNumber);
                        totalAmount -= seatPrice;
                    } else {
                        // Select seat
                        this.classList.add('selected');
                        selectedSeats.push(seatNumber);
                        totalAmount += seatPrice;
                    }
                    
                    // Sort seats alphabetically
                    selectedSeats.sort();
                    
                    // Update form and display
                    selectedSeatsInput.value = selectedSeats.join(',');
                    selectedSeatsDisplay.textContent = selectedSeats.length > 0 ? selectedSeats.join(', ') : 'None';
                    seatCountDisplay.textContent = selectedSeats.length;
                    totalAmountDisplay.textContent = 'Rp ' + totalAmount.toLocaleString('id-ID');
                    
                    // Enable/disable book button
                    bookButton.disabled = selectedSeats.length === 0;
                });
            });
        });
        </script>
        <?php endif; ?>
    </div>
</div>

<?php include '../views/partials/footer.php'; ?>