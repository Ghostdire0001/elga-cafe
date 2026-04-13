<?php
$page_title = 'Manage Categories';
require_once '../includes/config.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

$icon_options = [
    'fa-utensils' => '🍽️ Utensils',
    'fa-hamburger' => '🍔 Hamburger',
    'fa-pizza-slice' => '🍕 Pizza',
    'fa-fish' => '🐟 Fish',
    'fa-carrot' => '🥕 Carrot',
    'fa-apple-alt' => '🍎 Apple',
    'fa-coffee' => '☕ Coffee',
    'fa-mug-hot' => '🍵 Hot Mug',
    'fa-wine-glass-alt' => '🍷 Wine Glass',
    'fa-cocktail' => '🍹 Cocktail',
    'fa-ice-cream' => '🍦 Ice Cream',
    'fa-cake-candles' => '🎂 Cake',
    'fa-bread-slice' => '🍞 Bread',
    'fa-cheese' => '🧀 Cheese',
    'fa-egg' => '🥚 Egg',
    'fa-pepper-hot' => '🌶️ Hot Pepper',
    'fa-leaf' => '🌿 Leaf',
    'fa-seedling' => '🌱 Seedling',
    'fa-bowl-food' => '🥣 Bowl',
    'fa-plate-wheat' => '🍽️ Plate',
];

// Handle Add/Edit
if($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    $name = strtolower(trim($_POST['name']));
    $description = trim($_POST['description']);
    $icon_class = trim($_POST['icon_class']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if($action == 'add') {
        $stmt = $pdo->query("SELECT MAX(display_order) as max_order FROM categories");
        $result = $stmt->fetch();
        $display_order = ($result['max_order'] ?? 0) + 1;
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description, icon_class, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $icon_class, $display_order, $is_active]);
        $message = "Category added successfully!";
    } else {
        $id = intval($_POST['category_id']);
        $stmt = $pdo->prepare("UPDATE categories SET name=?, description=?, icon_class=?, is_active=? WHERE id=?");
        $stmt->execute([$name, $description, $icon_class, $is_active, $id]);
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

require_once 'includes/header.php';

$category = null;
if($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();

if(isset($_GET['message'])): ?>
    <div class="alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
    <div class="alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Manage Categories</h1>
    <a href="?action=add" class="btn-success">
        <i class="fas fa-plus"></i> Add Category
    </a>
</div>

<?php if($action == 'add' || ($action == 'edit' && $category)): ?>
    <div class="form-container">
        <h2 class="text-xl font-bold mb-4"><?php echo $action == 'add' ? 'Add Category' : 'Edit Category'; ?></h2>
        <form method="POST" action="">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" required value="<?php echo $category ? ucfirst($category['name']) : ''; ?>" 
                           placeholder="e.g., Appetizers, Mains, Desserts"
                           class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Icon *</label>
                    <select name="icon_class" required class="form-select">
                        <option value="">Select an icon...</option>
                        <?php foreach($icon_options as $icon_value => $icon_label): ?>
                            <option value="<?php echo $icon_value; ?>" <?php echo ($category && $category['icon_class'] == $icon_value) ? 'selected' : ''; ?>>
                                <?php echo $icon_label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Choose an icon that represents this category</p>
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-textarea" 
                              placeholder="Describe what this category offers..."><?php echo $category ? htmlspecialchars($category['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?php echo ($category && $category['is_active']) || !$category ? 'checked' : ''; ?>>
                        Active (show on menu)
                    </label>
                </div>
            </div>
            
            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Save Category' : 'Update Category'; ?>
                </button>
                <a href="categories.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $order = 1;
                foreach($categories as $cat): 
                ?>
                    <tr>
                        <td><?php echo $order++; ?></td>
                        <td><i class="fas <?php echo $cat['icon_class']; ?> text-orange-custom text-xl"></i></td>
                        <td>
                            <div class="font-semibold text-gray-900"><?php echo ucfirst($cat['name']); ?></div>
                            <div class="text-xs text-gray-500 mt-1"><?php echo $cat['icon_class']; ?></div>
                        </td>
                        <td><?php echo htmlspecialchars(substr($cat['description'], 0, 50)) . (strlen($cat['description']) > 50 ? '...' : ''); ?></td>
                        <td>
                            <span class="<?php echo $cat['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="btn-primary" style="padding: 0.25rem 0.5rem; background-color: #4f46e5;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?action=delete&id=<?php echo $cat['id']; ?>" onclick="return confirm('Are you sure?')" class="btn-danger" style="padding: 0.25rem 0.5rem;">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
