<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// mytickets.php - Main page for viewing purchased tickets
session_start();

include '../partials/header.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}

include '../partials/navbar.php';
include '../../config/db.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Filter tickets based on query parameter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

try {
    // Modified query to use booking_seats table to get seat information
    $sql = "SELECT b.id, b.showtime_id, b.user_id, b.booking_date, b.total_amount, b.status,
            m.title as movie_title, m.poster_path as poster_url, 
            th.name as theater_name, s.showdate as show_date, s.showtime as show_time,
            GROUP_CONCAT(bs.seat_number ORDER BY bs.seat_number SEPARATOR ', ') as seat_numbers,
            b.total_amount as total_price, b.booking_date as purchase_date
            FROM bookings b
            JOIN showtimes s ON b.showtime_id = s.id
            JOIN movies m ON s.movie_id = m.id
            JOIN theaters th ON s.theater_id = th.id
            LEFT JOIN booking_seats bs ON b.id = bs.booking_id
            WHERE b.user_id = :user_id AND b.status = 'pending'";

    // Add filter condition
    if ($filter === 'active') {
        $sql .= " AND s.showdate >= CURRENT_DATE()";
    } elseif ($filter === 'used') {
        $sql .= " AND s.showdate < CURRENT_DATE()";
    }
    
    // Group by booking ID to consolidate seat numbers
    $sql .= " GROUP BY b.id";

    // Order by date and time
    $sql .= " ORDER BY s.showdate DESC, s.showtime DESC";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $tickets = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - Cineplex21</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Cineplex21/assets/css/styles.css">
    <link rel="stylesheet" href="/Cineplex21/assets/css/mytickets.css">
</head>
<body>
    <main class="main-content">
        <div class="container">
            <div class="page-header">
                <h1>My Tickets</h1>
                <p>Manage your purchased movie tickets</p>
            </div>

            <div class="ticket-filters">
                <a href="mytickets.php?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-ticket-alt"></i> All Tickets
                </a>
            </div>

            <div class="tickets-container">
                <?php if (empty($tickets)): ?>
                    <div class="no-tickets">
                        <i class="fas fa-ticket-alt fa-3x"></i>
                        <p>No tickets found for this filter.</p>
                        <a href="movies.php" class="btn-buy">Browse Movies</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <?php 
                            // Determine ticket status
                            $now = new DateTime();
                            $show_date = new DateTime($ticket['show_date']);
                            $status = ($show_date < $now) ? 'used' : 'active';
                        ?>
                        <div class="ticket-card <?php echo $status; ?>">
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
                                <span class="ticket-status"><?php echo $status == 'active' ? 'Active' : 'Used'; ?></span>
                            </div>
                            <div class="ticket-details">
                                <h3><?php echo htmlspecialchars($ticket['movie_title']); ?></h3>
                                <div class="ticket-info">
                                    <p><i class="fas fa-film"></i> <?php echo htmlspecialchars($ticket['theater_name']); ?></p>
                                    <p><i class="far fa-calendar-alt"></i> <?php echo date('d M Y', strtotime($ticket['show_date'])); ?></p>
                                    <p><i class="far fa-clock"></i> <?php echo date('H:i', strtotime($ticket['show_time'])); ?></p>
                                    <p><i class="fas fa-couch"></i> <?php echo htmlspecialchars($ticket['seat_numbers']); ?></p>
                                    <p><i class="fas fa-money-bill"></i> Rp <?php echo number_format($ticket['total_price'], 0, ',', '.'); ?></p>
                                    <p><i class="fas fa-calendar-check"></i> <?php echo date('d M Y', strtotime($ticket['purchase_date'])); ?></p>
                                </div>
                            </div>
                            <div class="ticket-actions">
                                <a href="/Cineplex21/models/booking-confirmation.php?id=<?php echo $ticket['id']; ?>" class="btn-details">View Details</a>
                                <?php if ($status == 'active'): ?>
                                    <a href="download_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn-buy">Download</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../partials/footer.php'; ?>
    
    <script>
        // Simple script to highlight active filter
        document.addEventListener('DOMContentLoaded', function() {
            const currentFilter = '<?php echo $filter; ?>';
            const filterBtns = document.querySelectorAll('.filter-btn');
            
            filterBtns.forEach(btn => {
                if (btn.href.includes(`filter=${currentFilter}`)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>