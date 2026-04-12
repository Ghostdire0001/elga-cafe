<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Get stats
$total_meals = $pdo->query("SELECT COUNT(*) FROM meals")->fetchColumn();
$active_meals = $pdo->query("SELECT COUNT(*) FROM meals WHERE availability = 1")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$active_discounts = $pdo->query("SELECT COUNT(*) FROM discounts WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Elga Cafe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h2 class="text-xl font-bold">Elga Cafe Admin</h2>
                <p class="text-sm text-gray-400">Welcome, <?php echo $_SESSION['username']; ?></p>
            </div>
            <nav class="mt-8">
                <a href="index.php" class="block py-2 px-4 bg-orange-custom hover:bg-orange-600">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
                <a href="meals.php" class="block py-2 px-4 hover:bg-gray-700">
                    <i class="fas fa-utensils mr-2"></i> Meals
                </a>
                <a href="categories.php" class="block py-2 px-4 hover:bg-gray-700">
                    <i class="fas fa-tags mr-2"></i> Categories
                </a>
                <a href="dietary-labels.php" class="block py-2 px-4 hover:bg-gray-700">
                    <i class="fas fa-leaf mr-2"></i> Dietary Labels
                </a>
                <a href="discounts.php" class="block py-2 px-4 hover:bg-gray-700">
                    <i class="fas fa-tag mr-2"></i> Discounts
                </a>
                <a href="logout.php" class="block py-2 px-4 hover:bg-gray-700 mt-8">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-8">Dashboard</h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Total Meals</p>
                                <p class="text-3xl font-bold"><?php echo $total_meals; ?></p>
                            </div>
                            <i class="fas fa-utensils text-4xl text-orange-custom"></i>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Active Meals</p>
                                <p class="text-3xl font-bold"><?php echo $active_meals; ?></p>
                            </div>
                            <i class="fas fa-check-circle text-4xl text-green-500"></i>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Categories</p>
                                <p class="text-3xl font-bold"><?php echo $total_categories; ?></p>
                            </div>
                            <i class="fas fa-tags text-4xl text-blue-500"></i>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm">Active Discounts</p>
                                <p class="text-3xl font-bold"><?php echo $active_discounts; ?></p>
                            </div>
                            <i class="fas fa-tag text-4xl text-yellow-500"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="meals.php?action=add" class="bg-green-500 text-white text-center py-2 rounded hover:bg-green-600">
                            <i class="fas fa-plus mr-2"></i> Add New Meal
                        </a>
                        <a href="discounts.php?action=add" class="bg-yellow-500 text-white text-center py-2 rounded hover:bg-yellow-600">
                            <i class="fas fa-tag mr-2"></i> Create Discount
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>