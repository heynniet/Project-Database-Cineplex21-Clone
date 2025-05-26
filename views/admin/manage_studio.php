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
$pageTitle = "Manage Cinemas - Cineplex21";

// Initialize variables
$deleteSuccess = $deleteError = '';
$filterParams = [];

// Process delete request
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $theater_id = intval($_GET['delete']);
    
    // Check if cinema is still used in showtimes
    $checkStmt = $conn->prepare("SELECT COUNT(*) AS count FROM showtimes WHERE theater_id = ?");
    $checkStmt->bind_param("i", $theater_id);
    $checkStmt->execute();
    $showtimeCount = $checkStmt->get_result()->fetch_assoc()['count'];
    $checkStmt->close();
    
    if ($showtimeCount > 0) {
        $deleteError = "Cinema cannot be deleted because it is still used in showtimes.";
    } else {
        // Delete cinema from database
        $deleteStmt = $conn->prepare("DELETE FROM theaters WHERE id = ?");
        $deleteStmt->bind_param("i", $theater_id);
        
        if ($deleteStmt->execute()) {
            $deleteSuccess = "Cinema successfully deleted.";
        } else {
            $deleteError = "Failed to delete cinema: " . $conn->error;
        }
        
        $deleteStmt->close();
    }
    
    // Redirect after delete
    if (isset($_GET['redirect'])) {
        header("Location: " . $_GET['redirect'] . ($deleteSuccess ? "&success=".urlencode($deleteSuccess) : "&error=".urlencode($deleteError)));
    } else {
        header("Location: manage_cinemas.php" . ($deleteSuccess ? "?success=".urlencode($deleteSuccess) : "?error=".urlencode($deleteError)));
    }
    exit;
}

// Pagination setup
$limit = 20; // Number of items per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Process filter
$whereConditions = [];
$params = [];
$paramTypes = '';

// Get filter parameters
$filterOptions = [
    'name' => ['type' => 's', 'condition' => "t.name LIKE ?"],
    'city' => ['type' => 's', 'condition' => "t.city LIKE ?"],
    'status' => ['type' => 's', 'condition' => "t.status = ?"]
];

