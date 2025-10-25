<?php
// Create school_locations table script
// Run: php create_school_locations_table.php

echo "=== Creating School Locations Table ===\n\n";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'skoolwala';

try {
    echo "Connecting to database: $database\n";
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n\n";
    
    // Create table
    echo "Creating 'school_locations' table...\n";
    $sql = "CREATE TABLE IF NOT EXISTS `school_locations` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL COMMENT 'Location name (e.g., Main Campus, Branch Office)',
      `latitude` decimal(10,8) NOT NULL COMMENT 'Latitude coordinate',
      `longitude` decimal(11,8) NOT NULL COMMENT 'Longitude coordinate',
      `address` text DEFAULT NULL COMMENT 'Full address of the location',
      `radius` int(11) DEFAULT 100 COMMENT 'Radius in meters for attendance checking',
      `school_id` int(11) NOT NULL DEFAULT 1 COMMENT 'Associated school ID',
      `is_active` tinyint(1) DEFAULT 1 COMMENT 'Whether location is active (1) or inactive (0)',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_school_id` (`school_id`),
      KEY `idx_is_active` (`is_active`),
      KEY `idx_coordinates` (`latitude`, `longitude`),
      UNIQUE KEY `unique_school_location` (`school_id`, `name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores school-level locations for attendance tracking'";
    
    $pdo->exec($sql);
    echo "✅ Table created successfully\n\n";
    
    // Insert sample data
    echo "Inserting sample school locations...\n";
    $insertSql = "INSERT IGNORE INTO `school_locations` (`name`, `latitude`, `longitude`, `address`, `radius`, `school_id`, `is_active`) VALUES
    ('Main Campus', 23.8103, 90.4125, 'Main Campus, Dhaka, Bangladesh', 100, 1, 1),
    ('North Branch', 23.8500, 90.4000, 'North Branch Office, Dhaka', 150, 1, 1),
    ('South Branch', 23.7500, 90.4000, 'South Branch Office, Dhaka', 120, 1, 1),
    ('Training Center', 23.8200, 90.4200, 'Training Center, Dhaka', 200, 1, 1)";
    
    $pdo->exec($insertSql);
    echo "✅ Sample data inserted successfully\n\n";
    
    // Verify
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM school_locations");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Total school locations: $count\n\n";
    
    // Show sample data
    $stmt = $pdo->query("SELECT * FROM school_locations LIMIT 3");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "📝 Sample school locations:\n";
    foreach ($samples as $sample) {
        echo "  - {$sample['name']}: ({$sample['latitude']}, {$sample['longitude']}) - {$sample['radius']}m radius\n";
    }
    
    echo "\n🎉 SUCCESS! School Location API ready!\n\n";
    echo "🧪 Test the API:\n";
    echo "1. Set Location: POST /api/school-location/set\n";
    echo "2. List Locations: GET /api/school-location/list\n";
    echo "3. Check Location: POST /api/school-location/check\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Setup Complete ===\n";
?>
