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
$pageTitle = "View Movie Details - Cineplex21";

// Check movie ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: manage_movies.php?error=Invalid+movie+ID');
    exit;
}

$movie_id = intval($_GET['id']);

// Get movie data
$movieQuery = $conn->prepare("SELECT * FROM movies WHERE id = ?");
$movieQuery->bind_param("i", $movie_id);
$movieQuery->execute();
$movieResult = $movieQuery->get_result();

if ($movieResult->num_rows === 0) {
    header('Location: manage_movies.php?error=Movie+not+found');
    exit;
}

$movie = $movieResult->fetch_assoc();
$movieQuery->close();

// Get movie schedule
$showtimesQuery = $conn->prepare("
    SELECT s.*, t.name as theater_name 
    FROM showtimes s 
    LEFT JOIN theaters t ON s.theater_id = t.id 
    WHERE s.movie_id = ? 
    ORDER BY s.showdate, s.showtime
");
$showtimesQuery->bind_param("i", $movie_id);
$showtimesQuery->execute();
$showtimesResult = $showtimesQuery->get_result();
$showtimes = [];
while ($row = $showtimesResult->fetch_assoc()) {
    $showtimes[] = $row;
}
$showtimesQuery->close();

// Get ticket sales data
$ticketsQuery = $conn->prepare("
    SELECT 
        COUNT(*) AS total_tickets,
        SUM(amount) AS total_revenue
    FROM tickets
    WHERE movie_id = ?
");
$ticketsQuery->bind_param("i", $movie_id);
$ticketsQuery->execute();
$ticketsData = $ticketsQuery->get_result()->fetch_assoc();
$ticketsQuery->close();

// Function to get status class based on status
function getStatusClass($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'now-showing':
            return 'status-active';
        case 'coming-soon':
            return 'status-coming-soon';
        default:
            return 'status-inactive';
    }
}

// Function to get formatted status text for display
function getStatusText($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'now-showing':
            return 'Now Showing';
        case 'coming-soon':
            return 'Coming Soon';
        default:
            return 'Not Showing';
    }
}
?>

