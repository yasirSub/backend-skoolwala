<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance For Teacher Controller
 * Simple and fast attendance system for teachers
 */
class AttendanceForTeacher extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->helper('general');
        
        // Disable CSRF protection for API endpoints
        $this->config->set_item('csrf_protection', false);
        
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Quick Teacher Attendance - Check In/Out
     * POST /api/attendanceForTeacher
     */
    public function quickAttendance()
    {
        header('Content-Type: application/json');
        
        // Enable error logging
        error_log('=== ATTENDANCE API CALLED ===');
        error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
        error_log('Request URI: ' . $_SERVER['REQUEST_URI']);
        
        $raw = file_get_contents('php://input');
        error_log('Raw Input: ' . $raw);
        
        $data = json_decode($raw, true);
        error_log('Parsed Data: ' . json_encode($data));
        
        $face_data = $data['face_data'] ?? null;
        $staff_id = $data['staff_id'] ?? null;
        $validated_face = $data['validated_face'] ?? false;
        $attendance_type = $data['attendance_type'] ?? 'check_in';
        $user_latitude = $data['user_latitude'] ?? null;
        $user_longitude = $data['user_longitude'] ?? null;
        
        error_log("Face data present: " . (!empty($face_data) ? 'Yes' : 'No'));
        error_log("User latitude: $user_latitude");
        error_log("User longitude: $user_longitude");
        error_log("Attendance type: $attendance_type");
        
        // ========================================
        // PRE-CHECK: VERIFY USER IS ENROLLED
        // ========================================
        if (is_loggedin() && loggedin_role_id() == 3) {
            $logged_in_staff_id = get_loggedin_user_id();
            error_log("Checking enrollment for logged-in staff_id: $logged_in_staff_id");
            
            // Check if face is enrolled
            $enrollment_check = $this->db->get_where('staff_face', ['staff_id' => $logged_in_staff_id])->row();
            
            if (empty($enrollment_check)) {
                error_log("ENROLLMENT CHECK FAILED: Staff ID $logged_in_staff_id is not enrolled");
                echo json_encode([
                    'status' => 'error',
                    'step' => 0,
                    'code' => 'FACE_NOT_ENROLLED',
                    'message' => 'Face enrollment required',
                    'details' => 'Please enroll your face before marking attendance. Go to Profile > Face Enrollment to enroll.',
                    'action_required' => 'enroll_face'
                ]);
                return; // STOP HERE - User not enrolled
            }
            
            error_log("ENROLLMENT CHECK PASSED: Staff ID $logged_in_staff_id is enrolled");
        } else if (!empty($staff_id)) {
            // If staff_id is provided directly, check enrollment for that staff
            $enrollment_check = $this->db->get_where('staff_face', ['staff_id' => $staff_id])->row();
            
            if (empty($enrollment_check)) {
                error_log("ENROLLMENT CHECK FAILED: Staff ID $staff_id is not enrolled");
                echo json_encode([
                    'status' => 'error',
                    'step' => 0,
                    'code' => 'FACE_NOT_ENROLLED',
                    'message' => 'Face enrollment required',
                    'details' => 'Please enroll your face before marking attendance. Contact administrator for enrollment.',
                    'action_required' => 'enroll_face'
                ]);
                return; // STOP HERE - User not enrolled
            }
            
            error_log("ENROLLMENT CHECK PASSED: Staff ID $staff_id is enrolled");
        }
        
        // Check if we have face data or validated face
        if (!$validated_face && (empty($face_data) || !is_array($face_data))) {
            error_log('ERROR: Face data missing or invalid');
            echo json_encode([
                'status' => 'error', 
                'message' => 'Face data or validated face required',
                'debug' => [
                    'face_data_present' => !empty($face_data),
                    'face_data_type' => gettype($face_data),
                    'validated_face' => $validated_face
                ]
            ]);
            return;
        }
        
        // If using validated face, we don't need face_data
        if ($validated_face && !empty($staff_id)) {
            error_log("Using validated face for staff_id: $staff_id");
        } else {
        // Reduce to 128 dimensions
        if (count($face_data) > 128) {
            $face_data = array_slice($face_data, 0, 128);
                error_log('Reduced face data to 128 dimensions');
            }
        }
        
        try {
            // ========================================
            // STEP 1: LOCATION CHECK FIRST (MANDATORY)
            // ========================================
            error_log('=== STEP 1: LOCATION CHECK FIRST ===');
            
            $location_data = null;
            $location_branch_id = null;
            
            if ($user_latitude === null || $user_longitude === null) {
                error_log('STEP 1 FAILED: Location coordinates missing');
                echo json_encode([
                    'status' => 'error',
                    'step' => 1,
                    'message' => 'STEP 1 FAILED: Location coordinates are required',
                    'details' => 'GPS coordinates must be provided for location verification'
                ]);
                return; // STOP HERE - No face check if location missing
            }
            
            error_log("Location coordinates received: $user_latitude, $user_longitude");
            
            // Get all active locations
                $this->db->select('*');
                $this->db->from('attendance_locations');
                $this->db->where('is_active', 1);
                $locations = $this->db->get()->result_array();
            
            if (empty($locations)) {
                echo json_encode([
                    'status' => 'error',
                    'step' => 1,
                    'message' => 'STEP 1 FAILED: No active locations configured',
                    'details' => 'Please contact administrator to configure school locations'
                ]);
                return; // STOP HERE - No face check if no locations
            }
                
                $is_within_location = false;
                $matched_location = null;
            $min_distance = PHP_FLOAT_MAX;
                
                foreach ($locations as $location) {
                    $distance = $this->calculateDistance(
                        $user_latitude, 
                        $user_longitude, 
                        $location['latitude'], 
                        $location['longitude']
                    );
                
                error_log("Checking location {$location['id']}: distance = {$distance}m, radius = {$location['radius']}m");
                    
                    if ($distance <= $location['radius']) {
                        $is_within_location = true;
                        $matched_location = $location;
                    $min_distance = $distance;
                        break; // Found a valid location, stop checking
                    }
                
                // Keep track of closest location for error message
                if ($distance < $min_distance) {
                    $min_distance = $distance;
                }
                }
                
                if (!$is_within_location) {
                error_log("STEP 1 FAILED: User not within any location radius. Closest distance: {$min_distance}m");
                    echo json_encode([
                        'status' => 'error',
                    'step' => 1,
                    'message' => 'STEP 1 FAILED: You are not within any allowed location radius',
                    'details' => "Closest location is {$min_distance}m away. Please move closer to the school.",
                        'location_data' => [
                            'is_within_location' => false,
                            'current_position' => [
                                'latitude' => $user_latitude,
                                'longitude' => $user_longitude
                        ],
                        'closest_distance' => $min_distance
                        ]
                    ]);
                return; // STOP HERE - No face check if location fails
                }
            
            error_log("STEP 1 PASSED: Location verified. Distance: {$min_distance}m from location {$matched_location['id']}");
                
                $location_branch_id = $matched_location['branch_id'];
                $location_data = [
                    'is_within_location' => true,
                    'matched_location' => $matched_location,
                'distance' => $min_distance
                ];
            
            // ========================================
            // STEP 2: FACE RECOGNITION SECOND (ONLY IF LOCATION PASSED)
            // ========================================
            error_log('=== STEP 2: FACE RECOGNITION SECOND ===');
            
            $best_match = null;
            $best_score = 0;
            
            if ($validated_face && !empty($staff_id)) {
                // Use validated face data (skip face recognition)
                error_log("Using validated face for staff_id: $staff_id");
                
                $staff = $this->db->select('sf.staff_id, sf.embedding, s.name, s.email, s.mobileno, s.branch_id')
                    ->from('staff_face sf')
                    ->join('staff s', 's.id = sf.staff_id')
                    ->where('sf.staff_id', $staff_id)
                    ->get()->row_array();
                
                if ($staff) {
                    $best_match = $staff;
                    $best_score = 1.0; // Perfect match for validated face
                    error_log("STEP 2 PASSED: Using validated face. Staff: {$best_match['name']}");
                } else {
                    error_log("STEP 2 FAILED: Staff not found for validated face");
                    echo json_encode([
                        'status' => 'error',
                        'step' => 2,
                        'message' => 'STEP 2 FAILED: Staff not found',
                        'details' => 'Staff ID not found in enrolled faces'
                    ]);
                    return;
                }
            } else {
                // Perform face recognition
                error_log('Performing face recognition...');
                
            // Get all enrolled faces
            $faces = $this->db->select('sf.staff_id, sf.embedding, s.name, s.email, s.mobileno, s.branch_id')
                ->from('staff_face sf')
                ->join('staff s', 's.id = sf.staff_id')
                ->get()->result_array();
            
            if (empty($faces)) {
                    error_log('STEP 2 FAILED: No enrolled faces found');
                    echo json_encode([
                        'status' => 'error',
                        'step' => 2,
                        'message' => 'STEP 2 FAILED: No enrolled faces found',
                        'details' => 'Please contact administrator to enroll faces in the system'
                    ]);
                    return; // STOP HERE - No faces to check against
                }
                
                error_log('Found ' . count($faces) . ' enrolled faces for comparison');
                
            $threshold = 0.65;
            
            // Find best match
            foreach ($faces as $face) {
                $stored = json_decode($face['embedding'], true);
                if (is_array($stored) && count($stored) === count($face_data)) {
                    $score = $this->cosine_similarity($face_data, $stored);
                    if ($score !== null && $score > $best_score) {
                        $best_score = $score;
                        $best_match = $face;
                    }
                }
            }
            
            if (!$best_match || $best_score < $threshold) {
                    error_log("STEP 2 FAILED: Face not recognized. Best score: {$best_score}, Threshold: {$threshold}");
                echo json_encode([
                    'status' => 'error',
                        'step' => 2,
                        'message' => 'STEP 2 FAILED: Face not recognized',
                        'details' => 'Face does not match any enrolled staff member',
                        'similarity' => $best_score,
                        'threshold' => $threshold
                    ]);
                    return; // STOP HERE - Face recognition failed
                }
                
                error_log("STEP 2 PASSED: Face recognized. Staff: {$best_match['name']}, Score: {$best_score}");
            }
            
            // ========================================
            // STEP 3: LOGIN VERIFICATION & BRANCH VALIDATION
            // ========================================
            error_log('=== STEP 3: LOGIN VERIFICATION & BRANCH VALIDATION ===');
            
            // Get staff information
            if ($validated_face && !empty($staff_id)) {
                // Use the staff_id from request directly when validated_face is true
                $branch_id = $staff['branch_id'];
                error_log("Using request staff_id: $staff_id, branch_id: $branch_id");
            } else {
                // Use staff_id from face recognition result
            $staff_id = $best_match['staff_id'];
            $branch_id = $best_match['branch_id'];
                error_log("Using face recognition staff_id: $staff_id, branch_id: $branch_id");
            }
            
            // CRITICAL SECURITY CHECK: Verify logged-in user matches the face
            // Only the logged-in teacher can mark their own attendance
            if (is_loggedin() && loggedin_role_id() == 3) {
                $logged_in_staff_id = get_loggedin_user_id();
                error_log("Security Check - Logged in staff_id: $logged_in_staff_id, Face matched staff_id: $staff_id");
                
                if ($logged_in_staff_id != $staff_id) {
                    error_log("SECURITY FAILED: Logged in user ($logged_in_staff_id) does not match face recognition result ($staff_id)");
                    echo json_encode([
                        'status' => 'error',
                        'step' => 3,
                        'message' => 'Unauthorized: You can only mark your own attendance',
                        'details' => 'The face recognition result does not match your logged-in account'
                    ]);
                    return; // STOP HERE - Security check failed
                }
                
                error_log("SECURITY PASSED: Logged in user matches face recognition");
            } else {
                error_log("WARNING: No valid session found. Proceeding without login verification.");
            }
            
            // STEP 4: Verify staff belongs to the correct branch (if location was checked)
            if ($location_data && $branch_id != $location_branch_id) {
                error_log("STEP 4 FAILED: Staff branch mismatch. Staff branch: {$branch_id}, Location branch: {$location_branch_id}");
                echo json_encode([
                    'status' => 'error',
                    'step' => 4,
                    'message' => 'STEP 4 FAILED: Staff does not belong to this location branch',
                    'details' => 'Staff is not assigned to this school location',
                    'staff_branch_id' => $branch_id,
                    'location_branch_id' => $location_branch_id
                ]);
                return; // STOP HERE - Branch validation failed
            }
            
            error_log("STEP 4 PASSED: Branch validation successful. Staff: {$staff_id}, Branch: {$branch_id}");
            
            // Mark attendance
            // Set timezone to India
            date_default_timezone_set('Asia/Kolkata');
            
            $date = date('Y-m-d');
            $time = date('H:i:s'); // 24-hour format for database storage
            $status = ($attendance_type == 'check_in') ? 'P' : 'A';
            
            // Check existing record
            $existing = $this->db->get_where('staff_attendance', [
                'staff_id' => $staff_id,
                'branch_id' => $branch_id,
                'date' => $date
            ])->row();
            
            $record = [
                'staff_id' => $staff_id,
                'branch_id' => $branch_id,
                'date' => $date,
                'status' => $status,
                'remark' => 'Face recognition + Location verified'
            ];
            
            // Add location information based on check-in/check-out
                $record['location_id'] = $location_data['matched_location']['id'] ?? null;
            
            if ($attendance_type == 'check_in') {
                $record['in_time'] = $time;
                $record['user_latitude'] = $user_latitude;
                $record['user_longitude'] = $user_longitude;
            } else {
                $record['out_time'] = $time;
                $record['user_latitude'] = $user_latitude;
                $record['user_longitude'] = $user_longitude;
            }
            
            if ($existing) {
                $this->db->where('id', $existing->id)->update('staff_attendance', $record);
                $message = 'Updated';
                error_log("ATTENDANCE UPDATE: {$best_match['name']} - Updated existing record ID: {$existing->id}");
            } else {
                $insert_result = $this->db->insert('staff_attendance', $record);
                $insert_id = $this->db->insert_id();
                $message = 'Marked';
                error_log("ATTENDANCE INSERT: {$best_match['name']} - Insert result: " . ($insert_result ? 'SUCCESS' : 'FAILED') . ", Insert ID: {$insert_id}");
                error_log("ATTENDANCE INSERT: Record data: " . json_encode($record));
            }
            
            error_log("ATTENDANCE SUCCESS: {$best_match['name']} - {$message} at {$time}");
            
            echo json_encode([
                'status' => 'success',
                'step' => 4,
                'name' => $best_match['name'],
                'staff_id' => $staff_id,
                'confidence' => round($best_score * 100, 1),
                'attendance_type' => $attendance_type,
                'time' => $time,
                'date' => $date,
                'message' => $message,
                'location_verified' => true,
                'face_verified' => true,
                'distance_from_school' => $location_data['distance']
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Calculate cosine similarity between two arrays
     */
    private function cosine_similarity($array1, $array2)
    {
        if (count($array1) !== count($array2)) {
            return null;
        }
        
        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        for ($i = 0; $i < count($array1); $i++) {
            $dot_product += $array1[$i] * $array2[$i];
            $magnitude1 += $array1[$i] * $array1[$i];
            $magnitude2 += $array2[$i] * $array2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dot_product / ($magnitude1 * $magnitude2);
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     * @param float $lat1 Latitude of first point
     * @param float $lng1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lng2 Longitude of second point
     * @return float Distance in meters
     */
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
    
}
?>
