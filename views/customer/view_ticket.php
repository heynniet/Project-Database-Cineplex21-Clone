<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// view_ticket.php - Detailed view of a single ticket
session_start();

include '../partials/header.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}

// Include header files
include '../partials/navbar.php';
include '../../config/db.php';

// Check if ticket ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: mytickets.php");
    exit();
}

$ticket_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $sql = "SELECT t.*, 
    m.title AS movie_title, 
    m.poster_path AS poster_url, 
    m.duration, 
    m.rating, 
    th.name AS theater_name, 
    th.address AS theater_address,
    s.showdate, 
    s.showtime
    FROM tickets t
    JOIN showtimes s ON t.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id
    JOIN theaters th ON s.theater_id = th.id
    WHERE t.id = :ticket_id AND t.user_id = :user_id";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Check if ticket exists and belongs to the user
    if ($stmt->rowCount() === 0) {
        header("Location: mytickets.php");
        exit();
    }

    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log error and redirect
    error_log("Database error: " . $e->getMessage());
    header("Location: mytickets.php?error=db");
    exit();
}

// Generate QR Code data URL
$qrCodeData = "TICKET: {$ticket['ticket_code']}\nMOVIE: {$ticket['movie_title']}\nDATE: {$ticket['showdate']}\nTIME: {$ticket['showtime']}\nSEATS: {$ticket['seat_numbers']}";
$qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrCodeData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details - Cineplex21</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Cineplex21/assets/css/styles.css">
    <link rel="stylesheet" href="/Cineplex21/assets/css/ticket-details.css">
</head>
<body>
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <div class="back-link">
                    <a href="mytickets.php"><i class="fas fa-arrow-left"></i> Back to My Tickets</a>
                </div>
                <h1>Ticket Details</h1>
            </div>

            <div class="ticket-detail-card">
                <div class="ticket-header">
                    <div class="ticket-code">
                        <span>Ticket ID:</span>
                        <h2><?php echo htmlspecialchars($ticket['ticket_code']); ?></h2>
                    </div>
                    <div class="ticket-status <?php echo $ticket['status']; ?>">
                        <?php echo ucfirst($ticket['status']); ?>
                    </div>
                </div>

                <div class="ticket-body">
                    <div class="ticket-movie">
                        <div class="ticket-poster">
                            <?php
                            $posterFilename = htmlspecialchars($ticket['poster_url']);
                            
                            // Check if the poster exists
                            if (!empty($posterFilename)) {
                                echo "<img src='/Cineplex21/uploads/posters/{$posterFilename}' alt='Movie Poster'>";
                            } else {
                                echo "<img src='/Cineplex21/assets/images/default-poster.jpg' alt='Default Poster'>";
                            }
                            ?>
                        </div>
                        <div class="movie-details">
                            <h3><?php echo htmlspecialchars($ticket['movie_title']); ?></h3>
                            <p class="movie-meta">
                                <span><i class="fas fa-clock"></i> <?php echo $ticket['duration']; ?> min</span>
                                <span><i class="fas fa-star"></i> <?php echo htmlspecialchars($ticket['rating']); ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="ticket-info-grid">
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-film"></i> Theater</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['theater_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['theater_address']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="far fa-calendar-alt"></i> Date</div>
                            <div class="info-value"><?php echo date('l, d F Y', strtotime($ticket['showdate'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="far fa-clock"></i> Time</div>
                            <div class="info-value"><?php echo date('H:i', strtotime($ticket['showtime'])); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-couch"></i> Seats</div>
                            <div class="info-value"><?php echo htmlspecialchars($ticket['seat_numbers']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-users"></i> Quantity</div>
                            <div class="info-value"><?php echo $ticket['quantity']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-money-bill-wave"></i> Total Price</div>
                            <div class="info-value">Rp <?php echo number_format($ticket['total_price'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label"><i class="fas fa-shopping-cart"></i> Purchase Date</div>
                            <div class="info-value"><?php echo date('d M Y H:i', strtotime($ticket['purchase_date'])); ?></div>
                        </div>
                    </div>

                    <?php if ($ticket['status'] == 'active'): ?>
                    <div class="ticket-qr">
                        <h4>Scan QR Code at Theater</h4>
                        <img src="<?php echo $qrCodeUrl; ?>" alt="Ticket QR Code">
                        <p class="qr-note">Present this QR code at the theater entrance</p>
                    </div>
                    <?php endif; ?>

                    <div class="ticket-actions">
                        <?php if ($ticket['status'] == 'active'): ?>
                            <a href="download_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-buy">
                                <i class="fas fa-download"></i> Download Ticket
                            </a>
                            <a href="#" class="btn-details" onclick="printTicket()">
                                <i class="fas fa-print"></i> Print Ticket
                            </a>
                        <?php else: ?>
                            <div class="ticket-used-message">
                                This ticket has been used on <?php echo date('d M Y', strtotime($ticket['showdate'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../partials/footer.php'; ?>
    
    <script>
        function printTicket() {
            window.print();
        }
    </script>
</body>
</html>