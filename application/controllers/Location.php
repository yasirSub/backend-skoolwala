<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('location_model');
    }

    /**
     * Add a new location
     * POST /location/add
     */
    public function add() {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $name = $data['name'] ?? null;
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $address = $data['address'] ?? '';
        $radius = $data['radius'] ?? 100; // Default 100 meters
        $branch_id = $data['branch_id'] ?? null;
        $is_active = $data['is_active'] ?? 1;
        
        if (empty($name) || empty($latitude) || empty($longitude)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Name, latitude, and longitude are required'
            ]);
            return;
        }
        
        try {
            $location_data = [
                'name' => $name,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'address' => $address,
                'radius' => $radius,
                'branch_id' => $branch_id,
                'is_active' => $is_active,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $location_id = $this->location_model->add_location($location_data);
            
            if ($location_id) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location added successfully',
                    'data' => [
                        'location_id' => $location_id,
                        'location' => $location_data
                    ]
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to add location'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error adding location: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get all locations
     * GET /location/list
     */
    public function list() {
        header('Content-Type: application/json');
        
        $branch_id = $this->input->get('branch_id');
        $is_active = $this->input->get('is_active');
        
        try {
            $locations = $this->location_model->get_locations($branch_id, $is_active);
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Locations retrieved successfully',
                'data' => $locations
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error retrieving locations: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update a location
     * PUT /location/update/{id}
     */
    public function update($id) {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (empty($id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Location ID is required'
            ]);
            return;
        }
        
        try {
            $update_data = [];
            
            if (isset($data['name'])) $update_data['name'] = $data['name'];
            if (isset($data['latitude'])) $update_data['latitude'] = $data['latitude'];
            if (isset($data['longitude'])) $update_data['longitude'] = $data['longitude'];
            if (isset($data['address'])) $update_data['address'] = $data['address'];
            if (isset($data['radius'])) $update_data['radius'] = $data['radius'];
            if (isset($data['branch_id'])) $update_data['branch_id'] = $data['branch_id'];
            if (isset($data['is_active'])) $update_data['is_active'] = $data['is_active'];
            
            $update_data['updated_at'] = date('Y-m-d H:i:s');
            
            $result = $this->location_model->update_location($id, $update_data);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location updated successfully',
                    'data' => ['location_id' => $id]
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to update location or location not found'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error updating location: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Delete a location
     * DELETE /location/delete/{id}
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
            $result = $this->location_model->delete_location($id);
            
            if ($result) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location deleted successfully',
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
     * Check if current location is within any registered location
     * POST /location/check
     */
    public function check() {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $latitude = $data['latitude'] ?? null;
        $longitude = $data['longitude'] ?? null;
        $branch_id = $data['branch_id'] ?? null;
        
        if (empty($latitude) || empty($longitude)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Latitude and longitude are required'
            ]);
            return;
        }
        
        try {
            $result = $this->location_model->check_location($latitude, $longitude, $branch_id);
            
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
     * Get location by ID
     * GET /location/get/{id}
     */
    public function get($id) {
        header('Content-Type: application/json');
        
        if (empty($id)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Location ID is required'
            ]);
            return;
        }
        
        try {
            $location = $this->location_model->get_location_by_id($id);
            
            if ($location) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Location retrieved successfully',
                    'data' => $location
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Location not found'
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error retrieving location: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get locations within radius
     * GET /location/within-radius
     */
    public function within_radius() {
        header('Content-Type: application/json');
        
        $latitude = $this->input->get('latitude');
        $longitude = $this->input->get('longitude');
        $radius = $this->input->get('radius') ?? 1000;
        $branch_id = $this->input->get('branch_id');
        
        if (empty($latitude) || empty($longitude)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Latitude and longitude are required'
            ]);
            return;
        }
        
        try {
            $locations = $this->location_model->get_locations_within_radius(
                $latitude, 
                $longitude, 
                $radius, 
                $branch_id
            );
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Locations within radius retrieved successfully',
                'data' => $locations
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error retrieving locations: ' . $e->getMessage()
            ]);
        }
    }
}
