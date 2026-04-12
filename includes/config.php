<?php
// Start session at the very beginning
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'fhunzl.h.filess.io');
define('DB_NAME', 'meal_menu_db_sometimego');
define('DB_USER', 'meal_menu_db_sometimego');
define('DB_PASS', '238446391c2971f7d2668dd6be72bf408400ce26');

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('UTC');

// Site configuration
define('SITE_NAME', 'Elga Cafe');
define('SITE_URL', 'http://yourdomain.com'); // Update this
?>