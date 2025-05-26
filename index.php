<?php
// Page title
$pageTitle = "Home - SimpleCinema";

// Include header files
include 'views/partials/header.php';

// Cek status login sebelum menampilkan navbar
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Include navbar dengan status login
include 'views/partials/navbar.php';
include 'config/db.php';

// Fetch Now Playing Movies (limit to 4)
$nowPlayingQuery = "SELECT * FROM movies WHERE status = 'now-showing' ORDER BY release_date DESC LIMIT 4";
$nowPlayingStmt = $db->query($nowPlayingQuery);
$nowPlayingMovies = $nowPlayingStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="main-content">
    <!-- Hero Banner -->
    <div class="hero-banner">
        <div class="container">
            <h1>Welcome to Cineplex 21</h1>
            <p>Your one-stop destination for the latest movies</p>
            <?php if($isLoggedIn): ?>
                <p>Hello, <?= $_SESSION['username'] ?>! Ready to watch some movies?</p>
            <?php else: ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Now Playing Section -->
    <div class="container">
        <div class="section-header">
            <h2>Now Playing</h2>
            <a href="movies.php" class="view-all">View All</a>
        </div>
        
        <div class="movie-grid">
            <?php if(count($nowPlayingMovies) == 0): ?>
                <div class="no-movies">No movies currently playing.</div>
            <?php else: ?>
                <?php foreach($nowPlayingMovies as $movie): ?>
                    <div class="movie-card">
                        <div class="movie-poster">
                        <?php
$posterFilename = htmlspecialchars($movie['poster_path']);
$posterURL = '/Cineplex21/uploads/posters/' . $posterFilename;
?>
<img src="<?= $posterURL ?>" 
     alt="<?= htmlspecialchars($movie['title']) ?>" 
     onerror="this.src='https://via.placeholder.com/230x345?text=<?= urlencode($movie['title']) ?>'">

                            <span class="rating"><?= htmlspecialchars($movie['rating']) ?></span>
                        </div>
                        <div class="movie-info">
                            <h3><?= htmlspecialchars($movie['title']) ?></h3>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'views/partials/footer.php'; ?>