<?php
// Simple test to check basic server response
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'Basic server test',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
