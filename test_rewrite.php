<?php
// Upload this as test_rewrite.php to your live server root
echo "Testing URL Rewriting...\n";

// Test 1: Check if .htaccess is working
if (file_exists('.htaccess')) {
    echo "✅ .htaccess exists\n";
} else {
    echo "❌ .htaccess missing\n";
}

// Test 2: Check if mod_rewrite is enabled
if (function_exists('apache_get_modules')) {
    if (in_array('mod_rewrite', apache_get_modules())) {
        echo "✅ mod_rewrite is enabled\n";
    } else {
        echo "❌ mod_rewrite is NOT enabled\n";
    }
} else {
    echo "⚠️ Cannot check mod_rewrite status\n";
}

// Test 3: Check current URL
echo "Current URL: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Query String: " . ($_SERVER['QUERY_STRING'] ?? 'None') . "\n";

// Test 4: Check if we can access the API directory
$api_dir = 'api';
if (is_dir($api_dir)) {
    echo "✅ API directory exists\n";
} else {
    echo "❌ API directory does NOT exist\n";
}

// Test 5: Check file permissions
$controller_file = 'application/controllers/F2FApi.php';
if (file_exists($controller_file)) {
    echo "✅ F2FApi.php exists on live server\n";
    if (is_readable($controller_file)) {
        echo "✅ F2FApi.php is readable\n";
    } else {
        echo "❌ F2FApi.php is NOT readable\n";
    }
} else {
    echo "❌ F2FApi.php does NOT exist on live server\n";
}
?>
