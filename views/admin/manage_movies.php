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
$pageTitle = "Manage Movies - Cineplex21";

// Process delete request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $movie_id = intval($_GET['delete']);
    
    // Check if movie is still used in showtimes
    $checkShowtimes = $conn->query("SELECT COUNT(*) AS count FROM showtimes WHERE movie_id = $movie_id");
    $showtimeCount = $checkShowtimes->fetch_assoc()['count'];
    
    // Check if movie is still used in tickets
    $checkTickets = $conn->query("SELECT COUNT(*) AS count FROM tickets WHERE movie_id = $movie_id");
    $ticketCount = $checkTickets->fetch_assoc()['count'];
    
    if ($showtimeCount > 0 || $ticketCount > 0) {
        $deleteError = "Movie cannot be deleted because it still has scheduled showtimes or sold tickets.";
    } else {
        // Get image filename before deleting
        $getImageQuery = $conn->query("SELECT poster_path FROM movies WHERE id = $movie_id");
        if ($getImageQuery->num_rows > 0) {
            $imageData = $getImageQuery->fetch_assoc();
            $posterFile = $imageData['poster_url'];
            
            // Delete poster file if exists
            if ($posterFile && file_exists("../../uploads/posters/" . $posterFile)) {
                unlink("../../uploads/posters/" . $posterFile);
            }
        }
        
        // Delete movie from database
        $deleteQuery = $conn->query("DELETE FROM movies WHERE id = $movie_id");
        if ($deleteQuery) {
            $deleteSuccess = "Movie successfully deleted.";
        } else {
            $deleteError = "Failed to delete movie: " . $conn->error;
        }
    }
    
    // Redirect to avoid form resubmission
    header("Location: manage_movies.php" . ($deleteSuccess ? "?success=".urlencode($deleteSuccess) : "?error=".urlencode($deleteError)));
    exit;
}

// Process search
$searchTerm = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchCondition = '';
if (!empty($searchTerm)) {
    $searchCondition = " WHERE title LIKE '%$searchTerm%' OR genre LIKE '%$searchTerm%'";
}

