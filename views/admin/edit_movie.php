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
$pageTitle = "Edit Movie - Cineplex21";

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

$errors = [];
$success = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration = intval($_POST['duration']);
    $genre = trim($_POST['genre']);
    $rating = trim($_POST['rating']);
    $release_date = trim($_POST['release_date']);
    $status = isset($_POST['status']) ? 'now-showing' : 'inactive';
    
    if (empty($title)) {
        $errors[] = "Movie title is required.";
    }
    
    if (empty($description)) {
        $errors[] = "Movie description is required.";
    }
    
    if ($duration <= 0) {
        $errors[] = "Movie duration must be greater than 0 minutes.";
    }
    
    if (empty($genre)) {
        $errors[] = "Movie genre is required.";
    }
    
    if (empty($rating)) {
        $errors[] = "Movie rating is required.";
    }
    
    // Handle poster upload if there's a new file
    $poster_url = $movie['poster_url']; // Default to existing poster
    
    if (isset($_FILES['poster']) && $_FILES['poster']['size'] > 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['poster']['type'], $allowedTypes)) {
            $errors[] = "Poster file format must be JPEG or PNG.";
        } elseif ($_FILES['poster']['size'] > $maxSize) {
            $errors[] = "Poster file size must not exceed 2MB.";
        } else {
            $uploadDir = "../../uploads/posters/";
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Create unique filename
            $fileExtension = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
            $new_poster_url = uniqid('movie_') . '.' . $fileExtension;
            $targetFile = $uploadDir . $new_poster_url;
            
            if (move_uploaded_file($_FILES['poster']['tmp_name'], $targetFile)) {
                // Delete old poster if upload successful and old poster exists
                if (!empty($movie['poster_url']) && file_exists($uploadDir . $movie['poster_url'])) {
                    unlink($uploadDir . $movie['poster_url']);
                }
                $poster_url = $new_poster_url;
            } else {
                $errors[] = "Failed to upload movie poster.";
            }
        }
    }
    
    // Remove poster if "remove_poster" is checked
    if (isset($_POST['remove_poster']) && $_POST['remove_poster'] == 1 && !isset($_FILES['poster']['size'])) {
        $uploadDir = "../../uploads/posters/";
        if (!empty($movie['poster_path']) && file_exists($uploadDir . $movie['poster_path'])) {
            unlink($uploadDir . $movie['poster_path']);
        }
        $poster_url = '';
    }
    
    // If no errors, update movie data
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE movies SET 
                               title = ?, 
                               description = ?, 
                               duration = ?, 
                               genre = ?, 
                               rating = ?, 
                               release_date = ?, 
                               poster_url = ?, 
                               status = ?,
                               updated_at = NOW()
                               WHERE id = ?");
                               
        $stmt->bind_param("ssisssssi", $title, $description, $duration, $genre, $rating, $release_date, $poster_url, $status, $movie_id);
        
        if ($stmt->execute()) {
            $success = "Movie updated successfully.";
            
            // Refresh movie data
            $movieQuery = $conn->prepare("SELECT * FROM movies WHERE id = ?");
            $movieQuery->bind_param("i", $movie_id);
            $movieQuery->execute();
            $movieResult = $movieQuery->get_result();
            $movie = $movieResult->fetch_assoc();
            $movieQuery->close();
        } else {
            $errors[] = "Failed to update movie: " . $stmt->error;
        }
        
        $stmt->close();
    }
}
?>

