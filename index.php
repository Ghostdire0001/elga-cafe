<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Language setup
$available_languages = [
    'am' => 'አማርኛ',
    'en' => 'English',
    'so' => 'Somali',
    'om' => 'Oromo'
];

// Get selected language from cookie, session, or default to Amharic
$current_lang = isset($_COOKIE['user_lang']) ? $_COOKIE['user_lang'] : 'am';
$current_lang = isset($_GET['lang']) ? $_GET['lang'] : $current_lang;
if (isset($_GET['lang'])) {
    setcookie('user_lang', $current_lang, time() + (86400 * 30), "/");
    $_SESSION['user_lang'] = $current_lang;
}

// Language translations
$translations = [
    'am' => [
        'site_title' => 'ኤልጋ ካፌ - ምናሌ',
        'site_name' => 'ኤልጋ ካፌ',
        'tagline' => 'በፍቅር የተዘጋጁ ጣፋጭ ምግቦች',
        'search_placeholder' => 'ምግብ ፈልግ...',
        'all_categories' => 'ሁሉም ምድቦች',
        'dietary' => 'የአመጋገብ መለያዎች',
        'found_items' => 'ተገኙ',
        'menu_items' => 'የምናሌ ዕቃዎች',
        'no_meals' => 'ምንም ምግቦች አልተገኙም',
        'adjust_filters' => 'እባክዎ ማጣሪያዎችዎን ያስተካክሉ',
        'reset_filters' => 'ማጣሪያዎችን ዳግም አስጀምር',
        'featured' => 'የተለየ',
        'popular' => 'ታዋቂ',
        'off' => '% ቅናሽ',
        'min' => 'ደቂቃ',
        'cal' => 'ካሎሪ',
        'copyright' => 'መብቱ በህግ የተጠበቀ ነው',
        'made_with_love' => 'በፍቅር የተዘጋጀ',
        'dark_mode' => 'ጨለማ ሁነታ',
        'light_mode' => 'ብርሃን ሁነታ',
        'language' => 'ቋንቋ'
    ],
    'en' => [
        'site_title' => 'Elga Cafe - Menu',
        'site_name' => 'Elga Cafe',
        'tagline' => 'Delicious meals made with love',
        'search_placeholder' => 'Search menu...',
        'all_categories' => 'All Categories',
        'dietary' => 'Dietary',
        'found_items' => 'Found',
        'menu_items' => 'menu items',
        'no_meals' => 'No meals found',
        'adjust_filters' => 'Try adjusting your filters',
        'reset_filters' => 'Reset Filters',
        'featured' => 'Featured',
        'popular' => 'Popular',
        'off' => '% OFF',
        'min' => 'min',
        'cal' => 'cal',
        'copyright' => 'All rights reserved',
        'made_with_love' => 'Made with love',
        'dark_mode' => 'Dark Mode',
        'light_mode' => 'Light Mode',
        'language' => 'Language'
    ],
    'so' => [
        'site_title' => 'Elga Cafe - Menu',
        'site_name' => 'Elga Cafe',
        'tagline' => 'Cuntooyin macaan oo laga sameeyay jacayl',
        'search_placeholder' => 'Raadi menu...',
        'all_categories' => 'Dhammaan Qaybaha',
        'dietary' => 'Cunto',
        'found_items' => 'La helay',
        'menu_items' => 'waxyaabaha menu-ga',
        'no_meals' => 'Cunto lama helin',
        'adjust_filters' => 'Fadlan hagaaji shaandheeyahaaga',
        'reset_filters' => 'Dib u habayn',
        'featured' => 'Gaar ah',
        'popular' => 'Caanka ah',
        'off' => '% Dhimis',
        'min' => 'daqiiqo',
        'cal' => 'kalori',
        'copyright' => 'Dhamaan xuquuqaha way dhawrsan yihiin',
        'made_with_love' => 'Jacayl laga sameeyay',
        'dark_mode' => 'Qaab Mugdi',
        'light_mode' => 'Qaab Iftiin',
        'language' => 'Luqadda'
    ],
    'om' => [
        'site_title' => 'Elga Cafe - Menu',
        'site_name' => 'Elga Cafe',
        'tagline' => 'Jaalalaan qopheeffame nyaata mi’aawaa',
        'search_placeholder' => 'Nyaata barbaadi...',
        'all_categories' => 'Ramaddii Hunda',
        'dietary' => 'Nyaataa',
        'found_items' => 'Arganne',
        'menu_items' => 'meeshaa nyaataa',
        'no_meals' => 'Nyaantiin hin argamne',
        'adjust_filters' => 'Sakkooqqaa keessan sirreessaa',
        'reset_filters' => 'Irradeessi',
        'featured' => 'Addaa',
        'popular' => 'Beekamaa',
        'off' => '% Hirmaata',
        'min' => 'daqiiqo',
        'cal' => 'kalaarii',
        'copyright' => 'Mirgiin hundaa eeggama',
        'made_with_love' => 'Jaalalaan qopheeffame',
        'dark_mode' => 'Haala Duwwaa',
        'light_mode' => 'Haala Ifaa',
        'language' => 'Afaan'
    ]
];

