-- Database Performance Optimizations for Face Recognition
-- Run these SQL commands to improve performance

-- 1. Add indexes to staff_attendance table for faster queries
CREATE INDEX IF NOT EXISTS idx_staff_attendance_staff_date 
ON staff_attendance(staff_id, date);

CREATE INDEX IF NOT EXISTS idx_staff_attendance_branch_date 
ON staff_attendance(branch_id, date);

-- 2. Add index to staff_face table for faster face lookups
CREATE INDEX IF NOT EXISTS idx_staff_face_staff_id 
ON staff_face(staff_id);

-- 3. Optimize staff table for faster joins
CREATE INDEX IF NOT EXISTS idx_staff_id 
ON staff(id);

-- 4. Check current table sizes
SELECT 
    'staff_attendance' as table_name,
    COUNT(*) as record_count
FROM staff_attendance
UNION ALL
SELECT 
    'staff_face' as table_name,
    COUNT(*) as record_count
FROM staff_face
UNION ALL
SELECT 
    'staff' as table_name,
    COUNT(*) as record_count
FROM staff;
