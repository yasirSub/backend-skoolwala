-- Add Principal Login Account
-- This will create a principal user with Role 2 (Admin/Pythonipal)

-- Step 1: Add staff record
INSERT INTO `staff` 
(`staff_id`, `name`, `email`, `mobileno`, `joining_date`, `branch_id`, `created_at`) 
VALUES 
('PRIN001', 'Principal Admin', 'principal@school.com', '9999999999', CURDATE(), 1, NOW());

SET @principal_staff_id = LAST_INSERT_ID();

-- Step 2: Add login credentials (password: principal123)
INSERT INTO `login_credential` 
(`user_id`, `username`, `password`, `role`, `active`, `created_at`) 
VALUES 
(@principal_staff_id, 'principal@school.com', MD5('principal123'), 2, 1, NOW());

-- Principal Login Credentials:
-- Username: principal@school.com
-- Password: principal123
-- Role: 2 (Admin/Principal)
