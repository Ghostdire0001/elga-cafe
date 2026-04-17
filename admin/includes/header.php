<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/theme.php';
require_once __DIR__ . '/../../includes/language.php';

// Get current language for this page
$current_lang = getCurrentLanguage();

if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: index.php');
    exit();
}
$page_title = $page_title ?? 'Admin Dashboard';
?><!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" data-theme="<?php echo getCurrentTheme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo t('site_name'); ?> <?php echo t('admin_panel'); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='20' fill='%23F97316'/%3E%3Ctext x='50' y='67' font-size='45' text-anchor='middle' fill='white' font-weight='bold'%3EEC%3C/text%3E%3C/svg%3E">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../../css/common.css">
    <?php echo getThemeStyles(); ?>
    <style>
        /* Ensure sidebar is fixed on scroll */
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }
        
        .main-content {
            flex: 1;
            margin-left: 280px;
            overflow-x: auto;
            background-color: var(--bg-primary);
            min-height: 100vh;
        }
        
        /* Mobile styles */
        @media (max-width: 767px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
        
        .content-wrapper {
            padding: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .content-wrapper {
                padding: 2rem;
            }
        }
        
        /* Theme toggle and language selector styling */
        .theme-toggle, .lang-selector {
            background: transparent;
            border: 1px solid var(--border-color);
            padding: 0.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            color: var(--text-primary);
        }
        
        .theme-toggle:hover {
            background: rgba(249, 115, 22, 0.1);
        }
        
        .lang-selector {
            background-color: var(--bg-primary);
        }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <div class="overlay" onclick="toggleSidebar()"></div>
    <div class="admin-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2><?php echo t('site_name'); ?> <?php echo t('admin_panel'); ?></h2>
                <p><?php echo t('welcome_back'); ?>, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> <?php echo t('dashboard'); ?>
                </a>
                <a href="meals.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'meals.php' ? 'active' : ''; ?>">
                    <i class="fas fa-utensils"></i> <?php echo t('meals'); ?>
                </a>
                <a href="categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> <?php echo t('categories'); ?>
                </a>
                <a href="dietary-labels.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dietary-labels.php' ? 'active' : ''; ?>">
                    <i class="fas fa-leaf"></i> <?php echo t('dietary_labels'); ?>
                </a>
                <a href="discounts.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'discounts.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tag"></i> <?php echo t('discounts'); ?>
                </a>
                <a href="logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?>
                </a>
            </nav>
        </div>
        <div class="main-content">
            <div class="content-wrapper">
                <div class="flex justify-end items-center gap-3 mb-4">
                    <?php echo getLanguageSelectorHTML(); ?>
                    <?php echo getThemeToggleHTML(); ?>
                </div>
