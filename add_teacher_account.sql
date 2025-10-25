-- Add Teacher Login Account
-- This will create a teacher user with Role 3

-- Step 1: Add staff record
INSERT INTO `staff` 
(`staff_id`, `name`, `email`, `mobileno`, `joining_date`, `branch_id`, `created_at`) 
VALUES 
('TCHR001', 'Teacher One', 'teacher@skoolwala.com', '8888888888', CURDATE(), 1, NOW())
ON DUPLICATE KEY UPDATE name = name;

-- Get staff ID (use existing if already exists)
SELECT @teacher_staff_id := id FROM `staff` WHERE email = 'teacher@skoolwala.com' LIMIT 1;

-- Step 2: Add/Update login credentials (password: teacher123)
INSERT INTO `login_credential` 
(`user_id`, `username`, `password`, `role`, `active`, `created_at`) 
VALUES 
(@teacher_staff_id, 'teacher@skoolwala.com', MD5('teacher123'), 3, 1, NOW())
ON DUPLICATE KEY UPDATE password = MD5('teacher123');

-- Teacher Login Credentials:
-- Username: teacher@skoolwala.com
-- Password: teacher123
-- Role: 3 (Teacher)
