<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize session and database connection
session_start();

// Redirect if user is not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}

// Include required files
require_once '../partials/header.php';
require_once '../../config/db.php';

// Page title for header
$pageTitle = "Manage Schedules - Cineplex21";

// Initialize variables
$deleteSuccess = $deleteError = '';
$filterParams = [];

// Process delete request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $showtime_id = intval($_GET['delete']);
    
    // Check if the schedule is still used in tickets
    $checkStmt = $conn->prepare("SELECT COUNT(*) AS count FROM tickets WHERE showtime_id = ?");
    $checkStmt->bind_param("i", $showtime_id);
    $checkStmt->execute();
    $ticketCount = $checkStmt->get_result()->fetch_assoc()['count'];
    $checkStmt->close();
    
    if ($ticketCount > 0) {
        $deleteError = "Schedule cannot be deleted because tickets have already been sold.";
    } else {
        // Delete schedule from database
        $deleteStmt = $conn->prepare("DELETE FROM showtimes WHERE id = ?");
        $deleteStmt->bind_param("i", $showtime_id);
        
        if ($deleteStmt->execute()) {
            $deleteSuccess = "Schedule successfully deleted.";
        } else {
            $deleteError = "Failed to delete schedule: " . $conn->error;
        }
        
        $deleteStmt->close();
    }
    
    // Redirect after delete
    if (isset($_GET['redirect'])) {
        header("Location: " . $_GET['redirect'] . ($deleteSuccess ? "&success=".urlencode($deleteSuccess) : "&error=".urlencode($deleteError)));
    } else {
        header("Location: manage_schedules.php" . ($deleteSuccess ? "?success=".urlencode($deleteSuccess) : "?error=".urlencode($deleteError)));
    }
    exit;
}

// Pagination setup
$limit = 20; // Items per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Process filter
$whereConditions = [];
$params = [];
$paramTypes = '';

// Get filter parameters
$filterOptions = [
    'movie_id' => ['type' => 'i', 'condition' => "s.movie_id = ?"],
    'theater_id' => ['type' => 'i', 'condition' => "s.theater_id = ?"],
    'date_from' => ['type' => 's', 'condition' => "s.showdate >= ?"],
    'date_to' => ['type' => 's', 'condition' => "s.showdate <= ?"]
];

// Process all filter options
foreach ($filterOptions as $param => $options) {
    if (isset($_GET[$param]) && $_GET[$param] !== '') {
        $whereConditions[] = $options['condition'];
        
        if ($options['type'] === 'i') {
            $params[] = intval($_GET[$param]);
        } else {
            $params[] = $_GET[$param];
        }
        
        $paramTypes .= $options['type'];
        $filterParams[$param] = $_GET[$param];
    }
}

// Construct WHERE clause
$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : '';

// Get total count for pagination
$countQuery = "SELECT COUNT(*) AS total FROM showtimes s $whereClause";
$stmt = $conn->prepare($countQuery);

if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}

$stmt->execute();
$totalResults = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Calculate total pages
$totalPages = max(1, ceil($totalResults / $limit));
$page = min($page, $totalPages); // Ensure page is within valid range

// Get showtimes with pagination
$query = "
    SELECT 
        s.*,
        m.title AS movie_title,
        m.poster_path,
        t.name AS theater_name
    FROM 
        showtimes s
    JOIN 
        movies m ON s.movie_id = m.id
    JOIN 
        theaters t ON s.theater_id = t.id
    $whereClause
    ORDER BY 
        s.showdate DESC, s.showtime ASC
    LIMIT ?, ?
";

$stmt = $conn->prepare($query);
$limitParams = array_merge($params, [$offset, $limit]);
$limitParamTypes = $paramTypes . 'ii';
$stmt->bind_param($limitParamTypes, ...$limitParams);
$stmt->execute();
$showtimes = $stmt->get_result();
$stmt->close();

// Get all movies for filter
$moviesQuery = $conn->query("SELECT id, title FROM movies ORDER BY title");
$movies = [];
while ($movie = $moviesQuery->fetch_assoc()) {
    $movies[] = $movie;
}

// Get all theaters for filter
$theatersQuery = $conn->query("SELECT id, name FROM theaters ORDER BY name");
$theaters = [];
while ($theater = $theatersQuery->fetch_assoc()) {
    $theaters[] = $theater;
}

