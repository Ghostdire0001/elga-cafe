<?php
require_once __DIR__ . '/translations.php';

function getCurrentLanguage() {
    // Check URL parameter first (highest priority)
    if (isset($_GET['lang'])) {
        $lang = $_GET['lang'];
        setLanguageCookie($lang);
        return $lang;
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

function setLanguageCookie($lang) {
    setcookie('user_lang', $lang, time() + (86400 * 30), "/", "", false, true);
    $_SESSION['user_lang'] = $lang;
}

function t($key) {
    global $translations, $current_lang;
    
    // If current_lang is not set in global scope, get it
    if (!isset($current_lang)) {
        $current_lang = getCurrentLanguage();
    }
    
    // Return translation if exists, otherwise return the key
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

function getLanguageScript() {
    return '
    <script>
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set("lang", lang);
            window.location.href = url.toString();
        }
    </script>';
}
?>
