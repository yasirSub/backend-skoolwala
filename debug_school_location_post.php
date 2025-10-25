<?php
// Debug School Location API POST requests
echo "=== Debugging School Location API POST Requests ===\n\n";

$baseUrl = 'http://192.168.31.129:8080/api';

// Test 1: Check if the endpoint exists
echo "1. Testing endpoint existence\n";
$url = $baseUrl . '/school-location/set';
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['test' => 'data'])
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Failed to connect to endpoint\n";
    $error = error_get_last();
    echo "Error: " . ($error['message'] ?? 'Unknown error') . "\n";
} else {
    echo "✅ Got response from endpoint\n";
    echo "Response: " . substr($response, 0, 200) . "\n";
    
    // Try to decode as JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "✅ Valid JSON response\n";
        print_r($data);
    } else {
        echo "❌ Invalid JSON response\n";
        echo "Raw response: " . $response . "\n";
    }
}

echo "\n";

// Test 2: Check with curl if available
echo "2. Testing with curl (if available)\n";
$curlCommand = "curl -X POST -H \"Content-Type: application/json\" -d '{\"name\":\"Test Location\",\"latitude\":23.8103,\"longitude\":90.4125,\"school_id\":1}' " . $url;
echo "Command: $curlCommand\n";

$curlOutput = shell_exec($curlCommand . " 2>&1");
if ($curlOutput) {
    echo "Curl output: $curlOutput\n";
} else {
    echo "Curl not available or failed\n";
}

echo "\n=== Debug Complete ===\n";
?>
