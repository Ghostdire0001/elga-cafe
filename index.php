<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get filter parameters
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$dietary_ids = isset($_GET['dietary']) ? array_map('intval', (array)$_GET['dietary']) : [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Debug: Check if we have meals
try {
    // Get data
    $categories = getCategories($pdo);
    $dietary_labels = getDietaryLabels($pdo);
    $meals = getMeals($pdo, $category_id, $dietary_ids, $search);
    
    // Debug: Check if meals are found
    if(empty($meals)) {
        // Test query to see if any meals exist
        $test = $pdo->query("SELECT COUNT(*) as total FROM meals WHERE availability = 1")->fetch();
        error_log("Total available meals: " . $test['total']);
    }
} catch(Exception $e) {
    die("Error loading menu: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elga Cafe - Menu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-orange-custom { background-color: #F97316; }
        .text-orange-custom { color: #F97316; }
        .hover\:bg-orange-custom:hover { background-color: #F97316; }
        .border-orange-custom { border-color: #F97316; }
        .meal-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .meal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px -5px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-orange-custom text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold">Elga Cafe</h1>
                    <p class="text-orange-100 text-sm mt-1">Delicious meals made with love</p>
                </div>
                <div class="hidden md:block">
                    <div class="bg-white rounded-full w-12 h-12 flex items-center justify-center">
                        <i class="fas fa-mug-hot text-orange-custom text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Search and Filters -->
    <div class="container mx-auto px-4 py-6">
        <form method="GET" action="" id="filterForm">
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex flex-col md:flex-row gap-4">
                    <!-- Search Bar -->
                    <div class="flex-1">
                        <div class="relative">
                            <input type="text" name="search" id="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search menu items..." 
                                   class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom focus:ring-1 focus:ring-orange-custom">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <!-- Category Filter -->
                    <div>
                        <select name="category" id="category" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom focus:ring-1 focus:ring-orange-custom">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                    <i class="fas <?php echo $cat['icon_class']; ?>"></i> <?php echo ucfirst($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Dietary Filters -->
                <?php if(!empty($dietary_labels)): ?>
                <div class="mt-4">
                    <div class="flex flex-wrap gap-2 items-center">
                        <span class="text-gray-600 text-sm font-semibold">Dietary:</span>
                        <?php foreach($dietary_labels as $label): ?>
                            <label class="inline-flex items-center cursor-pointer hover:opacity-75 transition-opacity">
                                <input type="checkbox" name="dietary[]" value="<?php echo $label['id']; ?>" 
                                       class="dietary-checkbox mr-1 hidden"
                                       <?php echo in_array($label['id'], $dietary_ids) ? 'checked' : ''; ?>>
                                <span class="text-sm px-3 py-1 rounded-full transition-all" 
                                      style="background-color: <?php echo $label['color_hex']; ?>20; color: <?php echo $label['color_hex']; ?>; border: 1px solid <?php echo $label['color_hex']; ?>40">
                                    <i class="fas <?php echo $label['icon_class']; ?> mr-1"></i>
                                    <?php echo ucfirst($label['name']); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </form>

        <!-- Results Count -->
        <div class="mb-4">
            <p class="text-gray-600">
                <i class="fas fa-utensils mr-1"></i> 
                Found <strong><?php echo count($meals); ?></strong> menu items
            </p>
        </div>

        <!-- Menu Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if(empty($meals)): ?>
                <div class="col-span-full text-center py-16">
                    <div class="bg-white rounded-full w-24 h-24 flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i class="fas fa-search text-4xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 text-lg mb-2">No meals found</p>
                    <p class="text-gray-400">Try adjusting your search or filters</p>
                    <button onclick="resetFilters()" class="mt-4 bg-orange-custom text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition">
                        Reset Filters
                    </button>
                </div>
            <?php endif; ?>
            
            <?php foreach($meals as $meal): 
                $discount = getMealDiscount($pdo, $meal['id'], $meal['price'], $meal['category_id']);
                $dietary_labels_meal = getMealDietaryLabels($pdo, $meal['id']);
            ?>
                <div class="meal-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all duration-300">
                    <!-- Image Section -->
                    <div class="relative h-48 overflow-hidden bg-gradient-to-br from-orange-100 to-orange-200">
                        <?php if($meal['image_url'] && file_exists($meal['image_url'])): ?>
                            <img src="<?php echo $meal['image_url']; ?>" alt="<?php echo htmlspecialchars($meal['name']); ?>" 
                                 class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <i class="fas fa-utensils text-6xl text-orange-300"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Badges -->
                        <div class="absolute top-2 left-2 flex flex-col gap-1">
                            <?php if($meal['is_featured']): ?>
                                <span class="bg-yellow-500 text-white px-2 py-1 rounded text-xs font-semibold shadow-md">
                                    <i class="fas fa-star"></i> Featured
                                </span>
                            <?php endif; ?>
                            <?php if($meal['is_popular']): ?>
                                <span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-semibold shadow-md">
                                    <i class="fas fa-fire"></i> Popular
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Discount Badge -->
                        <?php if($discount): ?>
                            <div class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded text-xs font-bold shadow-md">
                                <?php if($discount['discount_type'] == 'percentage'): ?>
                                    -<?php echo $discount['discount_value']; ?>% OFF
                                <?php else: ?>
                                    -$<?php echo number_format($discount['discount_value'], 2); ?> OFF
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content Section -->
                    <div class="p-4">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-lg font-bold text-gray-800 line-clamp-1"><?php echo htmlspecialchars($meal['name']); ?></h3>
                            <div class="text-right">
                                <?php if($discount): ?>
                                    <span class="text-gray-400 line-through text-sm">$<?php echo number_format($meal['price'], 2); ?></span>
                                    <div class="text-orange-custom font-bold text-xl">$<?php echo number_format($discount['discounted_price'], 2); ?></div>
                                <?php else: ?>
                                    <div class="text-orange-custom font-bold text-xl">$<?php echo number_format($meal['price'], 2); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars($meal['description']); ?></p>
                        
                        <!-- Dietary Labels -->
                        <?php if(!empty($dietary_labels_meal)): ?>
                        <div class="flex flex-wrap gap-1 mb-3">
                            <?php foreach($dietary_labels_meal as $label): ?>
                                <span class="text-xs px-2 py-1 rounded-full" 
                                      style="background-color: <?php echo $label['color_hex']; ?>20; color: <?php echo $label['color_hex']; ?>">
                                    <i class="fas <?php echo $label['icon_class']; ?> text-xs"></i>
                                    <span class="ml-1"><?php echo ucfirst($label['name']); ?></span>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Meta Info -->
                        <div class="flex justify-between items-center text-sm text-gray-500 pt-2 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <i class="far fa-clock"></i>
                                <span><?php echo $meal['preparation_time']; ?> min</span>
                            </div>
                            <?php if($meal['calories']): ?>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-fire"></i>
                                    <span><?php echo $meal['calories']; ?> cal</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12 py-8">
        <div class="container mx-auto px-4 text-center">
            <div class="mb-4">
                <i class="fas fa-mug-hot text-3xl text-orange-custom"></i>
            </div>
            <p class="text-lg font-semibold mb-2">Elga Cafe</p>
            <p class="text-gray-400 text-sm">Delicious meals made with love</p>
            <p class="text-gray-500 text-xs mt-4">&copy; 2026 Elga Cafe. All rights reserved.</p>
        </div>
    </footer>

    <script>
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
        document.getElementById('search')?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 500);
        });
        
        // Reset filters
        function resetFilters() {
            window.location.href = window.location.pathname;
        }
    </script>
</body>
</html>