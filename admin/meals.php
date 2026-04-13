<?php
$page_title = 'Manage Meals';
require_once '../includes/config.php';
require_once 'includes/header.php';

// Handle different actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// Handle Add/Edit Meal
if(($_SERVER['REQUEST_METHOD'] === 'POST') && in_array($action, ['add', 'edit'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $availability = isset($_POST['availability']) ? 1 : 0;
    $preparation_time = intval($_POST['preparation_time']);
    $calories = !empty($_POST['calories']) ? intval($_POST['calories']) : null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_popular = isset($_POST['is_popular']) ? 1 : 0;
    $image_url = !empty($_POST['image_url']) ? trim($_POST['image_url']) : null;
    $dietary_labels = isset($_POST['dietary_labels']) ? $_POST['dietary_labels'] : [];
    
    if($action == 'add') {
        $sql = "INSERT INTO meals (name, description, price, category_id, availability, preparation_time, calories, is_featured, is_popular, image_url) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $price, $category_id, $availability, $preparation_time, $calories, $is_featured, $is_popular, $image_url]);
        $meal_id = $pdo->lastInsertId();
        $message = "Meal added successfully!";
    } else {
        $meal_id = intval($_POST['meal_id']);
        $sql = "UPDATE meals SET name=?, description=?, price=?, category_id=?, availability=?, preparation_time=?, calories=?, is_featured=?, is_popular=?, image_url=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $description, $price, $category_id, $availability, $preparation_time, $calories, $is_featured, $is_popular, $image_url, $meal_id]);
        $message = "Meal updated successfully!";
    }
    
    // Update dietary labels
    $pdo->prepare("DELETE FROM meal_dietary_labels WHERE meal_id = ?")->execute([$meal_id]);
    foreach($dietary_labels as $label_id) {
        $stmt = $pdo->prepare("INSERT INTO meal_dietary_labels (meal_id, dietary_label_id) VALUES (?, ?)");
        $stmt->execute([$meal_id, $label_id]);
    }
    
    header("Location: meals.php?message=" . urlencode($message));
    exit();
}

// Handle Delete
if($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $pdo->prepare("DELETE FROM meal_dietary_labels WHERE meal_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM meals WHERE id = ?")->execute([$id]);
    header("Location: meals.php?message=Meal deleted successfully");
    exit();
}

// Handle Toggle Availability
if($action == 'toggle' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $pdo->prepare("UPDATE meals SET availability = NOT availability WHERE id = ?")->execute([$id]);
    header("Location: meals.php?message=Availability updated");
    exit();
}

// Get all data for forms
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order")->fetchAll();
$dietary_labels = $pdo->query("SELECT * FROM dietary_labels ORDER BY id")->fetchAll();

// Get meal data for edit form
$meal = null;
if($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM meals WHERE id = ?");
    $stmt->execute([$id]);
    $meal = $stmt->fetch();
    
    if($meal) {
        $stmt = $pdo->prepare("SELECT dietary_label_id FROM meal_dietary_labels WHERE meal_id = ?");
        $stmt->execute([$id]);
        $meal_dietary = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Get all meals for listing
$meals = $pdo->query("SELECT m.*, c.name as category_name 
                      FROM meals m 
                      LEFT JOIN categories c ON m.category_id = c.id 
                      ORDER BY m.id DESC")->fetchAll();

// Display message
if(isset($_GET['message'])): ?>
    <div class="alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Manage Meals</h1>
    <a href="?action=add" class="btn-success">
        <i class="fas fa-plus"></i> Add New Meal
    </a>
</div>

<?php if($action == 'add' || ($action == 'edit' && $meal)): ?>
    <!-- Add/Edit Form -->
    <div class="form-container">
        <h2 class="text-xl font-bold mb-4"><?php echo $action == 'add' ? 'Add New Meal' : 'Edit Meal'; ?></h2>
        <form method="POST" action="">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" required value="<?php echo $meal ? htmlspecialchars($meal['name']) : ''; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Price *</label>
                    <input type="number" step="0.01" name="price" required value="<?php echo $meal ? $meal['price'] : ''; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Category *</label>
                    <select name="category_id" required class="form-select">
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($meal && $meal['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Preparation Time (minutes)</label>
                    <input type="number" name="preparation_time" value="<?php echo $meal ? $meal['preparation_time'] : '15'; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Calories</label>
                    <input type="number" name="calories" value="<?php echo $meal ? $meal['calories'] : ''; ?>" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Image URL</label>
                    <input type="text" name="image_url" value="<?php echo $meal ? htmlspecialchars($meal['image_url']) : ''; ?>" placeholder="/images/meal-name.jpg" class="form-input">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="3" class="form-textarea"><?php echo $meal ? htmlspecialchars($meal['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dietary Labels</label>
                    <div class="checkbox-group">
                        <?php foreach($dietary_labels as $label): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="dietary_labels[]" value="<?php echo $label['id']; ?>"
                                       <?php echo ($meal && in_array($label['id'], $meal_dietary ?? [])) ? 'checked' : ''; ?>>
                                <span style="color: <?php echo $label['color_hex']; ?>">
                                    <i class="fas <?php echo $label['icon_class']; ?>"></i>
                                    <?php echo ucfirst($label['name']); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="availability" <?php echo ($meal && $meal['availability']) ? 'checked' : 'checked'; ?>>
                            Available
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_featured" <?php echo ($meal && $meal['is_featured']) ? 'checked' : ''; ?>>
                            Featured
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_popular" <?php echo ($meal && $meal['is_popular']) ? 'checked' : ''; ?>>
                            Popular
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Save Meal' : 'Update Meal'; ?>
                </button>
                <a href="meals.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- Meals List -->
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($meals as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td>
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></div>
                            <?php if($item['is_featured'] || $item['is_popular']): ?>
                                <div class="text-xs">
                                    <?php if($item['is_featured']): ?>
                                        <span class="text-yellow-600"><i class="fas fa-star"></i> Featured</span>
                                    <?php endif; ?>
                                    <?php if($item['is_popular']): ?>
                                        <span class="text-red-600 ml-2"><i class="fas fa-fire"></i> Popular</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo ucfirst($item['category_name']); ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <span class="<?php echo $item['availability'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $item['availability'] ? 'Available' : 'Unavailable'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="?action=toggle&id=<?php echo $item['id']; ?>" class="btn-secondary" style="padding: 0.25rem 0.5rem;">
                                <i class="fas fa-toggle-<?php echo $item['availability'] ? 'on' : 'off'; ?>"></i>
                            </a>
                            <a href="?action=edit&id=<?php echo $item['id']; ?>" class="btn-primary" style="padding: 0.25rem 0.5rem; background-color: #4f46e5;">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?action=delete&id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?')" class="btn-danger" style="padding: 0.25rem 0.5rem;">
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
