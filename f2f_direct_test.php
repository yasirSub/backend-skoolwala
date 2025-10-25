<?php
/**
 * Simple F2F API Test Script
 * Tests F2F endpoints directly without CodeIgniter framework
 */

echo "🧪 F2F API Direct Test\n";
echo "=====================\n\n";

// Test 1: Check if we can access the controller file directly
echo "1. Testing Direct File Access...\n";
$controller_path = __DIR__ . '/application/controllers/F2FApi.php';
if (file_exists($controller_path)) {
    echo "   ✅ F2FApi.php exists at: $controller_path\n";
    
    // Check file permissions
    if (is_readable($controller_path)) {
        echo "   ✅ File is readable\n";
    } else {
        echo "   ❌ File is NOT readable\n";
    }
} else {
    echo "   ❌ F2FApi.php does NOT exist\n";
}

// Test 2: Check routes file
echo "\n2. Testing Routes File...\n";
$routes_path = __DIR__ . '/application/config/routes.php';
if (file_exists($routes_path)) {
    echo "   ✅ routes.php exists\n";
    
    $routes_content = file_get_contents($routes_path);
    $f2f_routes = [
        'api/f2f/testController' => 'f2fapi/test',
        'api/f2f/create' => 'f2fapi/create',
        'api/f2f/list' => 'f2fapi/list',
        'api/f2f/analyze' => 'f2fapi/analyze',
        'api/f2f/checkFace' => 'f2fapi/checkFace'
    ];
    
    foreach ($f2f_routes as $route => $controller) {
        if (strpos($routes_content, $route) !== false) {
            echo "   ✅ Route '$route' is defined\n";
        } else {
            echo "   ❌ Route '$route' is NOT defined\n";
        }
    }
} else {
    echo "   ❌ routes.php does NOT exist\n";
}

// Test 3: Check controller methods
echo "\n3. Testing Controller Methods...\n";
if (file_exists($controller_path)) {
    $controller_content = file_get_contents($controller_path);
    $methods = ['test', 'create', 'list', 'analyze', 'checkFace', 'attach', 'delete'];
    
    foreach ($methods as $method) {
        if (strpos($controller_content, "public function $method()") !== false) {
            echo "   ✅ Method '$method()' exists\n";
        } else {
            echo "   ❌ Method '$method()' does NOT exist\n";
        }
    }
}

// Test 4: Check for syntax errors
echo "\n4. Checking for PHP Syntax Errors...\n";
$output = shell_exec("php -l \"$controller_path\" 2>&1");
if (strpos($output, 'No syntax errors') !== false) {
    echo "   ✅ No syntax errors in F2FApi.php\n";
} else {
    echo "   ❌ Syntax errors found:\n";
    echo "   $output\n";
}

// Test 5: Check server configuration
echo "\n5. Server Configuration...\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "   Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";

// Test 6: Check if we're in the right directory
echo "\n6. Directory Structure...\n";
$required_dirs = [
    'application',
    'application/controllers',
    'application/config',
    'application/models',
    'system'
];

foreach ($required_dirs as $dir) {
    if (is_dir(__DIR__ . '/' . $dir)) {
        echo "   ✅ Directory '$dir' exists\n";
    } else {
        echo "   ❌ Directory '$dir' does NOT exist\n";
    }
}

echo "\n=====================\n";
echo "✅ Direct test complete!\n";
echo "\nIf all checks pass, the issue might be:\n";
echo "1. Server configuration (mod_rewrite, PHP settings)\n";
echo "2. File permissions on the live server\n";
echo "3. Different file versions between local and live\n";
echo "4. Server-level restrictions or security rules\n";
?>
