# Location-Based Attendance System Documentation

## Overview

The Location-Based Attendance System allows administrators to set up specific locations where staff can mark their attendance. Users can only mark attendance when they are within the specified radius of an allowed location, ensuring accurate attendance tracking based on physical presence.

## Features

### 🔧 Admin Panel Features
- **Location Management**: Add, edit, delete, and manage attendance locations
- **Radius Configuration**: Set custom radius for each location (10m to 1000m)
- **Location Settings**: Enable/disable location verification per branch
- **Real-time Location**: Get current location using GPS
- **Visual Interface**: Modern, responsive admin panel with Bootstrap

### 📱 Mobile App Features
- **GPS Integration**: Real-time location detection using device GPS
- **Location Verification**: Automatic verification against allowed locations
- **Face + Location**: Combined face recognition and location verification
- **User-Friendly UI**: Clear feedback on location status
- **Permission Handling**: Proper location permission requests

### 🔒 Security Features
- **Radius Validation**: Precise distance calculation using Haversine formula
- **Location Verification**: Backend validation of user coordinates
- **Multi-location Support**: Support for multiple allowed locations per branch
- **Settings Control**: Admin can enable/disable location verification

## Installation & Setup

### 1. Database Setup

Run the SQL script to create the required tables:

```sql
-- Run this in your database management tool
source location_attendance_tables.sql
```

This will create:
- `attendance_locations` - Stores allowed locations
- `location_attendance_settings` - Branch-specific settings
- Updates existing attendance tables with location fields

### 2. Backend Configuration

The system includes the following new files:

#### Controllers
- `Location_attendance.php` - API endpoints for location management
- `Location_attendance_admin.php` - Admin panel controller

#### Models
- `Location_attendance_model.php` - Database operations for locations

#### Views
- `location_attendance/index.php` - Admin panel interface

### 3. Mobile App Configuration

Update your `pubspec.yaml` to include location dependencies:

```yaml
dependencies:
  geolocator: ^10.1.0
  permission_handler: ^11.0.1
```

### 4. API Integration

The system extends the existing face verification API to include location data:

```dart
// Example API call with location
final response = await FaceApiService.identifyFace(
  embedding: faceEmbedding,
  latitude: currentLocation['latitude'],
  longitude: currentLocation['longitude'],
);
```

## API Endpoints

### Location Management

#### Get Branch Locations
```
GET /api/location-attendance/locations/{branch_id}
```

#### Add Location
```
POST /api/location-attendance/locations
Content-Type: application/json

{
  "branch_id": 1,
  "name": "Main Office",
  "latitude": 23.0225,
  "longitude": 72.5714,
  "radius": 100,
  "address": "Main office building",
  "description": "Primary attendance location"
}
```

#### Update Location
```
PUT /api/location-attendance/locations/{location_id}
Content-Type: application/json

{
  "name": "Updated Name",
  "radius": 150
}
```

#### Delete Location
```
DELETE /api/location-attendance/locations/{location_id}
```

### Location Verification

#### Verify Location
```
POST /api/location-attendance/verify
Content-Type: application/json

{
  "staff_id": "123",
  "branch_id": "1",
  "user_latitude": 23.0225,
  "user_longitude": 72.5714
}
```

### Settings Management

#### Get Settings
```
GET /api/location-attendance/settings/{branch_id}
```

#### Update Settings
```
POST /api/location-attendance/settings
Content-Type: application/json

{
  "branch_id": 1,
  "location_verification_enabled": 1,
  "allow_multiple_locations": 1,
  "default_radius": 100
}
```

## Usage Guide

### For Administrators

1. **Access Admin Panel**
   - Navigate to `/location-attendance-admin`
   - Configure location verification settings

2. **Add Locations**
   - Click "Add Location" button
   - Enter location details
   - Use "Get Current Location" or enter coordinates manually
   - Set appropriate radius (10-1000 meters)

3. **Manage Settings**
   - Enable/disable location verification
   - Allow multiple locations per branch
   - Set default radius for new locations

