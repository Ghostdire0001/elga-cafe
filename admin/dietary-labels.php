<?php
require_once '../includes/auth.php';

$message = '';
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
    
    // Check if label is used by any meals
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Dietary Labels - Elga Cafe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar (same structure) -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h2 class="text-xl font-bold">Elga Cafe Admin</h2>
                <p class="text-sm text-gray-400">Welcome, <?php echo $_SESSION['username']; ?></p>
            </div>
            <nav class="mt-8">
                <a href="index.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
                <a href="meals.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-utensils mr-2"></i> Meals</a>
                <a href="categories.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-tags mr-2"></i> Categories</a>
                <a href="dietary-labels.php" class="block py-2 px-4 bg-orange-custom hover:bg-orange-600"><i class="fas fa-leaf mr-2"></i> Dietary Labels</a>
                <a href="discounts.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-tag mr-2"></i> Discounts</a>
                <a href="logout.php" class="block py-2 px-4 hover:bg-gray-700 mt-8"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-8">
                <?php if(isset($_GET['message'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($_GET['message']); ?></div>
                <?php endif; ?>
                <?php if(isset($_GET['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Manage Dietary Labels</h1>
                    <a href="?action=add" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"><i class="fas fa-plus mr-2"></i> Add Label</a>
                </div>
                
                <?php if($action == 'add' || ($action == 'edit' && $label)): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold mb-4"><?php echo $action == 'add' ? 'Add Dietary Label' : 'Edit Dietary Label'; ?></h2>
                        <form method="POST" action="">
                            <?php if($action == 'edit'): ?>
                                <input type="hidden" name="label_id" value="<?php echo $label['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Name *</label>
                                    <input type="text" name="name" required value="<?php echo $label ? ucfirst($label['name']) : ''; ?>" 
                                           placeholder="vegetarian, gluten-free, vegan, etc."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Icon Class (Font Awesome)</label>
                                    <input type="text" name="icon_class" value="<?php echo $label ? $label['icon_class'] : 'fa-leaf'; ?>" 
                                           placeholder="fa-leaf"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                    <p class="text-xs text-gray-500 mt-1">Examples: fa-leaf, fa-wheat-slash, fa-pepper-hot</p>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Color (Hex)</label>
                                    <div class="flex items-center">
                                        <input type="color" name="color_hex" value="<?php echo $label ? $label['color_hex'] : '#4CAF50'; ?>" 
                                               class="w-16 h-10 border border-gray-300 rounded">
                                        <input type="text" name="color_hex_text" value="<?php echo $label ? $label['color_hex'] : '#4CAF50'; ?>" 
                                               class="ml-2 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom"
                                               onchange="this.form.color_hex.value=this.value">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="bg-orange-custom text-white px-6 py-2 rounded hover:bg-orange-600">
                                    <i class="fas fa-save mr-2"></i> <?php echo $action == 'add' ? 'Save Label' : 'Update Label'; ?>
                                </button>
                                <a href="dietary-labels.php" class="ml-2 bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Icon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Color</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($labels as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4"><?php echo $item['id']; ?></td>
                                        <td class="px-6 py-4"><i class="fas <?php echo $item['icon_class']; ?>" style="color: <?php echo $item['color_hex']; ?>"></i></td>
                                        <td class="px-6 py-4 font-medium"><?php echo ucfirst($item['name']); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="w-8 h-8 rounded border" style="background-color: <?php echo $item['color_hex']; ?>"></div>
                                            <span class="text-xs ml-2"><?php echo $item['color_hex']; ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="?action=edit&id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></a>
                                            <a href="?action=delete&id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>