<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit();
}
$page_title = $page_title ?? 'Admin Dashboard';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($page_title); ?> - Elga Cafe Admin</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Fallback if no favicon files exist -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' fill='%23F97316'/%3E%3Ctext x='50' y='67' font-size='50' text-anchor='middle' fill='white' font-weight='bold'%3EEC%3C/text%3E%3C/svg%3E">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="overlay" onclick="toggleSidebar()"></div>
    <div class="admin-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Elga Cafe Admin</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="meals.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'meals.php' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> Meals
                </a>
                <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="dietary-labels.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dietary-labels.php' ? 'active' : ''; ?>">
                    <i class="fas fa-leaf"></i> Dietary Labels
                </a>
                <a href="discounts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'discounts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tag"></i> Discounts
                </a>
                <a href="logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
        <div class="main-content">
            <div class="content-wrapper">
