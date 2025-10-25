<?php
// Standalone database check script
// Run: php standalone_check.php

echo "=== Location API Database Check ===\n\n";

// Database configuration (update these values)
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'skoolwala'; // Update this to your actual database name

try {
    echo "Connecting to database: $database\n";
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n\n";
    
    // Check if locations table exists
    echo "Checking for 'locations' table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'locations'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ Locations table exists\n";
        
        // Get table structure
        $stmt = $pdo->query("DESCRIBE locations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Table structure:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']}: {$column['Type']}\n";
        }
        
        // Count records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM locations");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\n📊 Records in table: $count\n";
        
        if ($count > 0) {
            // Show sample data
            $stmt = $pdo->query("SELECT * FROM locations LIMIT 3");
            $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "\n📝 Sample data:\n";
            foreach ($samples as $sample) {
                echo "  - {$sample['name']}: ({$sample['latitude']}, {$sample['longitude']})\n";
            }
        }
        
        echo "\n🎯 Next steps:\n";
        echo "1. Test API endpoint: http://192.168.31.129:8080/api/location/list\n";
        echo "2. If API still returns HTML, restart your web server\n";
        echo "3. Check CodeIgniter routes are loaded\n";
        
    } else {
        echo "❌ Locations table does NOT exist\n\n";
        echo "🔧 Solution: Create the table with this SQL:\n\n";
        echo "CREATE TABLE IF NOT EXISTS `locations` (\n";
        echo "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
        echo "  `name` varchar(255) NOT NULL,\n";
        echo "  `latitude` decimal(10,8) NOT NULL,\n";
        echo "  `longitude` decimal(11,8) NOT NULL,\n";
        echo "  `address` text DEFAULT NULL,\n";
        echo "  `radius` int(11) DEFAULT 100,\n";
        echo "  `branch_id` int(11) DEFAULT NULL,\n";
        echo "  `is_active` tinyint(1) DEFAULT 1,\n";
        echo "  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
        echo "  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        echo "  PRIMARY KEY (`id`)\n";
        echo ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
        
        echo "INSERT INTO `locations` (`name`, `latitude`, `longitude`, `address`, `radius`, `branch_id`, `is_active`) VALUES\n";
        echo "('Main Office', 23.8103, 90.4125, 'Dhaka, Bangladesh', 100, 1, 1);\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n\n";
    echo "🔧 Troubleshooting:\n";
    echo "1. Check if MySQL server is running\n";
    echo "2. Verify database credentials\n";
    echo "3. Update database name in this script\n";
    echo "4. Check if database exists\n";
}

echo "\n=== Check Complete ===\n";
?>
