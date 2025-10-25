-- Add school location for attendance verification
-- Based on GPS coordinates: 25.054646, 86.992015

INSERT INTO `attendance_locations` 
(`branch_id`, `name`, `latitude`, `longitude`, `radius`, `address`, `description`, `is_active`, `created_at`) 
VALUES 
(1, 'Main School Building', 25.054646, 86.992015, 500, 'Godda - Jharkhand, India', 'Main school location with 500m radius', 1, NOW());

-- Note: 
-- - Latitude: 25.054646
-- - Longitude: 86.992015
-- - Radius: 500 meters (covers a large area around the school)
