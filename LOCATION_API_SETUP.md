# Location API Setup Guide

## 🚨 **Issue Fixed: HTML Response Instead of JSON**

The error `FormatException: Unexpected character (at character 1) <!doctype html>` was occurring because:

1. **Missing API Routes**: The location API routes were not configured in CodeIgniter
2. **Wrong API Endpoints**: Flutter was calling `/location/list` instead of `/api/location/list`
3. **Missing Database Table**: The `locations` table didn't exist

## ✅ **What I Fixed**

### 1. **Added API Routes** (`application/config/routes.php`)
```php
// Location Management API Routes
$route['api/location/add'] = 'location/add';
$route['api/location/list'] = 'location/list';
$route['api/location/update/(:num)'] = 'location/update/$1';
$route['api/location/delete/(:num)'] = 'location/delete/$1';
$route['api/location/get/(:num)'] = 'location/get/$1';
$route['api/location/check'] = 'location/check';
$route['api/location/within-radius'] = 'location/within_radius';
```

### 2. **Fixed Flutter API Endpoints** (`location_service.dart`)
- Changed all endpoints from `/location/...` to `/api/location/...`
- Now correctly calls: `$_baseUrl/api/location/list`

### 3. **Added Missing Controller Method**
- Added `within_radius()` method to Location controller

## 🗄️ **Database Setup Required**

**IMPORTANT**: You need to create the `locations` table in your database:

```sql
-- Run this SQL in your database
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
```

## 🧪 **Testing the API**

### Test Script Available
Visit: `http://your-domain/test_location_api.php` for API testing instructions.

### Manual Testing
1. **Test List Locations**: `GET http://192.168.31.129:8080/api/location/list`
2. **Test Add Location**: `POST http://192.168.31.129:8080/api/location/add`
3. **Test Check Location**: `POST http://192.168.31.129:8080/api/location/check`

## 🎯 **Next Steps**

1. **Create Database Table**: Run the SQL above in your database
2. **Restart Backend Server**: Restart your CodeIgniter server to load new routes
3. **Test Flutter App**: The location manager should now work without errors
4. **Add Sample Data**: Use the INSERT statements to add test locations

## 🔍 **Debug Information**

The error dialog now shows:
- **API URL**: `http://192.168.31.129:8080/api`
- **Branch ID**: `None` (this is normal if teacher doesn't have branch_id)
- **Current Position**: `Not available` (GPS permission needed)
- **Error Type**: `_Exception`
- **Timestamp**: Current time

After fixing the database table, the location manager should work perfectly! 🎉
