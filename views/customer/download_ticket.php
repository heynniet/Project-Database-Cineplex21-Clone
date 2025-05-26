<?php
/**
 * Cineplex21 - Modern E-Ticket PDF Generator
 * 
 * Redesigned with contemporary color scheme and sleek single-page layout
 */

// Prevent output before headers
ob_start();

// Error reporting for development environment
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session for user authentication
session_start();

// Security: Redirect unauthenticated users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: /Cineplex21/views/auth/login.php');
    exit;
}

// Load database configuration
include '../../config/db.php';

// Validate booking ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_tickets.php");
    exit();
}

$booking_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Get ticket details with security checks
    // Only the ticket owner can view/download their tickets
    $sql = "SELECT 
                b.id, b.showtime_id, b.booking_date, b.total_amount, b.status,
                m.title as movie_title, m.poster_path as poster_url, 
                m.duration, m.rating,
                th.name as theater_name, th.address as theater_address, 
                u.name as user_name, u.email as user_email,
                s.showdate as show_date, s.showtime as show_time,
                GROUP_CONCAT(bs.seat_number ORDER BY bs.seat_number SEPARATOR ', ') as seat_numbers
            FROM 
                bookings b
                JOIN showtimes s ON b.showtime_id = s.id
                JOIN movies m ON s.movie_id = m.id
                JOIN theaters th ON s.theater_id = th.id
                JOIN users u ON b.user_id = u.id
                LEFT JOIN booking_seats bs ON b.id = bs.booking_id
            WHERE 
                b.id = :booking_id 
                AND b.user_id = :user_id 
                AND b.status = 'pending'
            GROUP BY 
                b.id";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Check if ticket exists and belongs to the user
    if ($stmt->rowCount() === 0) {
        header("Location: my_tickets.php?error=not_found");
        exit();
    }
    
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if the ticket is still valid (not expired)
    $now = new DateTime();
    $show_date = new DateTime($ticket['show_date'] . ' ' . $ticket['show_time']);
    
    if ($show_date < $now) {
        header("Location: my_tickets.php?error=expired");
        exit();
    }
    
    // Generate unique ticket code
    $ticket_code = 'CX21-' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    
    // Count seats for the ticket
    $seats = explode(', ', $ticket['seat_numbers']);
    $seat_count = count($seats);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header("Location: my_tickets.php?error=database");
    exit();
}

// Find and load mPDF library
$autoloadPaths = [
    '../../vendor/autoload.php',
    '../vendor/autoload.php',
    'vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/Cineplex21/vendor/autoload.php',
    $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'
];

$autoloadFound = false;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    die('Error: Cannot locate autoload.php. Please check your mPDF installation.');
}

// Verify mPDF class exists
if (!class_exists('\Mpdf\Mpdf')) {
    die('Error: mPDF class not found. Please install mPDF via Composer.');
}

// Format date and time for display
$formatted_date = date('l, F j, Y', strtotime($ticket['show_date']));
$formatted_time = date('h:i A', strtotime($ticket['show_time']));

// Format total amount with currency
$formatted_amount = number_format($ticket['total_amount'], 2, '.', ',');

// Generate QR code - Use a simpler approach without relying on BaconQrCode
$qrCodeBase64 = '';
$useTextFallback = true;

// Generate QR code data
$qrData = $ticket_code . '|' . $ticket['movie_title'] . '|' . $ticket['show_date'] . '|' . $ticket['show_time'] . '|' . $ticket['theater_name'] . '|' . $ticket['seat_numbers'];

// Check if PHP GD library is available for image processing
if (extension_loaded('gd') && function_exists('imagecreate')) {
    // If cURL is available, use an online QR code generation service
    if (function_exists('curl_init')) {
        try {
            $encodedData = urlencode($qrData);
            // Limit data length to prevent overly long URLs
            if (strlen($encodedData) > 500) {
                $encodedData = urlencode($ticket_code);
            }
            
            $ch = curl_init("https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $encodedData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 second timeout
            $qrCodeImage = curl_exec($ch);
            
            if ($qrCodeImage !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
                $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrCodeImage);
                $useTextFallback = false;
            }
            curl_close($ch);
        } catch (Exception $e) {
            error_log('QR code generation error with online service: ' . $e->getMessage());
            $useTextFallback = true;
        }
    }
}

// Set QR text for fallback
$qrText = 'Booking Code: ' . $ticket_code;

