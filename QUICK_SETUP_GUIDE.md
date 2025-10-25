# 🚀 Location API Setup - Quick Start Guide

## ✅ **Current Status**
Your API test script is working! The JSON response shows the endpoints are properly configured.

## 🗄️ **Step 1: Create Database Table**

**Option A: Use the setup script**
1. Visit: `http://192.168.31.129:8080/setup_locations_db.php`
2. Copy the SQL from the response
3. Run it in your database (phpMyAdmin/MySQL Workbench)

**Option B: Manual SQL**
```sql
CREATE TABLE IF NOT EXISTS `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `address` text DEFAULT NULL,
  `radius` int(11) DEFAULT 100,
  `branch_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add sample data
INSERT INTO `locations` (`name`, `latitude`, `longitude`, `address`, `radius`, `branch_id`, `is_active`) VALUES
('Main Office', 23.8103, 90.4125, 'Dhaka, Bangladesh', 100, 1, 1),
('Branch Office North', 23.8500, 90.4000, 'North Dhaka, Bangladesh', 150, 1, 1);
```

## 🧪 **Step 2: Test API Endpoints**

Visit: `http://192.168.31.129:8080/test_location_endpoints.php`

This will test:
- ✅ GET `/api/location/list` - List all locations
- ✅ POST `/api/location/add` - Add new location
- ✅ POST `/api/location/check` - Check location proximity

## 📱 **Step 3: Test Flutter App**

1. **Restart your backend server** (to ensure routes are loaded)
2. **Open Flutter app** → Profile Screen
3. **Scroll to Developer Tools** → Location Manager
4. **Tap "Refresh"** - should now show locations instead of error
5. **Tap "Add Location"** - should work with current GPS position
6. **Tap "Check Location"** - should show if you're within any location

## 🔍 **Expected Results**

### ✅ **Success Indicators**
- Location Manager loads without HTML error
- Shows "Registered Locations (X)" with actual count
- Add Location dialog works with GPS coordinates
- Check Location shows proximity results

### ❌ **If Still Getting Errors**
- Check database table exists: `SHOW TABLES LIKE 'locations';`
- Verify API routes: Visit `http://192.168.31.129:8080/api/location/list`
- Check server logs for detailed error messages
- Ensure CodeIgniter server restarted after route changes

## 🎯 **Quick Test Commands**

```bash
# Test if API is responding
curl http://192.168.31.129:8080/api/location/list

# Test if database table exists
curl http://192.168.31.129:8080/test_location_endpoints.php
```

## 🎉 **You're Almost There!**

The hard part (API configuration) is done. Just create the database table and you'll have a fully working location management system! 🚀
