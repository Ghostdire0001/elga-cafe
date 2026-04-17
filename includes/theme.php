<?php
// Theme handling - Complete file for both public and admin

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

function getThemeStyles() {
    return '
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
        }
        
        [data-theme="dark"] {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --card-bg: #1f2937;
            --border-color: #374151;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .theme-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
            background: transparent;
            border: none;
            font-size: 1.25rem;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }
        
        .theme-toggle:hover {
            transform: rotate(15deg);
            background: rgba(249, 115, 22, 0.1);
        }
        
        .theme-transition {
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
    </style>';
}

function getThemeScript() {
    return '
    <script>
        function initTheme() {
            // Get theme from URL parameter first
            const urlParams = new URLSearchParams(window.location.search);
            let theme = urlParams.get("theme");
            
            if (!theme) {
                theme = localStorage.getItem("theme") || "light";
            }
            
            document.documentElement.setAttribute("data-theme", theme);
            localStorage.setItem("theme", theme);
            
            // Update cookie for server-side
            document.cookie = "user_theme=" + theme + "; path=/; max-age=" + (30 * 24 * 60 * 60);
            
            const themeToggle = document.getElementById("theme-toggle");
            if (themeToggle) {
                const icon = themeToggle.querySelector("i");
                if (theme === "dark") {
                    icon.classList.remove("fa-moon");
                    icon.classList.add("fa-sun");
                } else {
                    icon.classList.remove("fa-sun");
                    icon.classList.add("fa-moon");
                }
                
                themeToggle.addEventListener("click", function() {
                    const currentTheme = document.documentElement.getAttribute("data-theme");
                    const newTheme = currentTheme === "dark" ? "light" : "dark";
                    document.documentElement.setAttribute("data-theme", newTheme);
                    localStorage.setItem("theme", newTheme);
                    document.cookie = "user_theme=" + newTheme + "; path=/; max-age=" + (30 * 24 * 60 * 60);
                    
                    if (newTheme === "dark") {
                        icon.classList.remove("fa-moon");
                        icon.classList.add("fa-sun");
                    } else {
                        icon.classList.remove("fa-sun");
                        icon.classList.add("fa-moon");
                    }
                    
                    // Update URL without reload
                    const url = new URL(window.location.href);
                    url.searchParams.set("theme", newTheme);
                    window.history.pushState({}, "", url);
                });
            }
        }
        initTheme();
    </script>';
}
?>
