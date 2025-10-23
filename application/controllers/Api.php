<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @package : Ramom School Management System
 * @version : 6.5
 * @developed by : RamomCoder
 * @support : ramomcoder@yahoo.com
 * @author url : http://codecanyon.net/user/RamomCoder
 * @filename : Api.php
 */

class Api extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('authentication_model');
        $this->load->model('employee_model');
        // Disable CSRF protection for API endpoints
        $this->config->set_item('csrf_protection', false);
        // Set content type to JSON
        header('Content-Type: application/json');
        // CORS headers for local frontend
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $allowed_origins = $this->config->item('allowed_frontend_origins');
        if (is_array($allowed_origins) && in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
        }
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
    
    /**
     * API: V1 Auth Login (Generic login endpoint)
     * Authenticates user login credentials - compatible with v1 API structure
     */
    public function authLogin()
    {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $username = $data['username'] ?? $this->input->post('username');
        $password = $data['password'] ?? $this->input->post('password');
        
        if (empty($username) || empty($password)) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Username and password are required'
            );
            http_response_code(400);
            echo json_encode($response);
            return;
        }
        
        try {
            // Load authentication model
            $this->load->model('authentication_model');
            
            // Check login credentials
            $login_data = $this->authentication_model->login_credential($username, $password);
            
            if ($login_data === false) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Invalid username or password'
                );
                http_response_code(401);
                echo json_encode($response);
                return;
            }
            
            // Get user details based on role
            if ($login_data->role == 3) {
                // Teacher
                $user_data = $this->db->select('id, name, email, mobileno, photo, branch_id')
                                   ->get_where('staff', array('id' => $login_data->user_id))
                                   ->row();
            } else {
                // Other roles - get from login_credential table
                $user_data = $this->db->select('user_id as id, username as name')
                                   ->get_where('login_credential', array('id' => $login_data->id))
                                   ->row();
            }
            
            if (empty($user_data)) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'User data not found'
                );
                http_response_code(404);
                echo json_encode($response);
                return;
            }
            
            // Check face enrollment metadata (do not expose embeddings)
            $face_row = null;
            try {
                if ($this->db->table_exists('staff_face')) {
                    $face_row = $this->db->get_where('staff_face', array('staff_id' => $login_data->user_id))->row();
                }
            } catch (Exception $e) {
                // ignore
            }

            // SET SESSION DATA FOR AUTHENTICATION
            $sessionData = array(
                'loggedin' => true,
                'loggedin_id' => $login_data->id,
                'loggedin_userid' => $login_data->user_id,
                'loggedin_role_id' => $login_data->role,
                'loggedin_branch' => isset($user_data->branch_id) ? $user_data->branch_id : null,
                'loggedin_type' => 'api_user',
                'set_session_id' => get_session_id(),
                'set_lang' => 'english'
            );
            $this->authentication_model->sessionSet($sessionData);
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'id' => $user_data->id,
                    'name' => $user_data->name,
                    'email' => isset($user_data->email) ? $user_data->email : '',
                    'mobile_no' => isset($user_data->mobileno) ? $user_data->mobileno : '',
                    'photo' => isset($user_data->photo) ? $user_data->photo : '',
                    'role' => $login_data->role,
                    'branch_id' => isset($user_data->branch_id) ? $user_data->branch_id : null,
                    // Face enrollment metadata for UI (embedding not exposed)
                    'face_enrolled' => $face_row ? true : false,
                    // Treat as verified for UI badge if an enrollment exists
                    'face_verified' => $face_row ? true : false,
                    'face_model' => $face_row && isset($face_row->model_name) ? $face_row->model_name : null,
                    'face_enrolled_at' => $face_row && isset($face_row->updated_at) ? $face_row->updated_at : ($face_row && isset($face_row->enrolled_at) ? $face_row->enrolled_at : null)
                ),
                'message' => 'Login successful'
            );
            http_response_code(200);
            echo json_encode($response);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Login failed: ' . $e->getMessage()
            );
            http_response_code(500);
            echo json_encode($response);
        }
    }

    /**
     * API: Role 3 (Teacher) Login
     * Authenticates teacher login credentials
     */
    public function teacherLogin()
    {
        header('Content-Type: application/json');
        
        $username = $this->input->post('username');
        $password = $this->input->post('password');
        
        if (empty($username) || empty($password)) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Username and password are required'
            );
            http_response_code(400);
            echo json_encode($response);
            return;
        }
        
        try {
            // Load authentication model
            $this->load->model('authentication_model');
            
            // Check login credentials
            $login_data = $this->authentication_model->login_credential($username, $password);
            
            if ($login_data === false) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Invalid username or password'
                );
                http_response_code(401);
                echo json_encode($response);
                return;
            }
            
            // Check if user has role 3 (teacher)
            if ($login_data->role != 3) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'User is not authorized as a teacher'
                );
                http_response_code(403);
                echo json_encode($response);
                return;
            }
            
            // Get teacher details
            $teacher_data = $this->db->select('id, name, email, mobileno, photo, branch_id')
                                   ->get_where('staff', array('id' => $login_data->user_id))
                                   ->row();
            
            if (empty($teacher_data)) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Teacher data not found'
                );
                http_response_code(404);
                echo json_encode($response);
                return;
            }
            
            // Check if face is enrolled
            $face_row = $this->db->get_where('staff_face', array('staff_id' => $login_data->user_id))->row();
            
            // SET SESSION DATA FOR AUTHENTICATION
            $sessionData = array(
                'loggedin' => true,
                'loggedin_id' => $login_data->id,
                'loggedin_userid' => $login_data->user_id,
                'loggedin_role_id' => $login_data->role,
                'loggedin_branch' => $teacher_data->branch_id,
                'loggedin_type' => 'teacher',
                'set_session_id' => get_session_id(),
                'set_lang' => 'english'
            );
            $this->authentication_model->sessionSet($sessionData);
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'user_id' => $login_data->user_id,
                    'username' => $login_data->username,
                    'role' => $login_data->role,
                    'teacher_id' => $teacher_data->id,
                    'name' => $teacher_data->name,
                    'email' => $teacher_data->email,
                    'mobile' => $teacher_data->mobileno,
                    'photo' => $teacher_data->photo,
                    'branch_id' => $teacher_data->branch_id,
                    'last_login' => $login_data->last_login,
                    'face_enrolled' => $face_row ? true : false
                ),
                'message' => 'Teacher login successful'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Login failed: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Attendance for Role 3 (Teacher)
     * Gets or updates attendance records
     */
    public function teacherAttendance()
    {
        header('Content-Type: application/json');
        
        // For public access, we need to check authentication manually
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        $action = $this->input->post('action');
        $teacher_id = get_loggedin_user_id();
        
        try {
            if ($action == 'get') {
                // Get attendance data
                $date = $this->input->post('date');
                $class_id = $this->input->post('class_id');
                $section_id = $this->input->post('section_id');
                $branch_id = $this->application_model->get_branch_id();
                
                if (empty($date) || empty($class_id) || empty($section_id)) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Date, class_id and section_id are required'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
                // Load attendance model
                $this->load->model('attendance_model');
                
                // Get student attendance
                $attendance_data = $this->attendance_model->getStudentAttendence($class_id, $section_id, $date, $branch_id);
                
                $response = array(
                    'status' => 'success',
                    'data' => array(
                        'date' => $date,
                        'class_id' => $class_id,
                        'section_id' => $section_id,
                        'attendance' => $attendance_data
                    ),
                    'message' => 'Attendance data retrieved successfully'
                );
                http_response_code(200);
                
            } elseif ($action == 'save') {
                // Save attendance data
                $attendance_data = $this->input->post('attendance_data');
                $date = $this->input->post('date');
                $class_id = $this->input->post('class_id');
                $section_id = $this->input->post('section_id');
                
                if (empty($attendance_data) || empty($date) || empty($class_id) || empty($section_id)) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Attendance data, date, class_id and section_id are required'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
                // Process attendance data
                $saved_count = 0;
                foreach ($attendance_data as $student_id => $status) {
                    // Check if attendance record already exists
                    $this->db->select('id');
                    $this->db->from('student_attendance');
                    $this->db->where('enroll_id', $student_id);
                    $this->db->where('date', $date);
                    $query = $this->db->get();
                    
                    $attendance_record = array(
                        'enroll_id' => $student_id,
                        'date' => $date,
                        'status' => $status['status'],
                        'remark' => isset($status['remark']) ? $status['remark'] : ''
                    );
                    
                    if ($query->num_rows() > 0) {
                        // Update existing record
                        $this->db->where('id', $query->row()->id);
                        $this->db->update('student_attendance', $attendance_record);
                    } else {
                        // Insert new record
                        $this->db->insert('student_attendance', $attendance_record);
                    }
                    $saved_count++;
                }
                
                $response = array(
                    'status' => 'success',
                    'data' => array(
                        'saved_records' => $saved_count
                    ),
                    'message' => 'Attendance data saved successfully'
                );
                http_response_code(200);
                
            } else {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Invalid action. Use "get" or "save"'
                );
                http_response_code(400);
            }
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Attendance operation failed: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Teacher Self Attendance (Present/Absent) with Conditional Times
     * Allows teachers to mark their own attendance with in_time for Present and out_time for Absent
     */
    public function teacherSelfAttendance()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        $teacher_id = get_loggedin_user_id();
        $branch_id = $this->application_model->get_branch_id(); // Get branch ID
        $action = $this->input->post('action'); // 'mark' or 'get'
        
        try {
            if ($action == 'mark') {
                // Mark teacher attendance
                $status = $this->input->post('status'); // 'P' for present, 'A' for absent
                $date = $this->input->post('date') ? $this->input->post('date') : date('Y-m-d');
                $remark = $this->input->post('remark') ? $this->input->post('remark') : '';
                
                // Get current time for automatic time setting
                $current_time = date('H:i:s');
                
                if (empty($status) || !in_array($status, array('P', 'A'))) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Status is required and must be either "P" (Present) or "A" (Absent)'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
                // Check if attendance record already exists for this teacher on this date
                $this->db->select('id');
                $this->db->from('staff_attendance');
                $this->db->where('staff_id', $teacher_id);
                $this->db->where('date', $date);
                $query = $this->db->get();
                
                // Set time based on status
                $attendance_record = array(
                    'staff_id' => $teacher_id,
                    'branch_id' => $branch_id, // Add branch_id to the record
                    'date' => $date,
                    'status' => $status,
                    'remark' => $remark
                );
                
                // Add time based on status
                if ($status == 'P') {
                    // For Present status, set in_time
                    $attendance_record['in_time'] = $current_time;
                } else if ($status == 'A') {
                    // For Absent status, set out_time
                    $attendance_record['out_time'] = $current_time;
                }
                
                if ($query->num_rows() > 0) {
                    // Update existing record
                    $this->db->where('id', $query->row()->id);
                    $this->db->update('staff_attendance', $attendance_record);
                    $message = 'Attendance updated successfully';
                } else {
                    // Insert new record
                    $this->db->insert('staff_attendance', $attendance_record);
                    $message = 'Attendance marked successfully';
                }
                
                $response = array(
                    'status' => 'success',
                    'data' => array(
                        'teacher_id' => $teacher_id,
                        'branch_id' => $branch_id,
                        'date' => $date,
                        'status' => $status,
                        'in_time' => isset($attendance_record['in_time']) ? $attendance_record['in_time'] : null,
                        'out_time' => isset($attendance_record['out_time']) ? $attendance_record['out_time'] : null,
                        'remark' => $remark
                    ),
                    'message' => $message
                );
                http_response_code(200);
                
            } elseif ($action == 'get') {
                // Get teacher's attendance records
                $date = $this->input->post('date');
                $start_date = $this->input->post('start_date');
                $end_date = $this->input->post('end_date');
                
                $this->db->select('date, status, in_time, out_time, remark');
                $this->db->from('staff_attendance');
                $this->db->where('staff_id', $teacher_id);
                $this->db->where('branch_id', $branch_id); // Filter by branch_id
                
                if (!empty($date)) {
                    // Get specific date
                    $this->db->where('date', $date);
                } elseif (!empty($start_date) && !empty($end_date)) {
                    // Get date range
                    $this->db->where('date >=', $start_date);
                    $this->db->where('date <=', $end_date);
                } else {
                    // Get today's attendance by default
                    $this->db->where('date', date('Y-m-d'));
                }
                
                $this->db->order_by('date', 'DESC');
                $query = $this->db->get();
                $attendance_records = $query->result_array();
                
                $response = array(
                    'status' => 'success',
                    'data' => array(
                        'teacher_id' => $teacher_id,
                        'branch_id' => $branch_id,
                        'records' => $attendance_records
                    ),
                    'message' => 'Attendance records retrieved successfully'
                );
                http_response_code(200);
                
            } else {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Invalid action. Use "mark" or "get"'
                );
                http_response_code(400);
            }
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Attendance operation failed: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Get Present Days Count for Teacher
     * Returns the count of days the teacher was marked as Present
     */
    public function teacherPresentDaysCount()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        $teacher_id = get_loggedin_user_id();
        
        try {
            // Build query to count present days
            $this->db->from('staff_attendance');
            $this->db->where('staff_id', $teacher_id);
            $this->db->where('status', 'P');
            
            $present_count = $this->db->count_all_results();
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'teacher_id' => $teacher_id,
                    'present_days_count' => $present_count,
                ),
                'message' => 'Present days count retrieved successfully'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve present days count: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Get Absent Days Count for Teacher
     * Returns the count of days the teacher was marked as Absent
     */
    public function teacherAbsentDaysCount()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        $teacher_id = get_loggedin_user_id();
        
        try {
            // Build query to count absent days
            $this->db->from('staff_attendance');
            $this->db->where('staff_id', $teacher_id);
            $this->db->where('status', 'A'); // Only count Absent days
            
            
            $absent_count = $this->db->count_all_results();
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'teacher_id' => $teacher_id,
                    'absent_days_count' => $absent_count,
                ),
                'message' => 'Absent days count retrieved successfully'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve absent days count: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Get Monthly Present Count for Teacher
     * Returns the count of present days for the teacher month by month
     */
    public function teacherMonthlyPresentCount()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        $teacher_id = get_loggedin_user_id();
        $year = $this->input->post('year') ? $this->input->post('year') : date('Y');
        
        try {
            // Get present count for each month
            $monthly_data = array();
            
            for ($month = 1; $month <= 12; $month++) {
                // Format month with leading zero
                $month_formatted = str_pad($month, 2, '0', STR_PAD_LEFT);
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                
                // Build query to count present days for the month
                $this->db->from('staff_attendance');
                $this->db->where('staff_id', $teacher_id);
                $this->db->where('status', 'P');
                $this->db->where('date >=', "$year-$month_formatted-01");
                $this->db->where('date <=', "$year-$month_formatted-$days_in_month");
                
                $present_count = $this->db->count_all_results();
                
                $monthly_data[] = array(
                    'month' => $month,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 10)),
                    'year' => $year,
                    'present_count' => $present_count
                );
            }
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'teacher_id' => $teacher_id,
                    'year' => $year,
                    'monthly_data' => $monthly_data
                ),
                'message' => 'Monthly present count retrieved successfully'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve monthly present count: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Get Monthly Absent Count for Teacher
     * Returns the count of absent days for the teacher month by month
     */
    public function teacherMonthlyAbsentCount()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        $teacher_id = get_loggedin_user_id();
        $year = $this->input->post('year') ? $this->input->post('year') : date('Y');
        
        try {
            // Get absent count for each month
            $monthly_data = array();
            
            for ($month = 1; $month <= 12; $month++) {
                // Format month with leading zero
                $month_formatted = str_pad($month, 2, '0', STR_PAD_LEFT);
                $days_in_month = cal_days_in_month(CAL_GREGORIAN, $month, $year);
                
                // Build query to count absent days for the month
                $this->db->from('staff_attendance');
                $this->db->where('staff_id', $teacher_id);
                $this->db->where('status', 'A');
                $this->db->where('date >=', "$year-$month_formatted-01");
                $this->db->where('date <=', "$year-$month_formatted-$days_in_month");
                
                $absent_count = $this->db->count_all_results();
                
                $monthly_data[] = array(
                    'month' => $month,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 10)),
                    'year' => $year,
                    'absent_count' => $absent_count
                );
            }
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'teacher_id' => $teacher_id,
                    'year' => $year,
                    'monthly_data' => $monthly_data
                ),
                'message' => 'Monthly absent count retrieved successfully'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve monthly absent count: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Role 3 (Teacher) Profile
     * Gets teacher profile information
     */
    public function teacherProfile()
    {
        header('Content-Type: application/json');
        
        // For public access, we need to check authentication manually
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        try {
            $teacher_id = get_loggedin_user_id();
            
            // Load employee model
            $this->load->model('employee_model');
            
            // Get teacher profile data
            $profile_data = $this->employee_model->getSingleStaff($teacher_id);
            
            if (empty($profile_data)) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Teacher profile not found'
                );
                http_response_code(404);
                echo json_encode($response);
                return;
            }
            
            // Determine if face is enrolled (hidden backend-only storage)
            $face_row = $this->db->get_where('staff_face', array('staff_id' => $teacher_id))->row();

            // Format the response data
            $formatted_data = array(
                'id' => $profile_data['id'],
                'staff_id' => $profile_data['staff_id'],
                'name' => $profile_data['name'],
                'email' => $profile_data['email'],
                'mobile_no' => $profile_data['mobileno'],
                'sex' => $profile_data['sex'],
                'religion' => $profile_data['religion'],
                'blood_group' => $profile_data['blood_group'],
                'birthday' => $profile_data['birthday'],
                'present_address' => $profile_data['present_address'],
                'permanent_address' => $profile_data['permanent_address'],
                'photo' => $profile_data['photo'],
                'designation' => $profile_data['designation_name'],
                'department' => $profile_data['department_name'],
                'joining_date' => $profile_data['joining_date'],
                'qualification' => $profile_data['qualification'],
                'experience_details' => $profile_data['experience_details'],
                'total_experience' => $profile_data['total_experience'],
                'facebook_url' => $profile_data['facebook_url'],
                'linkedin_url' => $profile_data['linkedin_url'],
                'twitter_url' => $profile_data['twitter_url'],
                'username' => $profile_data['username'],
                'role' => $profile_data['role'],
                'active' => $profile_data['active'] ? true : false,
                // Hidden flag for UI gating; embedding never exposed here
                'face_enrolled' => $face_row ? true : false
            );
            
            $response = array(
                'status' => 'success',
                'data' => $formatted_data,
                'message' => 'Teacher profile retrieved successfully'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve teacher profile: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Update Teacher Profile (Role 3)
     * Allows an authenticated teacher to update limited profile fields
     */
    public function updateTeacherProfile()
    {
        header('Content-Type: application/json');

        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }

        try {
            $teacher_id = get_loggedin_user_id();

            $raw = file_get_contents('php://input');
            $json = json_decode($raw, true);

            $name = $json['name'] ?? $this->input->post('name');
            $email = $json['email'] ?? $this->input->post('email');
            $mobile = $json['mobile'] ?? $this->input->post('mobile');
            $present_address = $json['present_address'] ?? $this->input->post('present_address');
            $permanent_address = $json['permanent_address'] ?? $this->input->post('permanent_address');

            $updates = array();
            if (!empty($name)) { $updates['name'] = trim($name); }
            if (!empty($email)) { $updates['email'] = trim($email); }
            if (!empty($mobile)) { $updates['mobileno'] = trim($mobile); }
            if (!empty($present_address)) { $updates['present_address'] = trim($present_address); }
            if (!empty($permanent_address)) { $updates['permanent_address'] = trim($permanent_address); }

            if (empty($updates)) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'No valid fields to update'
                );
                http_response_code(400);
                echo json_encode($response);
                return;
            }

            $exists = $this->db->select('id')->get_where('staff', array('id' => $teacher_id))->row();
            if (!$exists) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Teacher not found'
                );
                http_response_code(404);
                echo json_encode($response);
                return;
            }

            $this->db->where('id', $teacher_id);
            $this->db->update('staff', $updates);

            $this->load->model('employee_model');
            $profile_data = $this->employee_model->getSingleStaff($teacher_id);

            $response = array(
                'status' => 'success',
                'data' => array(
                    'id' => $profile_data['id'],
                    'name' => $profile_data['name'],
                    'email' => $profile_data['email'],
                    'mobile_no' => $profile_data['mobileno'],
                    'present_address' => $profile_data['present_address'],
                    'permanent_address' => $profile_data['permanent_address'],
                ),
                'message' => 'Profile updated successfully'
            );
            http_response_code(200);

        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to update profile: ' . $e->getMessage()
            );
            http_response_code(500);
        }

        echo json_encode($response);
    }

    /**
     * API: Get School Name and URL from Custom Domain
     * Retrieves school name and URL information from the custom_domain table
     */
    public function getSchoolInfo()
    {
        header('Content-Type: application/json');
        
        try {
            // Check if custom_domain table exists
            if (!$this->db->table_exists('custom_domain')) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Custom domain table does not exist'
                );
                http_response_code(404);
                echo json_encode($response);
                return;
            }
            
            // Query the custom_domain table to get all approved domains
            $this->db->select('cd.school_id, cd.url, b.name as school_name');
            $this->db->from('custom_domain as cd');
            $this->db->join('branch as b', 'b.id = cd.school_id', 'inner');
            $this->db->where('cd.status', 1); // Only approved domains
            $query = $this->db->get();
            
            if ($query->num_rows() > 0) {
                $results = $query->result();
                
                // Format the data
                $schools = array();
                foreach ($results as $row) {
                    $schools[] = array(
                        'school_id' => $row->school_id,
                        'school_name' => $row->school_name,
                        'url' => $row->url
                    );
                }
                
                $response = array(
                    'status' => 'success',
                    'data' => $schools,
                    'message' => 'School information retrieved successfully'
                );
                http_response_code(200);
            } else {
                $response = array(
                    'status' => 'success',
                    'data' => array(),
                    'message' => 'No approved schools found in custom domain table'
                );
                http_response_code(200);
            }
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve school information: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Get Class List
     * Retrieves list of classes for the current branch
     */
    public function getClassList()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        try {
            $branch_id = $this->application_model->get_branch_id();
            
            // Load App_lib to get class list
            $this->load->library('app_lib');
            
            // Get class list for the branch
            $classList = $this->app_lib->getClass($branch_id, false);
            
            if (!empty($classList)) {
                $classes = array();
                foreach ($classList as $class_id => $class_name) {
                    $classes[] = array(
                        'class_id' => $class_id,
                        'class_name' => $class_name
                    );
                }
                
                $response = array(
                    'status' => 'success',
                    'data' => $classes,
                    'message' => 'Class list retrieved successfully'
                );
                http_response_code(200);
            } else {
                $response = array(
                    'status' => 'success',
                    'data' => array(),
                    'message' => 'No classes found for this branch'
                );
                http_response_code(200);
            }
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve class list: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Get Section List by Class
     * Retrieves list of sections for a given class
     */
    public function getSectionListByClass()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        $class_id = $this->input->post('class_id');
        
        if (empty($class_id)) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Class ID is required'
            );
            http_response_code(400);
            echo json_encode($response);
            return;
        }
        
        try {
            // Load App_lib to get section list
            $this->load->library('app_lib');
            
            // Get section list for the class
            $sectionList = $this->app_lib->getSections($class_id, false, false);
            
            if (!empty($sectionList)) {
                $sections = array();
                foreach ($sectionList as $section_id => $section_name) {
                    // Skip the empty option
                    if (!empty($section_id)) {
                        $sections[] = array(
                            'section_id' => $section_id,
                            'section_name' => $section_name
                        );
                    }
                }
                
                $response = array(
                    'status' => 'success',
                    'data' => $sections,
                    'message' => 'Section list retrieved successfully'
                );
                http_response_code(200);
            } else {
                $response = array(
                    'status' => 'success',
                    'data' => array(),
                    'message' => 'No sections found for this class'
                );
                http_response_code(200);
            }
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve section list: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Get Student List by Class, Section, and Date
     * Retrieves list of students for a given class, section, and date
     */
    public function getStudentList()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        $class_id = $this->input->post('class_id');
        $section_id = $this->input->post('section_id');
        $date = $this->input->post('date');
        
        // Validate required parameters
        if (empty($class_id) || empty($section_id)) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Class ID and Section ID are required'
            );
            http_response_code(400);
            echo json_encode($response);
            return;
        }
        
        // If date is not provided, use today's date
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        
        try {
            $branch_id = $this->application_model->get_branch_id();
            
            // Load Multiclass_model to get student list
            $this->load->model('multiclass_model');
            
            // Get student list for the class and section
            $studentList = $this->multiclass_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
            
            if (!empty($studentList)) {
                // Add attendance status for each student
                $students = array();
                foreach ($studentList as $student) {
                    // Check if student has attendance record for the given date
                    $this->db->select('id, status, remark');
                    $this->db->from('student_attendance');
                    $this->db->where('enroll_id', $student['id']);
                    $this->db->where('date', $date);
                    $attendance_query = $this->db->get();
                    
                    $attendance_status = '';
                    $attendance_remark = '';
                    if ($attendance_query->num_rows() > 0) {
                        $attendance_record = $attendance_query->row();
                        $attendance_status = $attendance_record->status;
                        $attendance_remark = $attendance_record->remark;
                    }
                    
                    $students[] = array(
                        'enroll_id' => $student['id'],
                        'student_id' => $student['student_id'],
                        'name' => $student['fullname'],
                        'register_no' => $student['register_no'],
                        'roll' => $student['roll'],
                        'photo' => $student['photo'],
                        'gender' => $student['gender'],
                        'attendance_status' => $attendance_status,
                        'attendance_remark' => $attendance_remark
                    );
                }
                
                $response = array(
                    'status' => 'success',
                    'data' => $students,
                    'message' => 'Student list retrieved successfully'
                );
                http_response_code(200);
            } else {
                $response = array(
                    'status' => 'success',
                    'data' => array(),
                    'message' => 'No students found for this class and section'
                );
                http_response_code(200);
            }
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve student list: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Student Attendance
     * Gets or sets student attendance records
     */
    public function studentAttendance()
    {
        
        
        header('Content-Type: application/json');
    
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            exit;
        }
        
        $action = $this->input->post('action'); // get or set
        $teacher_id = get_loggedin_user_id();
        
        try {
            if ($action == 'get') {
                // Get attendance data
                $date = $this->input->post('date');
                $class_id = $this->input->post('class_id');
                $section_id = $this->input->post('section_id');
                $branch_id = $this->application_model->get_branch_id();
                
                // Date is optional, use today if not provided
                if (empty($date)) {
                    $date = date('Y-m-d');
                }
                
                if (empty($class_id) || empty($section_id)) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Class ID and Section ID are required'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    exit;
                }
                
                // Load Multiclass_model to get student list
                $this->load->model('multiclass_model');
                
                // Get student list for the class and section
                $studentList = $this->multiclass_model->getStudentListByClassSection($class_id, $section_id, $branch_id);
                
                if (!empty($studentList)) {
                    // Add attendance status for each student
                    $students = array();
                    foreach ($studentList as $student) {
                        // Check if student has attendance record for the given date
                        $this->db->select('id, status, remark');
                        $this->db->from('student_attendance');
                        $this->db->where('enroll_id', $student['id']);
                        $this->db->where('date', $date);
                        $attendance_query = $this->db->get();
                        
                        $attendance_status = '';
                        $attendance_remark = '';
                        if ($attendance_query->num_rows() > 0) {
                            $attendance_record = $attendance_query->row();
                            $attendance_status = $attendance_record->status;
                            $attendance_remark = $attendance_record->remark;
                        }
                        
                        $students[] = array(
                            'enroll_id' => $student['id'],
                            'student_id' => $student['student_id'],
                            'name' => $student['fullname'],
                            'register_no' => $student['register_no'],
                            'roll' => $student['roll'],
                            'photo' => $student['photo'],
                            'gender' => $student['gender'],
                            'attendance_status' => $attendance_status,
                            'attendance_remark' => $attendance_remark
                        );
                    }
                    
                    $response = array(
                        'status' => 'success',
                        'data' => array(
                            'date' => $date,
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'students' => $students
                        ),
                        'message' => 'Student attendance data retrieved successfully'
                    );
                    http_response_code(200);
                } else {
                    $response = array(
                        'status' => 'success',
                        'data' => array(
                            'date' => $date,
                            'class_id' => $class_id,
                            'section_id' => $section_id,
                            'students' => array()
                        ),
                        'message' => 'No students found for this class and section'
                    );
                    http_response_code(200);
                }
                
            } elseif ($action == 'set') {
                // Set attendance data for a single student
                $enroll_id = $this->input->post('enroll_id');
                $status = $this->input->post('status');
                $date = $this->input->post('date');
                $remark = $this->input->post('remark');
                
                // Date is optional, use today if not provided
                if (empty($date)) {
                    $date = date('Y-m-d');
                }
                
                // Validate required parameters
                if (empty($enroll_id) || empty($status)) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Enroll ID and Status are required'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    exit;
                }
                
                if (!in_array($status, array('P', 'A', 'H', 'L'))) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Status must be P (Present), A (Absent), H (Half Day), or L (Late)'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    exit;
                }
                
                $branch_id = $this->application_model->get_branch_id();
                
                $this->db->select('id');
                $this->db->from('student_attendance');
                $this->db->where('enroll_id', $enroll_id);
                $this->db->where('date', $date);
                $query = $this->db->get();
                
                $attendance_record = array(
                    'enroll_id' => $enroll_id,
                    'date' => $date,
                    'status' => $status,
                    'remark' => $remark,
                    'branch_id' => $branch_id,
                    'qr_code' => 0
                );
                
                if ($query->num_rows() > 0) {
                    $this->db->where('id', $query->row()->id);
                    $this->db->update('student_attendance', $attendance_record);
                    $message = 'Attendance updated successfully';
                } else {
                    $this->db->insert('student_attendance', $attendance_record);
                    $message = 'Attendance marked successfully';
                }
                
                $response = array(
                    'status' => 'success',
                    'data' => array(
                        'enroll_id' => $enroll_id,
                        'date' => $date,
                        'status' => $status,
                        'remark' => $remark
                    ),
                    'message' => $message
                );
                http_response_code(200);
                
            } else {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Invalid action. Use "get" or "set"'
                );
                http_response_code(400);
            }
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Attendance operation failed: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * API: Staff QR Code Attendance
     * Marks attendance for staff using QR code
     */
    public function staffQrAttendance()
    {
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }
        
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            exit;
        }
        
        // Get the raw input data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if (empty($data)) {
            $staff_id = $this->input->post('staff_id');
            $in_out_time = $this->input->post('in_out_time');
            $remark = $this->input->post('remark');
        } else {
            // Get from JSON data
            $staff_id = isset($data['staff_id']) ? $data['staff_id'] : '';
            $in_out_time = isset($data['in_out_time']) ? $data['in_out_time'] : '';
            $remark = isset($data['remark']) ? $data['remark'] : '';
        }
        
        // Validate required parameters
        if (empty($staff_id) || empty($in_out_time)) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Staff ID and in/out time are required'
            );
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
        
        // Validate in_out_time value
        if (!in_array($in_out_time, array('in_time', 'out_time'))) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'In/out time must be either "in_time" or "out_time"'
            );
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
        
        try {
            // Load required models
            $this->load->model('qrcode_attendance_model');
            
            // Get staff details
            $staff_detail = $this->qrcode_attendance_model->getSingleStaff($staff_id);
            if (empty($staff_detail)) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Staff not found'
                );
                http_response_code(404);
                echo json_encode($response);
                exit;
            }
            
            // Check if attendance record already exists for today
            $attendance = $this->db->where(array('staff_id' => $staff_id, 'date' => date('Y-m-d')))->get('staff_attendance')->row();
            
            // Check if attendance has already been taken
            if (!empty($attendance)) {
                if (($in_out_time == 'in_time' && !empty($attendance->in_time)) || 
                    ($in_out_time == 'out_time' && !empty($attendance->out_time))) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Attendance has already been taken for this time period'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    exit;
                }
            }
            
            // Get QR code settings for the branch
            $setting = $this->qrcode_attendance_model->getSettings(empty($staff_detail->branch_id) ? 0 : $staff_detail->branch_id);
            
            // Determine attendance status based on settings
            $attendance_status = '';
            if ($in_out_time == 'in_time') {
                if ($setting->auto_late_detect == 1) {
                    if (strtotime($setting->staff_in_time) <= time()) {
                        $attendance_status = 'L'; // Late
                    } else {
                        $attendance_status = 'P'; // Present
                    }
                } else {
                    $attendance_status = 'P'; // Present by default
                }
            } else {
                if ($setting->auto_late_detect == 1) {
                    if (strtotime($setting->staff_out_time) >= time()) {
                        $attendance_status = 'HD'; // Half Day
                    }
                }
            }
            
            if (empty($attendance)) {
                // Insert new attendance record
                $array_attendance = array(
                    'staff_id' => $staff_id,
                    'status' => $attendance_status,
                    'remark' => $remark ? $remark : '',
                    'qr_code' => 1, // Tinyint format
                    'date' => date('Y-m-d'),
                    'branch_id' => $staff_detail->branch_id,
                );
                $array_attendance[$in_out_time] = date('H:i:s');
                $this->db->insert('staff_attendance', $array_attendance);
                $message = 'Attendance marked successfully';
            } else {
                // Update existing attendance record
                $update_data = array();
                $update_data[$in_out_time] = date('H:i:s');
                if (!empty($remark)) {
                    $update_data['remark'] = $remark;
                }
                if (!empty($attendance_status)) {
                    $update_data['status'] = $attendance_status;
                }
                $this->db->where('id', $attendance->id);
                $this->db->update('staff_attendance', $update_data);
                $message = 'Attendance updated successfully';
            }
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'staff_id' => $staff_id,
                    'date' => date('Y-m-d'),
                    'time' => date('H:i:s'),
                    'in_out_time' => $in_out_time,
                    'status' => $attendance_status,
                    'remark' => $remark
                ),
                'message' => $message
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Attendance operation failed: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
        exit;
    }

    public function enrollFace()
    {
        header('Content-Type: application/json');

        // If teacher is logged in, prefer session ID
        $staff_id = is_loggedin() ? get_loggedin_user_id() : $this->input->post('staff_id');

        // Ensure staff_face table exists (self-heal)
        try {
            if (!$this->db->table_exists('staff_face')) {
                $this->db->query("CREATE TABLE IF NOT EXISTS `staff_face` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `staff_id` INT UNSIGNED NOT NULL UNIQUE,
                    `embedding` JSON NOT NULL,
                    `fingerprint` VARCHAR(64) NOT NULL UNIQUE,
                    `model_name` VARCHAR(64) NULL,
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_staff_id` (`staff_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Failed ensuring staff_face table: '.$e->getMessage()]);
            exit;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $post_face_data = $this->input->post('face_data');

        if (!empty($data) && !empty($data['face_data'])) {
            $face_data = $data['face_data'];
            $model_name = isset($data['model_name']) ? $data['model_name'] : '';
            if (empty($staff_id) && isset($data['staff_id'])) {
                $staff_id = $data['staff_id'];
            }
        } elseif (!empty($post_face_data)) {
            $face_data = $post_face_data;
            $model_name = $this->input->post('model_name');
        } else {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'face_data required']);
            exit;
        }

        if (empty($staff_id)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'staff_id missing or null']);
            exit;
        }

        // Ensure embedding is stored as JSON string
        $embeddingJson = is_array($face_data) ? json_encode($face_data) : (is_string($face_data) ? $face_data : json_encode($face_data));
        $fingerprint = hash('sha256', $embeddingJson);

        // Prevent duplicate registration: same face (fingerprint) cannot be used by multiple users
        // Allow if the same staff is updating their own face
        $conflict = $this->db->select('staff_id')
            ->from('staff_face')
            ->where('fingerprint', $fingerprint)
            ->where('staff_id !=', $staff_id)
            ->get()->row();
        if ($conflict) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'This face is already registered to another user. One face can only be registered once.']);
            exit;
        }

        $payload = [
            'staff_id'   => $staff_id,
            'embedding'  => $embeddingJson,
            'fingerprint'=> $fingerprint,
            'model_name' => $model_name
        ];

        $exists = $this->db->get_where('staff_face', ['staff_id'=>$staff_id])->row();
        if ($exists) {
            $this->db->where('staff_id', $staff_id)->update('staff_face', $payload);
            $message = 'Face updated';
        } else {
            $this->db->insert('staff_face', $payload);
            $message = 'Face enrolled';
        }

        echo json_encode(['status'=>'success','message'=>$message,'staff_id'=>$staff_id]);
        exit;
    }


    public function verifyFace()
    {
        header('Content-Type: application/json');

        // Get input data - handle both JSON and form data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        // Check for staff_id and face_data in JSON or POST data
        if (!empty($data)) {
            $staff_id = isset($data['staff_id']) ? $data['staff_id'] : $this->input->post('staff_id');
            $face_data = isset($data['face_data']) ? $data['face_data'] : $this->input->post('face_data');
            $user_latitude = isset($data['user_latitude']) ? $data['user_latitude'] : $this->input->post('user_latitude');
            $user_longitude = isset($data['user_longitude']) ? $data['user_longitude'] : $this->input->post('user_longitude');
        } else {
            $staff_id = $this->input->post('staff_id');
            $face_data = $this->input->post('face_data');
            $user_latitude = $this->input->post('user_latitude');
            $user_longitude = $this->input->post('user_longitude');
        }

        if (empty($staff_id) || empty($face_data)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'staff_id & face_data required']);
            exit;
        }

        // Verify location if coordinates are provided
        $location_verified = false;
        $location_data = null;
        if (!empty($user_latitude) && !empty($user_longitude)) {
            $location_result = $this->verifyLocationForStaff($staff_id, $user_latitude, $user_longitude);
            if ($location_result['verified']) {
                $location_verified = true;
                $location_data = $location_result['data'];
            } else {
                http_response_code(403);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Location verification failed: ' . $location_result['message'],
                    'location_data' => $location_result['data']
                ]);
                exit;
            }
        }

        // decode embeddings (expect JSON array)
        $stored_row = $this->db->get_where('staff_face', ['staff_id' => $staff_id])->row();
        if (!$stored_row || empty($stored_row->embedding)) {
            http_response_code(404);
            echo json_encode(['status'=>'error','message'=>'No enrolled face for this staff']);
            exit;
        }

        // Accept either already-parsed arrays or JSON strings
        $vec_post = is_array($face_data) ? $face_data : json_decode($face_data, true);
        $vec_store = is_array($stored_row->embedding) ? $stored_row->embedding : json_decode($stored_row->embedding, true);
        if (!is_array($vec_post) || !is_array($vec_store)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'Embedding format invalid']);
            exit;
        }

        $sim = $this->cosine_similarity_arrays($vec_post, $vec_store);
        if ($sim === null) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'Similarity compute failed']);
            exit;
        }

        // convert similarity to percent confidence
        $confidence = round($sim * 100, 2);
        $threshold = 0.65; // relaxed threshold for dev/testing
        $verified = ($sim >= $threshold);

        echo json_encode([
            'status'=>'success',
            'verified' => $verified,
            'similarity' => $sim,
            'confidence' => $confidence,
            'location_verified' => $location_verified,
            'location_data' => $location_data
        ]);
        exit;
    }

    /**
     * Image-based Enroll: Accepts a face image, calls local embed service, stores embedding
     */
    public function enrollFaceImage()
    {
        header('Content-Type: application/json');
        $staff_id = is_loggedin() ? get_loggedin_user_id() : $this->input->post('staff_id');
        if (empty($staff_id)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'staff_id required']);
            exit;
        }

        if (empty($_FILES['image']['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'image file required (multipart field name: image)']);
            exit;
        }

        $embedUrl = 'http://127.0.0.1:8001/embed';
        $cfile = new CURLFile($_FILES['image']['tmp_name'], $_FILES['image']['type'], $_FILES['image']['name']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $embedUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $cfile]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'embed service unreachable: '.curl_error($ch)]);
            exit;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $edata = json_decode($resp, true);
        if ($httpCode !== 200 || empty($edata['embedding'])) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'embed failed','debug'=>$edata]);
            exit;
        }

        $embeddingJson = json_encode($edata['embedding']);
        $fingerprint = isset($edata['fingerprint']) ? $edata['fingerprint'] : hash('sha256', $embeddingJson);

        // Prevent duplicate registration: same face (fingerprint) cannot be used by multiple users
        // Allow if the same staff is updating their own face
        $conflict = $this->db->select('staff_id')
            ->from('staff_face')
            ->where('fingerprint', $fingerprint)
            ->where('staff_id !=', $staff_id)
            ->get()->row();
        if ($conflict) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'This face is already registered to another user. One face can only be registered once.']);
            exit;
        }
        $payload = [
            'staff_id'   => $staff_id,
            'embedding'  => $embeddingJson,
            'fingerprint'=> $fingerprint,
            'model_name' => 'arcface-mock',
        ];

        $exists = $this->db->get_where('staff_face', ['staff_id'=>$staff_id])->row();
        if ($exists) {
            $this->db->where('staff_id', $staff_id)->update('staff_face', $payload);
            $message = 'Face updated';
        } else {
            $this->db->insert('staff_face', $payload);
            $message = 'Face enrolled';
        }

        echo json_encode(['status'=>'success','message'=>$message,'staff_id'=>$staff_id]);
        exit;
    }

    /**
     * Image-based Verify: Accepts a face image, calls local embed service, compares with stored embedding
     */
    public function verifyFaceImage()
    {
        header('Content-Type: application/json');
        $staff_id = is_loggedin() ? get_loggedin_user_id() : $this->input->post('staff_id');
        if (empty($staff_id)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'staff_id required']);
            exit;
        }
        if (empty($_FILES['image']['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'image file required (multipart field name: image)']);
            exit;
        }

        // load stored embedding
        $row = $this->db->get_where('staff_face', ['staff_id'=>$staff_id])->row();
        if (!$row || empty($row->embedding)) {
            http_response_code(404);
            echo json_encode(['status'=>'error','message'=>'No enrolled face for this staff']);
            exit;
        }
        $stored = json_decode($row->embedding, true);
        if (!is_array($stored)) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Stored embedding invalid']);
            exit;
        }

        // call embed service
        $embedUrl = 'http://127.0.0.1:8001/embed';
        $cfile = new CURLFile($_FILES['image']['tmp_name'], $_FILES['image']['type'], $_FILES['image']['name']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $embedUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => $cfile]);
        $resp = curl_exec($ch);
        if ($resp === false) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'embed service unreachable: '.curl_error($ch)]);
            exit;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $edata = json_decode($resp, true);
        if ($httpCode !== 200 || empty($edata['embedding'])) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'embed failed','debug'=>$edata]);
            exit;
        }
        $probe = $edata['embedding'];

        // cosine similarity
        $sim = $this->cosine_similarity_arrays($probe, $stored);
        if ($sim === null) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'Similarity compute failed']);
            exit;
        }
        $confidence = round($sim * 100, 2);
        $threshold = 0.65;
        $verified = ($sim >= $threshold);

        echo json_encode([
            'status' => 'success',
            'verified' => $verified,
            'similarity' => $sim,
            'confidence' => $confidence,
        ]);
        exit;
    }

    /**
     * Identify a face embedding against all enrolled staff
     * Request JSON: { face_data: number[] }
     * Response: { matched: bool, staff_id?, name?, similarity?, confidence? }
     */
    public function identifyFace()
    {
        header('Content-Type: application/json');

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $face_data = isset($data['face_data']) ? $data['face_data'] : $this->input->post('face_data');
        if (empty($face_data)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'face_data required']);
            exit;
        }

        // Ensure vector
        $probe = is_array($face_data) ? $face_data : json_decode($face_data, true);
        if (!is_array($probe)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Embedding format invalid']);
            exit;
        }

        // Load all enrolled staff embeddings
        if (!$this->db->table_exists('staff_face')) {
            echo json_encode(['status' => 'success', 'matched' => false, 'message' => 'No enrolled faces']);
            exit;
        }

        $rows = $this->db->select('sf.staff_id, sf.embedding, s.name')
            ->from('staff_face as sf')
            ->join('staff as s', 's.id = sf.staff_id', 'left')
            ->get()->result();

        $best = null; $bestSim = -1.0;
        foreach ($rows as $r) {
            $emb = is_array($r->embedding) ? $r->embedding : json_decode($r->embedding, true);
            if (!is_array($emb)) continue;
            $sim = $this->cosine_similarity_arrays($probe, $emb);
            if ($sim !== null && $sim > $bestSim) {
                $bestSim = $sim;
                $best = $r;
            }
        }

        if ($best === null) {
            echo json_encode(['status' => 'success', 'matched' => false, 'message' => 'No embeddings found']);
            exit;
        }

        $threshold = 0.65; // tune as needed
        $matched = ($bestSim >= $threshold);
        $confidence = round($bestSim * 100, 2);
        echo json_encode([
            'status' => 'success',
            'matched' => $matched,
            'staff_id' => $matched ? intval($best->staff_id) : null,
            'name' => $matched ? ($best->name ?: null) : null,
            'similarity' => $bestSim,
            'confidence' => $confidence,
        ]);
        exit;
    }

    /**
     * List all enrolled staff faces (metadata only, no embeddings exposed)
     * GET api/listEnrolledFaces
     * Response: { status, data: [ { staff_id, name, model_name, enrolled_at, updated_at } ] }
     */
    public function listEnrolledFaces()
    {
        header('Content-Type: application/json');

        try {
            if (!$this->db->table_exists('staff_face')) {
                echo json_encode(['status' => 'success', 'data' => []]);
                exit;
            }

            // Some databases may not have created_at / updated_at columns yet
            $hasCreatedAt = $this->db->field_exists('created_at', 'staff_face');
            $hasUpdatedAt = $this->db->field_exists('updated_at', 'staff_face');

            // Build select with proper escaping; use literal NULL with escaping disabled
            $this->db->select('sf.staff_id, s.name, sf.model_name', FALSE)
                ->from('staff_face as sf')
                ->join('staff as s', 's.id = sf.staff_id', 'left');

            if ($hasCreatedAt) {
                $this->db->select('sf.created_at', FALSE);
            } else {
                $this->db->select('NULL AS created_at', FALSE);
            }

            if ($hasUpdatedAt) {
                $this->db->select('sf.updated_at', FALSE);
            } else {
                $this->db->select('NULL AS updated_at', FALSE);
            }

            if ($hasUpdatedAt) {
                $this->db->order_by('sf.updated_at', 'DESC');
            } else {
                $this->db->order_by('sf.staff_id', 'DESC');
            }

            $rows = $this->db->get()->result_array();

            // Normalize timestamps
            foreach ($rows as &$r) {
                if (!isset($r['created_at'])) $r['created_at'] = null;
                if (!isset($r['updated_at'])) $r['updated_at'] = null;
            }

            echo json_encode(['status' => 'success', 'data' => $rows]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to list enrolled faces: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Face2Face: Register a generic person with name, age, mobile and embedding
     * Request JSON: { name, age?, mobile?, embedding: number[] }
     */
    public function f2fRegister()
    {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        $name = isset($data['name']) ? trim($data['name']) : '';
        $age = isset($data['age']) ? intval($data['age']) : null;
        $mobile = isset($data['mobile']) ? trim($data['mobile']) : '';
        $embedding = isset($data['embedding']) ? $data['embedding'] : null;

        if ($name === '' || empty($embedding) || !is_array($embedding)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'name and embedding array required']);
            exit;
        }

        // Ensure tables exist
        $this->db->query("CREATE TABLE IF NOT EXISTS `f2f_person` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(191) NOT NULL,
            `age` INT NULL,
            `mobile` VARCHAR(32) NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query("CREATE TABLE IF NOT EXISTS `f2f_embedding` (
            `person_id` INT UNSIGNED NOT NULL,
            `embedding` JSON NOT NULL,
            `photo` VARCHAR(255) NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (`person_id`),
            CONSTRAINT `fk_f2f_person` FOREIGN KEY (`person_id`) REFERENCES `f2f_person`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Insert person
        $this->db->insert('f2f_person', [
            'name' => $name,
            'age' => $age,
            'mobile' => $mobile,
        ]);
        $person_id = $this->db->insert_id();

        // Store embedding as JSON string
        $this->db->insert('f2f_embedding', [
            'person_id' => $person_id,
            'embedding' => json_encode($embedding),
        ]);

        echo json_encode(['status'=>'success','person_id'=>$person_id,'message'=>'Registered']);
        exit;
    }

    /**
     * Face2Face: Analyze an embedding against registry and return best match
     * Request JSON: { embedding: number[] }
     */
    public function f2fAnalyze()
    {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $probe = isset($data['embedding']) ? $data['embedding'] : null;
        if (empty($probe) || !is_array($probe)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'embedding array required']);
            exit;
        }

        // If tables not exist or empty
        if (!$this->db->table_exists('f2f_embedding')) {
            echo json_encode(['status'=>'success','matched'=>false,'message'=>'registry empty']);
            exit;
        }

        $rows = $this->db->select('f2f_person.id, f2f_person.name, f2f_person.mobile, f2f_embedding.embedding')
            ->from('f2f_person')
            ->join('f2f_embedding','f2f_embedding.person_id = f2f_person.id','inner')
            ->get()->result_array();

        $best = null; $bestSim = -1.0;
        foreach ($rows as $r) {
            $emb = json_decode($r['embedding'], true);
            if (!is_array($emb)) continue;
            $sim = $this->cosine_similarity_arrays($probe, $emb);
            if ($sim !== null && $sim > $bestSim) {
                $bestSim = $sim;
                $best = $r;
            }
        }

        if ($best === null) {
            echo json_encode(['status'=>'success','matched'=>false,'message'=>'registry empty or invalid']);
            exit;
        }

        $threshold = 0.65; // tune
        $matched = ($bestSim >= $threshold);
        $confidence = round($bestSim * 100, 2);
        echo json_encode([
            'status' => 'success',
            'matched' => $matched,
            'similarity' => $bestSim,
            'confidence' => $confidence,
            // Top-level convenience fields for easy UI binding
            'name' => $matched ? $best['name'] : null,
            'percentage' => $matched ? $confidence : 0,
            'person' => $matched ? ['id'=>$best['id'],'name'=>$best['name'],'mobile'=>$best['mobile']] : null,
        ]);
        exit;
    }

    /**
     * Face2Face: Create person (no embedding yet)
     * POST api/f2f/create  JSON: {name, age?, mobile?}
     */
    public function f2fCreate()
    {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $name = isset($data['name']) ? trim($data['name']) : '';
        $age = isset($data['age']) ? intval($data['age']) : null;
        $mobile = isset($data['mobile']) ? trim($data['mobile']) : '';
        if ($name === '') {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'name required']);
            exit;
        }
        // Ensure table exists
        $this->db->query("CREATE TABLE IF NOT EXISTS `f2f_person` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(191) NOT NULL,
            `age` INT NULL,
            `mobile` VARCHAR(32) NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->db->insert('f2f_person', [
            'name' => $name,
            'age' => $age,
            'mobile' => $mobile,
        ]);
        $person_id = $this->db->insert_id();
        echo json_encode(['status'=>'success','person_id'=>$person_id,'message'=>'Person created']);
        exit;
    }

    /**
     * Face2Face: Attach embedding to an existing person
     * POST api/f2f/attach  JSON: {person_id, embedding: number[]}
     */
    public function f2fAttach()
    {
        header('Content-Type: application/json');
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $person_id = isset($data['person_id']) ? intval($data['person_id']) : 0;
        $embedding = isset($data['embedding']) ? $data['embedding'] : null;
        if ($person_id <= 0 || empty($embedding) || !is_array($embedding)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'person_id and embedding required']);
            exit;
        }
        // Ensure tables
        $this->db->query("CREATE TABLE IF NOT EXISTS `f2f_embedding` (
            `person_id` INT UNSIGNED NOT NULL,
            `embedding` JSON NOT NULL,
            `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (`person_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Upsert: replace existing row for person_id
        $exists = $this->db->get_where('f2f_embedding', ['person_id'=>$person_id])->row_array();
        if ($exists) {
            $this->db->where('person_id',$person_id)->update('f2f_embedding',[ 'embedding'=> json_encode($embedding) ]);
            echo json_encode(['status'=>'success','message'=>'Embedding updated']);
        } else {
            $this->db->insert('f2f_embedding',[ 'person_id'=>$person_id, 'embedding'=> json_encode($embedding) ]);
            echo json_encode(['status'=>'success','message'=>'Embedding attached']);
        }
        exit;
    }

    /**
     * Face2Face: Attach or update photo for person_id (multipart: image)
     */
    public function f2fAttachImage()
    {
        header('Content-Type: application/json');
        $person_id = isset($_POST['person_id']) ? intval($_POST['person_id']) : 0;
        if ($person_id <= 0) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'person_id required']);
            exit;
        }
        if (empty($_FILES['image']['tmp_name'])) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'image file required (field name: image)']);
            exit;
        }
        // ensure row exists
        $exists = $this->db->get_where('f2f_embedding',['person_id'=>$person_id])->row_array();
        if (!$exists) {
            // create empty embedding if not exists
            $this->db->insert('f2f_embedding',[ 'person_id'=>$person_id, 'embedding'=> json_encode([]) ]);
        }
        // save file
        $dir = FCPATH.'uploads/f2f/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        if ($ext == '') $ext = 'jpg';
        $filename = 'p'.$person_id.'_'.time().'.'.$ext;
        $dest = $dir.$filename;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'failed to save image']);
            exit;
        }
        $this->db->where('person_id',$person_id)->update('f2f_embedding',[ 'photo' => $filename ]);
        echo json_encode(['status'=>'success','message'=>'Photo attached','file'=>$filename]);
        exit;
    }

    /**
     * Face2Face: List registered persons
     * GET api/f2f/list
     */
    public function f2fList()
    {
        header('Content-Type: application/json');
        if (!$this->db->table_exists('f2f_person')) {
            echo json_encode(['status'=>'success','data'=>[]]);
            exit;
        }
        // include latest attached photo if available (and column exists)
        $select = 'p.id,p.name,p.age,p.mobile,p.created_at';
        if ($this->db->table_exists('f2f_embedding')) {
            // ensure photo column exists (migrate silently)
            $col = $this->db->query("SHOW COLUMNS FROM `f2f_embedding` LIKE 'photo'");
            if ($col && $col->num_rows() == 0) {
                $this->db->query("ALTER TABLE `f2f_embedding` ADD COLUMN `photo` VARCHAR(255) NULL");
            }
            $select .= ', e.photo';
            $this->db->select($select);
            $this->db->from('f2f_person as p');
            $this->db->join('f2f_embedding as e','e.person_id = p.id','left');
        } else {
            $this->db->select($select);
            $this->db->from('f2f_person as p');
        }
        $this->db->order_by('p.id','DESC');
        $rows = $this->db->get()->result_array();
        echo json_encode(['status'=>'success','data'=>$rows]);
        exit;
    }


    /**
     * Calculate cosine similarity between two arrays
     */
    private function cosine_similarity_arrays($array1, $array2) {
        // Check if arrays have the same length
        if (count($array1) !== count($array2)) {
            return null;
        }
        
        $dot_product = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;
        
        // Calculate dot product and magnitudes
        for ($i = 0; $i < count($array1); $i++) {
            $dot_product += $array1[$i] * $array2[$i];
            $magnitude1 += $array1[$i] * $array1[$i];
            $magnitude2 += $array2[$i] * $array2[$i];
        }
        
        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);
        
        // Avoid division by zero
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }
        
        return $dot_product / ($magnitude1 * $magnitude2);
    }


    /**
     * API: Teacher Face Verification Attendance
     * Marks attendance for teacher using face verification data
     */
    public function teacherFaceAttendance()
    {
        header('Content-Type: application/json');

        // --- Authentication check ---
        if (!is_loggedin() || loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            exit;
        }

        $teacher_id = get_loggedin_user_id();
        $branch_id  = $this->application_model->get_branch_id();

        // --- Get input (JSON or POST) ---
        $input = file_get_contents('php://input');
        $data  = json_decode($input, true);

        // Also check regular POST
        $post_face_data = $this->input->post('face_data');
        $post_confidence = $this->input->post('confidence');
        $post_device_id  = $this->input->post('device_id');
        $post_remark     = $this->input->post('remark');

        // Determine data source
        if (!empty($data) && isset($data['face_data']) && !empty($data['face_data'])) {
            $face_data  = trim($data['face_data']);
            $confidence = isset($data['confidence']) ? $data['confidence'] : '';
            $device_id  = isset($data['device_id']) ? $data['device_id'] : '';
            $remark     = isset($data['remark']) ? $data['remark'] : '';
        } elseif (!empty($post_face_data)) {
            $face_data  = trim($post_face_data);
            $confidence = $post_confidence;
            $device_id  = $post_device_id;
            $remark     = $post_remark;
        } else {
            $response = array(
                'status' => 'error',
                'message' => 'Face data is required but not received.',
                'debug' => array(
                    'raw_input' => !empty($input),
                    'json_parsed' => !empty($data),
                    'post_face_data' => !empty($post_face_data)
                )
            );
            http_response_code(400);
            echo json_encode($response);
            exit;
        }

        try {
            // --- Date/time ---
            $date = date('Y-m-d');
            $current_time = date('H:i:s');

            // --- Check if attendance exists for today ---
            $this->db->select('id');
            $this->db->from('staff_attendance');
            $this->db->where('staff_id', $teacher_id);
            $this->db->where('date', $date);
            $query = $this->db->get();

            // --- Prepare remark ---
            $face_remark = 'Face verification attendance';
            if (!empty($confidence)) $face_remark .= " (Confidence: {$confidence}%)";
            if (!empty($device_id)) $face_remark .= " (Device: {$device_id})";
            if (!empty($remark)) $face_remark .= " - {$remark}";

            // --- Prepare DB data ---
            $attendance_record = array(
                'staff_id'   => $teacher_id,
                'branch_id'  => $branch_id,
                'date'       => $date,
                'status'     => 'P',
                'in_time'    => $current_time,
                'remark'     => $face_remark,
                'face_data'  => $face_data // ✅ Save raw hash/string directly
            );

            // --- Insert or update ---
            if ($query->num_rows() > 0) {
                $this->db->where('id', $query->row()->id);
                $this->db->update('staff_attendance', $attendance_record);
                $message = 'Attendance updated successfully via face verification.';
            } else {
                $this->db->insert('staff_attendance', $attendance_record);
                $message = 'Attendance marked successfully via face verification.';
            }

            // --- Success response ---
            $response = array(
                'status' => 'success',
                'data' => array(
                    'teacher_id' => $teacher_id,
                    'branch_id'  => $branch_id,
                    'date'       => $date,
                    'status'     => 'P',
                    'in_time'    => $current_time,
                    'confidence' => $confidence,
                    'device_id'  => $device_id,
                    'remark'     => $remark,
                    'face_data'  => $face_data
                ),
                'message' => $message
            );
            http_response_code(200);

        } catch (Exception $e) {
            log_message('error', 'Face Attendance Error: ' . $e->getMessage());
            $response = array(
                'status' => 'error',
                'message' => 'Face verification attendance failed: ' . $e->getMessage()
            );
            http_response_code(500);
        }

        echo json_encode($response);
        exit;
    }



    /**
     * API: Teacher Logout
     * Logs out the currently logged in teacher and destroys the session
     */
    public function teacherLogout()
    {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        if (!is_loggedin()) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'No active session found'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        // Check if logged in user is a teacher (role 3)
        if (loggedin_role_id() != 3) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Teacher login required.'
            );
            http_response_code(403);
            echo json_encode($response);
            return;
        }
        
        try {
            // Destroy the session
            $this->session->sess_destroy();
            
            $response = array(
                'status' => 'success',
                'data' => array(),
                'message' => 'Teacher logged out successfully'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Logout failed: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
        exit;
    }

    /**
     * Verify if staff is within allowed location radius
     */
    private function verifyLocationForStaff($staff_id, $user_latitude, $user_longitude) {
        try {
            // Get staff branch information
            $staff = $this->db->get_where('staff', ['id' => $staff_id])->row();
            if (!$staff) {
                return [
                    'verified' => false,
                    'message' => 'Staff not found',
                    'data' => null
                ];
            }

            $branch_id = $staff->branch_id;

            // Check if location verification is enabled for this branch
            $settings = $this->db->get_where('location_attendance_settings', ['branch_id' => $branch_id])->row();
            if (!$settings || !$settings->location_verification_enabled) {
                return [
                    'verified' => true,
                    'message' => 'Location verification not enabled for this branch',
                    'data' => ['verification_disabled' => true]
                ];
            }

            // Get allowed locations for this branch
            $locations = $this->db->get_where('attendance_locations', [
                'branch_id' => $branch_id,
                'is_active' => 1
            ])->result();

            if (empty($locations)) {
                return [
                    'verified' => false,
                    'message' => 'No allowed locations configured for this branch',
                    'data' => null
                ];
            }

            $nearest_location = null;
            $min_distance = PHP_FLOAT_MAX;

            // Check distance to each allowed location
            foreach ($locations as $location) {
                $distance = $this->calculateDistance(
                    $user_latitude,
                    $user_longitude,
                    $location->latitude,
                    $location->longitude
                );

                if ($distance <= $location->radius && $distance < $min_distance) {
                    $min_distance = $distance;
                    $nearest_location = $location;
                }
            }

            if ($nearest_location) {
                return [
                    'verified' => true,
                    'message' => 'Location verified successfully',
                    'data' => [
                        'location_id' => $nearest_location->id,
                        'location_name' => $nearest_location->name,
                        'distance' => round($min_distance, 2),
                        'within_radius' => true
                    ]
                ];
            } else {
                return [
                    'verified' => false,
                    'message' => 'You are not within any allowed location radius',
                    'data' => [
                        'distance_to_nearest' => round($min_distance, 2),
                        'within_radius' => false
                    ]
                ];
            }

        } catch (Exception $e) {
            return [
                'verified' => false,
                'message' => 'Location verification failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
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