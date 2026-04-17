<?php
require_once __DIR__ . '/translations.php';

function getCurrentLanguage() {
    // Check URL parameter first (handled at top of index.php)
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

function getLanguageSelectorHTML() {
    global $available_languages, $current_lang;
    
    if (!isset($current_lang)) {
        $current_lang = getCurrentLanguage();
    }
    
    $html = '<select id="language-selector" class="lang-selector" onchange="changeLanguage(this.value)">';
    foreach ($available_languages as $code => $name) {
        $selected = $current_lang == $code ? 'selected' : '';
        $html .= "<option value=\"$code\" $selected>$name</option>";
    }
    $html .= '</select>';
    
    return $html;
}
?>
