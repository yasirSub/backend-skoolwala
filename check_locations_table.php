<?php
// Database check script for locations table
// Run this from your browser: http://your-domain/check_locations_table.php

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
    
    // Check if locations table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'locations'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        // Get table structure
        $stmt = $pdo->query("DESCRIBE locations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM locations");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Locations table exists',
            'table_exists' => true,
            'columns' => $columns,
            'record_count' => $count,
            'database' => $database,
            'next_steps' => [
                '1. Table exists - check if API routes are working',
                '2. Test API endpoint: GET /api/location/list',
                '3. Restart your backend server if needed'
            ]
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Locations table does not exist',
            'table_exists' => false,
            'database' => $database,
            'solution' => [
                '1. Run the SQL from locations_table.sql',
                '2. Or visit setup_locations_db.php for instructions',
                '3. Create the table in your database'
            ],
            'sql_to_run' => "
CREATE TABLE IF NOT EXISTS `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` text DEFAULT NULL,
  `radius` int(11) DEFAULT 100,
  `branch_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `locations` (`name`, `latitude`, `longitude`, `address`, `radius`, `branch_id`, `is_active`) VALUES
('Main Office', 23.8103, 90.4125, 'Dhaka, Bangladesh', 100, 1, 1);
            "
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'table_exists' => false,
        'instructions' => [
            '1. Check your database configuration',
            '2. Ensure database server is running',
            '3. Verify database credentials'
        ]
    ]);
}
?>
