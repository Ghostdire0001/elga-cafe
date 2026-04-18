<?php
// ALL cookie/session logic MUST be at the top, before ANY output
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/translations.php';
require_once __DIR__ . '/../../includes/theme.php';
require_once __DIR__ . '/../../includes/language.php';

// Handle language from URL parameter (must be before any output)
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    setcookie('user_lang', $lang, time() + (86400 * 30), "/", "", false, true);
    $_SESSION['user_lang'] = $lang;
}

// Handle theme from URL parameter (must be before any output)
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'];
    setcookie('user_theme', $theme, time() + (86400 * 30), "/", "", false, true);
    $_SESSION['user_theme'] = $theme;
}

// Get current language and theme
$current_lang = getCurrentLanguage();
$current_theme = getCurrentTheme();

// Session check
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: login.php');
    exit();
}

$page_title = $page_title ?? 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" data-theme="<?php echo $current_theme; ?>">
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
    
    <style>
        /* Theme Variables */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --sidebar-bg: #1f2937;
            --sidebar-text: #ffffff;
        }
        
        [data-theme="dark"] {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --card-bg: #1f2937;
            --border-color: #374151;
            --sidebar-bg: #030712;
            --sidebar-text: #f9fafb;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
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
        
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.25rem;
            font-weight: bold;
        }
        
        .sidebar-header p {
            font-size: 0.875rem;
            color: #9ca3af;
            margin-top: 0.25rem;
        }
        
        .sidebar-nav {
            margin-top: 1rem;
        }
        
        .sidebar-nav a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .sidebar-nav a i {
            width: 1.5rem;
            margin-right: 0.75rem;
        }
        
        .sidebar-nav a:hover {
            background-color: #374151;
            padding-left: 1.5rem;
        }
        
        .sidebar-nav a.active {
            background-color: #F97316;
        }
        
        .sidebar-nav .logout {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 1rem;
            padding-top: 1rem;
            color: #f87171;
        }
        
        .menu-toggle {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: #F97316;
            color: white;
            padding: 0.625rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            display: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        @media (max-width: 767px) {
            .menu-toggle {
                display: block;
            }
            body {
                padding-top: 4rem;
            }
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        @media (min-width: 768px) {
            .stats-grid {
                gap: 1.5rem;
            }
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }
        
        @media (min-width: 768px) {
            .stat-card {
                padding: 1.5rem;
            }
        }
        
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        @media (min-width: 768px) {
            .stat-card .stat-value {
                font-size: 1.875rem;
            }
        }
        
        .stat-card .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .page-header {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .page-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
            }
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--text-primary);
        }
        
        @media (min-width: 768px) {
            .page-title {
                font-size: 1.875rem;
            }
        }
        
        .btn-success {
            background-color: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.3s ease;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .text-orange-custom {
            color: #F97316;
        }
        
        .bg-orange-custom {
            background-color: #F97316;
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
                <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> <?php echo t('settings'); ?>
                </a>
                <a href="generate-table-qrcodes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'generate-table-qrcodes.php' ? 'active' : ''; ?>">
                    <i class="fas fa-qrcode"></i> <?php echo t('table_qr_codes'); ?>
                </a>
                <a href="logout.php" class="logout">
                    <i class="fas fa-sign-out-alt"></i> <?php echo t('logout'); ?>
                </a>
            </nav>
        </div>
        <div class="main-content">
            <div class="content-wrapper">
                <div class="flex justify-end items-center gap-3 mb-4">
                    <select id="language-selector" class="lang-selector" onchange="changeLanguage(this.value)">
                        <?php foreach($available_languages as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo $current_lang == $code ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button id="theme-toggle" class="theme-toggle">
                        <i class="fas <?php echo $current_theme == 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                    </button>
                </div>