// Get movies
$moviesQuery = $conn->query("SELECT * FROM movies $searchCondition ORDER BY created_at DESC");

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
            <h2><i class="fas fa-film"></i> Manage Movies</h2>
            <div class="admin-actions">
                <a href="add_movie.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Movie</a>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="close-alert">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="close-alert">&times;</button>
            </div>
        <?php endif; ?>
        
        <div class="admin-card">
            <!-- Search form -->
            <div class="search-container">
                <form action="" method="GET" class="search-form">
                    <div class="search-input-group">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" placeholder="Search title or genre..." 
                               value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="search-buttons">
                        <button type="submit" class="btn-search">Search</button>
                        <?php if (!empty($searchTerm)): ?>
                            <a href="manage_movies.php" class="btn-reset"><i class="fas fa-times"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Movies table -->
            <div class="table-responsive">
                <table class="admin-table movies-table">
                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th width="12%">Poster</th>
                            <th width="20%">Title</th>
                            <th width="10%">Duration</th>
                            <th width="15%">Genre</th>
                            <th width="8%">Rating</th>
                            <th width="15%">Status</th>
                            <th width="15%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($moviesQuery && $moviesQuery->num_rows > 0): ?>
                            <?php while($movie = $moviesQuery->fetch_assoc()): ?>
                                <tr>
                                    <td class="text-center"><?php echo $movie['id']; ?></td>
                                    <td>
                                        <?php if (!empty($movie['poster_path'])): ?>
                                            <div class="movie-poster-container">
                                                <?php
                                                $posterFilename = htmlspecialchars($movie['poster_path']);
                                                $posterPath = "../../uploads/posters/" . $posterFilename;
                                                
                                                if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/Cineplex21/uploads/posters/" . $posterFilename)) {
                                                    echo "<img src='/Cineplex21/uploads/posters/{$posterFilename}' alt='Poster' class='movie-poster-thumbnail'>";
                                                } else {
                                                    echo "<img src='/Cineplex21/public/assets/images/default-poster.jpg' alt='Default Poster' class='movie-poster-thumbnail'>";
                                                }
                                                ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="no-poster">
                                                <i class="fas fa-film"></i>
                                                <span>No Image</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></td>
                                    <td class="text-center"><?php echo $movie['duration']; ?> minutes</td>
                                    <td>
                                        <?php 
                                        $genres = explode(',', $movie['genre']);
                                        foreach ($genres as $genre) {
                                            echo '<span class="genre-badge">' . trim($genre) . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="rating-badge">
                                            <?php echo htmlspecialchars($movie['rating']); ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge <?php echo getStatusClass($movie['status']); ?>">
                                            <i class="fas fa-circle status-indicator"></i>
                                            <?php echo getStatusText($movie['status']); ?>
                                        </span>
                                    </td>
                                    <td class="action-btns">
                                        <a href="view_movie.php?id=<?php echo $movie['id']; ?>" class="btn-action btn-view" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_movie.php?id=<?php echo $movie['id']; ?>" class="btn-action btn-edit" title="Edit Movie">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $movie['id']; ?>, '<?php echo addslashes($movie['title']); ?>')" 
                                           class="btn-action btn-delete" title="Delete Movie">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center empty-table">
                                    <div class="empty-state">
                                        <i class="fas fa-search fa-3x"></i>
                                        <p>No movies found</p>
                                        <?php if (!empty($searchTerm)): ?>
                                            <a href="manage_movies.php" class="btn-reset-search">Reset Search</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* General Styles */
.admin-content {
    padding: 25px;
    background-color: #f5f7fa;
    min-height: calc(100vh - 60px);
}

.admin-card {
    background: #ffffff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    padding: 20px;
    margin-bottom: 30px;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.admin-header h2 {
    color: #2c3e50;
    font-size: 24px;
    margin: 0;
}

.admin-header h2 i {
    margin-right: 10px;
    color: #3498db;
}

/* Alert Styles */
.alert {
    padding: 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    position: relative;
}

.alert i {
    margin-right: 10px;
    font-size: 18px;
}

.alert-success {
    background-color: #e8f7ed;
    color: #28a745;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background-color: #feebed;
    color: #dc3545;
    border-left: 4px solid #dc3545;
}

.close-alert {
    position: absolute;
    right: 15px;
    top: 15px;
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: inherit;
    opacity: 0.7;
}

.close-alert:hover {
    opacity: 1;
}

/* Button Styles */
.btn-primary {
    background-color: #3498db;
    color: #fff;
    padding: 10px 18px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.btn-primary:hover {
    background-color: #2980b9;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.btn-primary i {
    margin-right: 8px;
}

/* Search Styles */
.search-container {
    margin-bottom: 25px;
}

.search-form {
    display: flex;
    align-items: center;
    gap: 15px;
}

.search-input-group {
    flex: 1;
    position: relative;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
}

.search-form input[type="text"] {
    width: 100%;
    padding: 12px 12px 12px 40px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 15px;
    transition: all 0.3s;
}

.search-form input[type="text"]:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
    outline: none;
}

.search-buttons {
    display: flex;
    gap: 10px;
}

.btn-search {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-search:hover {
    background-color: #2980b9;
}

.btn-reset {
    background-color: #e9ecef;
    color: #495057;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    font-weight: 500;
    transition: all 0.3s;
}

.btn-reset:hover {
    background-color: #dee2e6;
}

.btn-reset i {
    margin-right: 5px;
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
}

.admin-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.admin-table th, 
.admin-table td {
    padding: 15px;
    text-align: left;
}

.admin-table th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
    position: sticky;
    top: 0;
}

.admin-table tbody tr {
    transition: all 0.3s;
}

.admin-table tbody tr:hover {
    background-color: #f8f9fa;
}

.admin-table tbody tr:not(:last-child) {
    border-bottom: 1px solid #e9ecef;
}

.text-center {
    text-align: center;
}

/* Movie Item Styles */
.movie-poster-container {
    width: 100%;
    height: 120px;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
}

.movie-poster-thumbnail {
    max-width: 100%;
    max-height: 120px;
    border-radius: 4px;
    object-fit: cover;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.no-poster {
    width: 100%;
    height: 120px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    background-color: #f1f3f5;
    border-radius: 4px;
    color: #adb5bd;
}

.no-poster i {
    font-size: 32px;
    margin-bottom: 8px;
}

.movie-title {
    font-weight: 600;
    color: #2c3e50;
}

/* Genre Badges */
.genre-badge {
    display: inline-block;
    background-color: #e9ecef;
    color: #495057;
    padding: 4px 10px;
    border-radius: 30px;
    font-size: 12px;
    margin: 2px;
}

/* Rating Badge */
.rating-badge {
    display: inline-block;
    background-color: #ffd166;
    color: #6b5900;
    padding: 4px 12px;
    border-radius: 4px;
    font-weight: 600;
    font-size: 14px;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 13px;
    font-weight: 500;
}

.status-indicator {
    font-size: 8px;
    margin-right: 6px;
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

/* Action Buttons */
.action-btns {
    display: flex;
    gap: 8px;
    justify-content: center;
}

.btn-action {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    color: white;
    transition: all 0.3s;
}

.btn-view {
    background-color: #17a2b8;
}

.btn-view:hover {
    background-color: #138496;
}

.btn-edit {
    background-color: #ffc107;
    color: #212529;
}

.btn-edit:hover {
    background-color: #e0a800;
}

.btn-delete {
    background-color: #dc3545;
}

.btn-delete:hover {
    background-color: #c82333;
}

/* Empty State */
.empty-table {
    height: 300px;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    color: #6c757d;
}

.empty-state i {
    color: #adb5bd;
    margin-bottom: 15px;
}

.empty-state p {
    margin: 10px 0;
}

.btn-reset-search {
    margin-top: 15px;
    background-color: #e9ecef;
    color: #495057;
    padding: 8px 15px;
    border-radius: 4px;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-reset-search:hover {
    background-color: #dee2e6;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .search-form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-buttons {
        display: flex;
        justify-content: space-between;
    }
    
    .btn-search, .btn-reset {
        flex: 1;
    }
    
    .admin-table th, 
    .admin-table td {
        padding: 10px;
    }
}
</style>

<script>
// Close alert functionality
document.addEventListener('DOMContentLoaded', function() {
    const closeButtons = document.querySelectorAll('.close-alert');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.parentElement.style.display = 'none';
        });
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        });
    }, 5000);
});

function confirmDelete(id, title) {
    // Enhanced confirmation dialog
    if (confirm("Are you sure you want to delete the movie '" + title + "'?\nThis action cannot be undone.")) {
        window.location.href = "manage_movies.php?delete=" + id;
    }
}
</script>

<?php
$conn->close();
?>