// Prepare HTML content for the PDF ticket
$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cineplex21 - E-Ticket</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            padding: 0;
        }
        
        .ticket-container {
            width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
        }
        
        .company-header {
            text-align: center;
            padding: 15px 0;
            background-color: #111827;
            color: white;
        }
        
        .company-name {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        
        .ticket-header {
            padding: 20px 24px 10px 24px;
        }
        
        .title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .ref-number {
            font-size: 14px;
            color: #333;
            margin-bottom: 2px;
        }
        
        .movie-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .rating {
            font-size: 14px;
            margin-bottom: 15px;
            color: #555;
        }
        
        .ticket-info {
            padding: 0 24px;
        }
        
        .info-row {
            margin-bottom: 6px;
        }
        
        .info-label {
            display: inline-block;
            width: 80px;
            font-size: 14px;
            color: #333;
        }
        
        .info-value {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        
        .qr-code {
            text-align: center;
            margin: 24px 0;
            padding: 0 24px;
        }
        
        .qr-image {
            width: 150px;
            height: 150px;
            margin: 0 auto;
        }
        
        .qr-text {
            margin-top: 10px;
            font-size: 13px;
            color: #666;
        }
        
        .ticket-footer {
            padding: 15px 24px 24px;
            font-size: 13px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="company-header">
            <h1 class="company-name">CINEPLEX 21</h1>  
        </div>
        
        <div class="ticket-header">
            <h1 class="title">Your E-Ticket</h1>
            <p class="ref-number">REF: ' . htmlspecialchars($ticket_code) . '</p>
            <h2 class="movie-title">' . htmlspecialchars($ticket['movie_title']) . '</h2>
            <p class="rating">' . htmlspecialchars($ticket['rating']) . ' ' . htmlspecialchars($ticket['duration']) . ' min</p>
        </div>
        
        <div class="ticket-info">
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value">' . $formatted_date . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Time:</span>
                <span class="info-value">' . $formatted_time . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Theater:</span>
                <span class="info-value">' . htmlspecialchars($ticket['theater_name']) . '</span>
            </div>
            <div class="info-row">
                <span class="info-label">Seats:</span>
                <span class="info-value">' . htmlspecialchars($ticket['seat_numbers']) . '</span>
            </div>
        </div>
        
        <div class="qr-code">';

// If we have a valid QR code image from online service
if (!$useTextFallback && !empty($qrCodeBase64)) {
    $html .= '
    <div class="qr-image">
        <img src="' . $qrCodeBase64 . '" width="150" height="150" alt="QR Code">
    </div>';
} else {
    // Text fallback for QR code
    $html .= '
    <div style="width:150px; height:150px; margin:0 auto; border:1px solid #ccc; display:flex; align-items:center; justify-content:center; text-align:center; font-size:12px; padding:5px;">
        Please present booking code:<br><br>
        <strong style="font-size:16px;">' . htmlspecialchars($ticket_code) . '</strong>
    </div>';
}

$html .= '
            <p class="qr-text">Present this code at the cinema for entry</p>
        </div>
        
        <div class="ticket-footer">
            <p>Please arrive 15 minutes before the show. This ticket is non-refundable.</p>
        </div>
    </div>
</body>
</html>';

try {
    // Create mPDF instance with minimal settings for a clean, simple ticket
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => [210, 297], // A4 format for better compatibility
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
        'margin_header' => 0,
        'margin_footer' => 0
    ]);
    
    // Set document metadata
    $mpdf->SetTitle('Cineplex 21 - ' . $ticket['movie_title'] . ' Ticket');
    $mpdf->SetAuthor('Cineplex 21');
    $mpdf->SetCreator('Cineplex 21 Ticketing System');
    
    // Write the HTML to PDF
    $mpdf->WriteHTML($html);
    
    // Set file name for download
    $pdf_file_name = 'Cineplex21_Ticket_' . $ticket_code . '.pdf';
    
    // Clear any previous output buffering
    if (ob_get_length()) ob_clean();
    
    // Output PDF as download
    $mpdf->Output($pdf_file_name, 'D');
    
} catch (Exception $e) {
    // Log the error
    error_log('mPDF error: ' . $e->getMessage());
    
    // Display user-friendly error
    echo '<div style="text-align:center; margin-top:50px; font-family:-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; color:#333;">
            <h2 style="margin-bottom:15px;">Unable to Generate Ticket</h2>
            <p style="margin-bottom:20px; color:#555;">We encountered a problem while generating your ticket. Please try again later or contact customer support.</p>
            <p><a href="my_tickets.php" style="color:#fff; background-color:#4285f4; text-decoration:none; padding:8px 16px; border-radius:4px; display:inline-block;">Return to My Tickets</a></p>
          </div>';
}

// End script
exit();
?>