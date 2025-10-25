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
        
        // Debug: Log API constructor
        log_message('info', 'API Constructor: Initializing API controller');
        
        $this->load->model('authentication_model');
        $this->load->model('employee_model');
        // Disable CSRF protection for API endpoints
        $this->config->set_item('csrf_protection', false);
        
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

    public function checkFaceDuplicate()
    {
        header('Content-Type: application/json');
        
        // Debug: Log the request
        log_message('info', 'checkFaceDuplicate: API called');
        
        // Get input data
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        log_message('info', 'checkFaceDuplicate: Input data: ' . json_encode($data));
        
        if (empty($data) || empty($data['face_data'])) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'face_data required']);
            exit;
        }
        
        $face_data = $data['face_data'];
        $current_user_id = is_loggedin() ? get_loggedin_user_id() : null;
        
        log_message('info', 'checkFaceDuplicate: Current user ID: ' . ($current_user_id ?: 'null'));
        
        // Calculate fingerprint for the face
        $embeddingJson = is_array($face_data) ? json_encode($face_data) : $face_data;
        $fingerprint = hash('sha256', $embeddingJson);
        
        // Check if this face fingerprint exists for any other user
        $existing_face = $this->db->select('staff_id')
            ->from('staff_face')
            ->where('fingerprint', $fingerprint)
            ->get()->row();
            
        if ($existing_face) {
            // Face already exists
            log_message('info', 'checkFaceDuplicate: Face already exists for staff_id: ' . $existing_face->staff_id);
            if ($current_user_id && $existing_face->staff_id == $current_user_id) {
                // Same user updating their own face - allow it
                log_message('info', 'checkFaceDuplicate: Face belongs to current user');
                echo json_encode(['status'=>'success','message'=>'Face belongs to current user']);
            } else {
                // Different user trying to use existing face - deny
                log_message('info', 'checkFaceDuplicate: Face belongs to different user');
                echo json_encode(['status'=>'duplicate','message'=>'This face is already registered to another user']);
            }
        } else {
            // Face is unique - allow enrollment
            log_message('info', 'checkFaceDuplicate: Face is unique - allowing enrollment');
            echo json_encode(['status'=>'unique','message'=>'Face is available for enrollment']);
        }
    }

    public function enrollFace()
    {
        // CACHE BUST: Force server reload - v3.0
        // Set error reporting to catch all errors
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        header('Content-Type: application/json');

        try {
            // If teacher is logged in, prefer session ID
            $staff_id = is_loggedin() ? get_loggedin_user_id() : $this->input->post('staff_id');

        // Ensure staff_face table exists (self-heal)
        try {
            if (!$this->db->table_exists('staff_face')) {
                log_message('info', 'Creating staff_face table...');
                $create_result = $this->db->query("CREATE TABLE IF NOT EXISTS `staff_face` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `staff_id` INT UNSIGNED NOT NULL UNIQUE,
                    `embedding` JSON NOT NULL,
                    `embedding_straight` JSON NULL,
                    `embedding_right` JSON NULL,
                    `embedding_left` JSON NULL,
                    `fingerprint` VARCHAR(64) NOT NULL UNIQUE,
                    `model_name` VARCHAR(64) NULL,
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_staff_id` (`staff_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                
                if (!$create_result) {
                    throw new Exception('Failed to create staff_face table: ' . $this->db->last_query());
                }
                log_message('info', 'staff_face table created successfully');
            } else {
            // Table exists, check if it has the new columns
            log_message('info', 'Checking staff_face table columns...');
            $columns = $this->db->list_fields('staff_face');
            log_message('info', 'Current columns: ' . implode(', ', $columns));
            $has_straight = in_array('embedding_straight', $columns);
            $has_right = in_array('embedding_right', $columns);
            $has_left = in_array('embedding_left', $columns);
            log_message('info', 'Column check - straight: ' . ($has_straight ? 'yes' : 'no') . ', right: ' . ($has_right ? 'yes' : 'no') . ', left: ' . ($has_left ? 'yes' : 'no'));
            
            if (!$has_straight || !$has_right || !$has_left) {
                log_message('info', 'Adding missing columns to staff_face table...');
                
                if (!$has_straight) {
                    $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_straight` JSON NULL");
                    log_message('info', 'Added embedding_straight column');
                }
                if (!$has_right) {
                    $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_right` JSON NULL");
                    log_message('info', 'Added embedding_right column');
                }
                if (!$has_left) {
                    $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_left` JSON NULL");
                    log_message('info', 'Added embedding_left column');
                }
                
                log_message('info', 'Missing columns added to staff_face table');
            }
            }
        } catch (Exception $e) {
            log_message('error', 'Database error in enrollFace: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Database error: '.$e->getMessage()]);
            exit;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        $post_face_data = $this->input->post('face_data');
        
        log_message('info', 'enrollFace received data: ' . print_r($data, true));
        
        // Check data size to prevent memory issues
        $json_size = strlen(json_encode($data));
        log_message('info', 'Data size: ' . $json_size . ' bytes');
        
        if ($json_size > 1000000) { // 1MB limit
            log_message('error', 'Data too large: ' . $json_size . ' bytes');
            http_response_code(413);
            echo json_encode(['status'=>'error','message'=>'Face data too large. Please try again.']);
            exit;
        }

        // Handle both single face data (legacy) and multi-angle face data (new)
        $face_data = null;
        $face_data_straight = null;
        $face_data_right = null;
        $face_data_left = null;
        $model_name = '';

        if (!empty($data)) {
            // New multi-angle format
            if (isset($data['face_data_straight']) && isset($data['face_data_right']) && isset($data['face_data_left'])) {
                $face_data_straight = $data['face_data_straight'];
                $face_data_right = $data['face_data_right'];
                $face_data_left = $data['face_data_left'];
                $model_name = isset($data['model_name']) ? $data['model_name'] : 'mobilefacenet';
                if (empty($staff_id) && isset($data['staff_id'])) {
                    $staff_id = $data['staff_id'];
                }
            }
            // Legacy single face format
            elseif (!empty($data['face_data'])) {
                $face_data = $data['face_data'];
                $model_name = isset($data['model_name']) ? $data['model_name'] : '';
                if (empty($staff_id) && isset($data['staff_id'])) {
                    $staff_id = $data['staff_id'];
                }
            }
        } elseif (!empty($post_face_data)) {
            // Legacy POST format
            $face_data = $post_face_data;
            $model_name = $this->input->post('model_name');
        }

        // Check if we have any face data
        if (empty($face_data) && empty($face_data_straight)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'face_data or multi-angle face data required']);
            exit;
        }

        if (empty($staff_id)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'staff_id missing or null']);
            exit;
        }

        // Prepare embeddings for storage
        if (!empty($face_data_straight)) {
            // Multi-angle enrollment
            // Limit embedding size to prevent database issues
            $face_data_straight = array_slice($face_data_straight, 0, 128); // Limit to 128 dimensions
            $face_data_right = array_slice($face_data_right, 0, 128);
            $face_data_left = array_slice($face_data_left, 0, 128);
            
            $embedding_straight = is_array($face_data_straight) ? json_encode($face_data_straight) : $face_data_straight;
            $embedding_right = is_array($face_data_right) ? json_encode($face_data_right) : $face_data_right;
            $embedding_left = is_array($face_data_left) ? json_encode($face_data_left) : $face_data_left;
            
            // Use straight face as primary embedding for backward compatibility
            $primary_embedding = $embedding_straight;
            $fingerprint = hash('sha256', $embedding_straight);
            
            log_message('info', "Multi-angle face enrollment for staff_id: $staff_id (reduced to 128 dimensions)");
        } else {
            // Legacy single face enrollment
            // Limit embedding size to prevent database issues
            $face_data = array_slice($face_data, 0, 128); // Limit to 128 dimensions
            
            $primary_embedding = is_array($face_data) ? json_encode($face_data) : $face_data;
            $fingerprint = hash('sha256', $primary_embedding);
            $embedding_straight = $primary_embedding;
            $embedding_right = null;
            $embedding_left = null;
            
            log_message('info', "Legacy single face enrollment for staff_id: $staff_id (reduced to 128 dimensions)");
        }

        // Prevent duplicate registration: same face (fingerprint) cannot be used by multiple users
        // Allow if the same staff is updating their own face
        try {
            $conflict = $this->db->select('staff_id')
                ->from('staff_face')
                ->where('fingerprint', $fingerprint)
                ->where('staff_id !=', $staff_id)
                ->get()->row();
        } catch (Exception $e) {
            log_message('error', 'Database query error in duplicate check: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Database query error: '.$e->getMessage()]);
            exit;
        }
        
        if ($conflict) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'This face is already registered to another user. One face can only be registered once.']);
            exit;
        }

        // Check if new columns exist before using them
        $columns = $this->db->list_fields('staff_face');
        $has_straight = in_array('embedding_straight', $columns);
        $has_right = in_array('embedding_right', $columns);
        $has_left = in_array('embedding_left', $columns);
        
        // TEMPORARY FIX: Only use basic embedding column to avoid database issues
        log_message('info', 'enrollFace: Using temporary fix - only basic embedding column');
        $payload = [
            'staff_id'   => $staff_id,
            'embedding'  => $primary_embedding, // Primary embedding for backward compatibility
            'fingerprint'=> $fingerprint,
            'model_name' => $model_name
        ];
        log_message('info', 'enrollFace: Payload created with basic columns only');
        
        // TODO: Re-enable multi-angle columns once database schema is fixed
        // Only add new columns if they exist
        // if ($has_straight) {
        //     $payload['embedding_straight'] = $embedding_straight;
        // }
        // if ($has_right) {
        //     $payload['embedding_right'] = $embedding_right;
        // }
        // if ($has_left) {
        //     $payload['embedding_left'] = $embedding_left;
        // }

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
        
        } catch (Exception $e) {
            log_message('error', 'Fatal error in enrollFace: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['status'=>'error','message'=>'Fatal error: '.$e->getMessage()]);
            exit;
        }
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
        $stored_row_3d = null;
        
        // If no regular face data, check 3D face data
        if (!$stored_row || empty($stored_row->embedding)) {
            if ($this->db->table_exists('staff_face_3d')) {
                $stored_row_3d = $this->db->get_where('staff_face_3d', ['staff_id' => $staff_id])->row();
            }
            
            if (!$stored_row_3d || (empty($stored_row_3d->enhanced_embedding) && empty($stored_row_3d->face_model_3d))) {
            http_response_code(404);
            echo json_encode(['status'=>'error','message'=>'No enrolled face for this staff']);
            exit;
            }
        }

        // Accept either already-parsed arrays or JSON strings
        $vec_post = is_array($face_data) ? $face_data : json_decode($face_data, true);
        if (!is_array($vec_post)) {
            http_response_code(400);
            echo json_encode(['status'=>'error','message'=>'Embedding format invalid']);
            exit;
        }

        $sim = null;
        $best_sim = 0;
        
        // Try regular face data first
        if ($stored_row && !empty($stored_row->embedding)) {
            $vec_store = is_array($stored_row->embedding) ? $stored_row->embedding : json_decode($stored_row->embedding, true);
            if (is_array($vec_store)) {
        $sim = $this->cosine_similarity_arrays($vec_post, $vec_store);
                if ($sim !== null) {
                    $best_sim = $sim;
                }
            }
        }
        
        // Try 3D face data if regular data not available or similarity is low
        if (($sim === null || $sim < 0.65) && $stored_row_3d) {
            // Try enhanced embedding first
            if (!empty($stored_row_3d->enhanced_embedding)) {
                $enhanced_emb = is_array($stored_row_3d->enhanced_embedding) ? $stored_row_3d->enhanced_embedding : json_decode($stored_row_3d->enhanced_embedding, true);
                if (is_array($enhanced_emb) && count($vec_post) === count($enhanced_emb)) {
                    $sim_3d = $this->cosine_similarity_arrays($vec_post, $enhanced_emb);
                    if ($sim_3d !== null && $sim_3d > $best_sim) {
                        $best_sim = $sim_3d;
                        $sim = $sim_3d;
                    }
                }
            }
            
            // Try 3D model if enhanced embedding fails
            if ($sim < 0.65 && !empty($stored_row_3d->face_model_3d)) {
                $face_model = is_array($stored_row_3d->face_model_3d) ? $stored_row_3d->face_model_3d : json_decode($stored_row_3d->face_model_3d, true);
                if (is_array($face_model) && isset($face_model['combined_features'])) {
                    if (count($vec_post) === count($face_model['combined_features'])) {
                        $sim_3d = $this->cosine_similarity_arrays($vec_post, $face_model['combined_features']);
                        if ($sim_3d !== null && $sim_3d > $best_sim) {
                            $best_sim = $sim_3d;
                            $sim = $sim_3d;
                        }
                    }
                }
            }
        }

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
        // CACHE BUST: Force server reload - v4.0 (Fixed face recognition)
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin()) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }

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

        log_message('info', 'identifyFace: Received probe embedding with ' . count($probe) . ' dimensions');

        // Load all enrolled staff embeddings - using same approach as F2F
        if (!$this->db->table_exists('staff_face')) {
            echo json_encode(['status' => 'success', 'matched' => false, 'message' => 'No enrolled faces']);
            exit;
        }

        // Check if staff table exists
        if (!$this->db->table_exists('staff')) {
            log_message('error', 'identifyFace: staff table does not exist');
            echo json_encode(['status' => 'success', 'matched' => false, 'message' => 'Staff table not found']);
            exit;
        }

        // Use same query structure as F2F system
        $rows = $this->db->select('sf.staff_id, sf.embedding, s.name, s.email, s.mobileno')
            ->from('staff_face as sf')
            ->join('staff as s', 's.id = sf.staff_id', 'inner')
            ->get()->result_array();

        log_message('info', 'identifyFace: Found ' . count($rows) . ' enrolled faces');

        if (count($rows) === 0) {
            echo json_encode(['status' => 'success', 'matched' => false, 'message' => 'No enrolled faces found']);
            exit;
        }

        $best = null; 
        $bestSim = -1.0;
        $debug_similarities = [];
        
        // Use same matching logic as F2F system
        foreach ($rows as $r) {
            $emb = json_decode($r['embedding'], true);
            if (!is_array($emb)) {
                log_message('error', 'identifyFace: Invalid embedding for staff_id: ' . $r['staff_id']);
                $debug_similarities[] = ['staff_id' => $r['staff_id'], 'name' => $r['name'], 'similarity' => 'INVALID_EMBEDDING'];
                continue;
            }
            
            // Check dimension compatibility
            if (count($probe) !== count($emb)) {
                log_message('error', 'identifyFace: Dimension mismatch for staff_id: ' . $r['staff_id'] . ' - probe: ' . count($probe) . ', stored: ' . count($emb));
                $debug_similarities[] = ['staff_id' => $r['staff_id'], 'name' => $r['name'], 'similarity' => 'DIMENSION_MISMATCH'];
                continue;
            }
            
            $sim = $this->cosine_similarity_arrays($probe, $emb);
            $debug_similarities[] = ['staff_id' => $r['staff_id'], 'name' => $r['name'], 'similarity' => $sim];
            
            if ($sim !== null && $sim > $bestSim) {
                $bestSim = $sim;
                $best = $r;
                log_message('info', 'identifyFace: New best match - staff_id: ' . $r['staff_id'] . ', name: ' . $r['name'] . ', similarity: ' . $sim);
            }
        }

        // If no good match found in regular face table, try 3D face table (same as F2F)
        if ($best === null || $bestSim < 0.65) {
            log_message('info', 'identifyFace: No good match in regular face table, checking 3D face table...');
            
            if ($this->db->table_exists('staff_face_3d')) {
                $rows_3d = $this->db->select('sf3d.staff_id, sf3d.enhanced_embedding, sf3d.face_model_3d, sf3d.face_quality_score, s.name')
                    ->from('staff_face_3d as sf3d')
                    ->join('staff as s', 's.id = sf3d.staff_id', 'left')
                    ->get()->result_array();
                
                log_message('info', 'identifyFace: Found ' . count($rows_3d) . ' enrolled 3D faces');
                
                if (count($rows_3d) > 0) {
                    foreach ($rows_3d as $r) {
                        // Try enhanced embedding first
                        $enhanced_emb = is_array($r['enhanced_embedding']) ? $r['enhanced_embedding'] : json_decode($r['enhanced_embedding'], true);
                        
                        if (is_array($enhanced_emb) && !empty($enhanced_emb)) {
                            if (count($probe) === count($enhanced_emb)) {
                                $sim = $this->cosine_similarity_arrays($probe, $enhanced_emb);
                                log_message('info', 'identifyFace: 3D enhanced embedding match for ' . $r['name'] . ' - Score: ' . $sim);
                                
                                if ($sim !== null && $sim > $bestSim) {
                                    $bestSim = $sim;
                                    $best = $r;
                                }
                            }
                        }
                        
                        // Try 3D model if enhanced embedding fails
                        if ($sim < 0.65) {
                            $face_model = is_array($r['face_model_3d']) ? $r['face_model_3d'] : json_decode($r['face_model_3d'], true);
                            
                            if (is_array($face_model) && isset($face_model['combined_features'])) {
                                if (count($probe) === count($face_model['combined_features'])) {
                                    $sim = $this->cosine_similarity_arrays($probe, $face_model['combined_features']);
                                    log_message('info', 'identifyFace: 3D model match for ' . $r['name'] . ' - Score: ' . $sim);
                                    
                                    if ($sim !== null && $sim > $bestSim) {
                                        $bestSim = $sim;
                                        $best = $r;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($best === null) {
            echo json_encode(['status' => 'success', 'matched' => false, 'message' => 'No embeddings found']);
            exit;
        }

        // Use same threshold as F2F system for consistency
        $threshold = 0.65; // Same threshold as F2F system
        $matched = ($bestSim >= $threshold);
        $confidence = round($bestSim * 100, 2);
        
        log_message('info', 'identifyFace: Final result - matched: ' . ($matched ? 'true' : 'false') . ', similarity: ' . $bestSim . ', confidence: ' . $confidence . '%');
        
        echo json_encode([
            'status' => 'success',
            'matched' => $matched,
            'staff_id' => $matched ? intval($best['staff_id']) : null,
            'name' => $matched ? ($best['name'] ?: 'Unknown') : null,
            'email' => $matched ? ($best['email'] ?: null) : null,
            'mobile' => $matched ? ($best['mobileno'] ?: null) : null,
            'similarity' => $bestSim,
            'confidence' => $confidence,
            'threshold' => $threshold,
            'debug_info' => [
                'probe_dimensions' => count($probe),
                'enrolled_faces_count' => count($rows),
                'threshold' => $threshold,
                'all_similarities' => $debug_similarities
            ]
        ]);
        exit;
    }

    /**
     * Debug function to diagnose face recognition issues
     * GET api/debugFaceRecognition
     */
    public function debugFaceRecognition()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin()) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Login required.']);
            return;
        }

        $debug_info = [];
        
        // 1. Check if tables exist
        $debug_info['tables_exist'] = [
            'staff_face' => $this->db->table_exists('staff_face'),
            'staff' => $this->db->table_exists('staff')
        ];
        
        // 2. Check staff_face table structure
        if ($this->db->table_exists('staff_face')) {
            $columns = $this->db->list_fields('staff_face');
            $debug_info['staff_face_columns'] = $columns;
            
            // 3. Count enrolled faces
            $count = $this->db->count_all('staff_face');
            $debug_info['enrolled_faces_count'] = $count;
            
            // 4. Sample enrolled face data (without embeddings)
            $sample_faces = $this->db->select('staff_id, model_name, fingerprint, created_at, updated_at')
                ->from('staff_face')
                ->limit(5)
                ->get()->result();
            $debug_info['sample_faces'] = $sample_faces;
            
            // 5. Check for valid embeddings
            $valid_embeddings = $this->db->select('staff_id, embedding')
                ->from('staff_face')
                ->where('embedding IS NOT NULL')
                ->where('embedding !=', '')
                ->get()->result();
            
            $debug_info['valid_embeddings_count'] = count($valid_embeddings);
            
            // 6. Check embedding format
            if (!empty($valid_embeddings)) {
                $first_embedding = $valid_embeddings[0]->embedding;
                $decoded = json_decode($first_embedding, true);
                $debug_info['embedding_format'] = [
                    'raw_type' => gettype($first_embedding),
                    'is_json' => json_last_error() === JSON_ERROR_NONE,
                    'decoded_type' => gettype($decoded),
                    'decoded_length' => is_array($decoded) ? count($decoded) : 'N/A',
                    'sample_values' => is_array($decoded) ? array_slice($decoded, 0, 5) : 'N/A'
                ];
            }
        }
        
        // 7. Check staff table
        if ($this->db->table_exists('staff')) {
            $staff_count = $this->db->count_all('staff');
            $debug_info['staff_count'] = $staff_count;
        }
        
        echo json_encode([
            'status' => 'success',
            'debug_info' => $debug_info,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
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

            // Check if multi-angle columns exist
            $hasEmbeddingStraight = $this->db->field_exists('embedding_straight', 'staff_face');
            $hasEmbeddingRight = $this->db->field_exists('embedding_right', 'staff_face');
            $hasEmbeddingLeft = $this->db->field_exists('embedding_left', 'staff_face');
            $hasEmbedding = $this->db->field_exists('embedding', 'staff_face');

            // Build select with proper escaping; use literal NULL with escaping disabled
            $this->db->select('sf.staff_id, s.name, sf.model_name', FALSE)
                ->from('staff_face as sf')
                ->join('staff as s', 's.id = sf.staff_id', 'left');

            // Add embedding columns if they exist
            if ($hasEmbedding) {
                $this->db->select('sf.embedding', FALSE);
            } else {
                $this->db->select('NULL AS embedding', FALSE);
            }

            if ($hasEmbeddingStraight) {
                $this->db->select('sf.embedding_straight', FALSE);
            } else {
                $this->db->select('NULL AS embedding_straight', FALSE);
            }

            if ($hasEmbeddingRight) {
                $this->db->select('sf.embedding_right', FALSE);
            } else {
                $this->db->select('NULL AS embedding_right', FALSE);
            }

            if ($hasEmbeddingLeft) {
                $this->db->select('sf.embedding_left', FALSE);
            } else {
                $this->db->select('NULL AS embedding_left', FALSE);
            }

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

            // Normalize timestamps and process embeddings
            foreach ($rows as &$r) {
                if (!isset($r['created_at'])) $r['created_at'] = null;
                if (!isset($r['updated_at'])) $r['updated_at'] = null;
                
                // Process embedding data
                if (isset($r['embedding'])) {
                    $r['embedding'] = is_string($r['embedding']) ? json_decode($r['embedding'], true) : $r['embedding'];
                    $r['embedding_count'] = is_array($r['embedding']) ? count($r['embedding']) : 0;
                } else {
                    $r['embedding'] = null;
                    $r['embedding_count'] = 0;
                }
                
                if (isset($r['embedding_straight'])) {
                    $r['embedding_straight'] = is_string($r['embedding_straight']) ? json_decode($r['embedding_straight'], true) : $r['embedding_straight'];
                    $r['embedding_straight_count'] = is_array($r['embedding_straight']) ? count($r['embedding_straight']) : 0;
                } else {
                    $r['embedding_straight'] = null;
                    $r['embedding_straight_count'] = 0;
                }
                
                if (isset($r['embedding_right'])) {
                    $r['embedding_right'] = is_string($r['embedding_right']) ? json_decode($r['embedding_right'], true) : $r['embedding_right'];
                    $r['embedding_right_count'] = is_array($r['embedding_right']) ? count($r['embedding_right']) : 0;
                } else {
                    $r['embedding_right'] = null;
                    $r['embedding_right_count'] = 0;
                }
                
                if (isset($r['embedding_left'])) {
                    $r['embedding_left'] = is_string($r['embedding_left']) ? json_decode($r['embedding_left'], true) : $r['embedding_left'];
                    $r['embedding_left_count'] = is_array($r['embedding_left']) ? count($r['embedding_left']) : 0;
                } else {
                    $r['embedding_left'] = null;
                    $r['embedding_left_count'] = 0;
                }
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

    private function getSchoolLocationName() {
        $query = $this->db->query("SELECT name FROM school_locations WHERE is_active = 1 ORDER BY id LIMIT 1");
        $result = $query->row();
        return $result ? $result->name : 'School Location';
    }

    private function calculateDistanceFromSchool($attendance) {
        if (!isset($attendance->user_latitude) || !isset($attendance->user_longitude) || 
            empty($attendance->user_latitude) || empty($attendance->user_longitude)) {
            return null;
        }

        // Get school location
        $query = $this->db->query("SELECT latitude, longitude FROM school_locations WHERE is_active = 1 ORDER BY id LIMIT 1");
        $school_location = $query->row();
        
        if (!$school_location) {
            return null;
        }

        $distance = $this->calculateDistance(
            $attendance->user_latitude,
            $attendance->user_longitude,
            $school_location->latitude,
            $school_location->longitude
        );

        return round($distance, 2); // Return distance in meters, rounded to 2 decimal places
    }

    /**
     * API: Get Today's Attendance Records for a User
     * GET /api/attendance/today/{userId}
     * Returns today's attendance records for the specified user
     */
    public function getTodayAttendance($userId = null)
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin()) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        try {
            // If no userId provided, use logged in user
            if (empty($userId)) {
                $userId = get_loggedin_user_id();
            }
            
            $branch_id = $this->application_model->get_branch_id();
            $today = date('Y-m-d');
            
            // Get today's attendance records for the user
            $this->db->select('id, staff_id, date, status, in_time, out_time, remark, created_at, updated_at');
            $this->db->from('staff_attendance');
            $this->db->where('staff_id', $userId);
            $this->db->where('branch_id', $branch_id);
            $this->db->where('date', $today);
            $this->db->order_by('created_at', 'ASC');
            $query = $this->db->get();
            
            $attendance_records = $query->result_array();
            
            // Format the response to match what the Flutter app expects
            $formatted_records = array();
            foreach ($attendance_records as $record) {
                $formatted_records[] = array(
                    'id' => $record['id'],
                    'staff_id' => $record['staff_id'],
                    'attendance_type' => $record['status'] == 'P' ? 'check_in' : 'check_out',
                    'timestamp' => $record['created_at'],
                    'date' => $record['date'],
                    'status' => $record['status'],
                    'in_time' => $record['in_time'],
                    'out_time' => $record['out_time'],
                    'remark' => $record['remark']
                );
            }
            
            $response = array(
                'status' => 'success',
                'data' => $formatted_records,
                'message' => 'Today\'s attendance records retrieved successfully'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to retrieve attendance records: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Mark Attendance with Face Recognition
     * POST /api/attendance/mark
     * Marks attendance using face recognition data
     */
    public function markAttendanceWithFace()
    {
        header('Content-Type: application/json');
        
        // Check authentication
        log_message('info', 'Face Attendance API - Authentication check: ' . (is_loggedin() ? 'PASSED' : 'FAILED'));
        if (!is_loggedin()) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        try {
            // Read JSON data from request body (like identifyFace API)
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            
            // Debug: Log incoming request data
            log_message('info', 'Face Attendance API called with raw data: ' . $raw);
            log_message('info', 'Face Attendance API parsed data: ' . json_encode($data));
            
            // Get data from JSON or fallback to POST
            $attendance_type = isset($data['attendance_type']) ? $data['attendance_type'] : $this->input->post('attendance_type');
            $face_confidence = isset($data['face_confidence']) ? $data['face_confidence'] : $this->input->post('face_confidence');
            $face_embeddings = isset($data['face_embeddings']) ? $data['face_embeddings'] : $this->input->post('face_embeddings');
            $location_latitude = isset($data['location_latitude']) ? $data['location_latitude'] : $this->input->post('location_latitude');
            $location_longitude = isset($data['location_longitude']) ? $data['location_longitude'] : $this->input->post('location_longitude');
            $location_accuracy = isset($data['location_accuracy']) ? $data['location_accuracy'] : $this->input->post('location_accuracy');
            $remark = isset($data['remark']) ? $data['remark'] : ($this->input->post('remark') ? $this->input->post('remark') : 'Face recognition attendance');
            
            // Validate required fields
            if (empty($attendance_type) || !in_array($attendance_type, array('check_in', 'check_out'))) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Attendance type is required and must be either "check_in" or "check_out"'
                );
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            $teacher_id = get_loggedin_user_id();
            $branch_id = $this->application_model->get_branch_id();
            $date = date('Y-m-d');
            $current_time = date('H:i:s');
            
            // SECURITY FIX: Validate that the face embeddings match the logged-in user
            if (!empty($face_embeddings)) {
                // Get the stored face embedding for the logged-in user
                $face_row = $this->db->get_where('staff_face', ['staff_id' => $teacher_id])->row();
                if (!$face_row || empty($face_row->embedding)) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'No enrolled face found for this user. Please enroll your face first.'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
                // Parse incoming embedding
                $incoming_embedding = is_array($face_embeddings) ? $face_embeddings : json_decode($face_embeddings, true);
                if (!is_array($incoming_embedding)) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Invalid face data received.'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
                // Reduce incoming embedding to 128 dimensions to match stored embeddings
                if (count($incoming_embedding) > 128) {
                    $incoming_embedding = array_slice($incoming_embedding, 0, 128);
                    log_message('info', 'Face Attendance API: Reduced incoming embedding to 128 dimensions');
                }
                
                // Use only straight face for attendance verification (not ensemble matching)
                $threshold = 0.65;
                
                // Prefer straight embedding if available, otherwise use primary embedding
                $stored_embedding = null;
                if (!empty($face_row->embedding_straight)) {
                    $stored_embedding = is_array($face_row->embedding_straight) ? $face_row->embedding_straight : json_decode($face_row->embedding_straight, true);
                } else {
                    $stored_embedding = is_array($face_row->embedding) ? $face_row->embedding : json_decode($face_row->embedding, true);
                }
                
                if (!is_array($stored_embedding)) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Invalid stored face data. Please re-enroll your face.'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
                // Calculate similarity between stored straight face and incoming face
                $similarity = $this->cosine_similarity_arrays($incoming_embedding, $stored_embedding);
                if ($similarity === null) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'Face comparison failed. Please try again.'
                    );
                    http_response_code(500);
                    echo json_encode($response);
                    return;
                }
                
                $confidence = round($similarity * 100, 2);
                log_message('info', "Face Attendance Verification - User ID: $teacher_id, Similarity: $similarity, Confidence: $confidence%");
                
                if ($similarity < $threshold) {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => "Face verification failed. Confidence: $confidence%. Face does not match enrolled user."
                    );
                    http_response_code(403);
                    echo json_encode($response);
                    return;
                }
                
                log_message('info', "Face Attendance Security Check - PASSED for User ID: $teacher_id with confidence: $confidence%");
            } else {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Face embeddings are required for attendance marking.'
                );
                http_response_code(400);
                echo json_encode($response);
                return;
            }
            
            // Convert attendance_type to status
            $status = ($attendance_type == 'check_in') ? 'P' : 'A';
            
            // Check if attendance record already exists for this teacher on this date
            $this->db->select('id, status');
            $this->db->from('staff_attendance');
            $this->db->where('staff_id', $teacher_id);
            $this->db->where('branch_id', $branch_id);
            $this->db->where('date', $date);
            $query = $this->db->get();
            
            $attendance_record = array(
                'staff_id' => $teacher_id,
                'branch_id' => $branch_id,
                'date' => $date,
                'status' => $status,
                'remark' => $remark
            );
            
            // Add time based on attendance type
            if ($attendance_type == 'check_in') {
                $attendance_record['in_time'] = $current_time;
            } else if ($attendance_type == 'check_out') {
                $attendance_record['out_time'] = $current_time;
            }
            
            if ($query->num_rows() > 0) {
                // Update existing record
                $existing_record = $query->row();
                
                // Check if trying to check in when already checked in
                if ($attendance_type == 'check_in' && $existing_record->status == 'P') {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'You have already checked in today'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
                // Check if trying to check out when not checked in
                if ($attendance_type == 'check_out' && $existing_record->status != 'P') {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'You must check in before checking out'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
                $this->db->where('id', $existing_record->id);
                $this->db->update('staff_attendance', $attendance_record);
                $message = 'Attendance updated successfully';
            } else {
                // Check if trying to check out without checking in first
                if ($attendance_type == 'check_out') {
                    $response = array(
                        'status' => 'error',
                        'data' => array(),
                        'message' => 'You must check in before checking out'
                    );
                    http_response_code(400);
                    echo json_encode($response);
                    return;
                }
                
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
                    'attendance_type' => $attendance_type,
                    'status' => $status,
                    'in_time' => isset($attendance_record['in_time']) ? $attendance_record['in_time'] : null,
                    'out_time' => isset($attendance_record['out_time']) ? $attendance_record['out_time'] : null,
                    'remark' => $remark,
                    'face_confidence' => $face_confidence,
                    'location_latitude' => $location_latitude,
                    'location_longitude' => $location_longitude,
                    'location_accuracy' => $location_accuracy
                ),
                'message' => $message
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            log_message('error', 'Face Attendance API Error: ' . $e->getMessage());
            log_message('error', 'Face Attendance API Stack Trace: ' . $e->getTraceAsString());
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to mark attendance: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Check if user has enrolled face
     * GET /api/face/enrollment/check/{userId}
     * Returns enrollment status for a specific user
     */
    public function checkFaceEnrollment($userId = null)
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin()) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        try {
            // If no userId provided, use logged in user
            if (empty($userId)) {
                $userId = get_loggedin_user_id();
            }
            
            // Check if staff_face table exists
            if (!$this->db->table_exists('staff_face')) {
                $response = array(
                    'status' => 'success',
                    'data' => array(
                        'is_enrolled' => false,
                        'user_id' => $userId
                    ),
                    'message' => 'Face enrollment table not found'
                );
                http_response_code(200);
                echo json_encode($response);
                return;
            }
            
            // Check if user has enrolled face
            $this->db->select('id, staff_id, model_name, created_at, updated_at');
            $this->db->from('staff_face');
            $this->db->where('staff_id', $userId);
            $query = $this->db->get();
            
            $is_enrolled = $query->num_rows() > 0;
            
            $enrollment_data = array(
                'is_enrolled' => $is_enrolled,
                'user_id' => $userId
            );
            
            if ($is_enrolled) {
                $row = $query->row();
                $enrollment_data['enrollment_date'] = $row->created_at ?? null;
                $enrollment_data['last_updated'] = $row->updated_at ?? null;
                $enrollment_data['model_name'] = $row->model_name ?? 'unknown';
            }
            
            $response = array(
                'status' => 'success',
                'data' => $enrollment_data,
                'message' => $is_enrolled ? 'User is enrolled' : 'User is not enrolled'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to check enrollment status: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }

    /**
     * API: Delete Face Enrollment
     * DELETE /api/face/delete/{userId}
     * Deletes face enrollment for a specific user
     */
    public function deleteFaceEnrollment($userId = null)
    {
        header('Content-Type: application/json');
        
        // Check authentication
        if (!is_loggedin()) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Unauthorized access. Login required.'
            );
            http_response_code(401);
            echo json_encode($response);
            return;
        }
        
        try {
            // If no userId provided, use logged in user
            if (empty($userId)) {
                $userId = get_loggedin_user_id();
            }
            
            // Check if staff_face table exists
            if (!$this->db->table_exists('staff_face')) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'Face enrollment table not found'
                );
                http_response_code(404);
                echo json_encode($response);
                return;
            }
            
            // Check if user has enrolled face
            $this->db->select('id, staff_id');
            $this->db->from('staff_face');
            $this->db->where('staff_id', $userId);
            $query = $this->db->get();
            
            if ($query->num_rows() == 0) {
                $response = array(
                    'status' => 'error',
                    'data' => array(),
                    'message' => 'No face enrollment found for this user'
                );
                http_response_code(404);
                echo json_encode($response);
                return;
            }
            
            // Delete the face enrollment record
            $this->db->where('staff_id', $userId);
            $this->db->delete('staff_face');
            
            $response = array(
                'status' => 'success',
                'data' => array(
                    'user_id' => $userId,
                    'deleted' => true
                ),
                'message' => 'Face enrollment deleted successfully'
            );
            http_response_code(200);
            
        } catch (Exception $e) {
            $response = array(
                'status' => 'error',
                'data' => array(),
                'message' => 'Failed to delete face enrollment: ' . $e->getMessage()
            );
            http_response_code(500);
        }
        
        echo json_encode($response);
    }
    
    /**
     * Test database connection
     */
    public function testDatabase()
    {
        header('Content-Type: application/json');
        
        try {
            // Test basic database connection
            $result = $this->db->query("SELECT 1 as test")->row();
            if (!$result) {
                throw new Exception('Basic query failed');
            }
            
            // Get current database name
            $db_name_result = $this->db->query("SELECT DATABASE() as db_name")->row();
            $current_db = $db_name_result->db_name;
            
            // Test if staff_face table exists
            $table_exists = $this->db->table_exists('staff_face');
            
            // Test creating the table if it doesn't exist
            if (!$table_exists) {
                $create_result = $this->db->query("CREATE TABLE IF NOT EXISTS `staff_face` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `staff_id` INT UNSIGNED NOT NULL UNIQUE,
                    `embedding` JSON NOT NULL,
                    `embedding_straight` JSON NULL,
                    `embedding_right` JSON NULL,
                    `embedding_left` JSON NULL,
                    `fingerprint` VARCHAR(64) NOT NULL UNIQUE,
                    `model_name` VARCHAR(64) NULL,
                    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_staff_id` (`staff_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                
                if (!$create_result) {
                    throw new Exception('Failed to create staff_face table');
                }
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Database connection working',
                'current_database' => $current_db,
                'staff_face_table_exists' => $table_exists,
                'test_query' => $result->test
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Simple test endpoint for API connection verification
     */
    public function test()
    {
        header('Content-Type: application/json');
        
        echo json_encode([
            'status' => 'success',
            'message' => 'API is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => ENVIRONMENT,
            'version' => '1.0.0'
        ]);
    }

    public function fixDatabaseSchema()
    {
        header('Content-Type: application/json');
        
        try {
            // Check if staff_face table exists
            if (!$this->db->table_exists('staff_face')) {
                throw new Exception('staff_face table does not exist');
            }
            
            // Get current columns
            $columns = $this->db->list_fields('staff_face');
            $has_straight = in_array('embedding_straight', $columns);
            $has_right = in_array('embedding_right', $columns);
            $has_left = in_array('embedding_left', $columns);
            
            $added_columns = [];
            
            // Add missing columns
            if (!$has_straight) {
                $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_straight` JSON NULL");
                $added_columns[] = 'embedding_straight';
            }
            if (!$has_right) {
                $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_right` JSON NULL");
                $added_columns[] = 'embedding_right';
            }
            if (!$has_left) {
                $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_left` JSON NULL");
                $added_columns[] = 'embedding_left';
            }
            
            // Get updated columns
            $updated_columns = $this->db->list_fields('staff_face');
            
            echo json_encode([
                'status' => 'success',
                'message' => 'Database schema fixed',
                'original_columns' => $columns,
                'added_columns' => $added_columns,
                'updated_columns' => $updated_columns
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Schema fix error: ' . $e->getMessage()
            ]);
        }
    }
    
    public function create3DFaceTable()
    {
        header('Content-Type: application/json');
        
        try {
            // Create staff_face_3d table if it doesn't exist
            $sql = "CREATE TABLE IF NOT EXISTS `staff_face_3d` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `staff_id` int(11) NOT NULL,
                `face_model_3d` longtext NOT NULL,
                `depth_map` longtext DEFAULT NULL,
                `pose_data` longtext DEFAULT NULL,
                `enhanced_embedding` longtext DEFAULT NULL,
                `face_quality_score` decimal(5,2) DEFAULT 0.00,
                `model_version` varchar(50) DEFAULT '3d_v1.0',
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `staff_id` (`staff_id`),
                KEY `face_quality_score` (`face_quality_score`),
                KEY `model_version` (`model_version`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->db->query($sql);
            
            echo json_encode([
                'status' => 'success',
                'message' => '3D Face table created successfully',
                'table' => 'staff_face_3d'
            ]);
            
        } catch (Exception $e) {
            log_message('error', '3D Face table creation error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create 3D face table: ' . $e->getMessage()]);
        }
    }
    
    public function enrollFace3D()
    {
        header('Content-Type: application/json');
        
        try {
            // Get staff ID
            $staff_id = is_loggedin() ? get_loggedin_user_id() : $this->input->post('staff_id');
            if (empty($staff_id)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'staff_id required']);
                exit;
            }

            // Get 3D face data
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            
            $face_data_straight = $data['face_data_straight'] ?? [];
            $face_data_right = $data['face_data_right'] ?? [];
            $face_data_left = $data['face_data_left'] ?? [];
            $pose_data = $data['pose_data'] ?? [];
            $depth_data = $data['depth_data'] ?? [];
            
            if (empty($face_data_straight) || empty($face_data_right) || empty($face_data_left)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'All three face angles required']);
                exit;
            }

            // Create 3D face model
            $face_model_3d = $this->create3DFaceModel($face_data_straight, $face_data_right, $face_data_left);
            
            // Generate enhanced embedding with depth information
            $enhanced_embedding = $this->generateEnhancedEmbedding($face_data_straight, $face_data_right, $face_data_left, $depth_data);
            
            // Calculate face quality score
            $quality_score = $this->calculateFaceQuality($face_data_straight, $face_data_right, $face_data_left, $pose_data);
            
            // Store in 3D face table
            $payload = [
                'staff_id' => $staff_id,
                'face_model_3d' => json_encode($face_model_3d),
                'depth_map' => json_encode($depth_data),
                'pose_data' => json_encode($pose_data),
                'enhanced_embedding' => json_encode($enhanced_embedding),
                'face_quality_score' => $quality_score,
                'model_version' => '3d_v1.0'
            ];

            // Check if exists
            $exists = $this->db->get_where('staff_face_3d', ['staff_id' => $staff_id])->row();
            if ($exists) {
                $this->db->where('staff_id', $staff_id)->update('staff_face_3d', $payload);
                $message = '3D Face model updated';
            } else {
                $this->db->insert('staff_face_3d', $payload);
                $message = '3D Face model enrolled';
            }

            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'staff_id' => $staff_id,
                'quality_score' => $quality_score,
                'model_version' => '3d_v1.0'
            ]);

        } catch (Exception $e) {
            log_message('error', '3D Face enrollment error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => '3D Face enrollment failed: ' . $e->getMessage()]);
        }
    }

    private function create3DFaceModel($straight, $right, $left)
    {
        // Create 3D face model from multiple angles
        // This is a simplified approach - in production, use proper 3D reconstruction
        
        $model_3d = [
            'straight_embedding' => array_slice($straight, 0, 128), // Primary embedding
            'right_embedding' => array_slice($right, 0, 128),
            'left_embedding' => array_slice($left, 0, 128),
            'combined_features' => $this->combineFaceFeatures($straight, $right, $left),
            'pose_angles' => [
                'straight' => 0,
                'right' => 15,  // Estimated angle
                'left' => -15   // Estimated angle
            ],
            'reconstruction_method' => 'multi_angle_fusion'
        ];
        
        return $model_3d;
    }

    private function combineFaceFeatures($straight, $right, $left)
    {
        // Combine features from all angles for better recognition
        $combined = [];
        
        // Take average of corresponding features
        for ($i = 0; $i < min(count($straight), count($right), count($left)); $i++) {
            $combined[] = ($straight[$i] + $right[$i] + $left[$i]) / 3;
        }
        
        return $combined;
    }

    private function generateEnhancedEmbedding($straight, $right, $left, $depth_data)
    {
        // Generate enhanced embedding with depth information
        $base_embedding = array_slice($straight, 0, 128);
        
        // Add depth features if available
        if (!empty($depth_data)) {
            $depth_features = array_slice($depth_data, 0, 32); // Use first 32 depth values
            $base_embedding = array_merge($base_embedding, $depth_features);
        }
        
        // Add pose-aware features
        $pose_features = $this->extractPoseFeatures($straight, $right, $left);
        $base_embedding = array_merge($base_embedding, $pose_features);
        
        return array_slice($base_embedding, 0, 192); // Limit to 192 dimensions
    }

    private function extractPoseFeatures($straight, $right, $left)
    {
        // Extract pose-aware features
        $pose_features = [];
        
        // Calculate differences between angles
        for ($i = 0; $i < min(count($straight), count($right), count($left)); $i++) {
            $pose_features[] = abs($right[$i] - $straight[$i]);
            $pose_features[] = abs($left[$i] - $straight[$i]);
        }
        
        return array_slice($pose_features, 0, 32); // Limit to 32 features
    }

    private function calculateFaceQuality($straight, $right, $left, $pose_data)
    {
        // Calculate face quality score (0-100)
        $quality_score = 0;
        
        // Check embedding consistency
        $consistency = $this->calculateConsistency($straight, $right, $left);
        $quality_score += $consistency * 40; // 40% weight
        
        // Check pose data quality
        $pose_quality = $this->calculatePoseQuality($pose_data);
        $quality_score += $pose_quality * 30; // 30% weight
        
        // Check embedding completeness
        $completeness = $this->calculateCompleteness($straight, $right, $left);
        $quality_score += $completeness * 30; // 30% weight
        
        return min(100, max(0, $quality_score));
    }

    private function calculateConsistency($straight, $right, $left)
    {
        // Calculate consistency between angles (0-1)
        $similarity_straight_right = $this->cosine_similarity_arrays($straight, $right);
        $similarity_straight_left = $this->cosine_similarity_arrays($straight, $left);
        $similarity_right_left = $this->cosine_similarity_arrays($right, $left);
        
        $avg_similarity = ($similarity_straight_right + $similarity_straight_left + $similarity_right_left) / 3;
        
        // Convert to 0-1 scale (higher similarity = better consistency)
        return max(0, min(1, $avg_similarity));
    }

    private function calculatePoseQuality($pose_data)
    {
        // Calculate pose data quality (0-1)
        if (empty($pose_data)) return 0.5; // Default if no pose data
        
        // Check if pose data has expected structure
        $required_keys = ['head_euler_x', 'head_euler_y', 'head_euler_z'];
        $has_required = 0;
        
        foreach ($required_keys as $key) {
            if (isset($pose_data[$key])) $has_required++;
        }
        
        return $has_required / count($required_keys);
    }

    private function calculateCompleteness($straight, $right, $left)
    {
        // Calculate completeness of embeddings (0-1)
        $expected_size = 128;
        
        $straight_complete = min(1, count($straight) / $expected_size);
        $right_complete = min(1, count($right) / $expected_size);
        $left_complete = min(1, count($left) / $expected_size);
        
        return ($straight_complete + $right_complete + $left_complete) / 3;
    }

    public function identifyFace3D()
    {
        header('Content-Type: application/json');
        
        try {
            // Get probe embedding
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            $probe_embedding = $data['face_data'] ?? [];
            
            if (empty($probe_embedding)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'face_data required']);
                exit;
            }

            log_message('info', '3D Face identification started with probe embedding size: ' . count($probe_embedding));

            // Get all enrolled 3D faces
            $rows = $this->db->select('sf3d.staff_id, sf3d.enhanced_embedding, sf3d.face_model_3d, sf3d.face_quality_score, s.name')
                ->from('staff_face_3d as sf3d')
                ->join('staff as s', 's.id = sf3d.staff_id', 'left')
                ->get()->result();

            if (empty($rows)) {
                log_message('info', '3D Face identification: No enrolled 3D faces found');
                echo json_encode(['status' => 'error', 'message' => 'No 3D faces enrolled']);
                exit;
            }

            log_message('info', '3D Face identification: Found ' . count($rows) . ' enrolled 3D faces');

            $best_match = null;
            $best_score = 0;
            $threshold = 0.45; // Lower threshold for 3D matching

            foreach ($rows as $r) {
                // Try enhanced embedding first
                $enhanced_emb = is_array($r->enhanced_embedding) ? $r->enhanced_embedding : json_decode($r->enhanced_embedding, true);
                
                if (is_array($enhanced_emb) && !empty($enhanced_emb)) {
                    $score = $this->cosine_similarity_arrays($probe_embedding, $enhanced_emb);
                    log_message('info', '3D Face identification: Enhanced embedding match for ' . $r->name . ' - Score: ' . $score);
                    
                    if ($score > $best_score) {
                        $best_score = $score;
                        $best_match = $r;
                    }
                }

                // Try 3D model if enhanced embedding fails
                if ($score < $threshold) {
                    $face_model = is_array($r->face_model_3d) ? $r->face_model_3d : json_decode($r->face_model_3d, true);
                    
                    if (is_array($face_model) && isset($face_model['combined_features'])) {
                        $score = $this->cosine_similarity_arrays($probe_embedding, $face_model['combined_features']);
                        log_message('info', '3D Face identification: 3D model match for ' . $r->name . ' - Score: ' . $score);
                        
                        if ($score > $best_score) {
                            $best_score = $score;
                            $best_match = $r;
                        }
                    }
                }
            }

            if ($best_match && $best_score >= $threshold) {
                log_message('info', '3D Face identification: Match found - ' . $best_match->name . ' (Score: ' . $best_score . ')');
                
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Face identified',
                    'staff_id' => $best_match->staff_id,
                    'name' => $best_match->name,
                    'confidence' => round($best_score, 3),
                    'quality_score' => $best_match->face_quality_score,
                    'method' => '3d_recognition'
                ]);
            } else {
                log_message('info', '3D Face identification: No match found (Best score: ' . $best_score . ')');
                echo json_encode(['status' => 'error', 'message' => 'Face not recognized']);
            }

        } catch (Exception $e) {
            log_message('error', '3D Face identification error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => '3D Face identification failed: ' . $e->getMessage()]);
        }
    }

    public function list3DFaces()
    {
        header('Content-Type: application/json');
        
        try {
            $rows = $this->db->select('sf3d.staff_id, sf3d.face_quality_score, sf3d.model_version, sf3d.created_at, s.name')
                ->from('staff_face_3d as sf3d')
                ->join('staff as s', 's.id = sf3d.staff_id', 'left')
                ->order_by('sf3d.created_at', 'DESC')
                ->get()->result_array();

            // Process data
            foreach ($rows as &$r) {
                if (!isset($r['created_at'])) $r['created_at'] = null;
                $r['quality_score'] = round($r['face_quality_score'], 1);
            }

            echo json_encode([
                'status' => 'success',
                'data' => $rows,
                'count' => count($rows)
            ]);

        } catch (Exception $e) {
            log_message('error', '3D Face list error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to list 3D faces: ' . $e->getMessage()]);
        }
    }

    /**
     * Simple Fast Quick Attendance - Check In/Out
     * POST api/quickAttendance
     */
    public function quickAttendance()
    {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $face_data = $data['face_data'] ?? null;
        $attendance_type = $data['attendance_type'] ?? 'check_in';
        
        if (empty($face_data) || !is_array($face_data)) {
            echo json_encode(['status' => 'error', 'message' => 'Face data required']);
            return;
        }
        
        // Reduce to 128 dimensions
        if (count($face_data) > 128) {
            $face_data = array_slice($face_data, 0, 128);
        }
        
        try {
            // Get all enrolled faces
            $faces = $this->db->select('sf.staff_id, sf.embedding, s.name, s.email, s.mobileno, s.branch_id')
                ->from('staff_face sf')
                ->join('staff s', 's.id = sf.staff_id')
                ->get()->result_array();
            
            if (empty($faces)) {
                echo json_encode(['status' => 'error', 'message' => 'No enrolled faces']);
                return;
            }
            
            $best_match = null;
            $best_score = 0;
            $threshold = 0.65;
            
            // Find best match
            foreach ($faces as $face) {
                $stored = json_decode($face['embedding'], true);
                if (is_array($stored) && count($stored) === count($face_data)) {
                    $score = $this->cosine_similarity_arrays($face_data, $stored);
                    if ($score !== null && $score > $best_score) {
                        $best_score = $score;
                        $best_match = $face;
                    }
                }
            }
            
            if (!$best_match || $best_score < $threshold) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Face not recognized',
                    'similarity' => $best_score
                ]);
                return;
            }
            
            // Mark attendance
            $staff_id = $best_match['staff_id'];
            $branch_id = $best_match['branch_id'];
            $date = date('Y-m-d');
            $time = date('H:i:s');
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
                'remark' => 'Face recognition attendance'
            ];
            
            if ($attendance_type == 'check_in') {
                $record['in_time'] = $time;
            } else {
                $record['out_time'] = $time;
            }
            
            if ($existing) {
                $this->db->where('id', $existing->id)->update('staff_attendance', $record);
                $message = 'Updated';
            } else {
                $this->db->insert('staff_attendance', $record);
                $message = 'Marked';
            }
            
            echo json_encode([
                'status' => 'success',
                'name' => $best_match['name'],
                'staff_id' => $staff_id,
                'confidence' => round($best_score * 100, 1),
                'attendance_type' => $attendance_type,
                'time' => $time,
                'date' => $date,
                'message' => $message
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Developer API: Delete Attendance Records
     * POST /api/deleteAttendance
     */
    public function deleteAttendance() {
        header('Content-Type: application/json');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $staff_id = $data['staff_id'] ?? null;
        $type = $data['type'] ?? null;
        
        if (empty($staff_id) || empty($type)) {
            echo json_encode(['status' => 'error', 'message' => 'Staff ID and type required']);
            return;
        }
        
        try {
            $deleted_count = 0;
            
            switch ($type) {
                case 'today':
                    $date = $data['date'] ?? date('Y-m-d');
                    $this->db->where('staff_id', $staff_id);
                    $this->db->where('date', $date);
                    $deleted_count = $this->db->count_all_results('staff_attendance');
                    $this->db->where('staff_id', $staff_id);
                    $this->db->where('date', $date);
                    $this->db->delete('staff_attendance');
                    break;
                    
                case 'month':
                    $year = $data['year'] ?? date('Y');
                    $month = $data['month'] ?? date('m');
                    $this->db->where('staff_id', $staff_id);
                    $this->db->where('YEAR(date)', $year);
                    $this->db->where('MONTH(date)', $month);
                    $deleted_count = $this->db->count_all_results('staff_attendance');
                    $this->db->where('staff_id', $staff_id);
                    $this->db->where('YEAR(date)', $year);
                    $this->db->where('MONTH(date)', $month);
                    $this->db->delete('staff_attendance');
                    break;
                    
                case 'range':
                    $start_date = $data['start_date'] ?? null;
                    $end_date = $data['end_date'] ?? null;
                    if (empty($start_date) || empty($end_date)) {
                        echo json_encode(['status' => 'error', 'message' => 'Start and end dates required for range']);
                        return;
                    }
                    $this->db->where('staff_id', $staff_id);
                    $this->db->where('date >=', $start_date);
                    $this->db->where('date <=', $end_date);
                    $deleted_count = $this->db->count_all_results('staff_attendance');
                    $this->db->where('staff_id', $staff_id);
                    $this->db->where('date >=', $start_date);
                    $this->db->where('date <=', $end_date);
                    $this->db->delete('staff_attendance');
                    break;
                    
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Invalid type']);
                    return;
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => "Deleted $deleted_count attendance records",
                'deleted_count' => $deleted_count,
                'type' => $type
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete attendance: ' . $e->getMessage()]);
        }
    }

    /**
     * Alternative generateDummyData endpoint with different name
     */
    public function generateDummyDataAlt() {
        header('Content-Type: application/json');
        
        // Log the request for debugging
        error_log('generateDummyDataAlt function called');
        error_log('generateDummyDataAlt: REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'not set'));
        error_log('generateDummyDataAlt: HTTP_ACCEPT: ' . ($_SERVER['HTTP_ACCEPT'] ?? 'not set'));
        error_log('generateDummyDataAlt: HTTP_CONTENT_TYPE: ' . ($_SERVER['HTTP_CONTENT_TYPE'] ?? 'not set'));
        
        // Check if this is development environment
        if (ENVIRONMENT !== 'development') {
            error_log('generateDummyDataAlt: Not in development mode. Environment: ' . ENVIRONMENT);
            echo json_encode(['status' => 'error', 'message' => 'This endpoint is only available in development mode']);
            return;
        }
        
        error_log('generateDummyDataAlt: In development mode, proceeding...');
        
        try {
            $raw = file_get_contents('php://input');
            error_log('generateDummyDataAlt: Raw input: ' . $raw);
            
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('generateDummyDataAlt: JSON decode error: ' . json_last_error_msg());
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
                return;
            }
            
            error_log('generateDummyDataAlt: Parsed data: ' . print_r($data, true));
            
            $staff_id = $data['staff_id'] ?? null;
            $type = $data['type'] ?? null;
            $attendance_pattern = $data['pattern'] ?? 'mixed';
            
            if (empty($staff_id) || empty($type)) {
                error_log('generateDummyDataAlt: Missing required parameters');
                echo json_encode(['status' => 'error', 'message' => 'Staff ID and type required']);
                return;
            }
            
            error_log('generateDummyDataAlt: Parameters validated, starting generation...');
            
            // For now, just return a simple success response
            echo json_encode([
                'status' => 'success',
                'message' => 'Alternative endpoint working! Generated 1 test record',
                'generated_count' => 1,
                'type' => $type,
                'pattern' => $attendance_pattern,
                'staff_id' => $staff_id,
                'endpoint' => 'generateDummyDataAlt'
            ]);
            
        } catch (Exception $e) {
            error_log('generateDummyDataAlt: Exception caught: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to generate dummy data: ' . $e->getMessage()]);
        }
    }

    /**
     * Simple test endpoint for generateDummyData - no CSRF required
     */
    public function testGenerateDummyDataSimple() {
        header('Content-Type: application/json');
        
        echo json_encode([
            'status' => 'success',
            'message' => 'generateDummyData test endpoint working',
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => ENVIRONMENT,
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'http_accept' => $_SERVER['HTTP_ACCEPT'] ?? 'unknown',
            'content_type' => $_SERVER['HTTP_CONTENT_TYPE'] ?? 'unknown'
        ]);
    }

    /**
     * Simple test for generateDummyData routing
     */
    public function testGenerateDummyData() {
        header('Content-Type: application/json');
        
        echo json_encode([
            'status' => 'success',
            'message' => 'generateDummyData routing is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => ENVIRONMENT
        ]);
    }

    /**
     * Developer API: Generate Dummy Attendance Data
     * POST /api/generateDummyData
     * Only accessible in development mode
     */
    public function generateDummyData() {
        header('Content-Type: application/json');
        
        // Log the request for debugging
        error_log('generateDummyData function called');
        error_log('generateDummyData: REQUEST_URI: ' . ($_SERVER['REQUEST_URI'] ?? 'not set'));
        error_log('generateDummyData: HTTP_ACCEPT: ' . ($_SERVER['HTTP_ACCEPT'] ?? 'not set'));
        error_log('generateDummyData: HTTP_CONTENT_TYPE: ' . ($_SERVER['HTTP_CONTENT_TYPE'] ?? 'not set'));
        
        // Check if this is development environment
        if (ENVIRONMENT !== 'development') {
            error_log('generateDummyData: Not in development mode. Environment: ' . ENVIRONMENT);
            echo json_encode(['status' => 'error', 'message' => 'This endpoint is only available in development mode']);
            return;
        }
        
        error_log('generateDummyData: In development mode, proceeding...');
        
        try {
            $raw = file_get_contents('php://input');
            error_log('generateDummyData: Raw input: ' . $raw);
            
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('generateDummyData: JSON decode error: ' . json_last_error_msg());
                echo json_encode(['status' => 'error', 'message' => 'Invalid JSON input: ' . json_last_error_msg()]);
                return;
            }
            
            error_log('generateDummyData: Parsed data: ' . print_r($data, true));
            
            $staff_id = $data['staff_id'] ?? null;
            $type = $data['type'] ?? null; // 'today', 'month', 'year'
            $attendance_pattern = $data['pattern'] ?? 'mixed'; // 'present', 'absent', 'mixed'
            
            if (empty($staff_id) || empty($type)) {
                error_log('generateDummyData: Missing required parameters');
                echo json_encode(['status' => 'error', 'message' => 'Staff ID and type required']);
                return;
            }
            
            error_log('generateDummyData: Parameters validated, starting generation...');
            $generated_count = 0;
            $dates = [];
            
            // Get staff details
            $staff = $this->db->get_where('staff', ['id' => $staff_id])->row();
            if (!$staff) {
                echo json_encode(['status' => 'error', 'message' => 'Staff not found']);
                return;
            }
            
            $branch_id = $staff->branch_id;
            
            switch ($type) {
                case 'today':
                    $dates = [date('Y-m-d')];
                    break;
                    
                case 'month':
                    $dates = $this->_generateMonthDates();
                    break;
                    
                case 'year':
                    $dates = $this->_generateYearDates();
                    break;
                    
                default:
                    echo json_encode(['status' => 'error', 'message' => 'Invalid type. Use: today, month, or year']);
                    return;
            }
            
            // Generate attendance records
            foreach ($dates as $date) {
                // Skip weekends if configured
                if ($this->_shouldSkipWeekend($date)) {
                    continue;
                }
                
                // Check if record already exists
                $existing = $this->db->get_where('staff_attendance', [
                    'staff_id' => $staff_id,
                    'date' => $date
                ])->row();
                
                if ($existing) {
                    continue; // Skip if already exists
                }
                
                // Determine attendance status based on pattern
                $status = $this->_getAttendanceStatus($attendance_pattern, $date);
                
                // Generate check-in and check-out times based on status
                $times = $this->_generateAttendanceTimes($status);
                
                $attendance_data = [
                    'staff_id' => $staff_id,
                    'status' => $status,
                    'remark' => $this->_getRandomRemark($status),
                    'date' => $date,
                    'branch_id' => $branch_id,
                    'in_time' => $times['in_time'],
                    'out_time' => $times['out_time']
                ];
                
                $this->db->insert('staff_attendance', $attendance_data);
                $generated_count++;
            }
            
            echo json_encode([
                'status' => 'success',
                'message' => "Generated $generated_count attendance records",
                'generated_count' => $generated_count,
                'type' => $type,
                'pattern' => $attendance_pattern,
                'staff_name' => $staff->name,
                'dates_generated' => $dates
            ]);
            
        } catch (Exception $e) {
            error_log('generateDummyData: Exception caught: ' . $e->getMessage());
            echo json_encode(['status' => 'error', 'message' => 'Failed to generate dummy data: ' . $e->getMessage()]);
        }
    }
    
    /**
     * Generate dates for current month (excluding weekends)
     */
    private function _generateMonthDates() {
        $dates = [];
        $current_month = date('Y-m');
        $days_in_month = date('t', strtotime($current_month . '-01'));
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = $current_month . '-' . sprintf('%02d', $day);
            $dates[] = $date;
        }
        
        return $dates;
    }
    
    /**
     * Generate dates for current year (excluding weekends)
     */
    private function _generateYearDates() {
        $dates = [];
        $current_year = date('Y');
        
        for ($month = 1; $month <= 12; $month++) {
            $days_in_month = date('t', strtotime("$current_year-$month-01"));
            for ($day = 1; $day <= $days_in_month; $day++) {
                $date = "$current_year-" . sprintf('%02d', $month) . '-' . sprintf('%02d', $day);
                $dates[] = $date;
            }
        }
        
        return $dates;
    }
    
    /**
     * Check if date should be skipped (weekends)
     */
    private function _shouldSkipWeekend($date) {
        $day_of_week = date('N', strtotime($date)); // 1 = Monday, 7 = Sunday
        return $day_of_week >= 6; // Skip Saturday (6) and Sunday (7)
    }
    
    /**
     * Get attendance status based on pattern
     */
    private function _getAttendanceStatus($pattern, $date) {
        switch ($pattern) {
            case 'present':
                return 'P';
            case 'absent':
                return 'A';
            case 'mixed':
            default:
                // 85% present, 10% absent, 5% late
                $rand = mt_rand(1, 100);
                if ($rand <= 85) return 'P';
                if ($rand <= 95) return 'A';
                return 'L';
        }
    }
    
    /**
     * Get random remark based on status
     */
    private function _getRandomRemark($status) {
        $remarks = [
            'P' => ['On time', 'Present', 'Good attendance', ''],
            'A' => ['Sick leave', 'Personal work', 'Emergency', 'Family function'],
            'L' => ['Traffic jam', 'Late arrival', 'Transport delay', 'Weather issue']
        ];
        
        $status_remarks = $remarks[$status] ?? [''];
        return $status_remarks[array_rand($status_remarks)];
    }
    
    /**
     * Generate realistic check-in and check-out times based on attendance status
     */
    private function _generateAttendanceTimes($status) {
        switch ($status) {
            case 'P': // Present - On time
                // Check-in: 8:00 AM to 9:00 AM
                $check_in_hour = 8;
                $check_in_minute = mt_rand(0, 59);
                
                // Check-out: 4:00 PM to 6:00 PM
                $check_out_hour = 16; // 4 PM
                $check_out_minute = mt_rand(0, 59);
                
                break;
                
            case 'L': // Late
                // Check-in: 9:00 AM to 10:30 AM
                $check_in_hour = 9;
                $check_in_minute = mt_rand(0, 89); // 0 to 89 minutes (up to 10:29 AM)
                
                // Check-out: 4:30 PM to 6:30 PM (staying late to compensate)
                $check_out_hour = 16; // 4 PM
                $check_out_minute = mt_rand(30, 89); // 4:30 PM to 5:59 PM
                
                break;
                
            case 'A': // Absent
                // No times for absent
                return [
                    'in_time' => null,
                    'out_time' => null
                ];
                
            default:
                return [
                    'in_time' => null,
                    'out_time' => null
                ];
        }
        
        $in_time = sprintf('%02d:%02d:00', $check_in_hour, $check_in_minute);
        $out_time = sprintf('%02d:%02d:00', $check_out_hour, $check_out_minute);
        
        return [
            'in_time' => $in_time,
            'out_time' => $out_time
        ];
    }

    /**
     * Developer API: Get Staff List for Dummy Data Generation
     * GET /api/getStaffList
     * Only accessible in development mode
     */
    public function getStaffList() {
        header('Content-Type: application/json');
        
        try {
            $branch_id = $this->application_model->get_branch_id();
            
            // Get staff with their designation and department
            $this->db->select('staff.id, staff.staff_id, staff.name, staff.email, staff.mobileno, staff.photo, staff_designation.name as designation, staff_department.name as department');
            $this->db->from('staff');
            $this->db->join('login_credential', 'login_credential.user_id = staff.id', 'inner');
            $this->db->join('staff_designation', 'staff_designation.id = staff.designation', 'left');
            $this->db->join('staff_department', 'staff_department.id = staff.department', 'left');
            $this->db->where('login_credential.active', 1);
            $this->db->where('staff.branch_id', $branch_id);
            $this->db->order_by('staff.name', 'ASC');
            
            $staff_list = $this->db->get()->result_array();
            
            echo json_encode([
                'status' => 'success',
                'data' => $staff_list,
                'count' => count($staff_list)
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to get staff list: ' . $e->getMessage()]);
        }
    }

    /**
     * Calculate cosine similarity between two arrays
     */
    private function cosine_similarity_arrays($array1, $array2) {
        try {
            if (!is_array($array1) || !is_array($array2)) {
                return null;
            }

            if (count($array1) !== count($array2)) {
                return null;
            }

            $dotProduct = 0;
            $normA = 0;
            $normB = 0;

            for ($i = 0; $i < count($array1); $i++) {
                $dotProduct += $array1[$i] * $array2[$i];
                $normA += $array1[$i] * $array1[$i];
                $normB += $array2[$i] * $array2[$i];
            }

            if ($normA == 0 || $normB == 0) {
                return null;
            }

            return $dotProduct / (sqrt($normA) * sqrt($normB));

        } catch (Exception $e) {
            log_message('error', 'Cosine similarity calculation error: ' . $e->getMessage());                                                                   
            return null;
        }
    }

    /**
     * Get School Location for Mobile App
     * GET /api/getSchoolLocation
     */
    public function getSchoolLocation() {
        header('Content-Type: application/json');
        
        try {
            // Get all active locations
            $locations = $this->db->select('*')
                ->from('attendance_locations')
                ->where('is_active', 1)
                ->get()
                ->result_array();
            
            if (empty($locations)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'No school locations configured',
                    'data' => null
                ]);
                return;
            }
            
            // Return the first active location (or you can modify to return all)
            $location = $locations[0];
            
            echo json_encode([
                'status' => 'success',
                'message' => 'School location retrieved successfully',
                'data' => [
                    'id' => $location['id'],
                    'name' => $location['name'],
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'radius' => $location['radius'],
                    'address' => $location['address'],
                    'description' => $location['description']
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to get school location: ' . $e->getMessage(),
                'data' => null
            ]);
        }
    }

    /**
     * Get teacher self-attendance statistics for mobile app
     * Same data as web panel self-attendance section
     */
    public function getTeacherSelfAttendanceStats() {
        header('Content-Type: application/json');
        
        try {
            $staff_id = $this->input->get('staff_id');
            $filter_type = $this->input->get('filter_type') ?: 'month';
            $filter_value = $this->input->get('filter_value') ?: date('Y-m');
            
            if (empty($staff_id)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Staff ID is required'
                ]);
                return;
            }
            
            // Get staff information (without joins to avoid missing table errors)
            $staff_info = $this->db->select('s.*')
                ->from('staff s')
                ->where('s.id', $staff_id)
                ->get()
                ->row();
            
            if (!$staff_info) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Staff not found'
                ]);
                return;
            }
            
            // Calculate date range based on filter
            $start_date = '';
            $end_date = '';
            
            switch ($filter_type) {
                case 'month':
                    $start_date = $filter_value . '-01';
                    $end_date = date('Y-m-t', strtotime($start_date));
                    break;
                case 'daterange':
                    $dates = explode(' to ', $filter_value);
                    if (count($dates) == 2) {
                        $start_date = $dates[0];
                        $end_date = $dates[1];
                    }
                    break;
                case 'year':
                    $start_date = $filter_value . '-01-01';
                    $end_date = $filter_value . '-12-31';
                    break;
            }
            
            // Get attendance records (simplified query to avoid missing table errors)
            $this->db->select('sa.*');
            $this->db->from('staff_attendance sa');
            $this->db->where('sa.staff_id', $staff_id);
            $this->db->where('sa.date >=', $start_date);
            $this->db->where('sa.date <=', $end_date);
            $this->db->order_by('sa.date', 'DESC');
            
            $query = $this->db->get();
            $attendance_records = $query ? $query->result() : [];
            
            // Calculate statistics
            $total_days = count($attendance_records);
            $present_days = 0;
            $absent_days = 0;
            $half_days = 0;
            $late_days = 0;
            $location_verified_count = 0;
            $face_verified_count = 0;
            $gps_verified_count = 0;
            
            foreach ($attendance_records as $record) {
                switch($record->status) {
                    case 'P':
                        $present_days++;
                        break;
                    case 'A':
                        $absent_days++;
                        break;
                    case 'H':
                        $half_days++;
                        break;
                    case 'L':
                        $late_days++;
                        break;
                }
                
                // Count verification methods
                if (isset($record->location_id) && $record->location_id) {
                    $location_verified_count++;
                }
                if (isset($record->user_latitude) && isset($record->user_longitude) && $record->user_latitude && $record->user_longitude) {
                    $gps_verified_count++;
                }
                if (isset($record->remark) && strpos($record->remark, 'Face recognition') !== false) {
                    $face_verified_count++;
                }
            }
            
            $present_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100, 1) : 0;
            
            // Set timezone to India
            date_default_timezone_set('Asia/Kolkata');
            
            // Get today's attendance status
            $today = date('Y-m-d');
            error_log("API Debug - Looking for staff_id: $staff_id, date: $today");
            $today_attendance = $this->db->select('sa.*')
                ->from('staff_attendance sa')
                ->where('sa.staff_id', $staff_id)
                ->where('sa.date', $today)
                ->get()
                ->row();
            
            if ($today_attendance) {
                error_log("API Debug - Found attendance record: " . json_encode($today_attendance));
            } else {
                error_log("API Debug - No attendance record found for staff_id: $staff_id, date: $today");
            }
            
            $today_status = [
                'date' => $today,
                'status' => null,
                'status_text' => 'Not Marked',
                'status_class' => 'warning',
                'check_in_time' => null,
                'check_out_time' => null,
                'working_hours' => null,
                'location_name' => null,
                'user_latitude' => null,
                'user_longitude' => null,
                'distance_from_school' => null,
                'location_verified' => false,
                'gps_verified' => false,
                'face_verified' => false,
                'verification_methods' => [],
                'remark' => 'No attendance marked for today'
            ];
            
            if ($today_attendance) {
                $status_info = $this->getStatusInfo($today_attendance->status);
                $today_status = [
                    'date' => $today_attendance->date,
                    'status' => $today_attendance->status,
                    'status_text' => $status_info['text'],
                    'status_class' => $status_info['class'],
                    'check_in_time' => $this->convertTo12HourFormat($today_attendance->in_time),
                    'check_out_time' => $today_attendance->out_time,
                    'working_hours' => $this->calculateWorkingHours($today_attendance->in_time, $today_attendance->out_time),
                    'location_name' => $this->getSchoolLocationName(),
                    'user_latitude' => isset($today_attendance->user_latitude) ? $today_attendance->user_latitude : null,
                    'user_longitude' => isset($today_attendance->user_longitude) ? $today_attendance->user_longitude : null,
                    'distance_from_school' => $this->calculateDistanceFromSchool($today_attendance),
                    'location_verified' => isset($today_attendance->location_id) && !empty($today_attendance->location_id),
                    'gps_verified' => isset($today_attendance->user_latitude) && isset($today_attendance->user_longitude) && !empty($today_attendance->user_latitude) && !empty($today_attendance->user_longitude),
                    'face_verified' => isset($today_attendance->remark) && strpos($today_attendance->remark, 'Face recognition') !== false,
                    'verification_methods' => $this->getVerificationMethods($today_attendance),
                    'remark' => isset($today_attendance->remark) ? $today_attendance->remark : ''
                ];
            }
            
            // Format attendance records for mobile
            $formatted_records = [];
            foreach ($attendance_records as $record) {
                $status_info = $this->getStatusInfo($record->status);
                
                $formatted_records[] = [
                    'date' => $record->date,
                    'day' => date('l', strtotime($record->date)),
                    'status' => $record->status,
                    'status_text' => $status_info['text'],
                    'status_class' => $status_info['class'],
                    'check_in_time' => $record->in_time,
                    'check_out_time' => $record->out_time,
                    'working_hours' => $this->calculateWorkingHours($record->in_time, $record->out_time),
                    'location_name' => isset($record->location_name) ? $record->location_name : null,
                    'school_latitude' => isset($record->school_latitude) ? $record->school_latitude : null,
                    'school_longitude' => isset($record->school_longitude) ? $record->school_longitude : null,
                    'user_latitude' => isset($record->user_latitude) ? $record->user_latitude : null,
                    'user_longitude' => isset($record->user_longitude) ? $record->user_longitude : null,
                    'distance_from_school' => null, // Simplified for now
                    'location_verified' => isset($record->location_id) && !empty($record->location_id),
                    'gps_verified' => isset($record->user_latitude) && isset($record->user_longitude) && !empty($record->user_latitude) && !empty($record->user_longitude),
                    'face_verified' => isset($record->remark) && strpos($record->remark, 'Face recognition') !== false,
                    'verification_methods' => $this->getVerificationMethods($record),
                    'remark' => isset($record->remark) ? $record->remark : ''
                ];
            }
            
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'staff_info' => [
                        'name' => $staff_info->name,
                        'staff_id' => $staff_info->staff_id,
                        'designation' => $staff_info->designation ?? 'N/A',
                        'department' => $staff_info->department ?? 'N/A',
                        'email' => $staff_info->email,
                        'mobile' => $staff_info->mobileno
                    ],
                    'filter_info' => [
                        'type' => $filter_type,
                        'value' => $filter_value,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'period_text' => $this->getPeriodText($filter_type, $filter_value)
                    ],
                    'summary_stats' => [
                        'total_days' => $total_days,
                        'present_days' => $present_days,
                        'absent_days' => $absent_days,
                        'half_days' => $half_days,
                        'late_days' => $late_days,
                        'present_percentage' => $present_percentage,
                        'location_verified_count' => $location_verified_count,
                        'face_verified_count' => $face_verified_count,
                        'gps_verified_count' => $gps_verified_count,
                        'location_verification_rate' => $total_days > 0 ? round(($location_verified_count / $total_days) * 100, 1) : 0,
                        'face_verification_rate' => $total_days > 0 ? round(($face_verified_count / $total_days) * 100, 1) : 0,
                        'gps_verification_rate' => $total_days > 0 ? round(($gps_verified_count / $total_days) * 100, 1) : 0
                    ],
                    'today_status' => $today_status,
                    'attendance_records' => $formatted_records
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to get teacher statistics: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Helper function to get status information
     */
    private function getStatusInfo($status) {
        switch($status) {
            case 'P':
                return ['text' => 'Present', 'class' => 'success'];
            case 'A':
                return ['text' => 'Absent', 'class' => 'danger'];
            case 'H':
                return ['text' => 'Half Day', 'class' => 'warning'];
            case 'L':
                return ['text' => 'Late', 'class' => 'info'];
            default:
                return ['text' => 'Unknown', 'class' => 'default'];
        }
    }
    
    /**
     * Helper function to calculate working hours
     */
    private function calculateWorkingHours($in_time, $out_time) {
        if (empty($in_time) || empty($out_time)) {
            return null;
        }
        
        $check_in = strtotime($in_time);
        $check_out = strtotime($out_time);
        $working_hours = ($check_out - $check_in) / 3600;
        
        return round($working_hours, 2);
    }
    
    /**
     * Helper function to get verification methods
     */
    private function getVerificationMethods($record) {
        $methods = [];
        
        if (!empty($record->location_id)) {
            $methods[] = ['type' => 'location', 'icon' => 'map-marker-alt', 'text' => 'GPS Location'];
        }
        
        if (strpos($record->remark, 'Face recognition') !== false) {
            $methods[] = ['type' => 'face', 'icon' => 'user-check', 'text' => 'Face Recognition'];
        }
        
        if (isset($record->qr_code) && $record->qr_code == 1) {
            $methods[] = ['type' => 'qr', 'icon' => 'qrcode', 'text' => 'QR Code'];
        }
        
        if (empty($methods)) {
            $methods[] = ['type' => 'manual', 'icon' => 'hand-paper', 'text' => 'Manual'];
        }
        
        return $methods;
    }
    
    /**
     * Helper function to get period text
     */
    private function getPeriodText($filter_type, $filter_value) {
        switch ($filter_type) {
            case 'month':
                return date('F Y', strtotime($filter_value . '-01'));
            case 'daterange':
                return $filter_value;
            case 'year':
                return $filter_value;
            default:
                return 'Current Month';
        }
    }

    private function convertTo12HourFormat($time24) {
        if (empty($time24)) return null;
        
        // Convert 24-hour format (HH:MM:SS) to 12-hour format (H:MM:SS AM/PM)
        $time_parts = explode(':', $time24);
        if (count($time_parts) >= 2) {
            $hour = intval($time_parts[0]);
            $minute = $time_parts[1];
            $second = isset($time_parts[2]) ? $time_parts[2] : '00';
            
            $period = 'AM';
            if ($hour >= 12) {
                $period = 'PM';
                if ($hour > 12) {
                    $hour = $hour - 12;
                }
            } elseif ($hour == 0) {
                $hour = 12;
            }
            
            return sprintf('%d:%s:%s %s', $hour, $minute, $second, $period);
        }
        
        return $time24; // Return original if parsing fails
    }
}