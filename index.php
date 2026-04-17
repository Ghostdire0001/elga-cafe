<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/theme.php';
require_once 'includes/language.php';

$current_lang = getCurrentLanguage();

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
    $discount_a = getMealDiscount($pdo, $a['id'], $a['price'], $a['category_id']);
    $discount_b = getMealDiscount($pdo, $b['id'], $b['price'], $b['category_id']);
    
    if ($discount_a && !$discount_b) return -1;
    if (!$discount_a && $discount_b) return 1;
    if ($a['is_popular'] && !$b['is_popular']) return -1;
    if (!$a['is_popular'] && $b['is_popular']) return 1;
    if ($a['is_featured'] && !$b['is_featured']) return -1;
    if (!$a['is_featured'] && $b['is_featured']) return 1;
    
    return 0;
});
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>" data-theme="<?php echo getCurrentTheme(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo t('site_title'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/common.css">
    <?php echo getThemeStyles(); ?>
    <style>
        .bg-orange-custom { background-color: #F97316; }
        .text-orange-custom { color: #F97316; }
        
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
        
        .filters-container {
            background-color: var(--bg-secondary);
            border-radius: 0.5rem;
        }
        
        .lang-selector {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        [data-theme="dark"] .lang-selector {
            background: rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body class="theme-transition">
    <!-- Header -->
    <header class="bg-orange-custom text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 md:py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold"><?php echo t('site_name'); ?></h1>
                    <p class="text-orange-100 text-xs md:text-sm mt-0.5"><?php echo t('tagline'); ?></p>
                </div>
                <div class="flex items-center gap-3">
                    <?php echo getLanguageSelectorHTML(); ?>
                    <?php echo getThemeToggleHTML(); ?>
                    <div class="hidden md:block">
                        <div class="bg-white rounded-full w-10 h-10 flex items-center justify-center">
                            <i class="fas fa-mug-hot text-orange-custom text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Rest of your existing index.php content using t() for translations -->
    <!-- ... (keep your existing HTML structure, just replace text with t('key') ... -->
    
    <div class="container mx-auto px-4 py-4 md:py-6">
        <!-- Search and filters form -->
        <!-- ... -->
    </div>

    <footer class="mt-12 py-6 md:py-8" style="background-color: var(--footer-bg); color: white;">
        <div class="container mx-auto px-4 text-center">
            <div class="mb-4">
                <i class="fas fa-mug-hot text-2xl md:text-3xl text-orange-custom"></i>
            </div>
            <p class="text-lg font-semibold mb-2"><?php echo t('site_name'); ?></p>
            <p class="text-gray-400 text-sm"><?php echo t('made_with_love'); ?></p>
            <p class="text-gray-500 text-xs mt-4">&copy; <?php echo date('Y'); ?> <?php echo t('site_name'); ?>. <?php echo t('copyright'); ?></p>
        </div>
    </footer>

    <?php echo getThemeScript(); ?>
    <?php echo getLanguageScript(); ?>
</body>
</html>