$t = $translations[$current_lang];

// Get theme from cookie or default to light
$theme = isset($_COOKIE['user_theme']) ? $_COOKIE['user_theme'] : 'light';
if (isset($_GET['theme'])) {
    $theme = $_GET['theme'];
    setcookie('user_theme', $theme, time() + (86400 * 30), "/");
}

// Get filter parameters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$dietary_ids = isset($_GET['dietary']) ? array_map('intval', (array)$_GET['dietary']) : [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get data
$categories = getCategories($pdo);
$dietary_labels = getDietaryLabels($pdo);
$meals = getMeals($pdo, $category_id, $dietary_ids, $search);

// Sort meals: Discount first, then Popular, then Featured
usort($meals, function($a, $b) use ($pdo) {
    // Get discount status
    $discount_a = getMealDiscount($pdo, $a['id'], $a['price'], $a['category_id']);
    $discount_b = getMealDiscount($pdo, $b['id'], $b['price'], $b['category_id']);
    
    // Discount takes highest priority
    if ($discount_a && !$discount_b) return -1;
    if (!$discount_a && $discount_b) return 1;
    
    // Then Popular
    if ($a['is_popular'] && !$b['is_popular']) return -1;
    if (!$a['is_popular'] && $b['is_popular']) return 1;
    
    // Then Featured
    if ($a['is_featured'] && !$b['is_featured']) return -1;
    if (!$a['is_featured'] && $b['is_featured']) return 1;
    
    return 0;
});
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo $t['site_title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Theme Variables */
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f9fafb;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --card-bg: #ffffff;
            --border-color: #e5e7eb;
            --header-bg: #F97316;
            --footer-bg: #1f2937;
        }
        
        [data-theme="dark"] {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #9ca3af;
            --card-bg: #1f2937;
            --border-color: #374151;
            --header-bg: #EA580C;
            --footer-bg: #030712;
        }
        
        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .bg-orange-custom { background-color: #F97316; }
        .text-orange-custom { color: #F97316; }
        .hover\:bg-orange-custom:hover { background-color: #F97316; }
        .border-orange-custom { border-color: #F97316; }
        
        .meal-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
        }
        
        .meal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.15);
        }
        
        .meal-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .meal-card:hover .meal-image {
            transform: scale(1.05);
        }
        
        .image-container {
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #F9731620, #F9731605);
        }
        
        /* Mobile: 2 columns */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        @media (min-width: 768px) {
            .menu-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1.5rem;
            }
        }
        
        @media (min-width: 1024px) {
            .menu-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Theme toggle button */
        .theme-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: rotate(15deg);
        }
        
        /* Language selector */
        .lang-selector {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            padding: 0.25rem 0.5rem;
            color: white;
            cursor: pointer;
        }
        
        .lang-selector option {
            color: #1f2937;
        }
        
        [data-theme="dark"] .lang-selector {
            background: rgba(0, 0, 0, 0.3);
        }
        
        /* Filter bar background */
        .filters-container {
            background-color: var(--bg-secondary);
            border-radius: 0.5rem;
        }
        
        /* Loading animation */
        .theme-transition {
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
    </style>
</head>
<body class="theme-transition">
    <!-- Header -->
    <header class="bg-orange-custom text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 md:py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold"><?php echo $t['site_name']; ?></h1>
                    <p class="text-orange-100 text-xs md:text-sm mt-0.5"><?php echo $t['tagline']; ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Language Selector -->
                    <select id="language-selector" class="lang-selector text-sm">
                        <?php foreach($available_languages as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo $current_lang == $code ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- Theme Toggle -->
                    <button id="theme-toggle" class="theme-toggle text-white text-xl" aria-label="Toggle theme">
                        <i class="fas <?php echo $theme == 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                    </button>
                    
                    <div class="hidden md:block">
                        <div class="bg-white rounded-full w-10 h-10 flex items-center justify-center">
                            <i class="fas fa-mug-hot text-orange-custom text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Search and Filters -->
    <div class="container mx-auto px-4 py-4 md:py-6">
        <form method="GET" action="" id="filterForm" class="filters-container p-4 mb-4">
            <div class="flex flex-col md:flex-row gap-3">
                <!-- Search Bar -->
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" name="search" id="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="<?php echo $t['search_placeholder']; ?>" 
                               class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom focus:ring-1 focus:ring-orange-custom theme-transition"
                               style="background-color: var(--bg-primary); color: var(--text-primary);">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                
                <!-- Category Filter -->
                <div>
                    <select name="category" id="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom theme-transition"
                            style="background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value=""><?php echo $t['all_categories']; ?></option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Dietary Filters -->
            <?php if(!empty($dietary_labels)): ?>
            <div class="mt-3">
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="text-gray-600 text-xs md:text-sm"><?php echo $t['dietary']; ?>:</span>
                    <?php foreach($dietary_labels as $label): ?>
                        <label class="inline-flex items-center cursor-pointer hover:opacity-75 transition">
                            <input type="checkbox" name="dietary[]" value="<?php echo $label['id']; ?>" 
                                   class="dietary-checkbox mr-1 hidden"
                                   <?php echo in_array($label['id'], $dietary_ids) ? 'checked' : ''; ?>>
                            <span class="text-xs md:text-sm px-2 py-1 rounded-full transition" 
                                  style="background-color: <?php echo $label['color_hex']; ?>20; color: <?php echo $label['color_hex']; ?>; border: 1px solid <?php echo $label['color_hex']; ?>40">
                                <i class="fas <?php echo $label['icon_class']; ?> text-xs"></i>
                                <span class="ml-1 hidden sm:inline"><?php echo ucfirst($label['name']); ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </form>

        <!-- Results Count -->
        <div class="mb-3">
            <p class="text-gray-600 text-sm">
                <i class="fas fa-utensils mr-1"></i> 
                <?php echo $t['found_items']; ?> <strong><?php echo count($meals); ?></strong> <?php echo $t['menu_items']; ?>
            </p>
        </div>

        <!-- Menu Grid - 2 columns on mobile -->
        <div class="menu-grid">
            <?php if(empty($meals)): ?>
                <div class="col-span-full text-center py-12 md:py-16">
                    <div class="bg-white rounded-full w-20 h-20 md:w-24 md:h-24 flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-search text-3xl md:text-4xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 text-lg mb-2"><?php echo $t['no_meals']; ?></p>
                    <p class="text-gray-400 text-sm"><?php echo $t['adjust_filters']; ?></p>
                    <button onclick="resetFilters()" class="inline-block mt-4 bg-orange-custom text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition">
                        <i class="fas fa-redo-alt mr-2"></i><?php echo $t['reset_filters']; ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php foreach($meals as $meal): 
                $discount = getMealDiscount($pdo, $meal['id'], $meal['price'], $meal['category_id']);
                $dietary_labels_meal = getMealDietaryLabels($pdo, $meal['id']);
            ?>
                <div class="meal-card rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                    <!-- Image Section -->
                    <div class="image-container relative">
                        <?php if($meal['image_url']): ?>
                            <img src="<?php echo $meal['image_url']; ?>" 
                                 alt="<?php echo htmlspecialchars($meal['name']); ?>" 
                                 class="meal-image w-full object-cover"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-[180px] flex items-center justify-center">
                                <i class="fas fa-utensils text-4xl md:text-5xl text-orange-300"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Badges - Priority: Discount first -->
                        <div class="absolute top-2 left-2 flex flex-col gap-1">
                            <?php if($discount): ?>
                                <span class="bg-green-500 text-white px-2 py-0.5 rounded text-xs font-bold shadow-md">
                                    <?php if($discount['discount_type'] == 'percentage'): ?>
                                        <?php echo $discount['discount_value']; ?><?php echo $t['off']; ?>
                                    <?php else: ?>
                                        -$<?php echo number_format($discount['discount_value'], 2); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-2 right-2 flex flex-col gap-1 items-end">
                            <?php if($meal['is_popular']): ?>
                                <span class="bg-red-500 text-white px-2 py-0.5 rounded text-xs font-semibold shadow-md">
                                    <i class="fas fa-fire"></i> <?php echo $t['popular']; ?>
                                </span>
                            <?php endif; ?>
                            <?php if($meal['is_featured']): ?>
                                <span class="bg-yellow-500 text-white px-2 py-0.5 rounded text-xs font-semibold shadow-md">
                                    <i class="fas fa-star"></i> <?php echo $t['featured']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Content Section -->
                    <div class="p-3">
                        <div class="flex justify-between items-start mb-1">
                            <h3 class="text-sm md:text-base font-bold line-clamp-2"><?php echo htmlspecialchars($meal['name']); ?></h3>
                            <div class="text-right ml-2 flex-shrink-0">
                                <?php if($discount): ?>
                                    <span class="text-gray-400 line-through text-xs">$<?php echo number_format($meal['price'], 2); ?></span>
                                    <div class="text-orange-custom font-bold text-sm md:text-base">$<?php echo number_format($discount['discounted_price'], 2); ?></div>
                                <?php else: ?>
                                    <div class="text-orange-custom font-bold text-sm md:text-base">$<?php echo number_format($meal['price'], 2); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-xs mb-2 line-clamp-2"><?php echo htmlspecialchars(substr($meal['description'], 0, 60)) . '...'; ?></p>
                        
                        <!-- Dietary Labels -->
                        <?php if(!empty($dietary_labels_meal)): ?>
                        <div class="flex flex-wrap gap-1 mb-2">
                            <?php foreach($dietary_labels_meal as $label): ?>
                                <span class="text-xs px-1.5 py-0.5 rounded-full" 
                                      style="background-color: <?php echo $label['color_hex']; ?>20; color: <?php echo $label['color_hex']; ?>">
                                    <i class="fas <?php echo $label['icon_class']; ?> text-xs"></i>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Meta Info -->
                        <div class="flex justify-between items-center text-xs text-gray-500 pt-2 border-t" style="border-color: var(--border-color);">
                            <div class="flex items-center gap-1">
                                <i class="far fa-clock"></i>
                                <span><?php echo $meal['preparation_time']; ?> <?php echo $t['min']; ?></span>
                            </div>
                            <?php if($meal['calories']): ?>
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-fire"></i>
                                    <span><?php echo $meal['calories']; ?> <?php echo $t['cal']; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-12 py-6 md:py-8" style="background-color: var(--footer-bg); color: white;">
        <div class="container mx-auto px-4 text-center">
            <div class="mb-4">
                <i class="fas fa-mug-hot text-2xl md:text-3xl text-orange-custom"></i>
            </div>
            <p class="text-lg font-semibold mb-2"><?php echo $t['site_name']; ?></p>
            <p class="text-gray-400 text-sm"><?php echo $t['made_with_love']; ?></p>
            <p class="text-gray-500 text-xs mt-4">&copy; <?php echo date('Y'); ?> <?php echo $t['site_name']; ?>. <?php echo $t['copyright']; ?></p>
        </div>
    </footer>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;
        
        function setTheme(theme) {
            htmlElement.setAttribute('data-theme', theme);
            localStorage.setItem('theme', theme);
            const icon = themeToggle.querySelector('i');
            if (theme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
        
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme);
            // Update URL without reload
            const url = new URL(window.location.href);
            url.searchParams.set('theme', newTheme);
            window.history.pushState({}, '', url);
        });
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        setTheme(savedTheme);
        
        // Language Switcher
        const languageSelector = document.getElementById('language-selector');
        languageSelector.addEventListener('change', function() {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', this.value);
            window.location.href = url.toString();
        });
        
        // Auto-submit form when filters change
        document.getElementById('category')?.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
        
        document.querySelectorAll('.dietary-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
        
        // Search with debounce
        let searchTimeout;
        const searchInput = document.getElementById('search');
        if(searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500);
            });
        }
        
        // Reset filters
        function resetFilters() {
            window.location.href = window.location.pathname;
        }
        
        // Apply theme transition class
        document.body.classList.add('theme-transition');
    </script>
</body>
</html>
