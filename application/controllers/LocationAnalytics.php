<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Location Analytics Controller
 * Comprehensive analytics for location-based attendance
 */
class LocationAnalytics extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->helper('general');
        $this->load->model('location_attendance_model');
    }

    /**
     * Display the analytics dashboard
     */
    public function index() {
        $this->load->view('location_analytics/index');
    }

    /**
     * Get comprehensive attendance analytics with location data
     */
    public function getComprehensiveAnalytics() {
        header('Content-Type: application/json');
        
        try {
            $branch_id = $this->session->userdata('branch_id');
            if (empty($branch_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Branch ID not found'
                ]);
                return;
            }
            
            $start_date = $this->input->get('start_date') ?: date('Y-m-01');
            $end_date = $this->input->get('end_date') ?: date('Y-m-d');
            $staff_id = $this->input->get('staff_id');
            
            $analytics = [
                'summary' => $this->getAttendanceSummary($branch_id, $start_date, $end_date, $staff_id),
                'location_data' => $this->getLocationAnalytics($branch_id, $start_date, $end_date, $staff_id),
                'face_recognition_data' => $this->getFaceRecognitionAnalytics($branch_id, $start_date, $end_date, $staff_id),
                'gps_analytics' => $this->getGPSAnalytics($branch_id, $start_date, $end_date, $staff_id),
                'time_analytics' => $this->getTimeAnalytics($branch_id, $start_date, $end_date, $staff_id),
                'daily_breakdown' => $this->getDailyBreakdown($branch_id, $start_date, $end_date, $staff_id)
            ];
            
            echo json_encode([
                'status' => 'success',
                'data' => $analytics,
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to fetch analytics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get attendance summary with location verification
     */
    private function getAttendanceSummary($branch_id, $start_date, $end_date, $staff_id = null) {
        $this->db->select('
            COUNT(*) as total_records,
            SUM(CASE WHEN status = "P" THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = "A" THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN location_id IS NOT NULL THEN 1 ELSE 0 END) as location_verified_count,
            SUM(CASE WHEN user_latitude IS NOT NULL AND user_longitude IS NOT NULL THEN 1 ELSE 0 END) as gps_verified_count,
            SUM(CASE WHEN remark LIKE "%Face recognition%" THEN 1 ELSE 0 END) as face_verified_count
        ');
        
        $this->db->from('staff_attendance');
        $this->db->where('branch_id', $branch_id);
        $this->db->where('date >=', $start_date);
        $this->db->where('date <=', $end_date);
        
        if ($staff_id) {
            $this->db->where('staff_id', $staff_id);
        }
        
        $result = $this->db->get()->row();
        
        return [
            'total_attendance_records' => $result->total_records,
            'present_count' => $result->present_count,
            'absent_count' => $result->absent_count,
            'location_verified_count' => $result->location_verified_count,
            'gps_verified_count' => $result->gps_verified_count,
            'face_verified_count' => $result->face_verified_count,
            'location_verification_rate' => $result->total_records > 0 ? round(($result->location_verified_count / $result->total_records) * 100, 2) : 0,
            'gps_verification_rate' => $result->total_records > 0 ? round(($result->gps_verified_count / $result->total_records) * 100, 2) : 0,
            'face_verification_rate' => $result->total_records > 0 ? round(($result->face_verified_count / $result->total_records) * 100, 2) : 0
        ];
    }

    /**
     * Get location-based analytics
     */
    private function getLocationAnalytics($branch_id, $start_date, $end_date, $staff_id = null) {
        $this->db->select('
            al.name as location_name,
            al.latitude as school_latitude,
            al.longitude as school_longitude,
            al.radius as allowed_radius,
            COUNT(sa.id) as attendance_count,
            AVG(sa.user_latitude) as avg_user_latitude,
            AVG(sa.user_longitude) as avg_user_longitude,
            AVG(
                6371000 * acos(
                    cos(radians(al.latitude)) * 
                    cos(radians(sa.user_latitude)) * 
                    cos(radians(sa.user_longitude) - radians(al.longitude)) + 
                    sin(radians(al.latitude)) * 
                    sin(radians(sa.user_latitude))
                )
            ) as avg_distance_from_school
        ');
        
        $this->db->from('staff_attendance sa');
        $this->db->join('attendance_locations al', 'sa.location_id = al.id', 'left');
        $this->db->where('sa.branch_id', $branch_id);
        $this->db->where('sa.date >=', $start_date);
        $this->db->where('sa.date <=', $end_date);
        $this->db->where('sa.location_id IS NOT NULL');
        
        if ($staff_id) {
            $this->db->where('sa.staff_id', $staff_id);
        }
        
        $this->db->group_by('al.id');
        $this->db->order_by('attendance_count', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get face recognition analytics
     */
    private function getFaceRecognitionAnalytics($branch_id, $start_date, $end_date, $staff_id = null) {
        $this->db->select('
            s.name as staff_name,
            s.email,
            COUNT(sa.id) as total_attendance,
            SUM(CASE WHEN sa.remark LIKE "%Face recognition%" THEN 1 ELSE 0 END) as face_verified_count,
            AVG(CASE 
                WHEN sa.remark LIKE "%Face recognition%" THEN 
                    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(sa.remark, "similarity:", -1), "%", 1) AS DECIMAL(5,2))
                ELSE NULL 
            END) as avg_face_similarity
        ');
        
        $this->db->from('staff_attendance sa');
        $this->db->join('staff s', 's.id = sa.staff_id');
        $this->db->where('sa.branch_id', $branch_id);
        $this->db->where('sa.date >=', $start_date);
        $this->db->where('sa.date <=', $end_date);
        
        if ($staff_id) {
            $this->db->where('sa.staff_id', $staff_id);
        }
        
        $this->db->group_by('sa.staff_id');
        $this->db->order_by('face_verified_count', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get GPS analytics
     */
    private function getGPSAnalytics($branch_id, $start_date, $end_date, $staff_id = null) {
        $this->db->select('
            s.name as staff_name,
            COUNT(sa.id) as total_records,
            COUNT(sa.user_latitude) as gps_records,
            AVG(sa.user_latitude) as avg_latitude,
            AVG(sa.user_longitude) as avg_longitude,
            MIN(sa.user_latitude) as min_latitude,
            MAX(sa.user_latitude) as max_latitude,
            MIN(sa.user_longitude) as min_longitude,
            MAX(sa.user_longitude) as max_longitude
        ');
        
        $this->db->from('staff_attendance sa');
        $this->db->join('staff s', 's.id = sa.staff_id');
        $this->db->where('sa.branch_id', $branch_id);
        $this->db->where('sa.date >=', $start_date);
        $this->db->where('sa.date <=', $end_date);
        $this->db->where('sa.user_latitude IS NOT NULL');
        $this->db->where('sa.user_longitude IS NOT NULL');
        
        if ($staff_id) {
            $this->db->where('sa.staff_id', $staff_id);
        }
        
        $this->db->group_by('sa.staff_id');
        
        return $this->db->get()->result_array();
    }

    /**
     * Get time-based analytics
     */
    private function getTimeAnalytics($branch_id, $start_date, $end_date, $staff_id = null) {
        $this->db->select('
            s.name as staff_name,
            AVG(TIME_TO_SEC(sa.in_time)) as avg_check_in_time_seconds,
            AVG(TIME_TO_SEC(sa.out_time)) as avg_check_out_time_seconds,
            MIN(sa.in_time) as earliest_check_in,
            MAX(sa.in_time) as latest_check_in,
            MIN(sa.out_time) as earliest_check_out,
            MAX(sa.out_time) as latest_check_out
        ');
        
        $this->db->from('staff_attendance sa');
        $this->db->join('staff s', 's.id = sa.staff_id');
        $this->db->where('sa.branch_id', $branch_id);
        $this->db->where('sa.date >=', $start_date);
        $this->db->where('sa.date <=', $end_date);
        $this->db->where('sa.in_time IS NOT NULL');
        
        if ($staff_id) {
            $this->db->where('sa.staff_id', $staff_id);
        }
        
        $this->db->group_by('sa.staff_id');
        
        $results = $this->db->get()->result_array();
        
        // Convert seconds back to time format
        foreach ($results as &$result) {
            if ($result['avg_check_in_time_seconds']) {
                $result['avg_check_in_time'] = gmdate('H:i:s', $result['avg_check_in_time_seconds']);
            }
            if ($result['avg_check_out_time_seconds']) {
                $result['avg_check_out_time'] = gmdate('H:i:s', $result['avg_check_out_time_seconds']);
            }
        }
        
        return $results;
    }

    /**
     * Get daily breakdown with all verification data
     */
    private function getDailyBreakdown($branch_id, $start_date, $end_date, $staff_id = null) {
        $this->db->select('
            sa.date,
            s.name as staff_name,
            sa.status,
            sa.in_time,
            sa.out_time,
            sa.remark,
            al.name as location_name,
            al.latitude as school_latitude,
            al.longitude as school_longitude,
            sa.user_latitude,
            sa.user_longitude,
            CASE 
                WHEN sa.user_latitude IS NOT NULL AND sa.user_longitude IS NOT NULL AND al.latitude IS NOT NULL AND al.longitude IS NOT NULL THEN
                    6371000 * acos(
                        cos(radians(al.latitude)) * 
                        cos(radians(sa.user_latitude)) * 
                        cos(radians(sa.user_longitude) - radians(al.longitude)) + 
                        sin(radians(al.latitude)) * 
                        sin(radians(sa.user_latitude))
                    )
                ELSE NULL
            END as distance_from_school,
            CASE 
                WHEN sa.remark LIKE "%Face recognition%" THEN 1 
                ELSE 0 
            END as face_verified,
            CASE 
                WHEN sa.location_id IS NOT NULL THEN 1 
                ELSE 0 
            END as location_verified
        ');
        
        $this->db->from('staff_attendance sa');
        $this->db->join('staff s', 's.id = sa.staff_id');
        $this->db->join('attendance_locations al', 'sa.location_id = al.id', 'left');
        $this->db->where('sa.branch_id', $branch_id);
        $this->db->where('sa.date >=', $start_date);
        $this->db->where('sa.date <=', $end_date);
        
        if ($staff_id) {
            $this->db->where('sa.staff_id', $staff_id);
        }
        
        $this->db->order_by('sa.date', 'DESC');
        $this->db->order_by('sa.in_time', 'DESC');
        
        return $this->db->get()->result_array();
    }

    /**
     * Export comprehensive analytics to CSV
     */
    public function exportAnalytics() {
        $branch_id = $this->session->userdata('branch_id');
        $start_date = $this->input->get('start_date') ?: date('Y-m-01');
        $end_date = $this->input->get('end_date') ?: date('Y-m-d');
        $staff_id = $this->input->get('staff_id');
        
        $daily_breakdown = $this->getDailyBreakdown($branch_id, $start_date, $end_date, $staff_id);
        
        $filename = 'location_analytics_' . $start_date . '_to_' . $end_date . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'Date',
            'Staff Name',
            'Status',
            'Check In Time',
            'Check Out Time',
            'Remark',
            'Location Name',
            'School Latitude',
            'School Longitude',
            'User Latitude',
            'User Longitude',
            'Distance from School (meters)',
            'Face Verified',
            'Location Verified'
        ]);
        
        // CSV Data
        foreach ($daily_breakdown as $row) {
            fputcsv($output, [
                $row['date'],
                $row['staff_name'],
                $row['status'],
                $row['in_time'],
                $row['out_time'],
                $row['remark'],
                $row['location_name'],
                $row['school_latitude'],
                $row['school_longitude'],
                $row['user_latitude'],
                $row['user_longitude'],
                $row['distance_from_school'] ? round($row['distance_from_school'], 2) : '',
                $row['face_verified'] ? 'Yes' : 'No',
                $row['location_verified'] ? 'Yes' : 'No'
            ]);
        }
        
        fclose($output);
    }
}
