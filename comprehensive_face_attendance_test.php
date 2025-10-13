<?php
/**
 * Comprehensive test for Teacher Face Verification Attendance API
 * 
 * This script tests the teacherFaceAttendance endpoint with proper session handling
 * and different data formats to identify where the face_data issue occurs.
 */

// Configuration
$base_url = 'http://localhost/skoolwala/'; // Adjust as needed
$login_endpoint = $base_url . 'index.php?login';
$attendance_endpoint = $base_url . 'index.php?/api/teacherFaceAttendance';

echo "=== Comprehensive Teacher Face Attendance Test ===\n\n";

// Step 1: Test the debug endpoint first to see how data is received
echo "1. Testing debug endpoint to see how data is received...\n";

// Test with JSON data
$json_data = json_encode([
    'face_data' => 'sample_base64_encoded_face_data',
    'confidence' => '95.5',
    'device_id' => 'device_12345',
    'remark' => 'JSON test'
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . 'debug_face_data.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   JSON Test - HTTP Code: " . $http_code . "\n";
echo "   JSON Test - Response:\n" . $response . "\n\n";

// Test with form data
$form_data = [
    'face_data' => 'sample_base64_encoded_face_data',
    'confidence' => '87.3',
    'device_id' => 'device_67890',
    'remark' => 'Form data test'
];

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $base_url . 'debug_face_data.php');
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($form_data));
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

$response2 = curl_exec($ch2);
$http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "   Form Data Test - HTTP Code: " . $http_code2 . "\n";
echo "   Form Data Test - Response:\n" . $response2 . "\n\n";

// Step 2: Test actual API endpoint (will fail without authentication, but we can see the error)
echo "2. Testing actual API endpoint without authentication...\n";

$ch3 = curl_init();
curl_setopt($ch3, CURLOPT_URL, $attendance_endpoint);
curl_setopt($ch3, CURLOPT_POST, true);
curl_setopt($ch3, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response3 = curl_exec($ch3);
$http_code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "   API Test (No Auth) - HTTP Code: " . $http_code3 . "\n";
echo "   API Test (No Auth) - Response:\n" . $response3 . "\n\n";

// Step 3: Complete test with login and authentication
echo "3. Complete test with login and authentication (requires valid teacher credentials)...\n";

// Note: This requires valid teacher credentials in your database
echo "   To run this test, you need to provide valid teacher credentials.\n";
echo "   Modify this script with actual username/password from your database.\n\n";

/*
// Uncomment and modify with actual credentials to run this test:

// Login
$login_data = [
    'username' => 'actual_teacher_username',
    'password' => 'actual_teacher_password'
];

$login_ch = curl_init();
curl_setopt($login_ch, CURLOPT_URL, $login_endpoint);
curl_setopt($login_ch, CURLOPT_POST, true);
curl_setopt($login_ch, CURLOPT_POSTFIELDS, http_build_query($login_data));
curl_setopt($login_ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($login_ch, CURLOPT_HEADER, true);
curl_setopt($login_ch, CURLOPT_FOLLOWLOCATION, true);

$login_response = curl_exec($login_ch);
$http_code = curl_getinfo($login_ch, CURLINFO_HTTP_CODE);

// Extract cookies
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $login_response, $matches);
$cookies = array();
foreach($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
}

curl_close($login_ch);

if ($http_code == 200) {
    echo "   Login successful, now testing face attendance...\n";
    
    // Set cookies for session
    $cookie_string = '';
    foreach($cookies as $key => $value) {
        $cookie_string .= $key . '=' . $value . '; ';
    }
    
    // Test face attendance
    $attendance_ch = curl_init();
    curl_setopt($attendance_ch, CURLOPT_URL, $attendance_endpoint);
    curl_setopt($attendance_ch, CURLOPT_POST, true);
    curl_setopt($attendance_ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($attendance_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($attendance_ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($attendance_ch, CURLOPT_COOKIE, rtrim($cookie_string, '; '));
    
    $attendance_response = curl_exec($attendance_ch);
    $attendance_http_code = curl_getinfo($attendance_ch, CURLINFO_HTTP_CODE);
    curl_close($attendance_ch);
    
    echo "   Face Attendance - HTTP Code: " . $attendance_http_code . "\n";
    echo "   Face Attendance - Response:\n" . $attendance_response . "\n\n";
} else {
    echo "   Login failed with HTTP code: " . $http_code . "\n";
    echo "   Login response:\n" . $login_response . "\n\n";
}
*/

echo "=== Test Complete ===\n\n";

// Instructions for manual testing
echo "=== Manual Testing Instructions ===\n";
echo "1. First, login to the system as a teacher\n";
echo "2. Use browser developer tools to check your session cookies\n";
echo "3. Use Postman or curl to test the endpoint with your session cookies\n";
echo "4. Make sure to send face_data in either JSON or form data format\n\n";

echo "Example curl command:\n";
echo "curl -X POST \"" . $attendance_endpoint . "\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"Cookie: your_session_cookie_here\" \\\n";
echo "  -d '{\n";
echo "    \"face_data\": \"sample_base64_data\",\n";
echo "    \"confidence\": \"95.5\",\n";
echo "    \"device_id\": \"camera_001\",\n";
echo "    \"remark\": \"Test attendance\"\n";
echo "  }'\n\n";
?>