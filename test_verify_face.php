<?php
// Test script for verifyFace function
// This script tests the verifyFace API endpoint

echo "Testing verifyFace API endpoint\n";
echo "=============================\n\n";

// Test Case 1: Missing staff_id and face_data
echo "Test 1: Missing parameters\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/skoolwala/api/verifyFace");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, "");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "\n";
echo "Response: " . $response . "\n\n";

// Test Case 2: Valid parameters via POST
echo "Test 2: Valid parameters via POST\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/skoolwala/api/verifyFace");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'staff_id' => 1,
    'face_data' => json_encode([0.1, 0.2, 0.3, 0.4, 0.5])
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "\n";
echo "Response: " . $response . "\n\n";

// Test Case 3: Valid parameters via JSON
echo "Test 3: Valid parameters via JSON\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/skoolwala/api/verifyFace");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'staff_id' => 1,
    'face_data' => json_encode([0.1, 0.2, 0.3, 0.4, 0.5])
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $http_code . "\n";
echo "Response: " . $response . "\n\n";

echo "Test completed.\n";
?>