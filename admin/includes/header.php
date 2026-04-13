<?php
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin access
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit();
}

// Set page title if not set
$page_title = $page_title ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($page_title); ?> - Elga Cafe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Overlay -->
    <div class="overlay" onclick="toggleSidebar()"></div>
    
    <!-- Admin Container - Flex container for desktop -->
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Elga Cafe Admin</h2>
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
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
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="content-wrapper">
