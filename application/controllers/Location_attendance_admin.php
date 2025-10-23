<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Location_attendance_admin extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('location_attendance_model');
    }
    
    /**
     * Display the location attendance management page
     */
    public function index() {
        $this->data['title'] = 'Location-Based Attendance Management';
        $this->data['sub_page'] = 'location_attendance/index';
        $this->data['main_menu'] = 'location_attendance';
        
        // Get current branch ID
        $this->data['branch_id'] = $this->session->userdata('branch_id');
        
        $this->load->view('layout/index', $this->data);
    }
    
    /**
     * Get locations for DataTable
     */
    public function getLocationsDataTable() {
        header('Content-Type: application/json');
        
        try {
            $branch_id = $this->session->userdata('branch_id');
            if (empty($branch_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Branch ID not found'
                ]);
                exit;
            }
            
            $locations = $this->location_attendance_model->getAllBranchLocations($branch_id);
            
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
     * Get location statistics
     */
    public function getLocationStatistics() {
        header('Content-Type: application/json');
        
        try {
            $branch_id = $this->session->userdata('branch_id');
            if (empty($branch_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Branch ID not found'
                ]);
                exit;
            }
            
            $start_date = $this->input->get('start_date') ?: date('Y-m-01');
            $end_date = $this->input->get('end_date') ?: date('Y-m-d');
            
            $statistics = $this->location_attendance_model->getLocationStatistics(
                $branch_id, 
                $start_date, 
                $end_date
            );
            
            echo json_encode([
                'status' => 'success',
                'data' => $statistics
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ]);
        }
    }
}
