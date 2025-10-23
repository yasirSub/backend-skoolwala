<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2025-10-17 10:03:36 --> Severity: Warning --> mysqli::real_connect(): (HY000/2002): No connection could be made because the target machine actively refused it D:\git\skoolwala\backend\skoolwala\system\database\drivers\mysqli\mysqli_driver.php 211
ERROR - 2025-10-17 10:03:36 --> Unable to connect to the database
ERROR - 2025-10-17 10:03:40 --> Severity: Warning --> mysqli::real_connect(): (HY000/2002): No connection could be made because the target machine actively refused it D:\git\skoolwala\backend\skoolwala\system\database\drivers\mysqli\mysqli_driver.php 211
ERROR - 2025-10-17 10:03:40 --> Unable to connect to the database
ERROR - 2025-10-17 10:03:40 --> Query error: No connection could be made because the target machine actively refused it - Invalid query: SHOW TABLES FROM `your_hostinger_db_name`
ERROR - 2025-10-17 10:03:40 --> Severity: error --> Exception: Call to a member function result_array() on bool D:\git\skoolwala\backend\skoolwala\system\database\DB_driver.php 1271
ERROR - 2025-10-17 15:49:37 --> Severity: Warning --> Undefined array key "instagram_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 505
ERROR - 2025-10-17 15:49:37 --> Severity: Warning --> Undefined array key "google_plus_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 508
ERROR - 2025-10-17 15:49:50 --> Severity: Warning --> Undefined array key "instagram_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 505
ERROR - 2025-10-17 15:49:50 --> Severity: Warning --> Undefined array key "google_plus_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 508
ERROR - 2025-10-17 15:52:19 --> Severity: Warning --> Undefined array key "instagram_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 505
ERROR - 2025-10-17 15:52:19 --> Severity: Warning --> Undefined array key "google_plus_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 508
ERROR - 2025-10-17 15:56:26 --> Severity: Warning --> Undefined array key "instagram_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 505
ERROR - 2025-10-17 15:56:26 --> Severity: Warning --> Undefined array key "google_plus_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 508
ERROR - 2025-10-17 16:08:08 --> Severity: Warning --> Undefined array key "instagram_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 505
ERROR - 2025-10-17 16:08:08 --> Severity: Warning --> Undefined array key "google_plus_url" D:\git\skoolwala\backend\skoolwala\application\views\saas_website\index.php 508
ERROR - 2025-10-17 11:51:10 --> Query error: Unknown column 'sf.created_at' in 'field list' - Invalid query: SELECT `sf`.`staff_id`, `s`.`name`, `sf`.`model_name`, `sf`.`created_at`, `sf`.`updated_at`
FROM `staff_face` as `sf`
LEFT JOIN `staff` as `s` ON `s`.`id` = `sf`.`staff_id`
ORDER BY `sf`.`updated_at` DESC
ERROR - 2025-10-17 11:52:53 --> Query error: Unknown column 'sf.created_at' in 'field list' - Invalid query: SELECT `sf`.`staff_id`, `s`.`name`, `sf`.`model_name`, `sf`.`created_at`, `sf`.`updated_at`
FROM `staff_face` as `sf`
LEFT JOIN `staff` as `s` ON `s`.`id` = `sf`.`staff_id`
ORDER BY `sf`.`updated_at` DESC
ERROR - 2025-10-17 11:52:55 --> Query error: Unknown column 'sf.created_at' in 'field list' - Invalid query: SELECT `sf`.`staff_id`, `s`.`name`, `sf`.`model_name`, `sf`.`created_at`, `sf`.`updated_at`
FROM `staff_face` as `sf`
LEFT JOIN `staff` as `s` ON `s`.`id` = `sf`.`staff_id`
ORDER BY `sf`.`updated_at` DESC
ERROR - 2025-10-17 12:18:32 --> Query error: Unknown column 'sf.created_at' in 'field list' - Invalid query: SELECT `sf`.`staff_id`, `s`.`name`, `sf`.`model_name`, `sf`.`created_at`, `sf`.`updated_at`
FROM `staff_face` as `sf`
LEFT JOIN `staff` as `s` ON `s`.`id` = `sf`.`staff_id`
ORDER BY `sf`.`updated_at` DESC
ERROR - 2025-10-17 12:23:09 --> Query error: Unknown column 'NULL' in 'field list' - Invalid query: SELECT `sf`.`staff_id`, `s`.`name`, `sf`.`model_name`, `NULL` as `created_at`, `NULL` as `updated_at`
FROM `staff_face` as `sf`
LEFT JOIN `staff` as `s` ON `s`.`id` = `sf`.`staff_id`
ORDER BY `sf`.`staff_id` DESC
