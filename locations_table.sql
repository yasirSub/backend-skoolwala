-- Locations table for storing location data
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

-- Sample data for testing
INSERT INTO `locations` (`name`, `latitude`, `longitude`, `address`, `radius`, `branch_id`, `is_active`) VALUES
('Main Office', 23.8103, 90.4125, 'Dhaka, Bangladesh', 100, 1, 1),
('Branch Office North', 23.8500, 90.4000, 'North Dhaka, Bangladesh', 150, 1, 1),
('Branch Office South', 23.7500, 90.4000, 'South Dhaka, Bangladesh', 120, 1, 1),
('Training Center', 23.8200, 90.4200, 'Training Center, Dhaka', 200, 1, 1);

-- Add foreign key constraint if branches table exists
-- ALTER TABLE `locations` ADD CONSTRAINT `fk_locations_branch_id` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
