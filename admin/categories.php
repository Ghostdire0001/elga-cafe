<?php
$page_title = 'Manage Categories';
require_once '../includes/config.php';
require_once 'includes/header.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Handle Add/Edit
if($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    $name = strtolower(trim($_POST['name']));
    $description = trim($_POST['description']);
    $icon_class = trim($_POST['icon_class']);
    $display_order = intval($_POST['display_order']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, icon_class, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $icon_class, $display_order, $is_active]);
        $message = "Category added successfully!";
    } else {
        $id = intval($_POST['category_id']);
        $stmt = $pdo->prepare("UPDATE categories SET name=?, description=?, icon_class=?, display_order=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $description, $icon_class, $display_order, $is_active, $id]);
        $message = "Category updated successfully!";
    }
    header("Location: categories.php?message=" . urlencode($message));
    exit();
}

// Handle Delete
if($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM meals WHERE category_id = ?");
    $stmt->execute([$id]);
    $meal_count = $stmt->fetchColumn();
    
    if($meal_count > 0) {
        $message = "Cannot delete category with existing meals. Move or delete meals first.";
        header("Location: categories.php?error=" . urlencode($message));
    } else {
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
        header("Location: categories.php?message=Category deleted successfully");
    }
    exit();
}

// Get category for edit
$category = null;
if($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order")->fetchAll();

// Display messages
if(isset($_GET['message'])): ?>
    <div class="alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
    <div class="alert-error"><?php echo htmlspecialchars($_GET['error']);
