<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location_attendance_model extends MY_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get all allowed locations for a branch
     * @param int $branch_id
     * @return array
     */
    public function getBranchLocations($branch_id) {
        $this->db->select('*');
        $this->db->from('attendance_locations');
        $this->db->where('branch_id', $branch_id);
        $this->db->where('is_active', 1);
        $this->db->order_by('name', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get all locations for a branch (including inactive)
     * @param int $branch_id
     * @return array
     */
    public function getAllBranchLocations($branch_id) {
        $this->db->select('*');
        $this->db->from('attendance_locations');
        $this->db->where('branch_id', $branch_id);
        $this->db->order_by('name', 'ASC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get a specific location by ID
     * @param int $location_id
     * @return object|null
     */
    public function getLocationById($location_id) {
        $this->db->select('*');
        $this->db->from('attendance_locations');
        $this->db->where('id', $location_id);
        
        return $this->db->get()->row();
    }
    
    /**
     * Add a new location
     * @param array $data
     * @return int|false Location ID on success, false on failure
     */
    public function addLocation($data) {
        if ($this->db->insert('attendance_locations', $data)) {
            return $this->db->insert_id();
        }
        return false;
    }
    
    /**
     * Update a location
     * @param int $location_id
     * @param array $data
     * @return bool
     */
    public function updateLocation($location_id, $data) {
        $this->db->where('id', $location_id);
        return $this->db->update('attendance_locations', $data);
    }
    
    /**
     * Delete a location
     * @param int $location_id
     * @return bool
     */
    public function deleteLocation($location_id) {
        $this->db->where('id', $location_id);
        return $this->db->delete('attendance_locations');
    }
    
    /**
     * Soft delete a location (set is_active to 0)
     * @param int $location_id
     * @return bool
     */
    public function deactivateLocation($location_id) {
        $data = [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
            'updated_by' => $this->session->userdata('user_id')
        ];
        
        $this->db->where('id', $location_id);
        return $this->db->update('attendance_locations', $data);
    }
    
    /**
     * Get location-based attendance settings for a branch
     * @param int $branch_id
     * @return object|null
     */
    public function getBranchSettings($branch_id) {
        $this->db->select('*');
        $this->db->from('location_attendance_settings');
        $this->db->where('branch_id', $branch_id);
        
        $result = $this->db->get()->row();
        
        // If no settings exist, return default settings
        if (!$result) {
            return (object)[
                'branch_id' => $branch_id,
                'location_verification_enabled' => 0,
                'allow_multiple_locations' => 0,
                'default_radius' => 100
            ];
        }
        
        return $result;
    }
    
    /**
     * Update or create location-based attendance settings
     * @param array $data
     * @return bool
     */
    public function updateBranchSettings($data) {
        $branch_id = $data['branch_id'];
        
        // Check if settings exist
        $existing = $this->getBranchSettings($branch_id);
        
        if ($existing) {
            // Update existing settings
            $this->db->where('branch_id', $branch_id);
            return $this->db->update('location_attendance_settings', $data);
        } else {
            // Create new settings
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['created_by'] = $this->session->userdata('user_id');
            return $this->db->insert('location_attendance_settings', $data);
        }
    }
    
    /**
     * Check if location verification is enabled for a branch
     * @param int $branch_id
     * @return bool
     */
    public function isLocationVerificationEnabled($branch_id) {
        $settings = $this->getBranchSettings($branch_id);
        return $settings && $settings->location_verification_enabled == 1;
    }
    
    /**
     * Get attendance records with location data
     * @param int $staff_id
     * @param string $date
     * @return object|null
     */
    public function getAttendanceWithLocation($staff_id, $date) {
        $this->db->select('sa.*, al.name as location_name, al.latitude, al.longitude, al.radius');
        $this->db->from('staff_attendance sa');
        $this->db->join('attendance_locations al', 'sa.location_id = al.id', 'left');
        $this->db->where('sa.staff_id', $staff_id);
        $this->db->where('sa.date', $date);
        
        return $this->db->get()->row();
    }
    
    /**
     * Get location statistics for a branch
     * @param int $branch_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function getLocationStatistics($branch_id, $start_date = null, $end_date = null) {
        $this->db->select('al.name as location_name, COUNT(sa.id) as attendance_count, al.latitude, al.longitude');
        $this->db->from('attendance_locations al');
        $this->db->join('staff_attendance sa', 'sa.location_id = al.id', 'left');
        $this->db->join('staff s', 's.id = sa.staff_id', 'left');
        $this->db->where('al.branch_id', $branch_id);
        $this->db->where('al.is_active', 1);
        
        if ($start_date && $end_date) {
            $this->db->where('sa.date >=', $start_date);
            $this->db->where('sa.date <=', $end_date);
        }
        
        $this->db->group_by('al.id');
        $this->db->order_by('attendance_count', 'DESC');
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Get nearby locations for a given coordinate
     * @param float $latitude
     * @param float $longitude
     * @param int $branch_id
     * @param float $max_distance Maximum distance in meters
     * @return array
     */
    public function getNearbyLocations($latitude, $longitude, $branch_id, $max_distance = 1000) {
        $this->db->select('*, (
            6371000 * acos(
                cos(radians(' . $latitude . ')) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(' . $longitude . ')) +
                sin(radians(' . $latitude . ')) *
                sin(radians(latitude))
            )
        ) AS distance');
        
        $this->db->from('attendance_locations');
        $this->db->where('branch_id', $branch_id);
        $this->db->where('is_active', 1);
        $this->db->having('distance <=', $max_distance);
        $this->db->order_by('distance', 'ASC');
        
        return $this->db->get()->result_array();
    }
}
