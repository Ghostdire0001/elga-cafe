<?php
// ============================================
// CRITICAL: No HTML, no echo, no whitespace before this point
// ============================================
session_start();

// Handle ALL URL parameters that need cookies FIRST
if (isset($_GET['lang'])) {
    setcookie('user_lang', $_GET['lang'], time() + (86400 * 30), "/", "", false, true);
    $_SESSION['user_lang'] = $_GET['lang'];
}

if (isset($_GET['theme'])) {
    setcookie('user_theme', $_GET['theme'], time() + (86400 * 30), "/", "", false, true);
    $_SESSION['user_theme'] = $_GET['theme'];
}

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/translations.php';
require_once 'includes/theme.php';
require_once 'includes/language.php';
require_once 'includes/settings-functions.php';
require_once 'includes/order-functions.php';

// Get current settings
$current_lang = getCurrentLanguage();
$current_theme = getCurrentTheme();
$order_enabled = isOrderEnabled($pdo);
$current_table = getTableNumber();

// Check if user is a waiter (for waiter-assisted ordering)
$is_waiter = (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'waiter');
$waiter_assigned_tables = [];

if ($is_waiter) {
    $stmt = $pdo->prepare("SELECT assigned_tables FROM waiters WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $waiter = $stmt->fetch();
    if ($waiter && $waiter['assigned_tables']) {
        $waiter_assigned_tables = explode(',', $waiter['assigned_tables']);
    }
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
<html lang="<?php echo $current_lang; ?>" data-theme="<?php echo $current_theme; ?>">
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
            border-radius: 0.75rem;
        }
        
        .meal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.15);
        }
        
        .meal-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        @media (min-width: 768px) {
            .meal-image {
                height: 180px;
            }
        }
        
        .meal-card:hover .meal-image {
            transform: scale(1.05);
        }
        
        .image-container {
            overflow: hidden;
            position: relative;
            border-radius: 0.75rem 0.75rem 0 0;
            background: linear-gradient(135deg, #F9731620, #F9731605);
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        @media (min-width: 640px) {
            .menu-grid {
                gap: 1.25rem;
            }
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
            border-radius: 0.5rem;
            padding: 0.25rem 0.5rem;
            color: white;
            cursor: pointer;
        }
        
        [data-theme="dark"] .lang-selector {
            background: rgba(0, 0, 0, 0.3);
        }
        
        .lang-selector option {
            color: #1f2937;
        }
        
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        /* Waiter mode indicator */
        .waiter-badge {
            background-color: #F97316;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
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
                    <?php if ($is_waiter): ?>
                        <span class="waiter-badge">
                            <i class="fas fa-user-clock"></i> Waiter Mode
                        </span>
                    <?php endif; ?>
                    <select id="language-selector" class="lang-selector" onchange="changeLanguage(this.value)">
                        <?php foreach($available_languages as $code => $name): ?>
                            <option value="<?php echo $code; ?>" <?php echo $current_lang == $code ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button id="theme-toggle" class="theme-toggle text-white">
                        <i class="fas <?php echo $current_theme == 'dark' ? 'fa-sun' : 'fa-moon'; ?>"></i>
                    </button>
                    <?php if ($is_waiter): ?>
                        <a href="waiter/dashboard.php" class="bg-white text-orange-custom px-3 py-1 rounded-lg text-sm hover:bg-orange-100 transition">
                            <i class="fas fa-clipboard-list"></i> Dashboard
                        </a>
                    <?php endif; ?>
                    <div class="hidden md:block">
                        <div class="bg-white rounded-full w-10 h-10 flex items-center justify-center">
                            <i class="fas fa-mug-hot text-orange-custom text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-4 md:py-6">
        <!-- Waiter Table Selector (only visible to waiters) -->
        <?php if ($is_waiter && $order_enabled): ?>
        <div class="bg-white rounded-lg shadow p-4 mb-4" style="background-color: var(--card-bg);">
            <div class="flex flex-col sm:flex-row gap-4 items-center">
                <div class="flex items-center gap-2">
                    <i class="fas fa-table text-orange-custom"></i>
                    <span class="font-semibold">Order for Table:</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    <?php foreach($waiter_assigned_tables as $table): ?>
                        <button onclick="setWaiterTable('<?php echo $table; ?>')" 
                                class="waiter-table-btn px-4 py-2 bg-gray-200 rounded-lg hover:bg-orange-custom hover:text-white transition"
                                data-table="<?php echo $table; ?>">
                            <?php echo str_replace('TABLE_', 'Table ', $table); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <div id="selected-table-display" class="text-sm text-gray-500 hidden">
                    <i class="fas fa-check-circle text-green-500"></i> 
                    Selected: <span id="selected-table-name"></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <form method="GET" action="" id="filterForm" class="filters-container p-4 mb-4">
            <div class="flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <div class="relative">
                        <input type="text" name="search" id="search" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="<?php echo t('search_placeholder'); ?>" 
                               class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom focus:ring-1 focus:ring-orange-custom theme-transition"
                               style="background-color: var(--bg-primary); color: var(--text-primary);">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                <div>
                    <select name="category" id="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom theme-transition"
                            style="background-color: var(--bg-primary); color: var(--text-primary);">
                        <option value=""><?php echo t('all_categories'); ?></option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <?php if(!empty($dietary_labels)): ?>
            <div class="mt-3">
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="text-gray-600 text-xs md:text-sm"><?php echo t('dietary'); ?>:</span>
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

        <div class="mb-3">
            <p class="text-gray-600 text-sm">
                <i class="fas fa-utensils mr-1"></i> 
                <?php echo t('found_items'); ?> <strong><?php echo count($meals); ?></strong> <?php echo t('menu_items'); ?>
            </p>
        </div>

        <div class="menu-grid">
            <?php if(empty($meals)): ?>
                <div class="col-span-full text-center py-12 md:py-16">
                    <div class="bg-white rounded-full w-20 h-20 md:w-24 md:h-24 flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-search text-3xl md:text-4xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 text-lg mb-2"><?php echo t('no_meals'); ?></p>
                    <p class="text-gray-400 text-sm"><?php echo t('adjust_filters'); ?></p>
                    <button onclick="resetFilters()" class="inline-block mt-4 bg-orange-custom text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition">
                        <i class="fas fa-redo-alt mr-2"></i><?php echo t('reset_filters'); ?>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php foreach($meals as $meal): 
                $discount = getMealDiscount($pdo, $meal['id'], $meal['price'], $meal['category_id']);
                $dietary_labels_meal = getMealDietaryLabels($pdo, $meal['id']);
            ?>
                <div class="meal-card overflow-hidden">
                    <div class="image-container relative">
                        <?php if($meal['image_url']): ?>
                            <img src="<?php echo $meal['image_url']; ?>" 
                                 alt="<?php echo htmlspecialchars($meal['name']); ?>" 
                                 class="meal-image w-full object-cover"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-[160px] md:h-[180px] flex items-center justify-center">
                                <i class="fas fa-utensils text-4xl md:text-5xl text-orange-300"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute top-2 left-2 flex flex-col gap-1">
                            <?php if($discount): ?>
                                <span class="bg-green-500 text-white px-2 py-0.5 rounded text-xs font-bold shadow-md">
                                    <?php if($discount['discount_type'] == 'percentage'): ?>
                                        <?php echo $discount['discount_value']; ?><?php echo t('off'); ?>
                                    <?php else: ?>
                                        -$<?php echo number_format($discount['discount_value'], 2); ?>
                                    <?php endif; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-2 right-2 flex flex-col gap-1 items-end">
                            <?php if($meal['is_popular']): ?>
                                <span class="bg-red-500 text-white px-2 py-0.5 rounded text-xs font-semibold shadow-md">
                                    <i class="fas fa-fire"></i> <?php echo t('popular'); ?>
                                </span>
                            <?php endif; ?>
                            <?php if($meal['is_featured']): ?>
                                <span class="bg-yellow-500 text-white px-2 py-0.5 rounded text-xs font-semibold shadow-md">
                                    <i class="fas fa-star"></i> <?php echo t('featured'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
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
                        
                        <p class="text-gray-600 text-xs mb-2 line-clamp-2"><?php echo htmlspecialchars(substr($meal['description'], 0, 60)); ?></p>
                        
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
                        
                        <div class="flex justify-between items-center text-xs text-gray-500 pt-2 border-t" style="border-color: var(--border-color);">
                            <div class="flex items-center gap-1">
                                <i class="far fa-clock"></i>
                                <span><?php echo $meal['preparation_time']; ?> <?php echo t('min'); ?></span>
                            </div>
                            <?php if($meal['calories']): ?>
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-fire"></i>
                                    <span><?php echo $meal['calories']; ?> <?php echo t('cal'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Order Button - Different for Waiter vs Customer -->
                        <?php if ($order_enabled): ?>
                            <?php if ($is_waiter): ?>
                                <button class="waiter-add-to-cart-btn w-full mt-2 bg-orange-custom text-white py-2 rounded-lg hover:bg-orange-600 transition flex items-center justify-center gap-2"
                                        data-meal-id="<?php echo $meal['id']; ?>"
                                        data-meal-name="<?php echo htmlspecialchars($meal['name']); ?>"
                                        data-meal-price="<?php echo $meal['price']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                    Add for Table
                                </button>
                            <?php elseif ($current_table): ?>
                                <button class="customer-add-to-cart-btn w-full mt-2 bg-orange-custom text-white py-2 rounded-lg hover:bg-orange-600 transition flex items-center justify-center gap-2"
                                        data-meal-id="<?php echo $meal['id']; ?>"
                                        data-meal-name="<?php echo htmlspecialchars($meal['name']); ?>"
                                        data-meal-price="<?php echo $meal['price']; ?>">
                                    <i class="fas fa-cart-plus"></i>
                                    Add to Order
                                </button>
                            <?php else: ?>
                                <div class="text-center text-xs text-gray-500 mt-2">
                                    <i class="fas fa-qrcode"></i> <?php echo t('scan_qr_to_order'); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="mt-12 py-6 md:py-8" style="background-color: #1f2937; color: white;">
        <div class="container mx-auto px-4 text-center">
            <div class="mb-4">
                <i class="fas fa-mug-hot text-2xl md:text-3xl text-orange-custom"></i>
            </div>
            <p class="text-lg font-semibold mb-2"><?php echo t('site_name'); ?></p>
            <p class="text-gray-400 text-sm"><?php echo t('made_with_love'); ?></p>
            <p class="text-gray-500 text-xs mt-4">&copy; <?php echo date('Y'); ?> <?php echo t('site_name'); ?>. <?php echo t('copyright'); ?></p>
        </div>
    </footer>

    <!-- Customer Order Sidebar (for QR code customers) -->
    <?php if ($order_enabled && $current_table && !$is_waiter): ?>
    <div id="customer-order-sidebar" class="fixed right-0 top-0 h-full w-full sm:w-96 bg-white shadow-xl z-50 transform translate-x-full transition-transform duration-300" style="background-color: var(--card-bg);">
        <div class="p-4 border-b" style="border-color: var(--border-color);">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold"><?php echo t('your_order'); ?></h2>
                <button onclick="toggleCustomerSidebar()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="customer-table-info" class="text-sm text-orange-custom mt-1"></div>
        </div>
        <div id="customer-order-items" class="flex-1 overflow-y-auto p-4" style="max-height: calc(100vh - 200px);">
            <p class="text-gray-500 text-center py-8"><?php echo t('your_cart_empty'); ?></p>
        </div>
        <div class="border-t p-4" style="border-color: var(--border-color);">
            <div class="flex justify-between mb-2">
                <span class="font-semibold"><?php echo t('subtotal'); ?>:</span>
                <span id="customer-order-subtotal" class="font-bold text-orange-custom">$0.00</span>
            </div>
            <div class="mb-3">
                <input type="text" id="customer-name" placeholder="<?php echo t('your_name_optional'); ?>" 
                       class="w-full px-3 py-2 border rounded-lg" style="background-color: var(--bg-primary); color: var(--text-primary); border-color: var(--border-color);">
            </div>
            <button onclick="submitCustomerOrder()" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
                <i class="fas fa-check-circle"></i> <?php echo t('place_order'); ?>
            </button>
        </div>
    </div>
    <div id="customer-order-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="toggleCustomerSidebar()"></div>
    <button id="customer-cart-btn" class="fixed bottom-4 right-4 bg-orange-custom text-white p-4 rounded-full shadow-lg z-40 hover:bg-orange-600 transition">
        <i class="fas fa-shopping-cart text-xl"></i>
        <span id="customer-cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
    </button>
    <?php endif; ?>

    <!-- Waiter Order Sidebar -->
    <?php if ($is_waiter && $order_enabled): ?>
    <div id="waiter-order-sidebar" class="fixed right-0 top-0 h-full w-full sm:w-96 bg-white shadow-xl z-50 transform translate-x-full transition-transform duration-300" style="background-color: var(--card-bg);">
        <div class="p-4 border-b" style="border-color: var(--border-color);">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold">Table Order</h2>
                <button onclick="toggleWaiterSidebar()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="mt-2">
                <label class="block text-sm font-medium mb-1">Select Table:</label>
                <select id="waiter-table-select" class="w-full p-2 border rounded-lg">
                    <option value="">-- Select Table --</option>
                    <?php foreach($waiter_assigned_tables as $table): ?>
                        <option value="<?php echo $table; ?>"><?php echo str_replace('TABLE_', 'Table ', $table); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div id="waiter-order-items" class="flex-1 overflow-y-auto p-4" style="max-height: calc(100vh - 280px);">
            <p class="text-gray-500 text-center py-8">No items added</p>
        </div>
        <div class="border-t p-4" style="border-color: var(--border-color);">
            <div class="flex justify-between mb-2">
                <span class="font-semibold">Subtotal:</span>
                <span id="waiter-order-subtotal" class="font-bold text-orange-custom">$0.00</span>
            </div>
            <div class="mb-3">
                <input type="text" id="waiter-customer-name" placeholder="Customer name (optional)" 
                       class="w-full px-3 py-2 border rounded-lg">
            </div>
            <button onclick="submitWaiterOrder()" class="w-full bg-green-500 text-white py-2 rounded-lg hover:bg-green-600 transition">
                <i class="fas fa-check-circle"></i> Request Order
            </button>
        </div>
    </div>
    <div id="waiter-order-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="toggleWaiterSidebar()"></div>
    <button id="waiter-cart-btn" class="fixed bottom-4 right-4 bg-orange-custom text-white p-4 rounded-full shadow-lg z-40 hover:bg-orange-600 transition">
        <i class="fas fa-clipboard-list text-xl"></i>
        <span id="waiter-cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">0</span>
    </button>
    <?php endif; ?>

    <script>
        // ============================================
        // Customer Order Functions (for QR code customers)
        // ============================================
        let customerCart = [];
        let currentTable = '<?php echo $current_table; ?>';
        
        function toggleCustomerSidebar() {
            const sidebar = document.getElementById('customer-order-sidebar');
            const overlay = document.getElementById('customer-order-overlay');
            if (sidebar) {
                sidebar.classList.toggle('translate-x-full');
                overlay.classList.toggle('hidden');
            }
        }
        
        function addToCustomerCart(mealId, mealName, mealPrice) {
            const existingItem = customerCart.find(item => item.id === mealId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                customerCart.push({
                    id: mealId,
                    name: mealName,
                    price: parseFloat(mealPrice),
                    quantity: 1
                });
            }
            updateCustomerCartDisplay();
            saveCustomerCartToServer();
        }
        
        function updateCustomerCartDisplay() {
            const container = document.getElementById('customer-order-items');
            const cartCount = document.getElementById('customer-cart-count');
            const subtotal = customerCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            if (customerCart.length === 0) {
                if (container) container.innerHTML = '<p class="text-gray-500 text-center py-8"><?php echo t('your_cart_empty'); ?></p>';
                if (cartCount) cartCount.textContent = '0';
                const subtotalEl = document.getElementById('customer-order-subtotal');
                if (subtotalEl) subtotalEl.textContent = '$0.00';
                return;
            }
            
            if (cartCount) cartCount.textContent = customerCart.reduce((sum, item) => sum + item.quantity, 0);
            if (document.getElementById('customer-order-subtotal')) {
                document.getElementById('customer-order-subtotal').textContent = '$' + subtotal.toFixed(2);
            }
            
            if (container) {
                container.innerHTML = customerCart.map(item => `
                    <div class="flex justify-between items-center mb-3 p-2 border rounded" style="border-color: var(--border-color);">
                        <div>
                            <p class="font-semibold">${item.name}</p>
                            <p class="text-sm text-gray-500">$${item.price.toFixed(2)} x ${item.quantity}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="updateCustomerQuantity(${item.id}, ${item.quantity - 1})" class="text-red-500">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="updateCustomerQuantity(${item.id}, ${item.quantity + 1})" class="text-green-500">+</button>
                            <button onclick="removeFromCustomerCart(${item.id})" class="text-red-500 ml-2">×</button>
                        </div>
                    </div>
                `).join('');
            }
        }
        
        function updateCustomerQuantity(mealId, newQuantity) {
            if (newQuantity <= 0) {
                removeFromCustomerCart(mealId);
                return;
            }
            const item = customerCart.find(i => i.id === mealId);
            if (item) {
                item.quantity = newQuantity;
                updateCustomerCartDisplay();
                saveCustomerCartToServer();
            }
        }
        
        function removeFromCustomerCart(mealId) {
            customerCart = customerCart.filter(item => item.id !== mealId);
            updateCustomerCartDisplay();
            saveCustomerCartToServer();
        }
        
        function saveCustomerCartToServer() {
            if (!currentTable) return;
            fetch('/save-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart: customerCart,
                    table: currentTable
                })
            }).catch(error => console.error('Error saving cart:', error));
        }
        
        function loadCustomerCartFromServer() {
            if (!currentTable) return;
            fetch('/get-cart.php?table=' + encodeURIComponent(currentTable))
                .then(response => response.json())
                .then(data => {
                    if (data.cart && data.cart.length > 0) {
                        customerCart = data.cart;
                        updateCustomerCartDisplay();
                    }
                })
                .catch(error => console.error('Error loading cart:', error));
        }
        
        function submitCustomerOrder() {
            if (customerCart.length === 0) {
                alert('<?php echo t('cart_empty_error'); ?>');
                return;
            }
            
            const customerName = document.getElementById('customer-name')?.value || '';
            const subtotal = customerCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            fetch('/place-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart: customerCart,
                    table: currentTable,
                    customer_name: customerName,
                    subtotal: subtotal,
                    order_source: 'customer'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order requested successfully! A waiter will confirm your order shortly.');
                    customerCart = [];
                    updateCustomerCartDisplay();
                    toggleCustomerSidebar();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error placing order. Please try again.');
            });
        }
        
        // ============================================
        // Waiter Order Functions
        // ============================================
        let waiterCart = [];
        let selectedWaiterTable = '';
        
        function toggleWaiterSidebar() {
            const sidebar = document.getElementById('waiter-order-sidebar');
            const overlay = document.getElementById('waiter-order-overlay');
            if (sidebar) {
                sidebar.classList.toggle('translate-x-full');
                overlay.classList.toggle('hidden');
            }
        }
        
        function setWaiterTable(table) {
            selectedWaiterTable = table;
            document.querySelectorAll('.waiter-table-btn').forEach(btn => {
                btn.classList.remove('bg-orange-custom', 'text-white');
                btn.classList.add('bg-gray-200');
                if (btn.dataset.table === table) {
                    btn.classList.remove('bg-gray-200');
                    btn.classList.add('bg-orange-custom', 'text-white');
                }
            });
            const displayDiv = document.getElementById('selected-table-display');
            const displaySpan = document.getElementById('selected-table-name');
            if (displayDiv && displaySpan) {
                displayDiv.classList.remove('hidden');
                displaySpan.textContent = table.replace('TABLE_', 'Table ');
            }
        }
        
        function addToWaiterCart(mealId, mealName, mealPrice) {
            if (!selectedWaiterTable) {
                alert('Please select a table first');
                return;
            }
            const existingItem = waiterCart.find(item => item.id === mealId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                waiterCart.push({
                    id: mealId,
                    name: mealName,
                    price: parseFloat(mealPrice),
                    quantity: 1
                });
            }
            updateWaiterCartDisplay();
            toggleWaiterSidebar();
        }
        
        function updateWaiterCartDisplay() {
            const container = document.getElementById('waiter-order-items');
            const cartCount = document.getElementById('waiter-cart-count');
            const subtotal = waiterCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            if (waiterCart.length === 0) {
                if (container) container.innerHTML = '<p class="text-gray-500 text-center py-8">No items added</p>';
                if (cartCount) cartCount.textContent = '0';
                const subtotalEl = document.getElementById('waiter-order-subtotal');
                if (subtotalEl) subtotalEl.textContent = '$0.00';
                return;
            }
            
            if (cartCount) cartCount.textContent = waiterCart.reduce((sum, item) => sum + item.quantity, 0);
            if (document.getElementById('waiter-order-subtotal')) {
                document.getElementById('waiter-order-subtotal').textContent = '$' + subtotal.toFixed(2);
            }
            
            if (container) {
                container.innerHTML = waiterCart.map(item => `
                    <div class="flex justify-between items-center mb-3 p-2 border rounded" style="border-color: var(--border-color);">
                        <div>
                            <p class="font-semibold">${item.name}</p>
                            <p class="text-sm text-gray-500">$${item.price.toFixed(2)} x ${item.quantity}</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="updateWaiterQuantity(${item.id}, ${item.quantity - 1})" class="text-red-500">-</button>
                            <span>${item.quantity}</span>
                            <button onclick="updateWaiterQuantity(${item.id}, ${item.quantity + 1})" class="text-green-500">+</button>
                            <button onclick="removeFromWaiterCart(${item.id})" class="text-red-500 ml-2">×</button>
                        </div>
                    </div>
                `).join('');
            }
        }
        
        function updateWaiterQuantity(mealId, newQuantity) {
            if (newQuantity <= 0) {
                removeFromWaiterCart(mealId);
                return;
            }
            const item = waiterCart.find(i => i.id === mealId);
            if (item) {
                item.quantity = newQuantity;
                updateWaiterCartDisplay();
            }
        }
        
        function removeFromWaiterCart(mealId) {
            waiterCart = waiterCart.filter(item => item.id !== mealId);
            updateWaiterCartDisplay();
        }
        
        function submitWaiterOrder() {
            if (!selectedWaiterTable) {
                alert('Please select a table');
                return;
            }
            if (waiterCart.length === 0) {
                alert('Please add items to the order');
                return;
            }
            
            const customerName = document.getElementById('waiter-customer-name')?.value || '';
            const subtotal = waiterCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            fetch('/place-order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cart: waiterCart,
                    table: selectedWaiterTable,
                    customer_name: customerName,
                    subtotal: subtotal,
                    order_source: 'waiter'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order requested for ' + selectedWaiterTable.replace('TABLE_', 'Table ') + '!');
                    waiterCart = [];
                    updateWaiterCartDisplay();
                    toggleWaiterSidebar();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error placing order. Please try again.');
            });
        }
        
        // ============================================
        // General Functions
        // ============================================
        
        // Auto-submit filters
        document.getElementById('category')?.addEventListener('change', function() {
            document.getElementById('filterForm').submit();
        });
        
        document.querySelectorAll('.dietary-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
        
        let searchTimeout;
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500);
            });
        }
        
        function resetFilters() {
            window.location.href = window.location.pathname;
        }
        
        function changeLanguage(lang) {
            const url = new URL(window.location.href);
            url.searchParams.set('lang', lang);
            window.location.href = url.toString();
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Customer mode
            if (currentTable && !<?php echo $is_waiter ? 'true' : 'false'; ?>) {
                loadCustomerCartFromServer();
                if (document.getElementById('customer-table-info')) {
                    document.getElementById('customer-table-info').innerHTML = '<i class="fas fa-table"></i> ' + currentTable.replace('TABLE_', 'Table ');
                }
                document.querySelectorAll('.customer-add-to-cart-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const mealId = parseInt(this.dataset.mealId);
                        const mealName = this.dataset.mealName;
                        const mealPrice = parseFloat(this.dataset.mealPrice);
                        addToCustomerCart(mealId, mealName, mealPrice);
                        toggleCustomerSidebar();
                    });
                });
                const cartBtn = document.getElementById('customer-cart-btn');
                if (cartBtn) cartBtn.addEventListener('click', toggleCustomerSidebar);
            }
            
            // Waiter mode
            <?php if ($is_waiter): ?>
            document.querySelectorAll('.waiter-add-to-cart-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const mealId = parseInt(this.dataset.mealId);
                    const mealName = this.dataset.mealName;
                    const mealPrice = parseFloat(this.dataset.mealPrice);
                    addToWaiterCart(mealId, mealName, mealPrice);
                });
            });
            const waiterCartBtn = document.getElementById('waiter-cart-btn');
            if (waiterCartBtn) waiterCartBtn.addEventListener('click', toggleWaiterSidebar);
            <?php endif; ?>
        });
    </script>
    
    <?php echo getThemeScript(); ?>
</body>
</html>
