<?php
require_once '../includes/config.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Cloudinary Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        form { background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0; }
        input, button { padding: 10px; margin: 5px; }
        button { background: #F97316; color: white; border: none; cursor: pointer; border-radius: 5px; }
        button:hover { background: #EA580C; }
        img { max-width: 300px; border: 1px solid #ccc; padding: 5px; margin-top: 10px; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>☁️ Cloudinary Upload Test</h1>
    <div class='info'>
        <strong>Cloud Name:</strong> <?php echo defined('CLOUDINARY_CLOUD_NAME') ? CLOUDINARY_CLOUD_NAME : 'Not set'; ?><br>
        <strong>cURL Extension:</strong> <?php echo function_exists('curl_version') ? '✅ Installed' : '❌ Not installed'; ?>
    </div>";

// Test upload function using unsigned preset
function uploadToCloudinaryTest($file) {
    $cloud_name = CLOUDINARY_CLOUD_NAME;
    $upload_preset = 'elga_cafe_unsigned'; // You need to create this in Cloudinary
    
    if (empty($cloud_name)) {
        return "ERROR: Cloudinary cloud name missing from config";
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => "File too large (server limit)",
            UPLOAD_ERR_FORM_SIZE => "File too large (form limit)",
            UPLOAD_ERR_PARTIAL => "File only partially uploaded",
            UPLOAD_ERR_NO_FILE => "No file uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "File upload stopped by extension"
        ];
        $error_msg = isset($errors[$file['error']]) ? $errors[$file['error']] : "Unknown error code: " . $file['error'];
        return "ERROR: " . $error_msg;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return "ERROR: Invalid file type. Allowed: JPG, PNG, GIF, WEBP. Got: " . $mime_type;
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return "ERROR: File too large. Max size: 5MB. Your file: " . round($file['size'] / 1024 / 1024, 2) . "MB";
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
        return "ERROR: CURL Error - " . $curl_error;
    }
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['secure_url'])) {
            return $result['secure_url'];
        } else {
            return "ERROR: No secure_url in response";
        }
    }
    
    // Try to parse error message
    $error_data = json_decode($response, true);
    if (isset($error_data['error']['message'])) {
        return "ERROR: " . $error_data['error']['message'];
    }
    
    return "ERROR: HTTP $http_code - " . substr($response, 0, 200);
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    echo "<h2>📤 Upload Result</h2>";
    
    $result = uploadToCloudinaryTest($_FILES['test_image']);
    
    if (strpos($result, 'ERROR') === false) {
        echo "<div class='success'>✅ Upload successful!</div>";
        echo "<p><strong>Image URL:</strong> <a href='$result' target='_blank'>$result</a></p>";
        echo "<img src='$result' alt='Uploaded image'>";
        echo "<p><strong>You can now use this URL in your meal entries!</strong></p>";
    } else {
        echo "<div class='error'>❌ $result</div>";
        
        // Provide helpful tips based on error
        if (strpos($result, 'upload preset') !== false) {
            echo "<div class='info'>
                <strong>🔧 How to fix:</strong><br>
                1. Go to <a href='https://cloudinary.com/console/settings/upload' target='_blank'>Cloudinary Upload Settings</a><br>
                2. Scroll to <strong>Upload Presets</strong><br>
                3. Click <strong>Add Upload Preset</strong><br>
                4. Set <strong>Preset name:</strong> <code>elga_cafe_unsigned</code><br>
                5. Set <strong>Signing mode:</strong> <strong>Unsigned</strong><br>
                6. Set <strong>Folder:</strong> <code>elga-cafe/meals</code><br>
                7. Click <strong>Save</strong><br>
                8. Try uploading again
            </div>";
        }
    }
}
?>

<h2>📸 Test Upload Form</h2>
<form method="POST" action="" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
    <button type="submit">Upload to Cloudinary</button>
</form>

<div class="info">
    <strong>📋 Before testing:</strong>
    <ol>
        <li>Go to <a href="https://cloudinary.com/console/settings/upload" target="_blank">Cloudinary Upload Settings</a></li>
        <li>Scroll to <strong>Upload Presets</strong> section</li>
        <li>Click <strong>Add Upload Preset</strong></li>
        <li>Fill in:
            <ul>
                <li><strong>Preset name:</strong> <code>elga_cafe_unsigned</code></li>
                <li><strong>Signing mode:</strong> Select <strong>Unsigned</strong></li>
                <li><strong>Folder:</strong> <code>elga-cafe/meals</code></li>
            </ul>
        </li>
        <li>Click <strong>Save</strong></li>
        <li>Return here and upload an image</li>
    </ol>
</div>

<div class="info">
    <strong>📝 Current Environment Variables (from Render):</strong>
    <ul>
        <li>CLOUDINARY_CLOUD_NAME: <?php echo getenv('CLOUDINARY_CLOUD_NAME') ? '✅ Set to: ' . getenv('CLOUDINARY_CLOUD_NAME') : '❌ Not set'; ?></li>
        <li>CLOUDINARY_API_KEY: <?php echo getenv('CLOUDINARY_API_KEY') ? '✅ Set' : '❌ Not set'; ?></li>
        <li>CLOUDINARY_API_SECRET: <?php echo getenv('CLOUDINARY_API_SECRET') ? '✅ Set' : '❌ Not set'; ?></li>
    </ul>
    <p><small>Note: For unsigned upload preset, only the Cloud Name is required. API Key/Secret are not used.</small></p>
</div>

</body>
</html>
