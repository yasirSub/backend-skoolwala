-- Location-based Attendance System Database Tables
-- Run this SQL script in your database management tool

-- Table to store allowed locations for attendance
CREATE TABLE IF NOT EXISTS `attendance_locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius` int(11) NOT NULL DEFAULT 100 COMMENT 'Radius in meters',
  `address` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_branch_id` (`branch_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_coordinates` (`latitude`, `longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to store location-based attendance settings for each branch
CREATE TABLE IF NOT EXISTS `location_attendance_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `location_verification_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `allow_multiple_locations` tinyint(1) NOT NULL DEFAULT 0,
  `default_radius` int(11) NOT NULL DEFAULT 100 COMMENT 'Default radius in meters',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_branch_settings` (`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add location_id column to staff_attendance table to track which location was used
ALTER TABLE `staff_attendance` 
ADD COLUMN `location_id` int(11) DEFAULT NULL AFTER `branch_id`,
ADD COLUMN `user_latitude` decimal(10,8) DEFAULT NULL AFTER `location_id`,
ADD COLUMN `user_longitude` decimal(11,8) DEFAULT NULL AFTER `user_latitude`,
ADD KEY `idx_location_id` (`location_id`);

-- Add location_id column to student_attendance table as well
ALTER TABLE `student_attendance` 
ADD COLUMN `location_id` int(11) DEFAULT NULL AFTER `branch_id`,
ADD COLUMN `user_latitude` decimal(10,8) DEFAULT NULL AFTER `location_id`,
ADD COLUMN `user_longitude` decimal(11,8) DEFAULT NULL AFTER `user_latitude`,
ADD KEY `idx_location_id` (`location_id`);

-- Insert sample location data (optional - remove if not needed)
INSERT INTO `attendance_locations` (`branch_id`, `name`, `latitude`, `longitude`, `radius`, `address`, `description`, `is_active`) VALUES
(1, 'Main School Building', 23.0225, 72.5714, 50, 'Main school entrance', 'Primary attendance location for main school building', 1),
(1, 'Sports Complex', 23.0230, 72.5720, 30, 'Sports complex area', 'Attendance location for sports activities', 1),
(1, 'Library Building', 23.0220, 72.5710, 25, 'Library entrance', 'Attendance location for library staff', 1);

-- Insert default settings for branch 1
INSERT INTO `location_attendance_settings` (`branch_id`, `location_verification_enabled`, `allow_multiple_locations`, `default_radius`) VALUES
(1, 1, 1, 100);

-- Add foreign key constraints (optional - uncomment if needed)
-- ALTER TABLE `attendance_locations` ADD CONSTRAINT `fk_attendance_locations_branch` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `location_attendance_settings` ADD CONSTRAINT `fk_location_settings_branch` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`id`) ON DELETE CASCADE;
-- ALTER TABLE `staff_attendance` ADD CONSTRAINT `fk_staff_attendance_location` FOREIGN KEY (`location_id`) REFERENCES `attendance_locations` (`id`) ON DELETE SET NULL;
-- ALTER TABLE `student_attendance` ADD CONSTRAINT `fk_student_attendance_location` FOREIGN KEY (`location_id`) REFERENCES `attendance_locations` (`id`) ON DELETE SET NULL;
