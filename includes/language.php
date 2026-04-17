<?php
require_once __DIR__ . '/translations.php';

function getCurrentLanguage() {
    // Check URL parameter first
    if (isset($_GET['lang'])) {
        return $_GET['lang'];
    }
    
    // Then check cookie
    if (isset($_COOKIE['user_lang'])) {
        return $_COOKIE['user_lang'];
    }
    
    // Then check session
    if (isset($_SESSION['user_lang'])) {
        return $_SESSION['user_lang'];
    }
    
    // Default to Amharic
    return 'am';
}

function t($key) {
    global $translations, $current_lang;
    
    if (!isset($current_lang)) {
        $current_lang = getCurrentLanguage();
    }
    
    return $translations[$current_lang][$key] ?? $key;
}
?>
