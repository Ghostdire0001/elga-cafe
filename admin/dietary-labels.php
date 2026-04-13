<?php
$page_title = 'Manage Dietary Labels';
require_once '../includes/config.php';
require_once 'includes/header.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Handle Add/Edit
if($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($action, ['add', 'edit'])) {
    $name = strtolower(trim($_POST['name']));
    $icon_class = trim($_POST['icon_class']);
    $color_hex = trim($_POST['color_hex']);
    
    if($action == 'add') {
        $stmt = $pdo->prepare("INSERT INTO dietary_labels (name, icon_class, color_hex) VALUES (?, ?, ?)");
        $stmt->execute([$name, $icon_class, $color_hex]);
        $message = "Dietary label added successfully!";
    } else {
        $id = intval($_POST['label_id']);
        $stmt = $pdo->prepare("UPDATE dietary_labels SET name=?, icon_class=?, color_hex=? WHERE id=?");
        $stmt->execute([$name, $icon_class, $color_hex, $id]);
        $message = "Dietary label updated successfully!";
    }
    header("Location: dietary-labels.php?message=" . urlencode($message));
    exit();
}

// Handle Delete
if($action == 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM meal_dietary_labels WHERE dietary_label_id = ?");
    $stmt->execute([$id]);
    $usage_count = $stmt->fetchColumn();
    
    if($usage_count > 0) {
        $message = "Cannot delete label that is used by meals. Remove it from meals first.";
        header("Location: dietary-labels.php?error=" . urlencode($message));
    } else {
        $pdo->prepare("DELETE FROM dietary_labels WHERE id = ?")->execute([$id]);
        header("Location: dietary-labels.php?message=Dietary label deleted successfully");
    }
    exit();
}

// Get label for edit
$label = null;
if($action == 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM dietary_labels WHERE id = ?");
    $stmt->execute([$id]);
    $label = $stmt->fetch();
}

// Get all labels
$labels = $pdo->query("SELECT * FROM dietary_labels ORDER BY id")->fetchAll();

// Display messages
if(isset($_GET['message'])): ?>
    <div class="alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
    <div class="alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Manage Dietary Labels</h1>
    <a href="?action=add" class="btn-success">
        <i class="fas fa-plus"></i> Add Label
    </a>
</div>

<?php if($action == 'add' || ($action == 'edit' && $label)): ?>
    <div class="form-container">
        <h2 class="text-xl font-bold mb-4"><?php echo $action == 'add' ? 'Add Dietary Label' : 'Edit Dietary Label'; ?></h2>
        <form method="POST" action="">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="label_id" value="<?php echo $label['id']; ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" required value="<?php echo $label ? ucfirst($label['name']) : ''; ?>" placeholder="vegetarian, gluten-free, vegan, etc." class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Icon Class (Font Awesome)</label>
                    <input type="text" name="icon_class" value="<?php echo $label ? $label['icon_class'] : 'fa-leaf'; ?>" placeholder="fa-leaf" class="form-input">
                    <p class="text-xs text-gray-500 mt-1">Examples: fa-leaf, fa-wheat-slash, fa-pepper-hot</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Color (Hex)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="color_hex" value="<?php echo $label ? $label['color_hex'] : '#4CAF50'; ?>" class="w-16 h-10 border border-gray-300 rounded">
                        <input type="text" name="color_hex" value="<?php echo $label ? $label['color_hex'] : '#4CAF50'; ?>" class="form-input flex-1">
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex gap-2">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'add' ? 'Save Label' : 'Update Label'; ?>
                </button>
                <a href="dietary-labels.php" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Color</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($labels as $item): ?>
                    <tr>
                        <td><?php echo $item['id']; ?></td>
                        <td><i class="fas <?php echo $item['icon_class']; ?>" style="color: <?php echo $item['color_hex']; ?>"></i></td>
                        <td><strong><?php echo ucfirst($item['name']); ?></strong></td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded border" style="background-color: <?php echo $item['color_hex']; ?>"></div>
                                <span class="text-xs"><?php echo $item['color_hex']; ?></span>
                            </div>
                        </td>
                        <td class="action-buttons">
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
