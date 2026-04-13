<?php
$page_title = 'Manage Dietary Labels';
require_once '../includes/config.php';
require_once 'includes/header.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Predefined icon options with display names and suggested colors
$icon_options = [
    'fa-leaf' => '🌿 Leaf (Vegetarian)',
    'fa-wheat-slash' => '🚫🌾 Gluten-Free',
    'fa-seedling' => '🌱 Seedling (Vegan)',
    'fa-pepper-hot' => '🌶️ Hot Pepper (Spicy)',
    'fa-cheese' => '🧀 Cheese (Dairy-Free)',
    'fa-circle-exclamation' => '⚠️ Warning (Contains Nuts)',
    'fa-chart-line' => '📈 Chart (Low Carb)',
    'fa-egg' => '🥚 Egg',
    'fa-fish' => '🐟 Fish',
    'fa-bacon' => '🥓 Bacon',
    'fa-apple-alt' => '🍎 Apple (Organic)',
    'fa-heart' => '❤️ Heart (Healthy)',
    'fa-dove' => '🕊️ Dove (Halal)',
    'fa-star-of-david' => '✡️ Star (Kosher)',
];

// Predefined color options
$color_options = [
    '#4CAF50' => 'Green (Vegetarian/Vegan)',
    '#FF9800' => 'Orange (Gluten-Free)',
    '#F44336' => 'Red (Spicy/Allergen)',
    '#2196F3' => 'Blue (Dairy-Free)',
    '#9C27B0' => 'Purple (Nuts)',
    '#00BCD4' => 'Cyan (Low Carb)',
    '#795548' => 'Brown (Organic)',
    '#E91E63' => 'Pink (Sweet)',
    '#607D8B' => 'Blue Grey (Seafood)',
    '#8BC34A' => 'Light Green (Healthy)',
];

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
$labels = $pdo->query("SELECT * FROM dietary_labels ORDER BY name ASC")->fetchAll();

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
                    <label class="form-label">Label Name *</label>
                    <input type="text" name="name" required value="<?php echo $label ? ucfirst($label['name']) : ''; ?>" 
                           placeholder="e.g., vegetarian, gluten-free, vegan, spicy"
                           class="form-input">
                    <p class="text-xs text-gray-500 mt-1">Use lowercase, hyphens instead of spaces (e.g., gluten-free)</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Icon *</label>
                    <select name="icon_class" required class="form-select">
                        <option value="">Select an icon...</option>
                        <?php foreach($icon_options as $icon_value => $icon_label): ?>
                            <option value="<?php echo $icon_value; ?>" <?php echo ($label && $label['icon_class'] == $icon_value) ? 'selected' : ''; ?>>
                                <?php echo $icon_label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Choose an icon that represents this dietary label</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Color *</label>
                    <div class="grid grid-cols-2 gap-2">
                        <select name="color_hex" id="color_select" class="form-select">
                            <option value="">Select a color...</option>
                            <?php foreach($color_options as $color_value => $color_label): ?>
                                <option value="<?php echo $color_value; ?>" <?php echo ($label && $label['color_hex'] == $color_value) ? 'selected' : ''; ?> 
                                        style="background-color: <?php echo $color_value; ?>20; color: <?php echo $color_value; ?>;">
                                    <?php echo $color_label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color_hex_custom" id="color_custom" 
                                   value="<?php echo $label ? $label['color_hex'] : '#4CAF50'; ?>" 
                                   class="w-12 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" name="color_hex" id="color_hex" 
                                   value="<?php echo $label ? $label['color_hex'] : '#4CAF50'; ?>" 
                                   class="form-input flex-1" placeholder="#RRGGBB">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Choose a color or pick a custom one using the color picker</p>
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
    
    <script>
        // Sync color inputs
        const colorSelect = document.getElementById('color_select');
        const colorCustom = document.getElementById('color_custom');
        const colorHex = document.getElementById('color_hex');
        
        function updateColor(value) {
            colorHex.value = value;
            colorCustom.value = value;
        }
        
        colorSelect.addEventListener('change', function() {
            if (this.value) {
                updateColor(this.value);
            }
        });
        
        colorCustom.addEventListener('change', function() {
            updateColor(this.value);
            colorSelect.value = '';
        });
        
        colorHex.addEventListener('input', function() {
            if (this.value.match(/^#[0-9A-Fa-f]{6}$/)) {
                updateColor(this.value);
                colorSelect.value = '';
            }
        });
    </script>
<?php else: ?>
    <div class="table-container">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Icon</th>
                    <th>Name</th>
                    <th>Color</th>
                    <th>Preview</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($labels as $item): ?>
                    <tr>
                        <td><i class="fas <?php echo $item['icon_class']; ?> text-xl" style="color: <?php echo $item['color_hex']; ?>"></i></td>
                        <td>
                            <div class="font-semibold text-gray-900"><?php echo ucfirst($item['name']); ?></div>
                            <div class="text-xs text-gray-500 mt-1"><?php echo $item['icon_class']; ?></div>
                        </td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded border" style="background-color: <?php echo $item['color_hex']; ?>"></div>
                                <span class="text-xs font-mono"><?php echo $item['color_hex']; ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs" 
                                  style="background-color: <?php echo $item['color_hex']; ?>20; color: <?php echo $item['color_hex']; ?>">
                                <i class="fas <?php echo $item['icon_class']; ?>"></i>
                                <?php echo ucfirst($item['name']); ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="?action=edit&id=<?php echo $item['id']; ?>" class="btn-primary" style="padding: 0.25rem 0.5rem; background-color: #4f46e5;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?action=delete&id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure? This label will be removed from all meals.')" class="btn-danger" style="padding: 0.25rem 0.5rem;">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($labels)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-500">
                            <i class="fas fa-tags text-4xl mb-2 block"></i>
                            No dietary labels yet. Click "Add Label" to create one.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
