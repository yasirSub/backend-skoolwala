<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location_attendance extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('location_attendance_model');
    }
    
    /**
     * Get allowed locations for a branch
     * GET /api/location-attendance/locations/{branch_id}
     */
    public function getLocations($branch_id = null) {
        header('Content-Type: application/json');
        
        try {
            if (empty($branch_id)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Branch ID is required'
                ]);
                exit;
            }
            
            $locations = $this->location_attendance_model->getBranchLocations($branch_id);
            
            echo json_encode([
                'status' => 'success',
                'data' => $locations
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch locations: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Add new location for attendance
     * POST /api/location-attendance/locations
     */
    public function addLocation() {
        header('Content-Type: application/json');
        
        if (!$this->input->is_ajax_request()) {
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed'
            ]);
            exit;
        }
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required_fields = ['branch_id', 'name', 'latitude', 'longitude', 'radius'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => "Field '{$field}' is required"
                    ]);
                    exit;
                }
            }
            
            // Validate coordinates
            if (!is_numeric($data['latitude']) || !is_numeric($data['longitude'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid coordinates'
                ]);
                exit;
            }
            
            // Validate radius (should be positive number)
            if (!is_numeric($data['radius']) || $data['radius'] <= 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Radius must be a positive number'
                ]);
                exit;
            }
            
            $location_data = [
                'branch_id' => $data['branch_id'],
                'name' => $data['name'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'radius' => $data['radius'],
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
                'created_at' => date('Y-m-d H:i:s'),
                'created_by' => $this->session->userdata('user_id')
            ];
            
            $location_id = $this->location_attendance_model->addLocation($location_data);
            
            if ($location_id) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location added successfully',
                    'data' => ['location_id' => $location_id]
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to add location'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to add location: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update location
     * PUT /api/location-attendance/locations/{location_id}
     */
    public function updateLocation($location_id = null) {
        header('Content-Type: application/json');
        
        if (!$this->input->is_ajax_request()) {
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed'
            ]);
            exit;
        }
        
        try {
            if (empty($location_id)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Location ID is required'
                ]);
                exit;
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate coordinates if provided
            if (isset($data['latitude']) && (!is_numeric($data['latitude']) || !is_numeric($data['longitude']))) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid coordinates'
                ]);
                exit;
            }
            
            // Validate radius if provided
            if (isset($data['radius']) && (!is_numeric($data['radius']) || $data['radius'] <= 0)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Radius must be a positive number'
                ]);
                exit;
            }
            
            $update_data = array_filter($data, function($key) {
                return in_array($key, ['name', 'latitude', 'longitude', 'radius', 'is_active']);
            }, ARRAY_FILTER_USE_KEY);
            
            $update_data['updated_at'] = date('Y-m-d H:i:s');
            $update_data['updated_by'] = $this->session->userdata('user_id');
            
            $updated = $this->location_attendance_model->updateLocation($location_id, $update_data);
            
            if ($updated) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to update location'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update location: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete location
     * DELETE /api/location-attendance/locations/{location_id}
     */
    public function deleteLocation($location_id = null) {
        header('Content-Type: application/json');
        
        if (!$this->input->is_ajax_request()) {
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed'
            ]);
            exit;
        }
        
        try {
            if (empty($location_id)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Location ID is required'
                ]);
                exit;
            }
            
            $deleted = $this->location_attendance_model->deleteLocation($location_id);
            
            if ($deleted) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location deleted successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to delete location'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to delete location: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Verify if user is within allowed location radius
     * POST /api/location-attendance/verify
     */
    public function verifyLocation() {
        header('Content-Type: application/json');
        
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            // Validate required fields
            $required_fields = ['staff_id', 'branch_id', 'user_latitude', 'user_longitude'];
            foreach ($required_fields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'status' => 'error',
                        'message' => "Field '{$field}' is required"
                    ]);
                    exit;
                }
            }
            
            // Validate coordinates
            if (!is_numeric($data['user_latitude']) || !is_numeric($data['user_longitude'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid user coordinates'
                ]);
                exit;
            }
            
            $staff_id = $data['staff_id'];
            $branch_id = $data['branch_id'];
            $user_lat = $data['user_latitude'];
            $user_lng = $data['user_longitude'];
            
            // Get allowed locations for this branch
            $locations = $this->location_attendance_model->getBranchLocations($branch_id);
            
            if (empty($locations)) {
                echo json_encode([
                    'status' => 'success',
                    'verified' => false,
                    'message' => 'No allowed locations configured for this branch'
                ]);
                exit;
            }
            
            $nearest_location = null;
            $min_distance = PHP_FLOAT_MAX;
            
            // Check distance to each allowed location
            foreach ($locations as $location) {
                $distance = $this->calculateDistance(
                    $user_lat,
                    $user_lng,
                    $location['latitude'],
                    $location['longitude']
                );
                
                if ($distance <= $location['radius'] && $distance < $min_distance) {
                    $min_distance = $distance;
                    $nearest_location = $location;
                }
            }
            
            if ($nearest_location) {
                echo json_encode([
                    'status' => 'success',
                    'verified' => true,
                    'message' => 'Location verified successfully',
                    'data' => [
                        'nearest_location' => $nearest_location,
                        'distance' => round($min_distance, 2),
                        'within_radius' => true
                    ]
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'verified' => false,
                    'message' => 'You are not within any allowed location radius',
                    'data' => [
                        'distance_to_nearest' => round($min_distance, 2),
                        'within_radius' => false
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Location verification failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get location-based attendance settings for a branch
     * GET /api/location-attendance/settings/{branch_id}
     */
    public function getSettings($branch_id = null) {
        header('Content-Type: application/json');
        
        try {
            if (empty($branch_id)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Branch ID is required'
                ]);
                exit;
            }
            
            $settings = $this->location_attendance_model->getBranchSettings($branch_id);
            
            echo json_encode([
                'status' => 'success',
                'data' => $settings
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch settings: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update location-based attendance settings
     * POST /api/location-attendance/settings
     */
    public function updateSettings() {
        header('Content-Type: application/json');
        
        if (!$this->input->is_ajax_request()) {
            http_response_code(405);
            echo json_encode([
                'status' => 'error',
                'message' => 'Method not allowed'
            ]);
            exit;
        }
        
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['branch_id'])) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Branch ID is required'
                ]);
                exit;
            }
            
            $settings_data = [
                'branch_id' => $data['branch_id'],
                'location_verification_enabled' => isset($data['location_verification_enabled']) ? (int)$data['location_verification_enabled'] : 0,
                'allow_multiple_locations' => isset($data['allow_multiple_locations']) ? (int)$data['allow_multiple_locations'] : 0,
                'default_radius' => isset($data['default_radius']) ? $data['default_radius'] : 100,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $this->session->userdata('user_id')
            ];
            
            $updated = $this->location_attendance_model->updateBranchSettings($settings_data);
            
            if ($updated) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Settings updated successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to update settings'
                ]);
            }
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ]);
        }
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
