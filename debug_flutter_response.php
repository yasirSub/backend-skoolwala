<?php
// Debug Flutter App Response Handling
echo "=== Debugging Flutter App Response Handling ===\n\n";

$baseUrl = 'http://192.168.31.129:8080/api';

// Test different response scenarios that Flutter might encounter
echo "1. Testing successful save response\n";
$url = $baseUrl . '/school-location/set';
$postData = json_encode([
    'name' => 'Debug Test Location',
    'latitude' => 23.8300,
    'longitude' => 90.4200,
    'address' => 'Debug Test Address',
    'radius' => 200,
    'school_id' => 1,
    'is_active' => 1
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Request failed\n";
} else {
    echo "✅ Response received\n";
    echo "Status Code: " . $http_response_header[0] . "\n";
    echo "Response Body: " . $response . "\n";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "✅ JSON parsed successfully\n";
        echo "Status: " . ($data['status'] ?? 'missing') . "\n";
        echo "Message: " . ($data['message'] ?? 'missing') . "\n";
        
        // Check what Flutter app expects
        if ($data['status'] === 'success') {
            echo "✅ Flutter should show success message: '{$data['message']}'\n";
            echo "✅ Flutter should reload locations list\n";
            echo "✅ Flutter should show green snackbar\n";
        } else {
            echo "❌ Flutter would show error: '{$data['message']}'\n";
        }
    } else {
        echo "❌ JSON parsing failed\n";
    }
}

echo "\n";

// Test 2: Test with invalid data (what Flutter validation should catch)
echo "2. Testing with invalid data (should be caught by Flutter validation)\n";
$url = $baseUrl . '/school-location/set';
$postData = json_encode([
    'name' => '', // Empty name - should be caught by Flutter
    'latitude' => 'invalid', // Invalid latitude - should be caught by Flutter
    'longitude' => 90.4200,
    'address' => 'Test Address',
    'radius' => 200,
    'school_id' => 1,
    'is_active' => 1
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $postData
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Request failed\n";
} else {
    echo "✅ Response received\n";
    echo "Response: " . $response . "\n";
    
    $data = json_decode($response, true);
    if ($data) {
        echo "Status: " . ($data['status'] ?? 'missing') . "\n";
        echo "Message: " . ($data['message'] ?? 'missing') . "\n";
    }
}

echo "\n";

// Test 3: Check current locations count
echo "3. Checking current locations count\n";
$url = $baseUrl . '/school-location/list?school_id=1';
$response = @file_get_contents($url);

if ($response === false) {
    echo "❌ Failed to get locations\n";
} else {
    $data = json_decode($response, true);
    if ($data && $data['status'] === 'success') {
        echo "✅ Total locations: " . $data['count'] . "\n";
        echo "✅ Flutter should display: 'School Locations ({$data['count']})'\n";
    }
}

echo "\n=== Debug Complete ===\n";
echo "🔍 If Flutter app is not showing saved locations, check:\n";
echo "1. Is _loadSchoolLocations() being called after save?\n";
echo "2. Is the UI being updated with setState()?\n";
echo "3. Are there any error dialogs being shown?\n";
echo "4. Check Flutter console for error messages\n";
?>
