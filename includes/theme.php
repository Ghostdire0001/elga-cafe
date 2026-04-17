<?php
// Theme handling
function getCurrentTheme() {
    // Check URL parameter first
    if (isset($_GET['theme'])) {
        return $_GET['theme'];
    }
    
    // Then check cookie
    if (isset($_COOKIE['user_theme'])) {
        return $_COOKIE['user_theme'];
    }
    
    // Then check session
    if (isset($_SESSION['user_theme'])) {
        return $_SESSION['user_theme'];
    }
    
    // Default to light
    return 'light';
}

function getThemeToggleHTML() {
    $current_theme = getCurrentTheme();
    $icon = $current_theme == 'dark' ? 'fa-sun' : 'fa-moon';
    
    return '<button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme">
                <i class="fas ' . $icon . '"></i>
            </button>';
}
?>
