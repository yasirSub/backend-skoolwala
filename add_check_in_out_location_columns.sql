-- Add check-in and check-out location columns to staff_attendance table
-- This allows tracking different locations for check-in and check-out

ALTER TABLE `staff_attendance` 
ADD COLUMN `check_in_location_id` int(11) DEFAULT NULL AFTER `location_id`,
ADD COLUMN `check_in_latitude` decimal(10,8) DEFAULT NULL AFTER `check_in_location_id`,
ADD COLUMN `check_in_longitude` decimal(11,8) DEFAULT NULL AFTER `check_in_latitude`,
ADD COLUMN `check_out_location_id` int(11) DEFAULT NULL AFTER `check_in_longitude`,
ADD COLUMN `check_out_latitude` decimal(10,8) DEFAULT NULL AFTER `check_out_location_id`,
ADD COLUMN `check_out_longitude` decimal(11,8) DEFAULT NULL AFTER `check_out_latitude`,
ADD KEY `idx_check_in_location` (`check_in_location_id`),
ADD KEY `idx_check_out_location` (`check_out_location_id`);