<div class="admin-layout">
    <?php include '../partials/sidebar.php'; ?>
    <div class="admin-content">
        <div class="admin-header">
            <div class="header-title">
                <a href="manage_movies.php" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2><i class="fas fa-film"></i> Movie Details</h2>
            </div>
            <div class="admin-actions">
                <a href="edit_movie.php?id=<?php echo $movie_id; ?>" class="btn-primary">
                    <i class="fas fa-edit"></i> Edit Movie
                </a>
                <a href="manage_movies.php" class="btn-secondary">
                    <i class="fas fa-list"></i> Movie List
                </a>
            </div>
        </div>
        
        <div class="movie-details-container">
            <div class="movie-details-card">
                <div class="movie-details-header">
                <div class="movie-poster-wrapper">
                        <?php if (!empty($movie['poster_path'])): ?>
                            <img src="../../uploads/posters/<?php echo htmlspecialchars($movie['poster_path']); ?>" 
                                alt="<?php echo htmlspecialchars($movie['title']); ?>" class="movie-poster">
                        <?php else: ?>
                            <div class="no-poster">
                                <i class="fas fa-film"></i>
                                <span>No Poster</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="movie-info">
                        <div class="movie-title-container">
                            <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                            <span class="status-badge <?php echo getStatusClass($movie['status']); ?>">
                                <i class="fas fa-circle status-indicator"></i>
                                <?php echo getStatusText($movie['status']); ?>
                            </span>
                        </div>
                        
                        <div class="movie-meta">
                            <?php if (!empty($movie['release_date'])): ?>
                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Release: <?php echo date('d F Y', strtotime($movie['release_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span>Duration: <?php echo $movie['duration']; ?> minutes</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-star"></i>
                                <span>Rating: <?php echo htmlspecialchars($movie['rating']); ?></span>
                            </div>
                        </div>
                        
                        <div class="genre-container">
                            <?php 
                            $genres = explode(',', $movie['genre']);
                            foreach ($genres as $genre) {
                                echo '<span class="genre-badge">'.trim($genre).'</span>';
                            }
                            ?>
                        </div>
                        
                        <?php if (!empty($movie['director'])): ?>
                            <div class="movie-crew">
                                <div class="crew-label">Director</div>
                                <div class="crew-value"><?php echo htmlspecialchars($movie['director']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['cast'])): ?>
                            <div class="movie-crew">
                                <div class="crew-label">Cast</div>
                                <div class="crew-value"><?php echo htmlspecialchars($movie['cast']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($movie['trailer_url'])): ?>
                            <div class="movie-actions">
                                <a href="<?php echo htmlspecialchars($movie['trailer_url']); ?>" target="_blank" class="btn-trailer">
                                    <i class="fab fa-youtube"></i> Watch Trailer
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="movie-stats-container">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo count($showtimes); ?></div>
                            <div class="stat-label">Showtimes</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo number_format($ticketsData['total_tickets'] ?? 0); ?></div>
                            <div class="stat-label">Tickets Sold</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">Rp <?php echo number_format(($ticketsData['total_revenue'] ?? 0), 0, ',', '.'); ?></div>
                            <div class="stat-label">Revenue</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="movie-details-tabs">
                <ul class="tabs-nav">
                    <li class="tab-item active" data-tab="synopsis">
                        <i class="fas fa-align-left"></i> Synopsis
                    </li>
                    <li class="tab-item" data-tab="schedule">
                        <i class="fas fa-calendar-alt"></i> Showtimes
                    </li>
                    <li class="tab-item" data-tab="details">
                        <i class="fas fa-info-circle"></i> Other Details
                    </li>
                </ul>
                
                <div class="tabs-content">
                    <div class="tab-pane active" id="synopsis">
                        <div class="movie-description">
                            <?php if (!empty($movie['description'])): ?>
                                <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
                            <?php else: ?>
                                <p class="no-data">No description available for this movie.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="tab-pane" id="schedule">
                        <?php if (count($showtimes) > 0): ?>
                            <div class="schedule-actions">
                                <a href="add_showtime.php?movie_id=<?php echo $movie_id; ?>" class="btn-add-schedule">
                                    <i class="fas fa-plus"></i> Add New Schedule
                                </a>
                            </div>
                            
                            <div class="showtimes-container">
                                <?php 
                                $current_date = '';
                                $showtimes_by_date = [];
                                
                                // Group showtimes by date
                                foreach ($showtimes as $showtime) {
                                    $date = date('Y-m-d', strtotime($showtime['showdate']));
                                    if (!isset($showtimes_by_date[$date])) {
                                        $showtimes_by_date[$date] = [];
                                    }
                                    $showtimes_by_date[$date][] = $showtime;
                                }
                                
                                foreach ($showtimes_by_date as $date => $daily_showtimes):
                                ?>
                                <div class="showtime-date">
                                    <div class="date-header">
                                        <i class="fas fa-calendar-day"></i>
                                        <h3><?php echo date('l, d F Y', strtotime($date)); ?></h3>
                                    </div>
                                    <div class="showtime-slots">
                                        <?php foreach ($daily_showtimes as $showtime): ?>
                                        <div class="showtime-slot">
                                            <div class="slot-time">
                                                <i class="far fa-clock"></i>
                                                <?php echo date('H:i', strtotime($showtime['showtime'])); ?>
                                            </div>
                                            <div class="slot-theater">
                                                <i class="fas fa-tv"></i>
                                                <?php echo htmlspecialchars($showtime['theater_name']); ?>
                                            </div>
                                            <div class="slot-actions">
                                                <a href="edit_showtime.php?id=<?php echo $showtime['id']; ?>" class="btn-action btn-edit-small" title="Edit Schedule">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="javascript:void(0);" onclick="confirmDeleteShowtime(<?php echo $showtime['id']; ?>)" 
                                                   class="btn-action btn-delete-small" title="Delete Schedule">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon">
                                    <i class="far fa-calendar-times"></i>
                                </div>
                                <p>No showtimes available for this movie yet</p>
                                <a href="add_showtime.php?movie_id=<?php echo $movie_id; ?>" class="btn-primary">
                                    <i class="fas fa-plus"></i> Add New Schedule
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="tab-pane" id="details">
                        <div class="movie-details-table">
                            <div class="detail-item">
                                <div class="detail-label">Movie ID</div>
                                <div class="detail-value"><?php echo $movie['id']; ?></div>
                            </div>
                            <?php if (!empty($movie['language'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">Language</div>
                                <div class="detail-value"><?php echo htmlspecialchars($movie['language']); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($movie['country'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">Country</div>
                                <div class="detail-value"><?php echo htmlspecialchars($movie['country']); ?></div>
                            </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <div class="detail-label">Created Date</div>
                                <div class="detail-value"><?php echo date('d F Y H:i', strtotime($movie['created_at'])); ?></div>
                            </div>
                            <?php if (!empty($movie['updated_at'])): ?>
                            <div class="detail-item">
                                <div class="detail-label">Last Updated</div>
                                <div class="detail-value"><?php echo date('d F Y H:i', strtotime($movie['updated_at'])); ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* General Layout */
.admin-content {
    padding: 25px;
    background-color: #f5f7fa;
    min-height: calc(100vh - 60px);
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.header-title {
    display: flex;
    align-items: center;
}

.back-button {
    margin-right: 15px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: #f1f3f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #495057;
    transition: all 0.3s;
}

.back-button:hover {
    background-color: #e9ecef;
    transform: translateX(-3px);
}

.admin-header h2 {
    color: #2c3e50;
    font-size: 24px;
    margin: 0;
    display: flex;
    align-items: center;
}

.admin-header h2 i {
    margin-right: 10px;
    color: #3498db;
}

.admin-actions {
    display: flex;
    gap: 10px;
}

/* Button Styles */
.btn-primary, .btn-secondary, .btn-trailer, .btn-add-schedule {
    padding: 10px 16px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-primary {
    background-color: #3498db;
    color: white;
}

.btn-primary:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-secondary {
    background-color: #e9ecef;
    color: #495057;
}

.btn-secondary:hover {
    background-color: #dee2e6;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-trailer {
    background-color: #ff0000;
    color: white;
    margin-top: 15px;
}

.btn-trailer:hover {
    background-color: #e60000;
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.btn-add-schedule {
    background-color: #20c997;
    color: white;
    margin-bottom: 20px;
}

.btn-add-schedule:hover {
    background-color: #1ba37e;
}

.btn-primary i, .btn-secondary i, .btn-trailer i, .btn-add-schedule i {
    margin-right: 8px;
}

/* Movie Details Container */
.movie-details-container {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

/* Movie Details Card */
.movie-details-card {
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.movie-details-header {
    display: flex;
    padding: 30px;
    background: linear-gradient(to right, #f8f9fa, #ffffff);
    border-bottom: 1px solid #e9ecef;
}

.movie-poster-wrapper {
    flex: 0 0 220px;
    margin-right: 30px;
}

.movie-poster {
    width: 100%;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.no-poster {
    width: 100%;
    height: 330px;
    background-color: #f1f3f5;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
}

.no-poster i {
    font-size: 48px;
    margin-bottom: 15px;
}

.movie-info {
    flex: 1;
}

.movie-title-container {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.movie-title {
    font-size: 28px;
    color: #2c3e50;
    margin: 0 15px 0 0;
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 500;
}

.status-indicator {
    font-size: 8px;
    margin-right: 8px;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-coming-soon {
    background-color: #fff3cd;
    color: #856404;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}

/* Movie Meta Info */
.movie-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.meta-item {
    display: flex;
    align-items: center;
    color: #6c757d;
    font-size: 15px;
}

.meta-item i {
    margin-right: 8px;
    color: #3498db;
}

/* Genre Badges */
.genre-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 20px;
}

.genre-badge {
    background-color: #e9ecef;
    color: #495057;
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 14px;
}

/* Movie Crew */
.movie-crew {
    margin-bottom: 15px;
}

.crew-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.crew-value {
    color: #6c757d;
    line-height: 1.6;
}

/* Movie Stats */
.movie-stats-container {
    display: flex;
    padding: 20px 30px;
    background-color: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.stat-card {
    flex: 1;
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 8px;
    background-color: white;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    margin: 0 10px;
}

.stat-card:first-child {
    margin-left: 0;
}

.stat-card:last-child {
    margin-right: 0;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background-color: #e8f4fd;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stat-icon i {
    font-size: 20px;
    color: #3498db;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 20px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 14px;
    color: #6c757d;
}

/* Tab Navigation */
.movie-details-tabs {
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.tabs-nav {
    display: flex;
    list-style: none;
    padding: 0;
    margin: 0;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.tab-item {
    padding: 15px 25px;
    font-weight: 500;
    color: #6c757d;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    border-bottom: 3px solid transparent;
}

.tab-item i {
    margin-right: 8px;
}

.tab-item:hover {
    color: #3498db;
    background-color: #ffffff;
}

.tab-item.active {
    color: #3498db;
    border-bottom-color: #3498db;
    background-color: #ffffff;
    font-weight: 600;
}

/* Tab Content */
.tabs-content {
    padding: 30px;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* Movie Description */
.movie-description {
    line-height: 1.8;
    color: #495057;
}

/* Showtime Styles */
.schedule-actions {
    margin-bottom: 20px;
    display: flex;
    justify-content: flex-end;
}

.showtimes-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.showtime-date {
    background-color: #ffffff;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    overflow: hidden;
}

.date-header {
    background-color: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
}

.date-header i {
    margin-right: 10px;
    color: #3498db;
}

.date-header h3 {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
}

.showtime-slots {
    padding: 15px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.showtime-slot {
    padding: 15px;
    border-radius: 8px;
    background-color: #f8f9fa;
    display: flex;
    flex-direction: column;
    gap: 10px;
    position: relative;
    transition: all 0.3s;
}

.showtime-slot:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.slot-time, .slot-theater {
    display: flex;
    align-items: center;
}

.slot-time i, .slot-theater i {
    margin-right: 8px;
    width: 20px;
    color: #3498db;
}

.slot-time {
    font-size: 18px;
    font-weight: 600;
    color: #2c3e50;
}

.slot-theater {
    color: #6c757d;
}

.slot-actions {
    position: absolute;
    top: 15px;
    right: 15px;
    display: flex;
    gap: 8px;
}

.btn-action {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.3s;
}

.btn-edit-small {
    background-color: #ffc107;
    color: #212529;
}

.btn-edit-small:hover {
    background-color: #e0a800;
}

.btn-delete-small {
    background-color: #dc3545;
    color: white;
}

.btn-delete-small:hover {
    background-color: #c82333;
}

/* Empty State */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
    text-align: center;
}

.empty-icon {
    font-size: 48px;
    color: #ced4da;
    margin-bottom: 20px;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 20px;
}

/* Movie Details Table */
.movie-details-table {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.detail-item {
    display: flex;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 15px;
}

.detail-label {
    flex: 0 0 200px;
    font-weight: 600;
    color: #2c3e50;
}

.detail-value {
    flex: 1;
    color: #6c757d;
}

/* No Data Message */
.no-data {
    color: #6c757d;
    font-style: italic;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .movie-details-header {
        flex-direction: column;
    }
    
    .movie-poster-wrapper {
        flex: 0 0 auto;
        margin-right: 0;
        margin-bottom: 20px;
        width: 50%;
        max-width: 220px;
        align-self: center;
    }
    
    .movie-stats-container {
        flex-direction: column;
        gap: 15px;
    }
    
    .stat-card {
        margin: 0;
    }
    
    .tabs-nav {
        overflow-x: auto;
        white-space: nowrap;
    }
    
    .showtime-slots {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .admin-actions {
        margin-top: 15px;
        width: 100%;
    }
    
    .btn-primary, .btn-secondary {
        flex: 1;
        justify-content: center;
    }
    
    .detail-item {
        flex-direction: column;
    }
    
    .detail-label {
        flex: 0 0 auto;
        margin-bottom: 5px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabItems = document.querySelectorAll('.tab-item');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabItems.forEach(item => {
        item.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and panes
            tabItems.forEach(tab => tab.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to selected tab and pane
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
});

// Function to confirm deletion of showtime
function confirmDeleteShowtime(showtimeId) {
    if (confirm('Apakah Anda yakin ingin menghapus jadwal tayang ini?')) {
        window.location.href = 'delete_showtime.php?id=' + showtimeId + '&movie_id=<?php echo $movie_id; ?>';
    }
}
</script>