// Helper function to build query string for pagination
function buildQueryString($params, $exclude = []) {
    $filteredParams = array_diff_key($params, array_flip($exclude));
    return !empty($filteredParams) ? '&' . http_build_query($filteredParams) : '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* Custom styles for manage schedules page */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-content {
            flex-grow: 1;
            padding: 2rem;
            background-color: #f8f9fa;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .admin-actions {
            display: flex;
            gap: 1rem;
        }
        
        .btn-primary {
            padding: 0.75rem 1.5rem;
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
        }
        
        .filter-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
        }
        
        .filter-group {
            flex: 1;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #344054;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
        }
        
        .filter-actions {
            display: flex;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .btn-filter,
        .btn-reset {
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-filter {
            background-color: #4361ee;
            color: white;
            border: none;
        }
        
        .btn-filter:hover {
            background-color: #3a56d4;
        }
        
        .btn-reset {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-reset:hover {
            background-color: #e5e7eb;
        }
        
        .schedule-cards {
            margin-bottom: 2rem;
        }
        
        .schedule-date {
            margin-bottom: 1.5rem;
        }
        
        .schedule-date h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
        }
        
        .schedule-items {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .schedule-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .schedule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .schedule-movie {
            display: flex;
            align-items: center;
            flex: 2;
            min-width: 0;
        }
        
        .movie-thumb {
            width: 60px;
            height: 90px;
            margin-right: 1rem;
            border-radius: 4px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .movie-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .no-poster-thumb {
            width: 100%;
            height: 100%;
            background-color: #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 1.5rem;
        }
        
        .movie-info {
            min-width: 0;
        }
        
        .movie-info h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .theater-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            color: #4b5563;
            font-size: 0.9rem;
        }
        
        .theater-name {
            font-weight: 600;
        }
        
        .seat-info {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .schedule-time {
            flex: 0.5;
            text-align: center;
            font-weight: 600;
            color: #1f2937;
            font-size: 1.2rem;
        }
        
        .schedule-price {
            flex: 0.75;
            text-align: center;
            font-weight: 600;
            color: #047857;
            font-size: 1.1rem;
        }
        
        .schedule-actions {
            flex: 0.5;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        .btn-edit,
        .btn-delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .btn-edit {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        
        .btn-edit:hover {
            background-color: #e5e7eb;
            color: #1f2937;
        }
        
        .btn-delete {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .btn-delete:hover {
            background-color: #fecaca;
            color: #991b1b;
        }
        
        .no-data {
            padding: 2rem;
            text-align: center;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            color: #6b7280;
            font-style: italic;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            color: #4b5563;
            background-color: #f3f4f6;
            transition: background-color 0.2s;
        }
        
        .page-link:hover {
            background-color: #e5e7eb;
        }
        
        .page-link.active {
            background-color: #4361ee;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border-left: 4px solid #b91c1c;
            color: #b91c1c;
        }
        
        .alert-success {
            background-color: #dcfce7;
            border-left: 4px solid #14532d;
            color: #14532d;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .schedule-card {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .schedule-movie {
                margin-bottom: 1rem;
                width: 100%;
            }
            
            .schedule-time,
            .schedule-price {
                width: 100%;
                text-align: left;
                margin-bottom: 0.5rem;
            }
            
            .schedule-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
        
        /* Modal styles */
        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s, visibility 0.2s;
        }
        
        .modal-backdrop.show {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            transform: translateY(-20px);
            transition: transform 0.2s;
        }
        
        .modal-backdrop.show .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-title {
            margin: 0;
            font-size: 1.25rem;
            color: #1f2937;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #6b7280;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 1.5rem;
            font-size: 1rem;
            color: #4b5563;
        }
        
        .modal-footer {
            padding: 1.25rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        
        .btn-secondary {
            padding: 0.6rem 1.2rem;
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-danger {
            padding: 0.6rem 1.2rem;
            background-color: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../partials/sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header">
                <h2><i class="fas fa-calendar-alt"></i> Manage Schedules</h2>
                <div class="admin-actions">
                    <a href="add_showtime.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Add New Schedule
                    </a>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>
            
            <!-- Filter form -->
            <div class="filter-container">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="movie_id">Movie</label>
                            <select name="movie_id" id="movie_id">
                                <option value="">All Movies</option>
                                <?php foreach ($movies as $movie): ?>
                                    <option value="<?php echo $movie['id']; ?>" <?php echo (isset($_GET['movie_id']) && $_GET['movie_id'] == $movie['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="theater_id">Theater</label>
                            <select name="theater_id" id="theater_id">
                                <option value="">All Theaters</option>
                                <?php foreach ($theaters as $theater): ?>
                                    <option value="<?php echo $theater['id']; ?>" <?php echo (isset($_GET['theater_id']) && $_GET['theater_id'] == $theater['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($theater['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                        
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="date_from">Start Date</label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">End Date</label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn-reset">
                            <i class="fas fa-undo"></i> Reset
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Data Summary -->
            <div class="data-summary">
                <p>Showing <?php echo $showtimes->num_rows; ?> of <?php echo $totalResults; ?> schedules</p>
            </div>
            
            <!-- Showtimes list -->
            <div class="data-list">
                <?php if ($showtimes->num_rows > 0): ?>
                    <div class="schedule-cards">
                        <?php 
                        $current_date = '';
                        while ($showtime = $showtimes->fetch_assoc()): 
                            $show_date = date('Y-m-d', strtotime($showtime['showdate']));
                            
                            if ($show_date != $current_date):
                                if ($current_date != '') echo '</div></div>'; // Close the previous day's container
                                $current_date = $show_date;
                        ?>
                            <div class="schedule-date">
                                <h3><i class="far fa-calendar"></i> <?php echo date('l, d F Y', strtotime($showtime['showdate'])); ?></h3>
                                <div class="schedule-items">
                        <?php endif; ?>
                                    <div class="schedule-card">
                                        <div class="schedule-movie">
                                        <div class="movie-thumb">
                                                <?php if (!empty($showtime['poster_path'])): ?>
                                                    <img src="<?php echo !empty($showtime['poster_path']) && strpos($showtime['poster_path'], 'http') === 0 ? 
                                                         htmlspecialchars($showtime['poster_path']) : 
                                                         '../../uploads/posters/' . htmlspecialchars($showtime['poster_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($showtime['movie_title']); ?>">
                                                <?php else: ?>
                                                    <div class="no-poster-thumb"><i class="fas fa-film"></i></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="movie-info">
                                                <h4><?php echo htmlspecialchars($showtime['movie_title']); ?></h4>
                                                <div class="theater-info">
                                                    <span class="theater-name"><i class="fas fa-tv"></i> <?php echo htmlspecialchars($showtime['theater_name']); ?></span>
                                                    <span class="seat-info">
                                                        <i class="fas fa-chair"></i> <?php echo $showtime['available_seats']; ?> seats available
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="schedule-time">
                                            <i class="far fa-clock"></i> <?php echo date('H:i', strtotime($showtime['showtime'])); ?>
                                        </div>
                                        <div class="schedule-price">
                                            <i class="fas fa-tag"></i> Rp <?php echo number_format($showtime['price'], 0, ',', '.'); ?>
                                        </div>
                                        <div class="schedule-actions">
                                            <a href="edit_showtime.php?id=<?php echo $showtime['id']; ?>" class="btn-edit" title="Edit Schedule">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0);" onclick="showDeleteModal(<?php echo $showtime['id']; ?>, '<?php echo addslashes($showtime['movie_title']); ?>', '<?php echo date('d/m/Y', strtotime($showtime['showdate'])); ?>', '<?php echo date('H:i', strtotime($showtime['showtime'])); ?>')" 
                                               class="btn-delete" title="Delete Schedule">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                        <?php endwhile; ?>
                        <?php if ($current_date != '') echo '</div></div>'; // Close the last day's container ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=1<?php echo buildQueryString($_GET, ['page']); ?>" class="page-link" title="First Page">«</a>
                                <a href="?page=<?php echo $page - 1 . buildQueryString($_GET, ['page']); ?>" class="page-link" title="Previous Page">‹</a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <a href="?page=<?php echo $i . buildQueryString($_GET, ['page']); ?>" 
                                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1 . buildQueryString($_GET, ['page']); ?>" class="page-link" title="Next Page">›</a>
                                <a href="?page=<?php echo $totalPages . buildQueryString($_GET, ['page']); ?>" class="page-link" title="Last Page">»</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-calendar-times fa-3x" style="margin-bottom: 1rem; color: #9ca3af;"></i>
                        <p>No schedules found</p>
                        <?php if (!empty($filterParams)): ?>
                            <p>Try changing your search filters</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
  <!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal-backdrop">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Delete Confirmation</h3>
            <button type="button" class="modal-close" onclick="hideDeleteModal()">×</button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete the schedule for <strong id="deleteMovieTitle"></strong>?</p>
            <p>Date: <span id="deleteDate"></span> - <span id="deleteTime"></span></p>
            <p>This action cannot be undone and will permanently remove the schedule.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="hideDeleteModal()">Cancel</button>
            <a href="#" id="deleteConfirmBtn" class="btn-danger">Delete</a>
        </div>
    </div>
</div>

<script>
    // Show delete confirmation modal
    function showDeleteModal(id, movieTitle, date, time) {
        document.getElementById('deleteMovieTitle').textContent = movieTitle;
        document.getElementById('deleteDate').textContent = date;
        document.getElementById('deleteTime').textContent = time;
        document.getElementById('deleteConfirmBtn').href = 'manage_schedules.php?delete=' + id + '&redirect=' + encodeURIComponent(window.location.href);
        document.getElementById('deleteModal').classList.add('show');
    }

    // Hide delete confirmation modal
    function hideDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
    }

    // Close modal when clicking outside the modal content
    document.getElementById('deleteModal').addEventListener('click', function(event) {
        if (event.target === this) {
            hideDeleteModal();
        }
    });

    // Prevent event propagation when clicking on modal content
    document.querySelector('.modal-content').addEventListener('click', function(event) {
        event.stopPropagation();
    });

    // Set default date values if not set
    document.addEventListener('DOMContentLoaded', function() {
        const dateFromInput = document.getElementById('date_from');
        const dateToInput = document.getElementById('date_to');

        if (!dateFromInput.value && !dateToInput.value) {
            // Optional: Set default date range (e.g., today and 30 days later)
            // Uncomment the below lines if you want to set default dates
            /*
            const today = new Date();
            const thirtyDaysLater = new Date();
            thirtyDaysLater.setDate(today.getDate() + 30);

            dateFromInput.value = today.toISOString().substr(0, 10);
            dateToInput.value = thirtyDaysLater.toISOString().substr(0, 10);
            */
        }
    });
</script>