<div class="admin-layout">
    <?php include '../partials/sidebar.php'; ?>
    <div class="admin-content">
        <div class="admin-header">
            <div class="header-title">
                <a href="view_movie.php?id=<?php echo $movie_id; ?>" class="back-button">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h2><i class="fas fa-film"></i> Edit Movie</h2>
            </div>
            <div class="admin-actions">
                <a href="view_movie.php?id=<?php echo $movie_id; ?>" class="btn-secondary">
                    <i class="fas fa-info-circle"></i> Movie Details
                </a>
                <a href="manage_movies.php" class="btn-secondary">
                    <i class="fas fa-list"></i> Movie List
                </a>
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <div class="alert-icon"><i class="fas fa-exclamation-circle"></i></div>
                <div class="alert-content">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
                <div class="alert-content">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form action="" method="POST" enctype="multipart/form-data" class="admin-form">
                <div class="form-section">
                    <h3 class="section-title">Basic Information</h3>
                    
                    <div class="form-group">
                        <label for="title">Movie Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($movie['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($movie['description']); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-col">
                            <label for="duration">Duration (minutes) <span class="required">*</span></label>
                            <input type="number" id="duration" name="duration" min="1" value="<?php echo intval($movie['duration']); ?>" required>
                        </div>
                        
                        <div class="form-group form-col">
                            <label for="genre">Genre <span class="required">*</span></label>
                            <input type="text" id="genre" name="genre" value="<?php echo htmlspecialchars($movie['genre']); ?>" required>
                            <small class="form-text">Separate with commas, example: Action, Adventure, Sci-Fi</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-col">
                            <label for="rating">Rating <span class="required">*</span></label>
                            <select id="rating" name="rating" required>
                                <option value="">Select Rating</option>
                                <option value="G" <?php echo ($movie['rating'] === 'G') ? 'selected' : ''; ?>>G (General Audiences)</option>
                                <option value="PG" <?php echo ($movie['rating'] === 'PG') ? 'selected' : ''; ?>>PG (Parental Guidance)</option>
                                <option value="PG-13" <?php echo ($movie['rating'] === 'PG-13') ? 'selected' : ''; ?>>PG-13 (13 Years and Above)</option>
                                <option value="R" <?php echo ($movie['rating'] === 'R') ? 'selected' : ''; ?>>R (Restricted)</option>
                                <option value="NC-17" <?php echo ($movie['rating'] === 'NC-17') ? 'selected' : ''; ?>>NC-17 (17 Years and Above)</option>
                            </select>
                        </div>
                        
                        <div class="form-group form-col">
                            <label for="release_date">Release Date</label>
                            <input type="date" id="release_date" name="release_date" value="<?php echo htmlspecialchars($movie['release_date']); ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 class="section-title">Poster & Status</h3>
                    
                    <div class="form-group poster-container">
                        <label>Movie Poster</label>
                        
                        <div class="poster-upload-area">
                            <div class="current-poster-container">
                                <?php if (!empty($movie['poster_path'])): ?>
                                    <div class="current-poster">
                                        <img src="../../uploads/posters/<?php echo htmlspecialchars($movie['poster_path']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="poster-preview">
                                        <div class="poster-options">
                                            <div class="checkbox-container">
                                                <input type="checkbox" id="remove_poster" name="remove_poster" value="1">
                                                <label for="remove_poster">Remove current poster</label>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="no-poster">
                                        <i class="fas fa-film"></i>
                                        <span>No poster currently</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="poster-upload">
                                <label for="poster" class="upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Upload New Poster</span>
                                </label>
                                <input type="file" id="poster" name="poster" accept="image/jpeg, image/png" class="file-input">
                                <small class="form-text">Format: JPG or PNG, maximum 2MB</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group status-toggle">
                        <label>Movie Status</label>
                        <div class="toggle-container">
                            <input type="checkbox" id="status" name="status" <?php echo ($movie['status'] === 'now-showing') ? 'checked' : ''; ?>>
                            <label for="status" class="toggle-label">
                                <div class="toggle-knob"></div>
                                <div class="toggle-text">
                                    <span class="on">Now Showing</span>
                                    <span class="off">Not Showing</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Update Movie</button>
                    <a href="view_movie.php?id=<?php echo $movie_id; ?>" class="btn-secondary"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
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

/* Alert Messages */
.alert {
    padding: 16px;
    border-radius: 6px;
    margin-bottom: 20px;
    display: flex;
    align-items: flex-start;
}

.alert-danger {
    background-color: #fef2f2;
    border-left: 4px solid #ef4444;
    color: #b91c1c;
}

.alert-success {
    background-color: #f0fdf4;
    border-left: 4px solid #22c55e;
    color: #166534;
}

.alert-icon {
    margin-right: 15px;
    font-size: 20px;
}

.alert-content {
    flex: 1;
}

.alert ul {
    margin: 5px 0 0 20px;
    padding: 0;
}

.alert li {
    margin-bottom: 5px;
}

/* Form Container */
.form-container {
    background-color: #ffffff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.form-section {
    padding: 25px;
    border-bottom: 1px solid #e9ecef;
}

.section-title {
    font-size: 18px;
    color: #2c3e50;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f3f5;
}

/* Form Elements */
.admin-form {
    width: 100%;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 0;
}

.form-col {
    flex: 1;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #495057;
}

.required {
    color: #dc3545;
}

input[type="text"],
input[type="number"],
input[type="date"],
textarea,
select {
    width: 100%;
    padding: 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    background-color: #f8f9fa;
    color: #495057;
    transition: border-color 0.3s, box-shadow 0.3s;
}

input[type="text"]:focus,
input[type="number"]:focus,
input[type="date"]:focus,
textarea:focus,
select:focus {
    border-color: #3498db;
    outline: none;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
    background-color: #ffffff;
}

.form-text {
    display: block;
    margin-top: 5px;
    font-size: 13px;
    color: #6c757d;
}

/* Poster Upload Area */
.poster-container {
    margin-bottom: 30px;
}

.poster-upload-area {
    display: flex;
    gap: 30px;
    align-items: flex-start;
}

.current-poster-container {
    flex: 0 0 200px;
}

.current-poster {
    position: relative;
}

.poster-preview {
    width: 100%;
    height: auto;
    border-radius: 6px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

.poster-options {
    margin-top: 10px;
}

.no-poster {
    width: 200px;
    height: 300px;
    background-color: #f1f3f5;
    border-radius: 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #adb5bd;
}

.no-poster i {
    font-size: 40px;
    margin-bottom: 15px;
}

.poster-upload {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 30px;
    background-color: #f8f9fa;
    border: 2px dashed #ced4da;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}

.upload-label:hover {
    border-color: #3498db;
    background-color: #e8f4fd;
}

.upload-label i {
    font-size: 36px;
    color: #3498db;
    margin-bottom: 10px;
}

.file-input {
    display: none;
}

/* Status Toggle */
.status-toggle {
    margin-top: 20px;
}

.toggle-container {
    margin-top: 10px;
}

.toggle-container input[type="checkbox"] {
    display: none;
}

.toggle-label {
    display: inline-block;
    position: relative;
    width: 140px;
    height: 34px;
    background-color: #e9ecef;
    border-radius: 34px;
    cursor: pointer;
    transition: background-color 0.3s;
    padding: 0;
}

.toggle-label .toggle-knob {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 30px;
    height: 30px;
    background-color: white;
    border-radius: 50%;
    transition: left 0.3s, transform 0.3s;
    z-index: 2;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-text {
    position: relative;
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 10px;
    box-sizing: border-box;
}

.toggle-text .on, .toggle-text .off {
    font-size: 12px;
    font-weight: 500;
    z-index: 1;
}

.toggle-text .on {
    display: none;
    margin-left: 10px;
    color: white;
}

.toggle-text .off {
    margin-left: auto;
    margin-right: 10px;
    color: #495057;
}

input[type="checkbox"]:checked + .toggle-label {
    background-color: #20c997;
}

input[type="checkbox"]:checked + .toggle-label .toggle-knob {
    left: calc(100% - 32px);
    transform: translateX(2px);
}

input[type="checkbox"]:checked + .toggle-label .toggle-text .on {
    display: block;
}

input[type="checkbox"]:checked + .toggle-label .toggle-text .off {
    display: none;
}

/* Checkbox Container */
.checkbox-container {
    display: flex;
    align-items: center;
}

.checkbox-container input[type="checkbox"] {
    margin-right: 8px;
    display: inline-block;
}

/* Form Actions */
.form-actions {
    padding: 25px;
    background-color: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    gap: 15px;
}

.btn-primary, .btn-secondary {
    padding: 12px 20px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s;
    border: none;
    cursor: pointer;
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

.btn-primary i, .btn-secondary i {
    margin-right: 8px;
}

/* Responsive Adjustments */
@media (max-width: 992px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .poster-upload-area {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .current-poster-container {
        margin-bottom: 20px;
    }
}

@media (max-width: 576px) {
    .admin-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .admin-actions {
        width: 100%;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to display image preview when uploading
    const fileInput = document.getElementById('poster');
    const removeCheckbox = document.getElementById('remove_poster');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const currentPosterContainer = document.querySelector('.current-poster-container');
                const currentPoster = document.querySelector('.current-poster');
                const noPoster = document.querySelector('.no-poster');
                
                // Create reader to read uploaded file
                const reader = new FileReader();
                
                // Callback when file is fully read
                reader.onload = function(e) {
                    // If there's already a poster
                    if (currentPoster) {
                        const imgPreview = currentPoster.querySelector('.poster-preview');
                        imgPreview.src = e.target.result;
                        imgPreview.style.opacity = '1';
                        
                        // Make sure remove checkbox is unchecked since we're uploading a new poster
                        if (removeCheckbox) {
                            removeCheckbox.checked = false;
                        }
                    } 
                    // If there's no poster yet (no-poster div)
                    else if (noPoster) {
                        // Remove no-poster div
                        noPoster.remove();
                        
                        // Create new poster div
                        const newPosterDiv = document.createElement('div');
                        newPosterDiv.className = 'current-poster';
                        
                        // Create img element for preview
                        const imgPreview = document.createElement('img');
                        imgPreview.className = 'poster-preview';
                        imgPreview.alt = 'Poster Preview';
                        imgPreview.src = e.target.result;
                        
                        // Create div for poster options (checkbox)
                        const posterOptions = document.createElement('div');
                        posterOptions.className = 'poster-options';
                        
                        // Add all to DOM
                        newPosterDiv.appendChild(imgPreview);
                        newPosterDiv.appendChild(posterOptions);
                        currentPosterContainer.appendChild(newPosterDiv);
                    }
                };
                
                // Start reading file as Data URL
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
    
    // If remove checkbox is checked, change poster preview appearance
    if (removeCheckbox) {
        removeCheckbox.addEventListener('change', function() {
            const currentPoster = document.querySelector('.current-poster');
            if (currentPoster) {
                const imgPreview = currentPoster.querySelector('.poster-preview');
                
                if (this.checked) {
                    // If checked, make poster look faded (low opacity)
                    imgPreview.style.opacity = '0.3';
                } else {
                    // If unchecked, restore normal appearance
                    imgPreview.style.opacity = '1';
                }
            }
            
            // Reset file input if checkbox is checked
            if (this.checked && fileInput) {
                fileInput.value = '';
            }
        });
    }
    
    // Hide success notifications after a few seconds
    const successAlerts = document.querySelectorAll('.alert-success');
    if (successAlerts.length > 0) {
        setTimeout(function() {
            successAlerts.forEach(function(alert) {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 500);
            });
        }, 5000);
    }
});
</script>