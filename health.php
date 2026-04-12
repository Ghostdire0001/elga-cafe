<?php
// Start session at the very beginning
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration - using environment variables for Render
define('DB_HOST', getenv('DB_HOST') ?: 'fhunzl.h.filess.io');
define('DB_NAME', getenv('DB_NAME') ?: 'meal_menu_db_sometimego');
define('DB_USER', getenv('DB_USER') ?: 'meal_menu_db_sometimego');
define('DB_PASS', getenv('DB_PASS') ?: '238446391c2971f7d2668dd6be72bf408400ce26');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=3306", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Set charset to UTF-8
    $pdo->exec("SET NAMES utf8mb4");
    
} catch(PDOException $e) {
    // Log error but don't show details in production
    error_log("Connection failed: " . $e->getMessage());
    die("Unable to connect to database. Please try again later.");
}

// Set timezone
date_default_timezone_set('UTC');

// Site configuration
define('SITE_NAME', 'Elga Cafe');
define('SITE_URL', getenv('RENDER_EXTERNAL_URL') ?: 'http://localhost');

// Debug mode - set to false in production
define('DEBUG_MODE', false);
if(DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>