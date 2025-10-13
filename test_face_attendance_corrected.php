<?php
/**
 * Corrected Test Script for Teacher Face Verification Attendance API
 * 
 * This script demonstrates the proper way to test the teacher face attendance endpoint.
 */

// Configuration - Adjust these values according to your setup
$base_url = 'http://localhost/skoolwala/index.php?'; // Adjust as needed
$login_endpoint = $base_url . 'login'; // Your login endpoint
$attendance_endpoint = $base_url . '/api/teacherFaceAttendance'; // Note the leading slash

// Teacher credentials (adjust as needed)
$username = 'teacher@example.com';
$password = 'teacher_password';

echo "=== Teacher Face Verification Attendance API Test ===\n\n";

// Step 1: Login to get session cookies
echo "1. Logging in...\n";
$login_ch = curl_init();

// Login data
$login_data = array(
    'username' => $username,
    'password' => $password
);

// Login cURL options
curl_setopt($login_ch, CURLOPT_URL, $login_endpoint);
curl_setopt($login_ch, CURLOPT_POST, true);
curl_setopt($login_ch, CURLOPT_POSTFIELDS, http_build_query($login_data));
curl_setopt($login_ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($login_ch, CURLOPT_HEADER, true);
curl_setopt($login_ch, CURLOPT_FOLLOWLOCATION, true);

// Execute login request
$login_response = curl_exec($login_ch);
$http_code = curl_getinfo($login_ch, CURLINFO_HTTP_CODE);

echo "   URL: " . $login_endpoint . "\n";
echo "   HTTP Code: " . $http_code . "\n";

// Extract cookies for session management
preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $login_response, $matches);
$cookies = array();
foreach($matches[1] as $item) {
    parse_str($item, $cookie);
    $cookies = array_merge($cookies, $cookie);
}

// Close login cURL session
curl_close($login_ch);

if ($http_code == 200) {
    echo "   Status: Login Successful\n\n";
    
    // Step 2: Test face attendance with JSON data
    echo "2. Testing Face Attendance with JSON data...\n";
    
    // Prepare attendance data with proper face_data
    $attendance_data = array(
        'face_data' => '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQECAQECAQEBAQIFBQUIBQYFBggICQoKBgkKCQoKCwwMCg4ODQ0MDhEQERERERERERERERERERH/2wBDAQEBAQEBAQIBAQICAgECAgUFBQUIBQYFBggICQoKBgkKCQoKCwwMCg4ODQ0MDhEQERERERERERERERERERH/wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=', // Sample base64 encoded image data
        'confidence' => '95.5',
        'device_id' => 'device_12345',
        'remark' => 'Morning attendance test'
    );
    
    // Convert to JSON
    $json_data = json_encode($attendance_data);
    
    // Initialize cURL session for attendance
    $attendance_ch = curl_init();
    
    // Set cookies for session
    $cookie_string = '';
    foreach($cookies as $key => $value) {
        $cookie_string .= $key . '=' . $value . '; ';
    }
    
    // Attendance cURL options
    curl_setopt($attendance_ch, CURLOPT_URL, $attendance_endpoint);
    curl_setopt($attendance_ch, CURLOPT_POST, true);
    curl_setopt($attendance_ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($attendance_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($attendance_ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json_data)
    ));
    curl_setopt($attendance_ch, CURLOPT_COOKIE, rtrim($cookie_string, '; '));
    
    // Execute attendance request
    $attendance_response = curl_exec($attendance_ch);
    $attendance_http_code = curl_getinfo($attendance_ch, CURLINFO_HTTP_CODE);
    
    echo "   URL: " . $attendance_endpoint . "\n";
    echo "   HTTP Code: " . $attendance_http_code . "\n";
    echo "   Request Data: " . $json_data . "\n";
    echo "   Response: " . $attendance_response . "\n\n";
    
    // Close attendance cURL session
    curl_close($attendance_ch);
    
    // Step 3: Test with form data
    echo "3. Testing Face Attendance with Form Data...\n";
    
    $form_data = array(
        'face_data' => '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQECAQECAQEBAQIFBQUIBQYFBggICQoKBgkKCQoKCwwMCg4ODQ0MDhEQERERERERERERERERERH/2wBDAQEBAQEBAQIBAQICAgECAgUFBQUIBQYFBggICQoKBgkKCQoKCwwMCg4ODQ0MDhEQERERERERERERERERERH/wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=',
        'confidence' => '87.3',
        'device_id' => 'device_67890',
        'remark' => 'Afternoon attendance test'
    );
    
    $form_ch = curl_init();
    curl_setopt($form_ch, CURLOPT_URL, $attendance_endpoint);
    curl_setopt($form_ch, CURLOPT_POST, true);
    curl_setopt($form_ch, CURLOPT_POSTFIELDS, http_build_query($form_data));
    curl_setopt($form_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($form_ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded'
    ));
    curl_setopt($form_ch, CURLOPT_COOKIE, rtrim($cookie_string, '; '));
    
    $form_response = curl_exec($form_ch);
    $form_http_code = curl_getinfo($form_ch, CURLINFO_HTTP_CODE);
    
    echo "   URL: " . $attendance_endpoint . "\n";
    echo "   HTTP Code: " . $form_http_code . "\n";
    echo "   Request Data: " . http_build_query($form_data) . "\n";
    echo "   Response: " . $form_response . "\n\n";
    
    curl_close($form_ch);
    
    // Step 4: Test error case - Missing face_data
    echo "4. Testing with Missing Face Data (should fail)...\n";
    
    $invalid_data = array(
        'confidence' => '92.3',
        'device_id' => 'device_54321'
    );
    
    $invalid_json = json_encode($invalid_data);
    
    $invalid_ch = curl_init();
    curl_setopt($invalid_ch, CURLOPT_URL, $attendance_endpoint);
    curl_setopt($invalid_ch, CURLOPT_POST, true);
    curl_setopt($invalid_ch, CURLOPT_POSTFIELDS, $invalid_json);
    curl_setopt($invalid_ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($invalid_ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($invalid_json)
    ));
    curl_setopt($invalid_ch, CURLOPT_COOKIE, rtrim($cookie_string, '; '));
    
    $invalid_response = curl_exec($invalid_ch);
    $invalid_http_code = curl_getinfo($invalid_ch, CURLINFO_HTTP_CODE);
    
    echo "   URL: " . $attendance_endpoint . "\n";
    echo "   HTTP Code: " . $invalid_http_code . "\n";
    echo "   Request Data: " . $invalid_json . "\n";
    echo "   Response: " . $invalid_response . "\n\n";
    
    curl_close($invalid_ch);
    
} else {
    echo "   Status: Login Failed\n";
    echo "   Response: " . $login_response . "\n\n";
}

