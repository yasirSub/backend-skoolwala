<?php
// Simple test file to check if the API method works
require_once 'application/config/config.php';
require_once 'application/config/database.php';
require_once 'application/core/CodeIgniter.php';

// Test the API method directly
$CI =& get_instance();
$CI->load->database();

// Set the input parameters
$_GET['staff_id'] = '10';
$_GET['filter_type'] = 'month';
$_GET['filter_value'] = '2025-10';

// Include the API controller
require_once 'application/controllers/Api.php';
$api = new Api();

// Call the method
$api->getTeacherSelfAttendanceStats();
?>
