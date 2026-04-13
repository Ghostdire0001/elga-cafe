<?php
require_once '../includes/config.php';

echo "<h1>Cloudinary Connection Test - Fixed Version</h1>";

function uploadToCloudinaryFixed($file) {
    $cloud_name = CLOUDINARY_CLOUD_NAME;
    $api_key = CLOUDINARY_API_KEY;
    $api_secret = CLOUDINARY_API_SECRET;
    
    if (empty($cloud_name) || empty($api_key) || empty($api_secret)) {
        return "ERROR: Cloudinary credentials missing";
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "ERROR: File upload error: " . $file['error'];
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return "ERROR: Invalid file type: " . $mime_type;
    }
    
    // Prepare upload parameters
    $timestamp = time();
    $folder = 'elga-cafe/meals';
    $public_id = $folder . '/test_' . $timestamp;
    
    // Parameters to sign (alphabetical order)
    $params_to_sign = [
        'api_key' => $api_key,
        'public_id' => $public_id,
        'timestamp' => $timestamp,
    ];
    
    // Build string to sign
    $signature_string = '';
    foreach ($params_to_sign as $key => $value) {
        $signature_string .= $key . '=' . $value . '&';
    }
    $signature_string = rtrim($signature_string, '&');
    
    echo "String to sign: " . $signature_string . "<br>";
    
    // Generate signature
    $signature = hash_hmac('sha256', $signature_string, $api_secret);
    echo "Generated signature: " . $signature . "<br>";
    
    // Read file and encode as base64
    $image_data = base64_encode(file_get_contents($file['tmp_name']));
    
    // Complete upload data
    $upload_data = [
        'file' => 'data:' . $mime_type . ';base64,' . $image_data,
        'public_id' => $public_id,
        'api_key' => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature,
    ];
    
    // Send to Cloudinary
    $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($upload_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        return $result['secure_url'];
    } else {
        return "ERROR: HTTP $http_code - " . $response;
    }
}

if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
    echo "<h2>Upload Result:</h2>";
    $result = uploadToCloudinaryFixed($_FILES['test_image']);
    if (strpos($result, 'ERROR') === false) {
        echo "<p style='color:green'>✓ Upload successful!</p>";
        echo "<p>Image URL: <a href='$result' target='_blank'>$result</a></p>";
        echo "<img src='$result' style='max-width: 300px; border: 1px solid #ccc; padding: 5px;'><br>";
    } else {
        echo "<p style='color:red'>✗ $result</p>";
    }
}
?>

<h2>Test Upload Form</h2>
<form method="POST" action="" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/jpeg,image/png,image/gif" required>
    <button type="submit">Test Upload</button>
</form>
