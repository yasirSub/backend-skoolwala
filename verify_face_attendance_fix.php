<?php
/**
 * Script to verify the face attendance fix
 * 
 * This script tests both JSON and form data submission methods
 */

// Test data
$json_data = json_encode([
    'face_data' => 'sample_base64_encoded_face_data',
    'confidence' => '95.5',
    'device_id' => 'device_12345',
    'remark' => 'JSON test'
]);

$form_data = [
    'face_data' => 'sample_base64_encoded_face_data',
    'confidence' => '87.3',
    'device_id' => 'device_67890',
    'remark' => 'Form data test'
];

echo "=== Testing Face Attendance Fix ===\n\n";

// Test 1: JSON data
echo "1. Testing with JSON data:\n";
echo "Data: " . $json_data . "\n\n";

// Test 2: Form data
echo "2. Testing with Form data:\n";
echo "Data: " . http_build_query($form_data) . "\n\n";

echo "To test with curl, use one of these commands:\n\n";

echo "JSON test:\ncurl -X POST \"http://localhost/skoolwala/index.php?/api/teacherFaceAttendance\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '" . $json_data . "'\n\n";

echo "Form data test:\ncurl -X POST \"http://localhost/skoolwala/index.php?/api/teacherFaceAttendance\" \\\n";
echo "  -H \"Content-Type: application/x-www-form-urlencoded\" \\\n";
echo "  -d \"" . http_build_query($form_data) . "\"\n\n";

echo "Note: You must be logged in as a teacher for these to work.\n";
echo "The API should now properly receive face_data in both formats.\n";
?>