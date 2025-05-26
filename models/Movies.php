<?php
// Enable error reporting for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include header files
include '../views/partials/header.php';

// Cek status login sebelum menampilkan navbar
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Include navbar dengan status login
include '../views/partials/navbar.php';
include '../config/db.php';

// Get filter parameters from URL
$filter_genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'now-showing'; // Default: now-showing
$filter_rating = isset($_GET['rating']) ? $_GET['rating'] : '';

// Build base query
$query = "SELECT * FROM movies WHERE 1=1";
$params = [];

// Add filters to query
if (!empty($filter_genre)) {
    $query .= " AND genre = :genre";
    $params[':genre'] = $filter_genre;
}

if (!empty($filter_status)) {
    $query .= " AND status = :status";
    $params[':status'] = $filter_status;
}

if (!empty($filter_rating)) {
    $query .= " AND rating = :rating";
    $params[':rating'] = $filter_rating;
}

// Add sorting
$query .= " ORDER BY release_date DESC";

// Prepare and execute query
$stmt = $db->prepare($query);
$stmt->execute($params);
$movies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique genres for filter dropdown
$genreQuery = $db->query("SELECT DISTINCT genre FROM movies ORDER BY genre");
$genres = $genreQuery->fetchAll(PDO::FETCH_ASSOC);

// Get unique ratings for filter dropdown
$ratingQuery = $db->query("SELECT DISTINCT rating FROM movies ORDER BY rating");
$ratings = $ratingQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-content">
    <div class="hero-banner">
        <div class="container">
            <h1>Discover the Latest Movies</h1>
            <p>Find your perfect movie experience with our vast collection of movies. From action-packed adventures to heart-warming dramas.</p>
        </div>
    </div>

    <div class="container">
        <!-- Filter form with GET method -->
        <form class="filter-section" method="GET" action="">
            <div class="filter-options">
                <div class="filter-group">
                    <label for="genre">Genre</label>
                    <select id="genre" name="genre">
                        <option value="">All Genres</option>
                        <?php foreach($genres as $genre): ?>
                            <option value="<?= htmlspecialchars($genre['genre']) ?>" <?= ($filter_genre == $genre['genre']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($genre['genre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">All</option>
                        <option value="now-showing" <?= ($filter_status == 'now-showing') ? 'selected' : '' ?>>Now Showing</option>
                        <option value="coming-soon" <?= ($filter_status == 'coming-soon') ? 'selected' : '' ?>>Coming Soon</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="rating">Rating</label>
                    <select id="rating" name="rating">
                        <option value="">All Ratings</option>
                        <?php foreach($ratings as $rating): ?>
                            <option value="<?= htmlspecialchars($rating['rating']) ?>" <?= ($filter_rating == $rating['rating']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rating['rating']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="filter-actions">
                <button type="button" class="btn-filter btn-reset" onclick="window.location='index.php'">Reset</button>
                <button type="submit" class="btn-filter btn-apply">Apply Filter</button>
            </div>
        </form>

        <div class="section-header">
            <h2><?= (!empty($filter_status) && $filter_status == 'coming-soon') ? 'Coming Soon' : 'Now Showing' ?></h2>
        </div>

        <div class="movie-grid">
            <?php if(count($movies) == 0): ?>
                <div class="no-movies">No movies found matching your criteria.</div>
            <?php else: ?>
                <?php foreach($movies as $movie): 
                    $releaseDate = date('M d, Y', strtotime($movie['release_date']));
                ?>
                    <div class="movie-card">
                        <div class="movie-poster">
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
                            <div class="rating"><?= htmlspecialchars($movie['rating']) ?></div>
                        </div>
                        <div class="movie-info">
                            <h3><?= htmlspecialchars($movie['title']) ?></h3>
                            <div class="movie-actions">
                                <?php if($isLoggedIn): ?>
                                    <a href="Ticket.php?id=<?= $movie['id'] ?>" class="btn-buy">Buy Tickets</a>
                                <?php else: ?>
                                    <a href="login.php?redirect=Ticket.php?id=<?= $movie['id'] ?>" class="btn-buy">Login to Buy</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../views/partials/footer.php'; ?>