<?php
// Page title
$pageTitle = "Theater Locations - MovieMax";

// Include header files
include '../views/partials/header.php';

// Check login status before displaying navbar
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Include navbar with login status
include '../views/partials/navbar.php';
include '../config/db.php';

// Fetch all cities with theater counts
$citiesQuery = "SELECT city, COUNT(*) as theater_count FROM theaters GROUP BY city ORDER BY city";
$citiesStmt = $db->query($citiesQuery);
$cities = $citiesStmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected city from query parameter or default to 'all'
$selectedCity = isset($_GET['city']) ? $_GET['city'] : 'all';

// Prepare theater query
if ($selectedCity !== 'all') {
    $theatersQuery = "SELECT * FROM theaters WHERE city = :city AND active = 1 ORDER BY name";
    $theatersStmt = $db->prepare($theatersQuery);
    $theatersStmt->bindParam(':city', $selectedCity);
    $theatersStmt->execute();
} else {
    $theatersQuery = "SELECT * FROM theaters WHERE active = 1 ORDER BY city, name";
    $theatersStmt = $db->query($theatersQuery);
}

$theaters = $theatersStmt->fetchAll(PDO::FETCH_ASSOC);

// Group theaters by city
$theatersByCity = [];
foreach ($theaters as $theater) {
    $theatersByCity[$theater['city']][] = $theater;
}
?>

<!-- Main Content -->
<div class="main-content">
    <!-- Hero Banner -->
    <div class="hero-banner">
        <div class="container">
            <h1>Our Theater Locations</h1>
            <p>Find the nearest MovieMax theater in your city and enjoy the ultimate movie experience</p>
        </div>
    </div>

    <!-- Theater Locations -->
    <div class="container">
        <!-- Theater Filter -->
        <div class="theater-filter">
            <div class="filter-group">
                <label for="city-filter">Select City:</label>
                <select id="city-filter" class="filter-select" onchange="filterByCity(this.value)">
                    <option value="all" <?= $selectedCity == 'all' ? 'selected' : '' ?>>All Cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= strtolower(htmlspecialchars($city['city'])) ?>" 
                                <?= strtolower($selectedCity) == strtolower($city['city']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city['city']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Search theater...">
                <button class="search-btn" onclick="searchTheaters()">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>
        
        <!-- City Navigation -->
        <?php if ($selectedCity == 'all' && count($cities) > 1): ?>
        <div class="city-nav">
            <a class="city-nav-item active" href="#" onclick="scrollToAllCities(); return false;">All Cities</a>
            <?php foreach ($cities as $city): ?>
                <a class="city-nav-item" href="#city-<?= strtolower(preg_replace('/\s+/', '-', htmlspecialchars($city['city']))) ?>" 
                   onclick="scrollToCity('<?= strtolower(preg_replace('/\s+/', '-', htmlspecialchars($city['city']))) ?>'); return false;">
                    <?= htmlspecialchars($city['city']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($theaters)): ?>
            <div class="no-theaters">No theaters found.</div>
        <?php else: ?>
            <?php foreach ($theatersByCity as $city => $cityTheaters): ?>
                <!-- Theater List by City -->
                <div class="city-container" id="city-<?= strtolower(preg_replace('/\s+/', '-', htmlspecialchars($city))) ?>">
                    <div class="theater-city-section">
                        <div class="city-header">
                            <h2><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($city) ?></h2>
                            <span class="theater-count"><?= count($cityTheaters) ?> theaters</span>
                        </div>
                        
                        <div class="theater-grid">
                            <?php foreach ($cityTheaters as $theater): ?>
                                <!-- Theater Card -->
                                <div class="theater-card">
                                    <div class="theater-card-header">
                                        <h3><?= htmlspecialchars($theater['name']) ?></h3>
                                        <?php if (!empty($theater['special_tag'])): ?>
                                            <span class="premium-tag"><?= htmlspecialchars($theater['special_tag']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="theater-details">
                                        <p><i class="fas fa-map"></i> <?= htmlspecialchars($theater['address']) ?></p>
                                        <p><i class="fas fa-phone"></i> <?= htmlspecialchars($theater['phone']) ?></p>
                                        <p><i class="fas fa-chair"></i> <?= htmlspecialchars($theater['total_seats']) ?> seats</p>
                                        <div class="theater-facilities">
                                            <?php
                                            // Parse facilities JSON string to array
                                            $facilities = json_decode($theater['facilities'], true);
                                            if (is_array($facilities)) {
                                                foreach ($facilities as $facility => $icon) {
                                                    echo '<span class="facility-icon" title="' . htmlspecialchars($facility) . '">';
                                                    echo '<i class="fas fa-' . htmlspecialchars($icon) . '"></i>';
                                                    echo '</span>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="theater-actions">
                                        <a href="booking.php?theater_id=<?= $theater['id'] ?>" class="btn-buy">Book Now</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function filterByCity(city) {
    if (city === 'all') {
        window.location.href = 'theaters.php';
    } else {
        // Check if we should navigate to another page or scroll to the city section
        if (document.getElementById('city-' + city)) {
            // City exists on current page, scroll to it
            document.getElementById('city-' + city).scrollIntoView({
                behavior: 'smooth'
            });
        } else {
            // Navigate to the page with the city filter
            window.location.href = 'theaters.php?city=' + city;
        }
    }
}

function searchTheaters() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const theaterCards = document.querySelectorAll('.theater-card');
    const cityContainers = document.querySelectorAll('.city-container');
    
    let foundInCity = {};
    
    // First pass: check all theaters and mark which cities have matches
    theaterCards.forEach(card => {
        const theaterName = card.querySelector('h3').textContent.toLowerCase();
        const theaterAddress = card.querySelector('.theater-details p:first-child').textContent.toLowerCase();
        const cityContainer = card.closest('.city-container');
        const cityId = cityContainer.id;
        
        if (theaterName.includes(searchTerm) || theaterAddress.includes(searchTerm)) {
            card.style.display = 'block';
            foundInCity[cityId] = true;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Second pass: show/hide city containers based on whether they have matches
    cityContainers.forEach(container => {
        if (foundInCity[container.id]) {
            container.style.display = 'block';
        } else {
            container.style.display = 'none';
        }
    });
}

function scrollToCity(cityId) {
    document.querySelectorAll('.city-nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    event.target.classList.add('active');
    
    document.getElementById('city-' + cityId).scrollIntoView({
        behavior: 'smooth'
    });
}

function scrollToAllCities() {
    document.querySelectorAll('.city-nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    event.target.classList.add('active');
    
    // Scroll to the top of the theaters section
    document.querySelector('.theater-filter').scrollIntoView({
        behavior: 'smooth'
    });
}
</script>

<?php include '../views/partials/footer.php'; ?>