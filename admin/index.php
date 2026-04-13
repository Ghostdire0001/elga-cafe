<?php
$page_title = 'Dashboard';
require_once '../includes/config.php';
require_once 'includes/header.php';

// Get stats
$total_meals = $pdo->query("SELECT COUNT(*) FROM meals")->fetchColumn();
$active_meals = $pdo->query("SELECT COUNT(*) FROM meals WHERE availability = 1")->fetchColumn();
$total_categories = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$active_discounts = $pdo->query("SELECT COUNT(*) FROM discounts WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW()")->fetchColumn();
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
</div>

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
                <p class="stat-label">Categories</p>
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

<div class="form-container">
    <h2 class="text-lg md:text-xl font-bold mb-4">Quick Actions</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="meals.php?action=add" class="btn-success text-center justify-center">
            <i class="fas fa-plus"></i> Add New Meal
        </a>
        <a href="discounts.php?action=add" class="btn-success text-center justify-center" style="background-color: #eab308;">
            <i class="fas fa-tag"></i> Create Discount
        </a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
