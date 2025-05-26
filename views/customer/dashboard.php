<?php
// Enable error reporting for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session to access user data
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

// Get user information
$user_id = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Get user's tickets
$ticketsQuery = "SELECT t.*, m.title, m.poster_path, s.showdate, s.showtime 
                FROM tickets t 
                JOIN movies m ON t.movie_id = m.id 
                JOIN showtimes s ON t.showtime_id = s.id 
                WHERE t.user_id = ? 
                ORDER BY s.showdate DESC, s.showtime DESC";
$ticketsStmt = $conn->prepare($ticketsQuery);
$ticketsStmt->bind_param("i", $user_id);
$ticketsStmt->execute();
$tickets = $ticketsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user's booking stats
$statsQuery = "SELECT COUNT(*) as total_bookings, SUM(amount) as total_spent 
              FROM tickets 
              WHERE user_id = ?";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bind_param("i", $user_id);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();
?>

<!-- Dashboard Welcome Section -->
<div class="container">
    <div class="section-header">
        <h2>Welcome, <?= htmlspecialchars($user['name']) ?>!</h2>
    </div>
    
    <!-- User Stats Overview -->
    <div class="user-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $stats['total_bookings'] ?? 0 ?></span>
                <span class="stat-label">Total Bookings</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-money-bill"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value">$<?= number_format($stats['total_spent'] ?? 0, 2) ?></span>
                <span class="stat-label">Total Spent</span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-info">
                <span class="stat-value"><?= $user['loyalty_points'] ?? 0 ?></span>
                <span class="stat-label">Loyalty Points</span>
            </div>
        </div>
    </div>
    
    <div class="dashboard-cards">
        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-film"></i>
            </div>
            <div class="card-content">
                <h3>Now Playing</h3>
                <p>Explore our current lineup of amazing movies showing in theaters.</p>
                <a href="/Cineplex21/models/Movies.php" class="btn-details">View Movies</a>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <div class="card-content">
                <h3>My Tickets</h3>
                <p>Access your booked tickets and manage your cinema reservations.</p>
                <a href="my_tickets.php" class="btn-buy">My Tickets</a>
            </div>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="card-content">
                <h3>My Profile</h3>
                <p>Update your personal information and preferences.</p>
                <a href="profile.php" class="btn-details">Edit Profile</a>
            </div>
        </div>
    </div>
    
    <!-- Recent Tickets Section -->
    <div class="section-header">
        <h2>My Recent Tickets</h2>
        <a href="my_tickets.php" class="view-all">View All</a>
    </div>
    
    <div class="recent-tickets">
        <?php if(count($tickets) == 0): ?>
            <div class="no-tickets">You haven't booked any tickets yet.</div>
        <?php else: ?>
            <?php 
            // Display only the 3 most recent tickets
            $recentTickets = array_slice($tickets, 0, 3);
            foreach($recentTickets as $ticket): 
            ?>
                <div class="ticket-card">
                    <div class="ticket-poster">
                        <?php
                        $posterPath = '../../public/assets/images/' . htmlspecialchars($ticket['poster_path']);
                        if (file_exists($posterPath) && !empty($ticket['poster_path'])): ?>
                            <img src="<?= $posterPath ?>" alt="<?= htmlspecialchars($ticket['title']) ?> Poster">
                        <?php else: ?>
                            <img src="../../public/assets/images/default-poster.jpg" alt="Default Poster">
                        <?php endif; ?>
                    </div>
                    <div class="ticket-info">
                        <h4><?= htmlspecialchars($ticket['title']) ?></h4>
                        <p>Date: <?= date('M d, Y', strtotime($ticket['date'])) ?></p>
                        <p>Time: <?= date('h:i A', strtotime($ticket['time'])) ?></p>
                        <p>Seats: <?= htmlspecialchars($ticket['seats']) ?></p>
                        <div class="ticket-actions">
                            <a href="ticket_details.php?id=<?= $ticket['id'] ?>" class="btn-details">View Details</a>
                            <a href="download_ticket.php?id=<?= $ticket['id'] ?>" class="btn-download">Download</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
        
    <!-- Personalized Recommendations -->
    <?php
    // Get recommendations based on user's watching history
    $recomQuery = "SELECT DISTINCT m.* FROM movies m
                  JOIN movies m2 ON m.genre = m2.genre AND m.id != m2.id
                  JOIN tickets t ON t.movie_id = m2.id
                  WHERE t.user_id = ? AND m.status = 'now-showing'
                  ORDER BY m.release_date DESC
                  LIMIT 3";
    $recomStmt = $conn->prepare($recomQuery);
    $recomStmt->bind_param("i", $user_id);
    $recomStmt->execute();
    $recommendations = $recomStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if(count($recommendations) > 0):
    ?>
    <div class="section-header">
        <h2>Recommended For You</h2>
    </div>
    
    <div class="recommendations">
        <?php foreach($recommendations as $movie): ?>
            <div class="recommendation-card">
                <?php
                $posterPath = '../../public/assets/images/' . htmlspecialchars($movie['poster_path']);
                if (file_exists($posterPath) && !empty($movie['poster_path'])): ?>
                    <img src="<?= $posterPath ?>" alt="<?= htmlspecialchars($movie['title']) ?> Poster">
                <?php else: ?>
                    <img src="../../public/assets/images/default-poster.jpg" alt="Default Poster">
                <?php endif; ?>
                <div class="recommendation-info">
                    <h4><?= htmlspecialchars($movie['title']) ?></h4>
                    <p><?= htmlspecialchars($movie['genre']) ?></p>
                    <div class="recommendation-actions">
                        <a href="../../movie-details.php?id=<?= $movie['id'] ?>" class="btn-details">Details</a>
                        <a href="../../Ticket.php?id=<?= $movie['id'] ?>" class="btn-buy">Buy Tickets</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php 
// Close connections
$userStmt->close();
$ticketsStmt->close();
$statsStmt->close();
if(isset($recomStmt)) $recomStmt->close();
$conn->close();

include '../partials/footer.php'; 
?>