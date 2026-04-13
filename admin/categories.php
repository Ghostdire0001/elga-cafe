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
                    <input type="text" name="name" required value="<?php echo $category ? ucfirst($category['name']) : ''; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Icon Class (Font Awesome)</label>
                    <input type="text" name="icon_class" value="<?php echo $category ? $category['icon_class'] : 'fa-utensils'; ?>" placeholder="fa-utensils" class="form-input">
                    <p class="text-xs text-gray-500 mt-1">Example: fa-utensils, fa-hamburger, fa-coffee</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" value="<?php echo $category ? $category['display_order'] : '0'; ?>" class="form-input">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-textarea"><?php echo $category ? htmlspecialchars($category['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" <?php echo ($category && $category['is_active']) || !$category ? 'checked' : ''; ?>>
                        Active
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
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($categories as $cat): ?>
                    <tr>
                        <td><?php echo $cat['display_order']; ?></td>
                        <td><i class="fas <?php echo $cat['icon_class']; ?> text-orange-custom"></i></td>
                        <td><strong><?php echo ucfirst($cat['name']); ?></strong></td>
                        <td>
                            <span class="<?php echo $cat['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="btn-primary" style="padding: 0.25rem 0.5rem; background-color: #4f46e5;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?action=delete&id=<?php echo $cat['id']; ?>" onclick="return confirm('Are you sure?')" class="btn-danger" style="padding: 0.25rem 0.5rem;">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
