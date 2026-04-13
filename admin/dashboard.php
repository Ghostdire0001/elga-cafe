<?php
$page_title = 'Dashboard';
require_once '../includes/config.php';
require_once 'includes/header.php';

// Get stats
$total_meals = $pdo->query("SELECT COUNT(*) FROM meals")->fetchColumn();
$active_meals = $pdo->query("SELECT COUNT(*) FROM meals WHERE availability = 1")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
$total_discounts = $pdo->query("SELECT COUNT(*) FROM discounts WHERE is_active = 1")->fetchColumn();
$active_discounts = $pdo->query("SELECT COUNT(*) FROM discounts WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW()")->fetchColumn();

// Get recent meals (last 5 added)
$recent_meals = $pdo->query("SELECT m.*, c.name as category_name 
                              FROM meals m 
                              LEFT JOIN categories c ON m.category_id = c.id 
                              ORDER BY m.created_at DESC LIMIT 5")->fetchAll();

// Get popular meals (most ordered - if you have order data, otherwise just featured)
$popular_meals = $pdo->query("SELECT * FROM meals WHERE is_popular = 1 AND availability = 1 LIMIT 5")->fetchAll();

// Get low stock or unavailable meals
$unavailable_meals = $pdo->query("SELECT COUNT(*) FROM meals WHERE availability = 0")->fetchColumn();
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        <p class="text-gray-600 mt-1">Here's what's happening with your menu today.</p>
    </div>
    <div class="flex gap-3">
        <a href="meals.php?action=add" class="btn-success">
            <i class="fas fa-plus"></i> Add Meal
        </a>
        <a href="discounts.php?action=add" class="btn-success" style="background-color: #eab308;">
            <i class="fas fa-tag"></i> Add Discount
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="stat-label">Total Meals</p>
                <p class="stat-value"><?php echo $total_meals; ?></p>
            </div>
            <i class="fas fa-utensils text-3xl md:text-4xl text-orange-custom"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="stat-label">Active Meals</p>
                <p class="stat-value"><?php echo $active_meals; ?></p>
            </div>
            <i class="fas fa-check-circle text-3xl md:text-4xl text-green-500"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="stat-label">Active Categories</p>
                <p class="stat-value"><?php echo $total_categories; ?></p>
            </div>
            <i class="fas fa-tags text-3xl md:text-4xl text-blue-500"></i>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="stat-label">Active Discounts</p>
                <p class="stat-value"><?php echo $active_discounts; ?></p>
            </div>
            <i class="fas fa-tag text-3xl md:text-4xl text-yellow-500"></i>
        </div>
    </div>
</div>

<!-- Two Column Layout -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Recent Meals -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">
                <i class="fas fa-clock text-orange-custom mr-2"></i> Recently Added Meals
            </h2>
        </div>
        <div class="p-4">
            <?php if(empty($recent_meals)): ?>
                <p class="text-gray-500 text-center py-4">No meals added yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach($recent_meals as $meal): ?>
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg transition">
                            <div class="flex items-center gap-3">
                                <?php if($meal['image_url']): ?>
                                    <img src="<?php echo $meal['image_url']; ?>" alt="<?php echo htmlspecialchars($meal['name']); ?>" class="w-10 h-10 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-utensils text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($meal['name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo ucfirst($meal['category_name']); ?></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-orange-custom">$<?php echo number_format($meal['price'], 2); ?></p>
                                <span class="text-xs <?php echo $meal['availability'] ? 'text-green-600' : 'text-red-600'; ?>">
                                    <?php echo $meal['availability'] ? 'Available' : 'Unavailable'; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Popular Meals -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">
                <i class="fas fa-fire text-orange-custom mr-2"></i> Popular Meals
            </h2>
        </div>
        <div class="p-4">
            <?php if(empty($popular_meals)): ?>
                <p class="text-gray-500 text-center py-4">No popular meals marked yet.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach($popular_meals as $meal): ?>
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg transition">
                            <div class="flex items-center gap-3">
                                <?php if($meal['image_url']): ?>
                                    <img src="<?php echo $meal['image_url']; ?>" alt="<?php echo htmlspecialchars($meal['name']); ?>" class="w-10 h-10 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-10 h-10 bg-gray-200 rounded flex items-center justify-center">
                                        <i class="fas fa-utensils text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo htmlspecialchars($meal['name']); ?></p>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="badge-success text-xs py-0 px-1">
                                            <i class="fas fa-fire"></i> Popular
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <a href="meals.php?action=edit&id=<?php echo $meal['id']; ?>" class="text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Stats Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Discounts</p>
                <p class="text-2xl font-bold"><?php echo $total_discounts; ?></p>
            </div>
            <i class="fas fa-tag text-3xl text-yellow-500"></i>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Unavailable Meals</p>
                <p class="text-2xl font-bold <?php echo $unavailable_meals > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                    <?php echo $unavailable_meals; ?>
                </p>
            </div>
            <i class="fas fa-ban text-3xl text-red-400"></i>
        </div>
        <?php if($unavailable_meals > 0): ?>
            <a href="meals.php" class="text-sm text-orange-custom mt-2 inline-block">
                <i class="fas fa-eye"></i> View unavailable meals
            </a>
        <?php endif; ?>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Menu Status</p>
                <p class="text-2xl font-bold <?php echo $active_meals > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $active_meals > 0 ? 'Active' : 'Empty'; ?>
                </p>
            </div>
            <i class="fas fa-check-circle text-3xl text-green-500"></i>
        </div>
        <?php if($active_meals > 0): ?>
            <a href="../index.php" target="_blank" class="text-sm text-orange-custom mt-2 inline-block">
                <i class="fas fa-external-link-alt"></i> View live menu
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