// Process all filter options
foreach ($filterOptions as $param => $options) {
    if (isset($_GET[$param]) && $_GET[$param] !== '') {
        $whereConditions[] = $options['condition'];
        
        if ($param === 'name' || $param === 'city') {
            $params[] = '%' . $_GET[$param] . '%';
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
$countQuery = "SELECT COUNT(*) AS total FROM theaters t $whereClause";
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

// Get theaters with pagination
$query = "
    SELECT 
        t.*, 
        (SELECT COUNT(*) FROM showtimes WHERE theater_id = t.id) AS showtime_count
    FROM 
        theaters t
    $whereClause
    ORDER BY 
        t.name ASC
    LIMIT ?, ?
";

$stmt = $conn->prepare($query);
$limitParams = array_merge($params, [$offset, $limit]);
$limitParamTypes = $paramTypes . 'ii';
$stmt->bind_param($limitParamTypes, ...$limitParams);
$stmt->execute();
$theaters = $stmt->get_result();
$stmt->close();

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
        /* Custom styles for manage cinemas page */
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
        
        .data-summary {
            margin-bottom: 1rem;
            color: #4b5563;
        }
        
        .cinema-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .cinema-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }
        
        .cinema-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .cinema-header {
            position: relative;
            background-color: #4361ee;
            color: white;
            padding: 1.2rem;
        }
        
        .cinema-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0 0 0.25rem 0;
        }
        
        .cinema-location {
            font-size: 0.9rem;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .status-badge {
            position: absolute;
            top: 1.2rem;
            right: 1.2rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #10b981;
        }
        
        .status-inactive {
            background-color: #ef4444;
        }
        
        .cinema-body {
            padding: 1.2rem;
        }
        
        .cinema-details {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .cinema-detail {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .detail-icon {
            color: #6b7280;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .detail-content {
            flex: 1;
        }
        
        .detail-label {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 0.2rem;
        }
        
        .detail-value {
            font-size: 0.95rem;
            color: #1f2937;
        }
        
        .cinema-stats {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-top: 1px solid #e5e7eb;
            font-size: 0.9rem;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .stat-value {
            font-weight: 600;
            color: #1f2937;
            font-size: 1.1rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.8rem;
        }
        
        .cinema-actions {
            display: flex;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
            gap: 0.75rem;
        }
        
        .btn-action {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            text-decoration: none;
            gap: 0.35rem;
        }
        
        .btn-edit {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        
        .btn-edit:hover {
            background-color: #e5e7eb;
        }
        
        .btn-delete {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        .btn-delete:hover {
            background-color: #fecaca;
        }
        
        .no-data {
            padding: 3rem;
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
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .cinema-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../partials/sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header">
                <h2><i class="fas fa-film"></i> Manage Cinemas</h2>
                <div class="admin-actions">
                    <a href="add_cinemas.php" class="btn-primary">
                        <i class="fas fa-plus"></i> Add New Cinema
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
                            <label for="name">Cinema Name</label>
                            <input type="text" name="name" id="name" value="<?php echo isset($_GET['name']) ? htmlspecialchars($_GET['name']) : ''; ?>" placeholder="Search by cinema name...">
                        </div>
                        
                        <div class="filter-group">
                            <label for="city">City</label>
                            <input type="text" name="city" id="city" value="<?php echo isset($_GET['city']) ? htmlspecialchars($_GET['city']) : ''; ?>" placeholder="Search by city...">
                        </div>
                        
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
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
                <p>Showing <?php echo $theaters->num_rows; ?> of <?php echo $totalResults; ?> cinemas</p>
            </div>
            
            <!-- Cinemas list -->
            <?php if ($theaters->num_rows > 0): ?>
                <div class="cinema-cards">
                    <?php while ($theater = $theaters->fetch_assoc()): ?>
                        <div class="cinema-card">
                            <div class="cinema-header">
                                <h3 class="cinema-name"><?php echo htmlspecialchars($theater['name']); ?></h3>
                                <div class="cinema-location">
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($theater['city']); ?>
                                </div>
                                <span class="status-badge <?php echo $theater['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $theater['status'] === 'active' ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                            <div class="cinema-body">
                                <div class="cinema-details">
                                    <div class="cinema-detail">
                                        <div class="detail-icon">
                                            <i class="fas fa-map-marked-alt"></i>
                                        </div>
                                        <div class="detail-content">
                                            <div class="detail-label">Address</div>
                                            <div class="detail-value"><?php echo !empty($theater['address']) ? htmlspecialchars($theater['address']) : 'No address available'; ?></div>
                                        </div>
                                    </div>
                                    <div class="cinema-detail">
                                        <div class="detail-icon">
                                            <i class="fas fa-phone"></i>
                                        </div>
                                        <div class="detail-content">
                                            <div class="detail-label">Phone</div>
                                            <div class="detail-value"><?php echo !empty($theater['phone']) ? htmlspecialchars($theater['phone']) : 'No phone number available'; ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($theater['special_tag'])): ?>
                                    <div class="cinema-detail">
                                        <div class="detail-icon">
                                            <i class="fas fa-tag"></i>
                                        </div>
                                        <div class="detail-content">
                                            <div class="detail-label">Special Tag</div>
                                            <div class="detail-value"><?php echo htmlspecialchars($theater['special_tag']); ?></div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="cinema-detail">
                                        <div class="detail-icon">
                                            <i class="fas fa-chair"></i>
                                        </div>
                                        <div class="detail-content">
                                            <div class="detail-label">Total Seats</div>
                                            <div class="detail-value"><?php echo number_format($theater['total_seats']); ?> seats</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="cinema-stats">
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo $theater['showtime_count']; ?></div>
                                        <div class="stat-label">Showtimes</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-value"><?php echo date('d/m/Y', strtotime($theater['created_at'])); ?></div>
                                        <div class="stat-label">Date Created</div>
                                    </div>
                                </div>
                                <div class="cinema-actions">
                                    <a href="edit_cinema.php?id=<?php echo $theater['id']; ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="javascript:void(0);" onclick="showDeleteModal(<?php echo $theater['id']; ?>, '<?php echo addslashes($theater['name']); ?>')" 
                                       class="btn-action btn-delete">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
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
                    <i class="fas fa-film fa-3x" style="margin-bottom: 1rem; color: #9ca3af;"></i>
                    <p>No cinemas found</p>
                    <?php if (!empty($filterParams)): ?>
                        <p>Try changing your search filters</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
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
                <p>Are you sure you want to delete cinema <strong id="deleteCinemaName"></strong>?</p>
                <p>This action cannot be undone and will permanently delete this cinema.</p>
                <p><strong>Note:</strong> Cinemas with active showtimes cannot be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="hideDeleteModal()">Cancel</button>
                <a href="#" id="deleteConfirmBtn" class="btn-danger">Delete</a>
            </div>
        </div>
    </div>
   
    <script>
        // Show delete confirmation modal
        function showDeleteModal(id, cinemaName) {
            document.getElementById('deleteCinemaName').textContent = cinemaName;
            document.getElementById('deleteConfirmBtn').href = 'manage_cinemas.php?delete=' + id + '&redirect=' + encodeURIComponent(window.location.href);
            document.getElementById('deleteModal').classList.add('show');
        }
        
        // Hide delete confirmation modal
        function hideDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }
        
        // Close alert messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            // Get all alert messages
            var alerts = document.querySelectorAll('.alert');
            
            // Set timeout to hide alerts
            if (alerts.length > 0) {
                setTimeout(function() {
                    alerts.forEach(function(alert) {
                        alert.style.opacity = '0';
                        setTimeout(function() {
                            alert.style.display = 'none';
                        }, 300);
                    });
                }, 5000);
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                var modal = document.getElementById('deleteModal');
                if (event.target === modal) {
                    hideDeleteModal();
                }
            });
        });
    </script>
</body>
</html>