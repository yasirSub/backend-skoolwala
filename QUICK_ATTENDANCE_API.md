# Enhanced Quick Attendance API Documentation

## Overview
The Enhanced Quick Attendance API uses the same face recognition analyzer logic to verify that the face data matches the logged-in user's ID before marking attendance. It includes comprehensive time, date, and location tracking for accurate attendance records.

## Endpoint
```
POST /api/quickAttendance
```

## Request Format
```json
{
    "face_data": [array of face embedding values],
    "user_id": 123,
    "attendance_type": "check_in" | "check_out",
    "remark": "optional remark",
    "location": "Office Building A",
    "latitude": 40.7128,
    "longitude": -74.0060
}
```

## Parameters
- `face_data` (required): Array of face embedding values from face detection
- `user_id` (required): The logged-in user's ID to verify face against
- `attendance_type` (optional): Either "check_in" or "check_out". Defaults to "check_in"
- `remark` (optional): Any additional remarks for the attendance record
- `location` (optional): Text description of the location
- `latitude` (optional): GPS latitude coordinate
- `longitude` (optional): GPS longitude coordinate

## Response Format

### Success Response (Face Verified & Attendance Marked)
```json
{
    "status": "success",
    "matched": true,
    "staff_id": 123,
    "name": "John Doe",
    "email": "john@example.com",
    "mobile": "1234567890",
    "similarity": 0.85,
    "confidence": 85.0,
    "threshold": 0.65,
    "attendance_type": "check_in",
    "attendance_time": "09:30:00",
    "attendance_date": "2024-01-15",
    "location": "Office Building A",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "message": "Attendance marked successfully"
}
```

### Error Response (Face Verification Failed)
```json
{
    "status": "error",
    "matched": false,
    "similarity": 0.45,
    "threshold": 0.65,
    "message": "Face verification failed. Face does not match logged-in user.",
    "confidence": 45.0
}
```

### Error Response (No Face Enrollment)
```json
{
    "status": "error",
    "message": "No face enrollment found for user ID: 123"
}
```

### Error Response (Already Checked In)
```json
{
    "status": "error",
    "message": "You have already checked in today",
    "matched": true,
    "staff_id": 123,
    "name": "John Doe",
    "confidence": 85.0,
    "attendance_time": "09:30:00",
    "attendance_date": "2024-01-15"
}
```

## Key Features
- **Face Verification**: Uses same analyzer logic to verify face matches logged-in user ID
- **Enhanced Security**: Prevents attendance marking unless face matches the specific user
- **Location Tracking**: Records GPS coordinates and location description
- **Time & Date**: Automatic timestamp and date recording
- **Duplicate Prevention**: Prevents multiple check-ins on the same day
- **Validation Logic**: Ensures check-out only happens after check-in
- **Comprehensive Logging**: All operations are logged for debugging
- **Same Threshold**: Uses 0.65 similarity threshold as FaceAnalyzer

## Security Features
- **User-Specific Verification**: Only verifies against the logged-in user's face data
- **Face Matching**: Uses cosine similarity to ensure face matches user ID
- **Enrollment Check**: Verifies user has face enrollment before processing
- **Dimension Validation**: Ensures face data dimensions are compatible

## Usage Example
```javascript
// Send face data to enhanced quick attendance API
fetch('/api/quickAttendance', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        face_data: faceEmbeddingArray,
        user_id: loggedInUserId, // Required for verification
        attendance_type: 'check_in',
        remark: 'Morning check-in',
        location: 'Main Office',
        latitude: 40.7128,
        longitude: -74.0060
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'success') {
        console.log('Attendance marked for:', data.name);
        console.log('Confidence:', data.confidence + '%');
        console.log('Location:', data.location);
        console.log('Time:', data.attendance_time);
    } else {
        console.log('Error:', data.message);
    }
});
```

## Database Fields
The attendance record includes these fields:
- `staff_id`: User ID
- `branch_id`: Branch ID
- `date`: Current date (Y-m-d format)
- `status`: 'P' for present (check_in), 'A' for absent (check_out)
- `in_time`: Check-in time (H:i:s format)
- `out_time`: Check-out time (H:i:s format)
- `remark`: User remarks
- `location`: Location description
- `latitude`: GPS latitude
- `longitude`: GPS longitude

## Integration Notes
- This API uses the same cosine similarity calculation as FaceAnalyzer
- Face embeddings are automatically reduced to 128 dimensions if needed
- The system verifies face against specific user ID, not all enrolled faces
- All face verification attempts are logged for debugging purposes
- Location data is optional but recommended for accurate tracking
