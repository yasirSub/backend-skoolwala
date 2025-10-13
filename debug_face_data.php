<?php
// Debug script to check how face_data is received

// Get raw input
$raw_input = file_get_contents('php://input');
echo "Raw Input:\n";
echo $raw_input . "\n\n";

// Try to parse as JSON
$json_data = json_decode($raw_input, true);
echo "Parsed JSON:\n";
print_r($json_data);
echo "\n\n";

// Check POST data
echo "POST Data:\n";
print_r($_POST);
echo "\n\n";

// Check if face_data exists in either
$face_data = '';

if (!empty($json_data) && isset($json_data['face_data'])) {
    $face_data = $json_data['face_data'];
    echo "Face data found in JSON: " . (empty($face_data) ? 'EMPTY' : 'SET') . "\n";
} elseif (isset($_POST['face_data'])) {
    $face_data = $_POST['face_data'];
    echo "Face data found in POST: " . (empty($face_data) ? 'EMPTY' : 'SET') . "\n";
} else {
    echo "Face data NOT FOUND in either JSON or POST\n";
}

echo "Face data value: " . ($face_data ? $face_data : 'NULL') . "\n";
?>