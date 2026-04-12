<?php
// Start session
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
$db_host = getenv('DB_HOST') ?: 'fhunzl.h.filess.io';
$db_name = getenv('DB_NAME') ?: 'meal_menu_db_sometimego';
$db_user = getenv('DB_USER') ?: 'meal_menu_db_sometimego';
$db_pass = getenv('DB_PASS') ?: '238446391c2971f7d2668dd6be72bf408400ce26';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;port=3306", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Unable to connect to database. Please try again later.");
}

date_default_timezone_set('UTC');
define('SITE_NAME', 'Elga Cafe');
define('DEBUG_MODE', false);
?>
