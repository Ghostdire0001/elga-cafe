<?php
require_once __DIR__ . '/translations.php';

function getCurrentLanguage() {
    if (isset($_COOKIE['user_lang'])) {
        return $_COOKIE['user_lang'];
    }
    if (isset($_SESSION['user_lang'])) {
        return $_SESSION['user_lang'];
    }
    return 'am'; // Default to Amharic
}

function setLanguageCookie($lang) {
    setcookie('user_lang', $lang, time() + (86400 * 30), "/");
    $_SESSION['user_lang'] = $lang;
}

function t($key) {
    global $translations, $current_lang;
    return $translations[$current_lang][$key] ?? $key;
}

function getLanguageSelectorHTML() {
    global $available_languages, $current_lang;
    
    $html = '<select id="language-selector" class="lang-selector">';
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
        const languageSelector = document.getElementById(\'language-selector\');
        if (languageSelector) {
            languageSelector.addEventListener(\'change\', function() {
                const url = new URL(window.location.href);
                url.searchParams.set(\'lang\', this.value);
                window.location.href = url.toString();
            });
        }
    </script>';
}
?>
