<?php
// Start session
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration - Get from environment variables
$db_host = getenv('DB_HOST') ?: 'fhunzl.h.filess.io';
$db_name = getenv('DB_NAME') ?: 'meal_menu_db_sometimego';
$db_user = getenv('DB_USER') ?: 'meal_menu_db_sometimego';
$db_pass = getenv('DB_PASS') ?: '238446391c2971f7d2668dd6be72bf408400ce26';
$db_port = '3307';  // Use the correct port

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;port=$db_port", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    // Log error but don't show details in production
    error_log("Connection failed: " . $e->getMessage());
    die("Unable to connect to database. Please try again later.");
}

date_default_timezone_set('UTC');
define('SITE_NAME', 'Elga Cafe');
define('DEBUG_MODE', false);
// Add at the end of your config.php
// Cloudinary configuration (from environment variables)

// Cloudinary configuration - from environment variables ONLY
define('CLOUDINARY_CLOUD_NAME', getenv('CLOUDINARY_CLOUD_NAME'));
define('CLOUDINARY_API_KEY', getenv('CLOUDINARY_API_KEY'));
define('CLOUDINARY_API_SECRET', getenv('CLOUDINARY_API_SECRET'));

// Check if Cloudinary is configured (optional)
if (!CLOUDINARY_CLOUD_NAME || !CLOUDINARY_API_KEY || !CLOUDINARY_API_SECRET) {
    error_log("Cloudinary credentials not configured in environment variables");
}
?>
