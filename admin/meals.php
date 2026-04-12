<?php
require_once '../includes/auth.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Meals - Elga Cafe Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h2 class="text-xl font-bold">Elga Cafe Admin</h2>
                <p class="text-sm text-gray-400">Welcome, <?php echo $_SESSION['username']; ?></p>
            </div>
            <nav class="mt-8">
                <a href="index.php" class="block py-2 px-4 hover:bg-gray-700">
                    <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                </a>
                <a href="meals.php" class="block py-2 px-4 bg-orange-custom hover:bg-orange-600">
                    <i class="fas fa-utensils mr-2"></i> Meals
                </a>
                <a href="categories.php" class="block py-2 px-4 hover:bg-gray-700">
                    <i class="fas fa-tags mr-2"></i> Categories
                </a>
                <a href="dietary-labels.php" class="block py-2 px-4 hover:bg-gray-700">
                    <i class="fas fa-leaf mr-2"></i> Dietary Labels
                </a>
                <a href="discounts.php" class="block py-2 px-4 hover:bg-gray-700">
                    <i class="fas fa-tag mr-2"></i> Discounts
                </a>
                <a href="logout.php" class="block py-2 px-4 hover:bg-gray-700 mt-8">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-8">
                <?php if(isset($_GET['message'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($_GET['message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Manage Meals</h1>
                    <a href="?action=add" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        <i class="fas fa-plus mr-2"></i> Add New Meal
                    </a>
                </div>
                
                <?php if($action == 'add' || ($action == 'edit' && $meal)): ?>
                    <!-- Add/Edit Form -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold mb-4"><?php echo $action == 'add' ? 'Add New Meal' : 'Edit Meal'; ?></h2>
                        <form method="POST" action="">
                            <?php if($action == 'edit'): ?>
                                <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Name *</label>
                                    <input type="text" name="name" required value="<?php echo $meal ? htmlspecialchars($meal['name']) : ''; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Price *</label>
                                    <input type="number" step="0.01" name="price" required value="<?php echo $meal ? $meal['price'] : ''; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Category *</label>
                                    <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                        <?php foreach($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ($meal && $meal['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Preparation Time (minutes)</label>
                                    <input type="number" name="preparation_time" value="<?php echo $meal ? $meal['preparation_time'] : '15'; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Calories</label>
                                    <input type="number" name="calories" value="<?php echo $meal ? $meal['calories'] : ''; ?>" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Image URL</label>
                                    <input type="text" name="image_url" value="<?php echo $meal ? htmlspecialchars($meal['image_url']) : ''; ?>" 
                                           placeholder="/images/meal-name.jpg"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom">
                                </div>
                                
                                <div class="md:col-span-2">
                                    <label class="block text-gray-700 font-bold mb-2">Description</label>
                                    <textarea name="description" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-orange-custom"><?php echo $meal ? htmlspecialchars($meal['description']) : ''; ?></textarea>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Dietary Labels</label>
                                    <div class="space-y-2">
                                        <?php foreach($dietary_labels as $label): ?>
                                            <label class="inline-flex items-center mr-4">
                                                <input type="checkbox" name="dietary_labels[]" value="<?php echo $label['id']; ?>"
                                                       <?php echo ($meal && in_array($label['id'], $meal_dietary ?? [])) ? 'checked' : ''; ?>
                                                       class="mr-2">
                                                <span style="color: <?php echo $label['color_hex']; ?>">
                                                    <i class="fas <?php echo $label['icon_class']; ?>"></i>
                                                    <?php echo ucfirst($label['name']); ?>
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-gray-700 font-bold mb-2">Status</label>
                                    <label class="inline-flex items-center mr-4">
                                        <input type="checkbox" name="availability" <?php echo ($meal && $meal['availability']) ? 'checked' : 'checked'; ?> class="mr-2">
                                        Available
                                    </label>
                                    <label class="inline-flex items-center mr-4">
                                        <input type="checkbox" name="is_featured" <?php echo ($meal && $meal['is_featured']) ? 'checked' : ''; ?> class="mr-2">
                                        Featured
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="checkbox" name="is_popular" <?php echo ($meal && $meal['is_popular']) ? 'checked' : ''; ?> class="mr-2">
                                        Popular
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mt-6">
                                <button type="submit" class="bg-orange-custom text-white px-6 py-2 rounded hover:bg-orange-600">
                                    <i class="fas fa-save mr-2"></i> <?php echo $action == 'add' ? 'Save Meal' : 'Update Meal'; ?>
                                </button>
                                <a href="meals.php" class="ml-2 bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- Meals List -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach($meals as $item): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo $item['id']; ?></td>
                                        <td class="px-6 py-4">
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
                                        <td class="px-6 py-4"><?php echo ucfirst($item['category_name']); ?></td>
                                        <td class="px-6 py-4">$<?php echo number_format($item['price'], 2); ?></td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $item['availability'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $item['availability'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="?action=toggle&id=<?php echo $item['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                <i class="fas fa-toggle-<?php echo $item['availability'] ? 'on' : 'off'; ?>"></i>
                                            </a>
                                            <a href="?action=edit&id=<?php echo $item['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?php echo $item['id']; ?>" onclick="return confirm('Are you sure?')" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </a>
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