echo "=== End of Test ===\n";

// Example cURL commands for manual testing in terminal/Postman
echo "\n=== Manual cURL Commands for Postman/Terminal ===\n";
echo "# 1. Login (adjust credentials):\n";
echo "curl -X POST \"$login_endpoint\" \\\n";
echo "  -H \"Content-Type: application/x-www-form-urlencoded\" \\\n";
echo "  -d \"username=$username&password=$password\" \\\n";
echo "  -c cookies.txt\n\n";

echo "# 2. Mark Face Attendance (after login):\n";
echo "curl -X POST \"$attendance_endpoint\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -b cookies.txt \\\n";
echo "  -d '{\n";
echo "    \"face_data\": \"/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQECAQECAQEBAQIFBQUIBQYFBggICQoKBgkKCQoKCwwMCg4ODQ0MDhEQERERERERERERERERERH/2wBDAQEBAQEBAQIBAQICAgECAgUFBQUIBQYFBggICQoKBgkKCQoKCwwMCg4ODQ0MDhEQERERERERERERERERERH/wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=\",\n";
echo "    \"confidence\": \"95.5\",\n";
echo "    \"device_id\": \"camera_001\",\n";
echo "    \"remark\": \"Morning attendance\"\n";
echo "  }'\n\n";

echo "# 3. Test with Form Data:\n";
echo "curl -X POST \"$attendance_endpoint\" \\\n";
echo "  -H \"Content-Type: application/x-www-form-urlencoded\" \\\n";
echo "  -b cookies.txt \\\n";
echo "  -d \"face_data=/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQECAQECAQEBAQIFBQUIBQYFBggICQoKBgkKCQoKCwwMCg4ODQ0MDhEQERERERERERERERERERH/2wBDAQEBAQEBAQIBAQICAgECAgUFBQUIBQYFBggICQoKBgkKCQoKCwwMCg4ODQ0MDhEQERERERERERERERERERH/wAARCAABAAEDAREAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAb/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k=&confidence=92.7&device_id=camera_003&remark=Afternoon+session\"\n\n";

echo "# 4. Test Error Case - Missing face_data:\n";
echo "curl -X POST \"$attendance_endpoint\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -b cookies.txt \\\n";
echo "  -d '{\n";
echo "    \"confidence\": \"87.2\",\n";
echo "    \"device_id\": \"camera_002\"\n";
echo "  }'\n\n";

echo "Note: You'll need to adjust the URLs and credentials according to your setup.\n";
echo "The cookies.txt file will store session cookies for authentication.\n";
?>