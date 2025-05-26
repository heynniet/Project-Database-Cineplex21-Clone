<?php
// Enable error reporting for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include '../config/db.php';

// Get parameters
$movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
$theater_id = isset($_GET['theater_id']) ? intval($_GET['theater_id']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';

// Validate inputs
if ($movie_id === 0 || $theater_id === 0 || empty($date)) {
    echo "<div class='no-times'>Invalid parameters</div>";
    exit;
}

try {
    // Get available times for this theater and date
    $timeQuery = $db->prepare("SELECT DISTINCT showtime as time 
                    FROM showtimes 
                    WHERE movie_id = :movie_id 
                    AND theater_id = :theater_id 
                    AND showdate = :date
                    ORDER BY showtime");
    $timeQuery->execute([
        ':movie_id' => $movie_id,
        ':theater_id' => $theater_id,
        ':date' => $date
    ]);
    $times = $timeQuery->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($times) > 0) {
        foreach($times as $time) {
            $formattedTime = date('H:i', strtotime($time));
            ?>
            <div class="option">
                <input type="radio" name="time" id="time<?= $formattedTime ?>" value="<?= $time ?>" required>
                <label for="time<?= $formattedTime ?>">
                    <div class="option-title"><?= $formattedTime ?></div>
                </label>
            </div>
            <?php
        }
    } else {
        echo "<div class='no-times'>No showtimes available for this selection.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='no-times'>Error retrieving showtimes</div>";
}
?>