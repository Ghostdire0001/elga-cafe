<?php
require_once __DIR__ . '/config.php';

// Check if user is logged in and has admin access
function requireAdmin() {
    if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
        header('Location: login.php');
        exit();
    }
}

// Call this at the beginning of all admin pages
requireAdmin();
?>