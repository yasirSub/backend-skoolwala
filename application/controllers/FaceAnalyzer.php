<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * New Face Analyzer - Simple and Working
 * Uses the same successful approach as F2F system
 */
class FaceAnalyzer extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->load->helper('general');
        
        // Disable CSRF protection for API endpoints
        $this->config->set_item('csrf_protection', false);
        
        // Add CORS headers
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
     * New Face Analyzer - Simple and Working
     * POST api/face/analyze
     */
    public function analyze()
    {
        header('Content-Type: application/json');
        
        // Debug logging
        log_message('info', 'FaceAnalyzer: analyze method called');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        log_message('info', 'FaceAnalyzer: Raw input = ' . $raw);
        log_message('info', 'FaceAnalyzer: Decoded data = ' . print_r($data, true));
        
        $probe_embedding = isset($data['face_data']) ? $data['face_data'] : null;
        
        if (empty($probe_embedding) || !is_array($probe_embedding)) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error', 
                'message' => 'face_data array is required'
            ]);
            exit;
        }

        log_message('info', 'FaceAnalyzer: Probe embedding dimensions = ' . count($probe_embedding));

        // Reduce probe embedding to 128 dimensions to match enrolled faces
        if (count($probe_embedding) > 128) {
            $probe_embedding = array_slice($probe_embedding, 0, 128);
            log_message('info', 'FaceAnalyzer: Reduced probe embedding to 128 dimensions');
        }

        try {
            // Check if staff_face table exists
            if (!$this->db->table_exists('staff_face')) {
                echo json_encode([
                    'status' => 'success',
                    'matched' => false,
                    'message' => 'No enrolled faces found'
                ]);
                exit;
            }

            // Get all enrolled faces - using same approach as F2F
            $rows = $this->db->select('sf.staff_id, sf.embedding, s.name, s.email, s.mobileno')
                ->from('staff_face as sf')
                ->join('staff as s', 's.id = sf.staff_id', 'inner')
                ->get()->result_array();

            log_message('info', 'FaceAnalyzer: Found ' . count($rows) . ' enrolled faces');

            if (count($rows) === 0) {
                echo json_encode([
                    'status' => 'success',
                    'matched' => false,
                    'message' => 'No enrolled faces found'
                ]);
                exit;
            }

            $best_match = null;
            $best_score = 0;
            $threshold = 0.65; // Same threshold as F2F system
            $debug_info = [];

            // Compare with each enrolled face
            foreach ($rows as $row) {
                $stored_embedding = json_decode($row['embedding'], true);
                
                if (!is_array($stored_embedding)) {
                    log_message('error', 'FaceAnalyzer: Invalid embedding for staff_id: ' . $row['staff_id']);
                    $debug_info[] = [
                        'staff_id' => $row['staff_id'],
                        'name' => $row['name'],
                        'similarity' => 'INVALID_EMBEDDING',
                        'probe_dimensions' => count($probe_embedding),
                        'stored_dimensions' => 'INVALID'
                    ];
                    continue;
                }

                // Check dimension compatibility
                $probe_dims = count($probe_embedding);
                $stored_dims = count($stored_embedding);
                
                log_message('info', 'FaceAnalyzer: Comparing dimensions - probe: ' . $probe_dims . ', stored: ' . $stored_dims . ' for staff: ' . $row['name']);
                
                if ($probe_dims !== $stored_dims) {
                    log_message('warning', 'FaceAnalyzer: Dimension mismatch for staff_id: ' . $row['staff_id'] . ' - probe: ' . $probe_dims . ', stored: ' . $stored_dims);
                    $debug_info[] = [
                        'staff_id' => $row['staff_id'],
                        'name' => $row['name'],
                        'similarity' => 'DIMENSION_MISMATCH',
                        'probe_dimensions' => $probe_dims,
                        'stored_dimensions' => $stored_dims
                    ];
                    continue;
                }

                // Calculate cosine similarity
                $similarity = $this->cosine_similarity($probe_embedding, $stored_embedding);
                
                $debug_info[] = [
                    'staff_id' => $row['staff_id'],
                    'name' => $row['name'],
                    'similarity' => $similarity,
                    'probe_dimensions' => $probe_dims,
                    'stored_dimensions' => $stored_dims
                ];

                log_message('info', 'FaceAnalyzer: Staff ' . $row['name'] . ' (ID: ' . $row['staff_id'] . ') similarity: ' . $similarity);

                if ($similarity !== null && $similarity > $best_score) {
                    $best_score = $similarity;
                    $best_match = $row;
                    log_message('info', 'FaceAnalyzer: New best match - ' . $row['name'] . ' with score: ' . $similarity);
                }
            }

            // Check if we have a good match
            if ($best_match && $best_score >= $threshold) {
                log_message('info', 'FaceAnalyzer: SUCCESS - Matched ' . $best_match['name'] . ' with confidence: ' . ($best_score * 100) . '%');
                
                echo json_encode([
                    'status' => 'success',
                    'matched' => true,
                    'staff_id' => intval($best_match['staff_id']),
                    'name' => $best_match['name'],
                    'email' => $best_match['email'],
                    'mobile' => $best_match['mobileno'],
                    'similarity' => $best_score,
                    'confidence' => round($best_score * 100, 2),
                    'threshold' => $threshold,
                    'message' => 'Face recognized successfully'
                ]);
            } else {
                log_message('info', 'FaceAnalyzer: No match found - best score: ' . $best_score . ', threshold: ' . $threshold);
                
                echo json_encode([
                    'status' => 'success',
                    'matched' => false,
                    'similarity' => $best_score,
                    'threshold' => $threshold,
                    'message' => 'No matching face found',
                    'debug_info' => $debug_info,
                    'probe_dimensions' => count($probe_embedding),
                    'enrolled_faces_count' => count($rows)
                ]);
            }

        } catch (Exception $e) {
            log_message('error', 'FaceAnalyzer error: ' . $e->getMessage());
            log_message('error', 'FaceAnalyzer stack trace: ' . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Face analysis failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate cosine similarity between two arrays
     * Same function as F2F system
     */
    private function cosine_similarity($array1, $array2) {
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
     * Test endpoint to verify analyzer is working
     * GET api/face/testAnalyzer
     */
    public function testAnalyzer()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        echo json_encode([
            'status' => 'success',
            'message' => 'FaceAnalyzer is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}
