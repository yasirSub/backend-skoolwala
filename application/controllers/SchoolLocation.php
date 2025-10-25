<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SchoolLocation extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('location_model');
    }

    /**
     * Set/Add a school location
     * POST /api/school-location/set
     */
    public function set() {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $name = $data['name'] ?? null;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $address = $data['address'] ?? '';
        $radius = $data['radius'] ?? 100;
        $school_id = $data['school_id'] ?? 1; // Default school ID
        $is_active = $data['is_active'] ?? 1;
        
        if (empty($name) || empty($latitude) || empty($longitude)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Name, latitude, and longitude are required'
            ]);
            return;
        }
        
        try {
            // Check if location already exists for this school
            $existing = $this->db->get_where('school_locations', [
                'school_id' => $school_id,
                'name' => $name
            ])->row();
            
            if ($existing) {
                // Update existing location
                $update_data = [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address' => $address,
                    'radius' => $radius,
                    'is_active' => $is_active,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->where('id', $existing->id);
                $this->db->update('school_locations', $update_data);
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'School location updated successfully',
                    'data' => [
                        'location_id' => $existing->id,
                        'action' => 'updated',
                        'location' => array_merge(['id' => $existing->id], $update_data)
                    ]
                ]);
            } else {
                // Create new location
                $location_data = [
                    'name' => $name,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'address' => $address,
                    'radius' => $radius,
                    'school_id' => $school_id,
                    'is_active' => $is_active,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                
                $this->db->insert('school_locations', $location_data);
                $location_id = $this->db->insert_id();
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'School location added successfully',
                    'data' => [
                        'location_id' => $location_id,
                        'action' => 'created',
                        'location' => array_merge(['id' => $location_id], $location_data)
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error setting school location: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get all school locations
     * GET /api/school-location/list
     */
    public function list() {
        header('Content-Type: application/json');
        
        $school_id = $this->input->get('school_id') ?? 1;
        $is_active = $this->input->get('is_active');
        
        try {
            $this->db->select('*');
            $this->db->from('school_locations');
            $this->db->where('school_id', $school_id);
            
            if ($is_active !== null) {
                $this->db->where('is_active', $is_active);
            }
            
            $this->db->order_by('name', 'ASC');
            $query = $this->db->get();
            $locations = $query->result_array();
            
            echo json_encode([
                'status' => 'success',
                'message' => 'School locations retrieved successfully',
                'data' => $locations,
                'count' => count($locations)
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error retrieving school locations: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Check if current location is within any school location
     * POST /api/school-location/check
     */
    public function check() {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $school_id = $data['school_id'] ?? 1;
        
        if (empty($latitude) || empty($longitude)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Latitude and longitude are required'
            ]);
            return;
        }
        
        try {
            $this->db->select('*');
            $this->db->from('school_locations');
            $this->db->where('school_id', $school_id);
            $this->db->where('is_active', 1);
            
            $query = $this->db->get();
            $locations = $query->result_array();
            
            $result = [
                'is_within_location' => false,
                'matched_locations' => [],
                'current_position' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ]
            ];
            
            foreach ($locations as $location) {
                $distance = $this->calculate_distance(
                    $latitude, 
                    $longitude, 
                    $location['latitude'], 
                    $location['longitude']
                );
                
                if ($distance <= $location['radius']) {
                    $result['is_within_location'] = true;
                    $result['matched_locations'][] = [
                        'id' => $location['id'],
                        'name' => $location['name'],
                        'address' => $location['address'],
                        'radius' => $location['radius'],
                        'distance' => round($distance, 2)
                    ];
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Location check completed',
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error checking location: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a school location
     * DELETE /api/school-location/delete/{id}
     */
    public function delete($id) {
        header('Content-Type: application/json');
        
        if (empty($id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Location ID is required'
            ]);
            return;
        }
        
        try {
            $this->db->where('id', $id);
            $result = $this->db->delete('school_locations');
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'School location deleted successfully',
                    'data' => ['location_id' => $id]
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to delete location or location not found'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error deleting location: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in meters
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371000; // Earth's radius in meters
        
        $lat1_rad = deg2rad($lat1);
        $lon1_rad = deg2rad($lon1);
        $lat2_rad = deg2rad($lat2);
        $lon2_rad = deg2rad($lon2);
        
        $delta_lat = $lat2_rad - $lat1_rad;
        $delta_lon = $lon2_rad - $lon1_rad;
        
        $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
             cos($lat1_rad) * cos($lat2_rad) *
             sin($delta_lon / 2) * sin($delta_lon / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earth_radius * $c;
    }
}
