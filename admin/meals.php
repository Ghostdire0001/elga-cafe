<?php
$page_title = 'Manage Meals';
require_once '../includes/config.php';
require_once 'includes/header.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';
$error = '';

// Cloudinary upload function using unsigned preset (working version from test)
function uploadToCloudinary($file) {
    $cloud_name = CLOUDINARY_CLOUD_NAME;
    $upload_preset = 'elga_cafe_unsigned'; // The preset you created
    
    if (empty($cloud_name)) {
        error_log("Cloudinary cloud name missing");
        return null;
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log("File upload error: " . $file['error']);
        return null;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return false;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Prepare upload data
    $upload_data = [
        'file' => curl_file_create($file['tmp_name'], $mime_type, $file['name']),
        'upload_preset' => $upload_preset,
    ];
    
    $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $upload_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log("CURL Error: " . $curl_error);
        return null;
    }
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['secure_url'])) {
            return $result['secure_url'];
        }
    }
    
    error_log("Cloudinary upload failed. HTTP: $http_code, Response: " . substr($response, 0, 500));
    return null;
}

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
    
    // Handle image upload to Cloudinary
    $image_url = $_POST['existing_image'] ?? null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded_image = uploadToCloudinary($_FILES['image']);
        if ($uploaded_image === false) {
            $error = "Invalid file type. Please upload JPG, PNG, GIF, or WEBP images only. Max size: 5MB";
        } elseif ($uploaded_image) {
            $image_url = $uploaded_image;
        } elseif ($uploaded_image === null) {
            $error = "Upload failed. Please check your Cloudinary configuration and try again.";
        }
    }
    
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
    
    if (empty($error)) {
        header("Location: meals.php?message=" . urlencode($message));
        exit();
    }
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

// Display messages
if(isset($_GET['message'])): ?>
    <div class="alert-success"><?php echo htmlspecialchars($_GET['message']); ?></div>
<?php endif; ?>
<?php if(isset($error)): ?>
    <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
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
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if($action == 'edit'): ?>
                <input type="hidden" name="meal_id" value="<?php echo $meal['id']; ?>">
                <input type="hidden" name="existing_image" value="<?php echo $meal['image_url']; ?>">
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
                    <label class="form-label">Meal Image</label>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="form-input" style="padding: 0.375rem;">
                    <?php if($meal && $meal['image_url']): ?>
                        <div class="mt-2">
                            <img src="<?php echo $meal['image_url']; ?>" alt="Current image" style="max-width: 100px; max-height: 100px; object-fit: cover;" class="rounded border">
                            <p class="text-xs text-gray-500 mt-1">Current image. Upload new to replace.</p>
                        </div>
                    <?php endif; ?>
                    <p class="text-xs text-gray-500 mt-1">Allowed: JPG, PNG, GIF, WEBP. Max size: 5MB</p>
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
                            <input type="checkbox" name="availability" <?php echo ($meal && $meal['availability']) || !$meal ? 'checked' : ''; ?>>
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
                    <th>Image</th>
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
                        <td>
                            <?php if($item['image_url']): ?>
                                <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="w-12 h-12 object-cover rounded">
                            <?php else: ?>
                                <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                    <i class="fas fa-utensils text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                        </td>
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
