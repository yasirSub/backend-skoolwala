<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class FaceApi extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->helper('general'); // Load the helper that contains is_loggedin()
        
        // Disable CSRF protection for API endpoints
        $this->config->set_item('csrf_protection', false);
        
        // Add CORS headers for all face API endpoints
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Multi-face enrollment with duplicate check
     * POST api/face/enroll
     * Supports both single face and multi-angle face enrollment
     */
    public function enroll()
    {
        header('Content-Type: application/json');
        
        if (!is_loggedin()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Login required.']);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        // Support both single face and multi-face enrollment
        $embedding = $data['embedding'] ?? null;
        $face_data_straight = $data['face_data_straight'] ?? null;
        $face_data_right = $data['face_data_right'] ?? null;
        $face_data_left = $data['face_data_left'] ?? null;
        $staff_id = $data['staff_id'] ?? null;
        $skip_duplicate_check = $data['skip_duplicate_check'] ?? false;

        // Check if we have any face data
        if (empty($embedding) && empty($face_data_straight)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Face embedding or multi-angle face data required']);
            return;
        }

        if (!$staff_id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'staff_id is required']);
            return;
        }

        try {
            // Create staff_face table if it doesn't exist
            if (!$this->db->table_exists('staff_face')) {
                $this->db->query("CREATE TABLE IF NOT EXISTS `staff_face` (
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
            }

            // Check if multi-angle columns exist, if not add them
            $columns = $this->db->list_fields('staff_face');
            $has_straight = in_array('embedding_straight', $columns);
            $has_right = in_array('embedding_right', $columns);
            $has_left = in_array('embedding_left', $columns);
            
            if (!$has_straight) {
                $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_straight` JSON NULL");
            }
            if (!$has_right) {
                $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_right` JSON NULL");
            }
            if (!$has_left) {
                $this->db->query("ALTER TABLE `staff_face` ADD COLUMN `embedding_left` JSON NULL");
            }

            // Prepare embeddings for storage
            if (!empty($face_data_straight)) {
                // Multi-angle enrollment
                $face_data_straight = array_slice($face_data_straight, 0, 128); // Limit to 128 dimensions
                $face_data_right = array_slice($face_data_right, 0, 128);
                $face_data_left = array_slice($face_data_left, 0, 128);
                
                $embedding_straight = is_array($face_data_straight) ? json_encode($face_data_straight) : $face_data_straight;
                $embedding_right = is_array($face_data_right) ? json_encode($face_data_right) : $face_data_right;
                $embedding_left = is_array($face_data_left) ? json_encode($face_data_left) : $face_data_left;
                
                // Use straight face as primary embedding for backward compatibility
                $primary_embedding = $embedding_straight;
                $fingerprint = hash('sha256', $embedding_straight);
                $primary_face_data = $face_data_straight;
                
                log_message('info', "Multi-angle face enrollment for staff_id: $staff_id (reduced to 128 dimensions)");
            } else {
                // Legacy single face enrollment
                $face_data = array_slice($embedding, 0, 128); // Limit to 128 dimensions
                
                $primary_embedding = is_array($face_data) ? json_encode($face_data) : $face_data;
                $fingerprint = hash('sha256', $primary_embedding);
                $primary_face_data = $face_data;
                
                log_message('info', "Legacy single face enrollment for staff_id: $staff_id (reduced to 128 dimensions)");
            }

            // Check if staff already enrolled
            $existing = $this->db->get_where('staff_face', ['staff_id' => $staff_id])->row();
            
            // Perform duplicate check only for new enrollments or when not skipped
            if (!$existing && !$skip_duplicate_check) {
                $threshold = 0.75; // Higher threshold for duplicate detection
                $duplicate_found = false;
                $duplicate_info = null;

                // Check for duplicates in staff_face table
                $rows = $this->db->select('sf.staff_id, sf.embedding, sf.embedding_straight, sf.embedding_right, sf.embedding_left, s.name, s.email, s.mobileno')
                    ->from('staff_face as sf')
                    ->join('staff as s', 's.id = sf.staff_id', 'inner')
                    ->get()->result_array();

                foreach ($rows as $r) {
                    // Check primary embedding
                    $stored_embedding = json_decode($r['embedding'], true);
                    if (is_array($stored_embedding) && count($stored_embedding) === count($primary_face_data)) {
                        $similarity = $this->cosine_similarity_arrays($primary_face_data, $stored_embedding);
                        
                        if ($similarity !== null && $similarity >= $threshold) {
                            $duplicate_found = true;
                            $duplicate_info = [
                                'staff_id' => intval($r['staff_id']),
                                'name' => $r['name'] ?: 'Unknown',
                                'email' => $r['email'] ?: null,
                                'mobile' => $r['mobileno'] ?: null,
                                'similarity' => $similarity,
                                'confidence' => round($similarity * 100, 2),
                                'matched_angle' => 'primary'
                            ];
                            break;
                        }
                    }

                    // Check multi-angle embeddings if available
                    $angles_to_check = [
                        'straight' => $r['embedding_straight'],
                        'right' => $r['embedding_right'],
                        'left' => $r['embedding_left']
                    ];

                    foreach ($angles_to_check as $angle => $angle_embedding) {
                        if (!empty($angle_embedding)) {
                            $stored_angle_embedding = json_decode($angle_embedding, true);
                            if (is_array($stored_angle_embedding) && count($stored_angle_embedding) === count($primary_face_data)) {
                                $similarity = $this->cosine_similarity_arrays($primary_face_data, $stored_angle_embedding);
                                
                                if ($similarity !== null && $similarity >= $threshold) {
                                    $duplicate_found = true;
                                    $duplicate_info = [
                                        'staff_id' => intval($r['staff_id']),
                                        'name' => $r['name'] ?: 'Unknown',
                                        'email' => $r['email'] ?: null,
                                        'mobile' => $r['mobileno'] ?: null,
                                        'similarity' => $similarity,
                                        'confidence' => round($similarity * 100, 2),
                                        'matched_angle' => $angle
                                    ];
                                    break 2; // Break out of both loops
                                }
                            }
                        }
                    }
                }

                // Check 3D face table if no duplicate found in simple table
                if (!$duplicate_found && $this->db->table_exists('staff_face_3d')) {
                    $rows = $this->db->select('sf3d.staff_id, sf3d.enhanced_embedding, sf3d.face_model_3d, s.name')
                        ->from('staff_face_3d as sf3d')
                        ->join('staff as s', 's.id = sf3d.staff_id', 'left')
                        ->get()->result_array();

                    foreach ($rows as $r) {
                        // Try enhanced embedding first
                        $enhanced_emb = is_array($r['enhanced_embedding']) ? $r['enhanced_embedding'] : json_decode($r['enhanced_embedding'], true);
                        
                        if (is_array($enhanced_emb) && count($enhanced_emb) === count($primary_face_data)) {
                            $similarity = $this->cosine_similarity_arrays($primary_face_data, $enhanced_emb);
                            
                            if ($similarity !== null && $similarity >= $threshold) {
                                $duplicate_found = true;
                                $duplicate_info = [
                                    'staff_id' => intval($r['staff_id']),
                                    'name' => $r['name'] ?: 'Unknown',
                                    'email' => null,
                                    'mobile' => null,
                                    'similarity' => $similarity,
                                    'confidence' => round($similarity * 100, 2)
                                ];
                                break;
                            }
                        }

                        // Try 3D model if enhanced embedding doesn't match
                        if (!$duplicate_found) {
                            $face_model = is_array($r['face_model_3d']) ? $r['face_model_3d'] : json_decode($r['face_model_3d'], true);
                            
                            if (is_array($face_model) && isset($face_model['combined_features'])) {
                                if (count($primary_face_data) === count($face_model['combined_features'])) {
                                    $similarity = $this->cosine_similarity_arrays($primary_face_data, $face_model['combined_features']);
                                    
                                    if ($similarity !== null && $similarity >= $threshold) {
                                        $duplicate_found = true;
                                        $duplicate_info = [
                                            'staff_id' => intval($r['staff_id']),
                                            'name' => $r['name'] ?: 'Unknown',
                                            'email' => null,
                                            'mobile' => null,
                                            'similarity' => $similarity,
                                            'confidence' => round($similarity * 100, 2)
                                        ];
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                if ($duplicate_found) {
                    http_response_code(409);
                    echo json_encode([
                        'status' => 'duplicate_found',
                        'message' => 'Face already enrolled',
                        'duplicate_info' => $duplicate_info,
                        'threshold' => $threshold
                    ]);
                    return;
                }
            }

            // Proceed with enrollment
            $payload = [
                'staff_id' => $staff_id,
                'embedding' => $primary_embedding,
                'fingerprint' => $fingerprint,
                'model_name' => 'facenet'
            ];

            // Add multi-angle embeddings if available
            if (!empty($face_data_straight)) {
                $payload['embedding_straight'] = $embedding_straight;
                $payload['embedding_right'] = $embedding_right;
                $payload['embedding_left'] = $embedding_left;
            }

            if ($existing) {
                // Update existing enrollment
                $this->db->update('staff_face', $payload, ['staff_id' => $staff_id]);
                $message = 'Face updated successfully';
            } else {
                // Create new enrollment
                $this->db->insert('staff_face', $payload);
                $message = 'Face enrolled successfully';
            }

            echo json_encode([
                'status' => 'success',
                'message' => $message,
                'staff_id' => $staff_id,
                'enrollment_type' => !empty($face_data_straight) ? 'multi_angle' : 'single_face'
            ]);

        } catch (Exception $e) {
            log_message('error', 'Face enrollment error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Failed to enroll face: ' . $e->getMessage(),
                'staff_id' => $staff_id
            ]);
        }
    }

    /**
     * Face identification
     * POST api/face/identify
     */
    public function identify()
    {
        header('Content-Type: application/json');
        
        if (!is_loggedin()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Login required.']);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $probe_embedding = $data['face_data'] ?? [];

        if (empty($probe_embedding)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'face_data required']);
            return;
        }

        // Reduce probe embedding to 128 dimensions to match enrolled faces
        if (count($probe_embedding) > 128) {
            $probe_embedding = array_slice($probe_embedding, 0, 128);
            log_message('info', 'FaceApi identify: Reduced probe embedding to 128 dimensions');
        }

        try {
            $best_match = null;
            $best_score = 0;
            $threshold = 0.65; // Use same threshold as F2F system

            // Check simple face table first - using same approach as F2F
            if ($this->db->table_exists('staff_face')) {
                $rows = $this->db->select('sf.staff_id, sf.embedding, s.name, s.email, s.mobileno')
                    ->from('staff_face as sf')
                    ->join('staff as s', 's.id = sf.staff_id', 'inner')
                    ->get()->result_array();

                foreach ($rows as $r) {
                    $stored_embedding = json_decode($r['embedding'], true);
                    
                    if (is_array($stored_embedding) && count($stored_embedding) === count($probe_embedding)) {
                        $score = $this->cosine_similarity_arrays($probe_embedding, $stored_embedding);
                        
                        if ($score !== null && $score > $best_score) {
                            $best_score = $score;
                            $best_match = $r;
                        }
                    }
                }
            }

            // Check 3D face table if no good match found - same as F2F
            if ($best_score < $threshold && $this->db->table_exists('staff_face_3d')) {
                $rows = $this->db->select('sf3d.staff_id, sf3d.enhanced_embedding, sf3d.face_model_3d, s.name')
                    ->from('staff_face_3d as sf3d')
                    ->join('staff as s', 's.id = sf3d.staff_id', 'left')
                    ->get()->result_array();

                foreach ($rows as $r) {
                    // Try enhanced embedding first
                    $enhanced_emb = is_array($r['enhanced_embedding']) ? $r['enhanced_embedding'] : json_decode($r['enhanced_embedding'], true);
                    
                    if (is_array($enhanced_emb) && count($enhanced_emb) === count($probe_embedding)) {
                        $score = $this->cosine_similarity_arrays($probe_embedding, $enhanced_emb);
                        
                        if ($score !== null && $score > $best_score) {
                            $best_score = $score;
                            $best_match = $r;
                        }
                    }

                    // Try 3D model if enhanced embedding fails
                    if ($score < $threshold) {
                        $face_model = is_array($r['face_model_3d']) ? $r['face_model_3d'] : json_decode($r['face_model_3d'], true);
                        
                        if (is_array($face_model) && isset($face_model['combined_features'])) {
                            if (count($probe_embedding) === count($face_model['combined_features'])) {
                                $score = $this->cosine_similarity_arrays($probe_embedding, $face_model['combined_features']);
                                
                                if ($score !== null && $score > $best_score) {
                                    $best_score = $score;
                                    $best_match = $r;
                                }
                            }
                        }
                    }
                }
            }

            if ($best_match && $best_score >= $threshold) {
                echo json_encode([
                    'status' => 'success',
                    'matched' => true,
                    'staff_id' => intval($best_match['staff_id']),
                    'name' => $best_match['name'] ?: 'Unknown',
                    'email' => $best_match['email'] ?: null,
                    'mobile' => $best_match['mobileno'] ?: null,
                    'similarity' => $best_score,
                    'confidence' => round($best_score * 100, 2),
                    'threshold' => $threshold
                ]);
            } else {
                echo json_encode([
                    'status' => 'success',
                    'matched' => false,
                    'message' => 'No matching face found',
                    'similarity' => $best_score,
                    'threshold' => $threshold
                ]);
            }

        } catch (Exception $e) {
            log_message('error', 'Face identification error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Face identification failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Check for duplicate face before enrollment
     * POST api/face/check-duplicate
     * Supports both single face and multi-angle face duplicate checking
     */
    public function checkDuplicate()
    {
        header('Content-Type: application/json');
        
        if (!is_loggedin()) {
            http_response_code(401);
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Login required.']);
            return;
        }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        // Support both single face and multi-face duplicate checking
        $embedding = $data['embedding'] ?? null;
        $face_data_straight = $data['face_data_straight'] ?? null;
        $face_data_right = $data['face_data_right'] ?? null;
        $face_data_left = $data['face_data_left'] ?? null;
        $staff_id = $data['staff_id'] ?? null; // Optional - if provided, exclude this staff from duplicate check

        // Check if we have any face data
        if (empty($embedding) && empty($face_data_straight)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Face embedding or multi-angle face data required']);
            return;
        }

        try {
            $threshold = 0.75; // Higher threshold for duplicate detection (more strict)
            $duplicate_found = false;
            $duplicate_info = null;
            $best_similarity = 0;

            // Prepare face data for comparison
            if (!empty($face_data_straight)) {
                // Multi-angle duplicate check - use straight face as primary
                $face_data_straight = array_slice($face_data_straight, 0, 128);
                $primary_face_data = $face_data_straight;
                $check_type = 'multi_angle';
            } else {
                // Single face duplicate check
                $face_data = array_slice($embedding, 0, 128);
                $primary_face_data = $face_data;
                $check_type = 'single_face';
            }

            // Check simple face table first
            if ($this->db->table_exists('staff_face')) {
                $query = $this->db->select('sf.staff_id, sf.embedding, sf.embedding_straight, sf.embedding_right, sf.embedding_left, s.name, s.email, s.mobileno')
                    ->from('staff_face as sf')
                    ->join('staff as s', 's.id = sf.staff_id', 'inner');
                
                // Exclude current staff if staff_id is provided (for updates)
                if ($staff_id) {
                    $query->where('sf.staff_id !=', $staff_id);
                }
                
                $rows = $query->get()->result_array();

                foreach ($rows as $r) {
                    // Check primary embedding
                    $stored_embedding = json_decode($r['embedding'], true);
                    if (is_array($stored_embedding) && count($stored_embedding) === count($primary_face_data)) {
                        $similarity = $this->cosine_similarity_arrays($primary_face_data, $stored_embedding);
                        
                        if ($similarity !== null && $similarity > $best_similarity) {
                            $best_similarity = $similarity;
                            
                            if ($similarity >= $threshold) {
                                $duplicate_found = true;
                                $duplicate_info = [
                                    'staff_id' => intval($r['staff_id']),
                                    'name' => $r['name'] ?: 'Unknown',
                                    'email' => $r['email'] ?: null,
                                    'mobile' => $r['mobileno'] ?: null,
                                    'similarity' => $similarity,
                                    'confidence' => round($similarity * 100, 2),
                                    'matched_angle' => 'primary'
                                ];
                            }
                        }
                    }

                    // Check multi-angle embeddings if available
                    $angles_to_check = [
                        'straight' => $r['embedding_straight'],
                        'right' => $r['embedding_right'],
                        'left' => $r['embedding_left']
                    ];

                    foreach ($angles_to_check as $angle => $angle_embedding) {
                        if (!empty($angle_embedding)) {
                            $stored_angle_embedding = json_decode($angle_embedding, true);
                            if (is_array($stored_angle_embedding) && count($stored_angle_embedding) === count($primary_face_data)) {
                                $similarity = $this->cosine_similarity_arrays($primary_face_data, $stored_angle_embedding);
                                
                                if ($similarity !== null && $similarity > $best_similarity) {
                                    $best_similarity = $similarity;
                                    
                                    if ($similarity >= $threshold) {
                                        $duplicate_found = true;
                                        $duplicate_info = [
                                            'staff_id' => intval($r['staff_id']),
                                            'name' => $r['name'] ?: 'Unknown',
                                            'email' => $r['email'] ?: null,
                                            'mobile' => $r['mobileno'] ?: null,
                                            'similarity' => $similarity,
                                            'confidence' => round($similarity * 100, 2),
                                            'matched_angle' => $angle
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Check 3D face table if no duplicate found in simple table
            if (!$duplicate_found && $this->db->table_exists('staff_face_3d')) {
                $query = $this->db->select('sf3d.staff_id, sf3d.enhanced_embedding, sf3d.face_model_3d, s.name')
                    ->from('staff_face_3d as sf3d')
                    ->join('staff as s', 's.id = sf3d.staff_id', 'left');
                
                // Exclude current staff if staff_id is provided
                if ($staff_id) {
                    $query->where('sf3d.staff_id !=', $staff_id);
                }
                
                $rows = $query->get()->result_array();

                foreach ($rows as $r) {
                    // Try enhanced embedding first
                    $enhanced_emb = is_array($r['enhanced_embedding']) ? $r['enhanced_embedding'] : json_decode($r['enhanced_embedding'], true);
                    
                    if (is_array($enhanced_emb) && count($enhanced_emb) === count($primary_face_data)) {
                        $similarity = $this->cosine_similarity_arrays($primary_face_data, $enhanced_emb);
                        
                        if ($similarity !== null && $similarity > $best_similarity) {
                            $best_similarity = $similarity;
                            
                            if ($similarity >= $threshold) {
                                $duplicate_found = true;
                                $duplicate_info = [
                                    'staff_id' => intval($r['staff_id']),
                                    'name' => $r['name'] ?: 'Unknown',
                                    'email' => null,
                                    'mobile' => null,
                                    'similarity' => $similarity,
                                    'confidence' => round($similarity * 100, 2)
                                ];
                            }
                        }
                    }

                    // Try 3D model if enhanced embedding doesn't match
                    if (!$duplicate_found) {
                        $face_model = is_array($r['face_model_3d']) ? $r['face_model_3d'] : json_decode($r['face_model_3d'], true);
                        
                        if (is_array($face_model) && isset($face_model['combined_features'])) {
                            if (count($primary_face_data) === count($face_model['combined_features'])) {
                                $similarity = $this->cosine_similarity_arrays($primary_face_data, $face_model['combined_features']);
                                
                                if ($similarity !== null && $similarity > $best_similarity) {
                                    $best_similarity = $similarity;
                                    
                                    if ($similarity >= $threshold) {
                                        $duplicate_found = true;
                                        $duplicate_info = [
                                            'staff_id' => intval($r['staff_id']),
                                            'name' => $r['name'] ?: 'Unknown',
                                            'email' => null,
                                            'mobile' => null,
                                            'similarity' => $similarity,
                                            'confidence' => round($similarity * 100, 2)
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if ($duplicate_found) {
                echo json_encode([
                    'status' => 'duplicate_found',
                    'message' => 'Face already enrolled',
                    'duplicate_info' => $duplicate_info,
                    'threshold' => $threshold,
                    'check_type' => $check_type
                ]);
            } else {
                echo json_encode([
                    'status' => 'no_duplicate',
                    'message' => 'Face is unique, enrollment allowed',
                    'best_similarity' => $best_similarity,
                    'threshold' => $threshold,
                    'check_type' => $check_type
                ]);
            }

        } catch (Exception $e) {
            log_message('error', 'Duplicate face check error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode([
                'status' => 'error', 
                'message' => 'Failed to check for duplicate face: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate cosine similarity between two arrays
     */
    private function cosine_similarity_arrays($array1, $array2) {
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
}
