<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize session and database connection
session_start();

// Redirect if user is not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}

// Include required files
require_once '../partials/header.php';
require_once '../../config/db.php';

// Page title for header
$pageTitle = "Add New Movie - Cineplex21";

// Initialize variables
$errors = [];
$success = '';
$formData = [
    'title' => '',
    'description' => '',
    'duration' => '',
    'genre' => '',
    'rating' => '',
    'release_date' => '',
    'status' => false
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate data
    $formData = [
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description']),
        'duration' => intval($_POST['duration']),
        'genre' => trim($_POST['genre']),
        'rating' => trim($_POST['rating']),
        'release_date' => trim($_POST['release_date']),
        'status' => isset($_POST['status']) ? true : false
    ];
    
    // Validate required fields
    if (empty($formData['title'])) {
        $errors[] = "Movie title is required.";
    }
    
    if (empty($formData['description'])) {
        $errors[] = "Movie description is required.";
    }
    
    if ($formData['duration'] <= 0) {
        $errors[] = "Movie duration must be greater than 0 minutes.";
    }
    
    if (empty($formData['genre'])) {
        $errors[] = "Movie genre is required.";
    }
    
    if (empty($formData['rating'])) {
        $errors[] = "Movie rating is required.";
    }
    
    // Handle poster upload
    $poster_url = '';
    if (isset($_FILES['poster']) && $_FILES['poster']['size'] > 0) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['poster']['type'], $allowedTypes)) {
            $errors[] = "Poster file format must be JPEG or PNG.";
        } elseif ($_FILES['poster']['size'] > $maxSize) {
            $errors[] = "Maximum poster file size is 2MB.";
        } else {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/Cineplex21/uploads/posters/";
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Create unique filename
            $fileExtension = pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION);
            $poster_url = uniqid('movie_') . '.' . $fileExtension;
            $targetFile = $uploadDir . $poster_url;
            
            if (!move_uploaded_file($_FILES['poster']['tmp_name'], $targetFile)) {
                $errors[] = "Failed to upload movie poster.";
                $poster_url = '';
            }
        }
    }
    
    // Save movie data if no errors
    if (empty($errors)) {
        $status = $formData['status'] ? 1 : 0;
        
        $stmt = $conn->prepare("INSERT INTO movies (
            title, description, duration, genre, rating, 
            release_date, poster_path, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                                
        $stmt->bind_param(
            "ssissssi", 
            $formData['title'], 
            $formData['description'], 
            $formData['duration'], 
            $formData['genre'], 
            $formData['rating'], 
            $formData['release_date'], 
            $poster_url, 
            $status
        );

        if ($stmt->execute()) {
            $success = "New movie successfully added!";
            // Reset form after success
            $formData = [
                'title' => '',
                'description' => '',
                'duration' => '',
                'genre' => '',
                'rating' => '',
                'release_date' => '',
                'status' => false
            ];
        } else {
            $errors[] = "Failed to save movie: " . $stmt->error;
            
            // Delete poster file if database error
            if (!empty($poster_url) && file_exists($targetFile)) {
                unlink($targetFile);
            }
        }
        
        $stmt->close();
    }
}

// Function to keep form values after submit with error
function getFormValue($key) {
    global $formData;
    return htmlspecialchars($formData[$key] ?? '');
}

// Function to check if option should be selected
function isSelected($key, $value) {
    global $formData;
    return (isset($formData[$key]) && $formData[$key] === $value) ? 'selected' : '';
}

// Function to check checkbox
function isChecked($key) {
    global $formData;
    return (isset($formData[$key]) && $formData[$key]) ? 'checked' : '';
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
        /* Custom styles for add movie page */
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
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .admin-actions {
            display: flex;
            gap: 1rem;
        }
        
        .form-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .form-col {
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #344054;
        }
        
        .required {
            color: #e63946;
        }
        
        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="date"]:focus,
        textarea:focus,
        select:focus {
            border-color: #4361ee;
            outline: none;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .form-text {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn-primary {
            padding: 0.75rem 1.5rem;
            background-color: #4361ee;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            background-color: #3a56d4;
        }
        
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.2s;
        }
        
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
        
        .alert-success {
            background-color: #dcfce7;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .alert ul {
            margin: 0.5rem 0 0 1.5rem;
            padding: 0;
        }
        
        .alert li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include '../partials/sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header">
                <h2><i class="fas fa-film"></i> Add New Movie</h2>
                <div class="admin-actions">
                    <a href="manage_movies.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <strong><i class="fas fa-exclamation-circle"></i> Errors occurred:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" class="admin-form">
                    <div class="form-group">
                        <label for="title">Movie Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" value="<?php echo getFormValue('title'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" rows="4" required><?php echo getFormValue('description'); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-col">
                            <label for="duration">Duration (minutes) <span class="required">*</span></label>
                            <input type="number" id="duration" name="duration" min="1" value="<?php echo getFormValue('duration'); ?>" required>
                        </div>
                        
                        <div class="form-group form-col">
                            <label for="genre">Genre <span class="required">*</span></label>
                            <input type="text" id="genre" name="genre" value="<?php echo getFormValue('genre'); ?>" placeholder="Action, Comedy, Drama, etc" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group form-col">
                            <label for="rating">Rating <span class="required">*</span></label>
                            <select id="rating" name="rating" required>
                                <option value="">Select Rating</option>
                                <option value="G" <?php echo isSelected('rating', 'G'); ?>>G (General Audiences)</option>
                                <option value="PG" <?php echo isSelected('rating', 'PG'); ?>>PG (Parental Guidance)</option>
                                <option value="PG-13" <?php echo isSelected('rating', 'PG-13'); ?>>PG-13 (13 Years and Older)</option>
                                <option value="R" <?php echo isSelected('rating', 'R'); ?>>R (Restricted)</option>
                                <option value="NC-17" <?php echo isSelected('rating', 'NC-17'); ?>>NC-17 (17 Years and Older)</option>
                            </select>
                        </div>
                        
                        <div class="form-group form-col">
                            <label for="release_date">Release Date</label>
                            <input type="date" id="release_date" name="release_date" value="<?php echo getFormValue('release_date'); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="poster">Movie Poster</label>
                        <input type="file" id="poster" name="poster" accept="image/jpeg, image/png" class="form-control">
                        <small class="form-text"><i class="fas fa-info-circle"></i> Format: JPG or PNG, maximum 2MB</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-container">
                            <input type="checkbox" id="status" name="status" <?php echo isChecked('status'); ?>>
                            <label for="status">Movie is currently showing</label>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Movie
                        </button>
                        <a href="manage_movies.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Form validation
        const form = document.querySelector('.admin-form');
        form.addEventListener('submit', function(e) {
            let hasError = false;
            const title = document.getElementById('title').value.trim();
            const description = document.getElementById('description').value.trim();
            const duration = document.getElementById('duration').value;
            const genre = document.getElementById('genre').value.trim();
            const rating = document.getElementById('rating').value;
            
            if (!title) {
                alert('Movie title is required');
                hasError = true;
            } else if (!description) {
                alert('Movie description is required');
                hasError = true;
            } else if (!duration || duration <= 0) {
                alert('Movie duration must be greater than 0 minutes');
                hasError = true;
            } else if (!genre) {
                alert('Movie genre is required');
                hasError = true;
            } else if (!rating) {
                alert('Movie rating must be selected');
                hasError = true;
            }
            
            if (hasError) {
                e.preventDefault();
            }
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>