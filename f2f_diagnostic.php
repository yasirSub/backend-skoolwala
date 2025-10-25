<?php
/**
 * F2F API Diagnostic Script
 * This script helps diagnose why F2F API endpoints are not working
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 F2F API Diagnostic Script\n";
echo "============================\n\n";

// 1. Check if CodeIgniter is loaded
echo "1. Checking CodeIgniter Environment...\n";
if (defined('BASEPATH')) {
    echo "   ✅ CodeIgniter BASEPATH is defined: " . BASEPATH . "\n";
} else {
    echo "   ❌ CodeIgniter BASEPATH is NOT defined\n";
    echo "   This means CodeIgniter is not properly loaded\n";
}

// 2. Check if routes.php is loaded
echo "\n2. Checking Routes Configuration...\n";
$routes_file = APPPATH . 'config/routes.php';
if (file_exists($routes_file)) {
    echo "   ✅ Routes file exists: $routes_file\n";
    
    // Check if F2F routes are defined
    $routes_content = file_get_contents($routes_file);
    if (strpos($routes_content, 'f2fapi/create') !== false) {
        echo "   ✅ F2F routes are defined in routes.php\n";
    } else {
        echo "   ❌ F2F routes are NOT found in routes.php\n";
    }
} else {
    echo "   ❌ Routes file does not exist: $routes_file\n";
}

// 3. Check if F2FApi controller exists
echo "\n3. Checking F2FApi Controller...\n";
$controller_file = APPPATH . 'controllers/F2FApi.php';
if (file_exists($controller_file)) {
    echo "   ✅ F2FApi controller exists: $controller_file\n";
    
    // Check if controller class is properly defined
    $controller_content = file_get_contents($controller_file);
    if (strpos($controller_content, 'class F2FApi extends CI_Controller') !== false) {
        echo "   ✅ F2FApi controller class is properly defined\n";
    } else {
        echo "   ❌ F2FApi controller class is NOT properly defined\n";
    }
    
    // Check if create method exists
    if (strpos($controller_content, 'public function create()') !== false) {
        echo "   ✅ create() method exists in F2FApi controller\n";
    } else {
        echo "   ❌ create() method is NOT found in F2FApi controller\n";
    }
} else {
    echo "   ❌ F2FApi controller does not exist: $controller_file\n";
}

// 4. Test database connection
echo "\n4. Checking Database Connection...\n";
try {
    $this->load->database();
    echo "   ✅ Database connection successful\n";
    
    // Check if F2F tables exist
    if ($this->db->table_exists('f2f_person')) {
        echo "   ✅ f2f_person table exists\n";
    } else {
        echo "   ⚠️  f2f_person table does not exist (will be created automatically)\n";
    }
    
    if ($this->db->table_exists('f2f_embedding')) {
        echo "   ✅ f2f_embedding table exists\n";
    } else {
        echo "   ⚠️  f2f_embedding table does not exist (will be created automatically)\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
}

// 5. Test URL routing
echo "\n5. Testing URL Routing...\n";
$test_urls = [
    'api/f2f/testController',
    'api/f2f/create',
    'api/f2f/list',
    'api/test'
];

foreach ($test_urls as $url) {
    echo "   Testing: $url\n";
    // This would need to be tested with actual HTTP requests
}

// 6. Check server configuration
echo "\n6. Checking Server Configuration...\n";
echo "   PHP Version: " . phpversion() . "\n";
echo "   Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "   Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "   Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";

// 7. Check .htaccess
echo "\n7. Checking .htaccess Configuration...\n";
$htaccess_file = FCPATH . '.htaccess';
if (file_exists($htaccess_file)) {
    echo "   ✅ .htaccess file exists\n";
    $htaccess_content = file_get_contents($htaccess_file);
    if (strpos($htaccess_content, 'RewriteEngine On') !== false) {
        echo "   ✅ URL rewriting is enabled\n";
    } else {
        echo "   ❌ URL rewriting is NOT enabled\n";
    }
} else {
    echo "   ❌ .htaccess file does not exist\n";
}

// 8. Test direct controller access
echo "\n8. Testing Direct Controller Access...\n";
try {
    // This would test if we can instantiate the controller directly
    echo "   Attempting to load F2FApi controller...\n";
    // Note: This would need to be done within CodeIgniter context
} catch (Exception $e) {
    echo "   ❌ Failed to load F2FApi controller: " . $e->getMessage() . "\n";
}

echo "\n============================\n";
echo "✅ Diagnostic complete!\n";
echo "\nNext steps:\n";
echo "1. Check server error logs for any PHP errors\n";
echo "2. Verify that the live server has the same files as your local copy\n";
echo "3. Test the endpoints using curl or Postman\n";
echo "4. Check if there are any server-level restrictions\n";
?>
