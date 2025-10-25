<?php
// Database setup script for locations table
// Run this from your browser: http://your-domain/setup_locations_db.php

header('Content-Type: application/json');

try {
    // Load CodeIgniter database configuration
    require_once('application/config/database.php');
    
    $db_config = $db['default'];
    $host = $db_config['hostname'];
    $username = $db_config['username'];
    $password = $db_config['password'];
    $database = $db_config['database'];
    
    // Create database connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Database connection successful',
        'database' => $database,
        'host' => $host,
        'instructions' => [
            '1. Copy the SQL below and run it in your database',
            '2. Or use phpMyAdmin/MySQL Workbench to execute the SQL',
            '3. After creating the table, test the API endpoints'
        ],
        'sql_to_run' => "
-- Create locations table
CREATE TABLE IF NOT EXISTS `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Location name (e.g., Main Office, Branch A)',
  `latitude` decimal(10,8) NOT NULL COMMENT 'Latitude coordinate',
  `longitude` decimal(11,8) NOT NULL COMMENT 'Longitude coordinate',
  `address` text DEFAULT NULL COMMENT 'Full address of the location',
  `radius` int(11) DEFAULT 100 COMMENT 'Radius in meters for location checking',
  `branch_id` int(11) DEFAULT NULL COMMENT 'Associated branch ID',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether location is active (1) or inactive (0)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_coordinates` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores location data for attendance and tracking';

-- Insert sample data
INSERT INTO `locations` (`name`, `latitude`, `longitude`, `address`, `radius`, `branch_id`, `is_active`) VALUES
('Main Office', 23.8103, 90.4125, 'Dhaka, Bangladesh', 100, 1, 1),
('Branch Office North', 23.8500, 90.4000, 'North Dhaka, Bangladesh', 150, 1, 1),
('Branch Office South', 23.7500, 90.4000, 'South Dhaka, Bangladesh', 120, 1, 1),
('Training Center', 23.8200, 90.4200, 'Training Center, Dhaka', 200, 1, 1);
        ",
        'next_steps' => [
            '1. Execute the SQL above in your database',
            '2. Test GET /api/location/list to see if it returns locations',
            '3. Test POST /api/location/add to add a new location',
            '4. Test POST /api/location/check to check location proximity'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'instructions' => [
            '1. Check your database configuration in application/config/database.php',
            '2. Ensure your database server is running',
            '3. Verify database credentials are correct'
        ]
    ]);
}
?>
