<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Koneksi database dan inisialisasi session
session_start();

include '../partials/header.php';

// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}
include '../../config/db.php';

// Page title for header
$pageTitle = "Admin Dashboard - Cineplex21";

// Query untuk mengambil data statistik - disesuaikan dengan struktur database yang terlihat
$statsQuery = $conn->query("
    SELECT
    (SELECT COUNT(*) FROM movies) AS total_movies,
    (SELECT COUNT(*) FROM showtimes) AS total_showtimes,
    (SELECT COUNT(*) FROM tickets) AS total_bookings,
    (SELECT IFNULL(SUM(amount), 0) FROM tickets) AS total_revenue
");
$stats = $statsQuery->fetch_assoc();

// Query untuk aktivitas terbaru (disesuaikan dengan struktur tabel yang terlihat di phpMyAdmin)
$recentActivityQuery = $conn->query("
    (SELECT 'user' AS type, 'New user registered' AS title,
    CONCAT(name, ' created an account') AS info,
    created_at AS time
    FROM users
    ORDER BY created_at DESC
    LIMIT 3)
    UNION
    (SELECT 'booking' AS type, 'New booking' AS title,
    CONCAT(quantity, ' tickets booked for ',
    (SELECT title FROM movies WHERE id = tickets.movie_id)) AS info,
    booking_date AS time
    FROM tickets
    ORDER BY created_at DESC
    LIMIT 3)
    UNION
    (SELECT 'movie' AS type, 'Movie added' AS title,
    CONCAT(title, ' added to the database') AS info,
    created_at AS time
    FROM movies
    ORDER BY created_at DESC
    LIMIT 3)
    ORDER BY time DESC
    LIMIT 5
");
?>
<div class="admin-layout">
    <?php include '../partials/sidebar.php'; ?>
    <div class="admin-content">
        <div class="admin-header">
            <h2>Admin Dashboard</h2>
            <div class="admin-actions">
                <a href="manage_movies.php" class="btn-action">Manage Movies</a>
                <a href="manage_schedules.php" class="btn-action">Manage Schedules</a>
                <a href="manage_studio.php" class="btn-action">Manage Cinemas</a>
            </div>
        </div>
        <div class="stats-grid">
            <!-- Total Movies Card -->
            <div class="stats-card">
                <div class="stats-card-content">
                    <div class="stats-header">
                        <h3>Total Movies</h3>
                        <div class="stats-icon film-icon">
                            <i class="fas fa-film"></i>
                        </div>
                    </div>
                    <div class="stats-value"><?php echo number_format($stats['total_movies']); ?></div>
                    <div class="stats-description">Movies in database</div>
                </div>
            </div>
            <!-- Total Showtimes Card -->
            <div class="stats-card">
                <div class="stats-card-content">
                    <div class="stats-header">
                        <h3>Total Showtimes</h3>
                        <div class="stats-icon studio-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="stats-value"><?php echo number_format($stats['total_showtimes']); ?></div>
                    <div class="stats-description">Scheduled screenings</div>
                </div>
            </div>
        </div>
        <!-- Recent Activity Section -->
        <div class="admin-section">
            <div class="section-header admin-section-header">
                <h2>Recent Activity</h2>
            </div>
            <div class="activity-list">
                <?php if ($recentActivityQuery->num_rows > 0): ?>
                    <?php while ($activity = $recentActivityQuery->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php if ($activity['type'] == 'user'): ?>
                                    <i class="fas fa-user-plus"></i>
                                <?php elseif ($activity['type'] == 'booking'): ?>
                                    <i class="fas fa-ticket-alt"></i>
                                <?php elseif ($activity['type'] == 'movie'): ?>
                                    <i class="fas fa-film"></i>
                                <?php endif; ?>
                            </div>
                            <div class="activity-details">
                                <div class="activity-title"><?php echo htmlspecialchars($activity['title']); ?></div>
                                <div class="activity-info"><?php echo htmlspecialchars($activity['info']); ?></div>
                                <div class="activity-time"><?php echo timeAgo($activity['time']); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-activity">No recent activity found</div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Quick Actions Section -->
        <div class="admin-section">
            <div class="section-header admin-section-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="quick-actions">
                <a href="add_movie.php" class="quick-action-btn">
                    <i class="fas fa-plus"></i>
                    <span>Add New Movie</span>
                </a>
                <a href="add_showtime.php" class="quick-action-btn">
                    <i class="fas fa-plus"></i>
                    <span>Add Showtime</span>
                </a>
                <a href="add_showtime.php" class="quick-action-btn">
                    <i class="fas fa-plus"></i>
                    <span>Add Cinema</span>
                </a>
            </div>
        </div>
    </div>
</div>
<?php
// Helper function untuk format waktu
function timeAgo($timestamp) {
    $timestamp = strtotime($timestamp);
    $difference = time() - $timestamp;
    if ($difference < 60) {
        return "Just now";
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . " minute" . ($minutes > 1 ? "s" : "") . " ago";
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . " day" . ($days > 1 ? "s" : "") . " ago";
    } else {
        return date("M j, Y", $timestamp);
    }
}
$conn->close();
?>