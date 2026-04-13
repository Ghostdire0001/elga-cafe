<?php
// Simple health check - just return OK
// This file should be fast and always succeed

// Optional: Check if critical files exist
$required_files = ['index.php', 'includes/config.php'];
$all_exist = true;

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        $all_exist = false;
        break;
    }
}

if ($all_exist) {
    http_response_code(200);
    echo "OK - Elga Cafe is healthy";
} else {
    http_response_code(500);
    echo "ERROR - Critical files missing";
}
?>
