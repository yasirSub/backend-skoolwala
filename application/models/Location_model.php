<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location_model extends CI_Model {

    public function __construct() {
        parent::__construct();
    }

    /**
     * Add a new location
     */
    public function add_location($data) {
        $this->db->insert('locations', $data);
        return $this->db->insert_id();
    }

    /**
     * Get all locations with optional filters
     */
    public function get_locations($branch_id = null, $is_active = null) {
        $this->db->select('*');
        $this->db->from('locations');
        
        if ($branch_id !== null) {
            $this->db->where('branch_id', $branch_id);
        }
        
        if ($is_active !== null) {
            $this->db->where('is_active', $is_active);
        }
        
        $this->db->order_by('name', 'ASC');
        $query = $this->db->get();
        
        return $query->result_array();
    }

    /**
     * Get location by ID
     */
    public function get_location_by_id($id) {
        $this->db->select('*');
        $this->db->from('locations');
        $this->db->where('id', $id);
        $query = $this->db->get();
        
        return $query->row_array();
    }

    /**
     * Update location
     */
    public function update_location($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('locations', $data);
    }

    /**
     * Delete location
     */
    public function delete_location($id) {
        $this->db->where('id', $id);
        return $this->db->delete('locations');
    }

    /**
     * Check if current location is within any registered location
     */
    public function check_location($latitude, $longitude, $branch_id = null) {
        $this->db->select('*');
        $this->db->from('locations');
        $this->db->where('is_active', 1);
        
        if ($branch_id !== null) {
            $this->db->where('branch_id', $branch_id);
        }
        
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
        
        return $result;
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

    /**
     * Get locations within a specific radius of given coordinates
     */
    public function get_locations_within_radius($latitude, $longitude, $radius = 1000, $branch_id = null) {
        $this->db->select('*, 
            (6371000 * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
            sin(radians(latitude)))) AS distance', false);
        
        $this->db->from('locations');
        $this->db->where('is_active', 1);
        
        if ($branch_id !== null) {
            $this->db->where('branch_id', $branch_id);
        }
        
        $this->db->having('distance <=', $radius);
        $this->db->order_by('distance', 'ASC');
        
        $query = $this->db->query($this->db->get_compiled_select(), [$latitude, $longitude, $latitude]);
        
        return $query->result_array();
    }

    /**
     * Get location statistics
     */
    public function get_location_stats($branch_id = null) {
        $this->db->select('
            COUNT(*) as total_locations,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_locations,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_locations,
            AVG(radius) as average_radius
        ');
        
        $this->db->from('locations');
        
        if ($branch_id !== null) {
            $this->db->where('branch_id', $branch_id);
        }
        
        $query = $this->db->get();
        return $query->row_array();
    }
}
