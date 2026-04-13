<?php
require_once '../includes/config.php';

echo "<h1>Cloudinary Connection Test</h1>";

// Check if constants are defined
echo "<h2>1. Checking Configuration:</h2>";
if (defined('CLOUDINARY_CLOUD_NAME')) {
    echo "✓ CLOUDINARY_CLOUD_NAME is set to: " . CLOUDINARY_CLOUD_NAME . "<br>";
} else {
    echo "✗ CLOUDINARY_CLOUD_NAME is NOT defined<br>";
}

if (defined('CLOUDINARY_API_KEY')) {
    echo "✓ CLOUDINARY_API_KEY is set<br>";
} else {
    echo "✗ CLOUDINARY_API_KEY is NOT defined<br>";
}

if (defined('CLOUDINARY_API_SECRET')) {
    echo "✓ CLOUDINARY_API_SECRET is set<br>";
} else {
    echo "✗ CLOUDINARY_API_SECRET is NOT defined<br>";
}

// Check if cURL is installed
echo "<h2>2. Checking PHP Extensions:</h2>";
if (function_exists('curl_version')) {
    echo "✓ cURL is installed<br>";
} else {
    echo "✗ cURL is NOT installed - This is required for Cloudinary uploads<br>";
}

// Test a simple CURL to Cloudinary API
echo "<h2>3. Testing Cloudinary API Connection:</h2>";
$cloud_name = CLOUDINARY_CLOUD_NAME;
$url = "https://api.cloudinary.com/v1_1/$cloud_name/upload";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // Just check connection, don't download
curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200 || $http_code === 400) {
    echo "✓ Cloudinary API is reachable (HTTP $http_code)<br>";
} else {
    echo "✗ Cannot reach Cloudinary API (HTTP $http_code)<br>";
}

// Show environment variables (without exposing full secret)
echo "<h2>4. Environment Variables (from Render):</h2>";
echo "DB_HOST: " . (getenv('DB_HOST') ? '✓ Set' : '✗ Not set') . "<br>";
echo "CLOUDINARY_CLOUD_NAME: " . (getenv('CLOUDINARY_CLOUD_NAME') ? '✓ Set' : '✗ Not set') . "<br>";
echo "CLOUDINARY_API_KEY: " . (getenv('CLOUDINARY_API_KEY') ? '✓ Set' : '✗ Not set') . "<br>";
echo "CLOUDINARY_API_SECRET: " . (getenv('CLOUDINARY_API_SECRET') ? '✓ Set' : '✗ Not set') . "<br>";

// If all looks good, try a real upload test
if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
    echo "<h2>5. Testing Actual Upload:</h2>";
    
    $result = uploadToCloudinaryDebug($_FILES['test_image']);
    if ($result) {
        echo "✓ Upload successful!<br>";
        echo "Image URL: <a href='$result' target='_blank'>$result</a><br>";
        echo "<img src='$result' style='max-width: 200px;'><br>";
    } else {
        echo "✗ Upload failed<br>";
    }
}
?>

<!-- Test upload form -->
<h2>Test Upload Form</h2>
<form method="POST" action="" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/jpeg,image/png,image/gif" required>
    <button type="submit">Test Upload</button>
</form>

<?php
function uploadToCloudinaryDebug($file) {
    $cloud_name = CLOUDINARY_CLOUD_NAME;
    $api_key = CLOUDINARY_API_KEY;
    $api_secret = CLOUDINARY_API_SECRET;
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "File upload error: " . $file['error'] . "<br>";
        return null;
    }
    
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . round($file['size'] / 1024, 2) . " KB<br>";
    echo "File type: " . $file['type'] . "<br>";
    
    $curl = curl_init();
    $timestamp = time();
    $public_id = 'test_upload_' . $timestamp;
    
    $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
    
    $post_fields = [
        'file' => curl_file_create($file['tmp_name'], $file['type'], $file['name']),
        'public_id' => $public_id,
        'api_key' => $api_key,
        'timestamp' => $timestamp,
    ];
    
    // Generate signature
    ksort($post_fields);
    $signature_string = '';
    foreach ($post_fields as $key => $value) {
        if ($key !== 'file') {
            $signature_string .= $key . '=' . $value . '&';
        }
    }
    $signature_string = rtrim($signature_string, '&');
    $signature = hash_hmac('sha256', $signature_string, $api_secret);
    $post_fields['signature'] = $signature;
    
    echo "Cloud name: $cloud_name<br>";
    echo "API Key: " . substr($api_key, 0, 5) . "...<br>";
    echo "Timestamp: $timestamp<br>";
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true,
    ]);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($curl);
    curl_close($curl);
    
    if ($curl_error) {
        echo "CURL Error: $curl_error<br>";
        return null;
    }
    
    echo "HTTP Response Code: $http_code<br>";
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        return $result['secure_url'];
    } else {
        echo "Response: $response<br>";
        return null;
    }
}
?>