### For Staff (Mobile App)

1. **Location Permission**
   - App will request location permission
   - Grant permission for attendance marking

2. **Marking Attendance**
   - Open face verification screen
   - Location will be verified automatically
   - Must be within allowed radius to mark attendance

3. **Location Status**
   - Green indicator: Location verified
   - Red indicator: Outside allowed area
   - Blue indicator: Verifying location

## Configuration Options

### Location Settings

| Setting | Description | Default |
|---------|-------------|---------|
| `location_verification_enabled` | Enable/disable location verification | 0 (disabled) |
| `allow_multiple_locations` | Allow multiple locations per branch | 0 (single location) |
| `default_radius` | Default radius for new locations | 100 meters |

### Location Properties

| Property | Type | Description |
|----------|------|-------------|
| `name` | String | Location name (required) |
| `latitude` | Decimal(10,8) | GPS latitude (required) |
| `longitude` | Decimal(11,8) | GPS longitude (required) |
| `radius` | Integer | Allowed radius in meters (required) |
| `address` | Text | Physical address (optional) |
| `description` | Text | Additional description (optional) |
| `is_active` | Boolean | Location status (default: active) |

## Distance Calculation

The system uses the Haversine formula to calculate distances between coordinates:

```php
private function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371000; // Earth's radius in meters
    
    $d_lat = deg2rad($lat2 - $lat1);
    $d_lng = deg2rad($lng2 - $lng1);
    
    $a = sin($d_lat / 2) * sin($d_lat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($d_lng / 2) * sin($d_lng / 2);
    
    $c = 2 * asin(sqrt($a));
    
    return $earth_radius * $c;
}
```

## Error Handling

### Common Error Messages

| Error | Description | Solution |
|-------|-------------|----------|
| `Location verification not enabled` | Location verification disabled for branch | Enable in admin settings |
| `No allowed locations configured` | No locations set up for branch | Add locations in admin panel |
| `Outside allowed location radius` | User outside any allowed radius | Move to allowed location |
| `Location services disabled` | GPS disabled on device | Enable location services |
| `Location permission denied` | App permission denied | Grant location permission |

### Troubleshooting

1. **Location Not Detected**
   - Check GPS is enabled
   - Verify location permissions
   - Ensure good signal strength

2. **Always Outside Radius**
   - Verify location coordinates
   - Check radius settings
   - Test with larger radius

3. **Admin Panel Not Loading**
   - Check database tables created
   - Verify controller routes
   - Check user permissions

## Security Considerations

1. **Location Spoofing Prevention**
   - Use high-accuracy GPS settings
   - Validate location timestamps
   - Consider additional verification methods

2. **Privacy Protection**
   - Location data is stored securely
   - Access controlled by permissions
   - Data retention policies

3. **API Security**
   - Validate all input parameters
   - Use proper authentication
   - Rate limiting for API calls

## Performance Optimization

1. **Database Indexing**
   - Index on branch_id and coordinates
   - Optimize location queries

2. **Caching**
   - Cache location settings
   - Store frequently accessed locations

3. **Mobile Optimization**
   - Efficient GPS usage
   - Background location handling
   - Battery optimization

## Future Enhancements

1. **Geofencing**
   - Automatic entry/exit detection
   - Background location monitoring

2. **Analytics**
   - Location usage statistics
   - Attendance patterns analysis

3. **Advanced Features**
   - Time-based location restrictions
   - Role-based location access
   - Integration with mapping services

## Support & Maintenance

### Regular Maintenance
- Monitor location accuracy
- Update location coordinates if needed
- Review and adjust radius settings
- Clean up inactive locations

### Updates
- Keep GPS libraries updated
- Monitor API performance
- Update security measures
- Test with new devices

## Conclusion

The Location-Based Attendance System provides a robust solution for ensuring accurate attendance tracking based on physical presence. With proper configuration and maintenance, it offers enhanced security and reliability for attendance management.

For technical support or feature requests, please contact the development team.
