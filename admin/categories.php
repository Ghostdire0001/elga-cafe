<?php
require_once '../includes/auth.php';

$message = '';
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
    
    // Check if category has meals
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Elga Cafe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar (same as meals.php) -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h2 class="text-xl font-bold">Elga Cafe Admin</h2>
                <p class="text-sm text-gray-400">Welcome, <?php echo $_SESSION['username']; ?></p>
            </div>
            <nav class="mt-8">
                <a href="index.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-tachometer-alt mr-2"></i> Dashboard</a>
                <a href="meals.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-utensils mr-2"></i> Meals</a>
                <a href="categories.php" class="block py-2 px-4 bg-orange-custom hover:bg-orange-600"><i class="fas fa-tags mr-2"></i> Categories</a>
                <a href="dietary-labels.php" class="block py-2 px-4 hover:bg-gray-700"><i class="fas fa-leaf mr-2"></i> Dietary Labels</a>
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
                    <h1 class="text-3xl font-bold text-gray-800">Manage Categories</h1>
                    <a href="?action=add" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"><i class="fas fa-plus mr-2"></i> Add Category</a>
                </div>
                
                <?php if($action == 'add' || ($action == 'edit' && $category)): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold mb-4"><?php echo $action == 'add' ? 'Add Category' : 'Edit Category'; ?></h2>
                        <form method="POST" action="">
                            <?php if($action == 'edit'): ?>
                                <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Name *</label>
                                    <input type="text" name="name" required value="<?php echo $category ? ucfirst($category['name']) : ''; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Icon Class (Font Awesome)</label>
                                    <input type="text" name="icon_class" value="<?php echo $category ? $category['icon_class'] : 'fa-utensils'; ?>" 
                                           placeholder="fa-utensils"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                    <p class="text-xs text-gray-500 mt-1">Example: fa-utensils, fa-hamburger, fa-coffee</p>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Display Order</label>
                                    <input type="number" name="display_order" value="<?php echo $category ? $category['display_order'] : '0'; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 font-bold mb-2">Description</label>
                                    <textarea name="description" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom"><?php echo $category ? htmlspecialchars($category['description']) : ''; ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="is_active" <?php echo ($category && $category['is_active']) || !$category ? 'checked' : ''; ?> class="mr-2">
                                        Active
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="bg-orange-custom text-white px-6 py-2 rounded hover:bg-orange-600">
                                    <i class="fas fa-save mr-2"></i> <?php echo $action == 'add' ? 'Save Category' : 'Update Category'; ?>
                                </button>
                                <a href="categories.php" class="ml-2 bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">Cancel</a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Icon</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($categories as $cat): ?>
                                    <tr>
                                        <td class="px-6 py-4"><?php echo $cat['display_order']; ?></td>
                                        <td class="px-6 py-4"><i class="fas <?php echo $cat['icon_class']; ?> text-orange-custom"></i></td>
                                        <td class="px-6 py-4 font-medium"><?php echo ucfirst($cat['name']); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $cat['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="?action=edit&id=<?php echo $cat['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-edit"></i></a>
                                            <a href="?action=delete&id=<?php echo $cat['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900"><i class="fas fa-trash"></i></a>
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