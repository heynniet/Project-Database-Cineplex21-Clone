<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle : 'SimpleCinema' ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Bootstrap CSS (optional, can be removed if not needed) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <?php include_once __DIR__ . '/../../config/config.php'; ?>

    <link rel="stylesheet" href="<?= $baseURL ?>public/css/styles.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/navbar.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/footer.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/admin.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/film.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/cinemas.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/promotion.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/register.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/login.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/ticket.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/seats.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/profile.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/my_tickets.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/view_tickets.css">
    <link rel="stylesheet" href="<?= $baseURL ?>public/css/sidebar.css">


</head>
<body>