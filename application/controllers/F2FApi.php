<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class F2FApi extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('general');
        
        // Set CORS headers
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
     * Test endpoint to verify F2FApi is accessible
     */
    public function test()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        echo json_encode(['status' => 'success', 'message' => 'F2FApi controller is working']);
        exit;
    }

    /**
     * Test endpoint for single delete
     */
    public function testDelete()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $person_id = isset($data['person_id']) ? intval($data['person_id']) : null;
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Test delete endpoint reached',
            'received_person_id' => $person_id,
            'raw_input' => $raw
        ]);
        exit;
    }

    /**
     * Face2Face: Register a generic person with name, age, mobile and embedding
     * Request JSON: { name, age?, mobile?, embedding: number[] }
     */
    public function register()
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
    public function analyze()
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

        // If no good match found in f2f table, check 3D face table
        if ($best === null || $bestSim < 0.65) {
            log_message('info', 'f2fAnalyze: No good match in f2f table, checking 3D face table...');
            
            if ($this->db->table_exists('staff_face_3d')) {
                $rows_3d = $this->db->select('sf3d.staff_id, sf3d.enhanced_embedding, sf3d.face_model_3d, sf3d.face_quality_score, s.name')
                    ->from('staff_face_3d as sf3d')
                    ->join('staff as s', 's.id = sf3d.staff_id', 'left')
                    ->get()->result_array();
                
                log_message('info', 'f2fAnalyze: Found ' . count($rows_3d) . ' enrolled 3D faces');
                
                if (count($rows_3d) > 0) {
                    foreach ($rows_3d as $r) {
                        // Try enhanced embedding first
                        $enhanced_emb = is_array($r['enhanced_embedding']) ? $r['enhanced_embedding'] : json_decode($r['enhanced_embedding'], true);
                        
                        if (is_array($enhanced_emb) && !empty($enhanced_emb)) {
                            // Check dimension compatibility
                            if (count($probe) === count($enhanced_emb)) {
                                $sim = $this->cosine_similarity_arrays($probe, $enhanced_emb);
                                log_message('info', 'f2fAnalyze: 3D enhanced embedding match for ' . $r['name'] . ' - Score: ' . $sim);
                                
                                if ($sim !== null && $sim > $bestSim) {
                                    $bestSim = $sim;
                                    $best = [
                                        'id' => $r['staff_id'],
                                        'name' => $r['name'],
                                        'mobile' => null,
                                        'embedding' => json_encode($enhanced_emb)
                                    ];
                                }
                            }
                        }
                        
                        // Try 3D model if enhanced embedding fails
                        if ($sim < 0.65) {
                            $face_model = is_array($r['face_model_3d']) ? $r['face_model_3d'] : json_decode($r['face_model_3d'], true);
                            
                            if (is_array($face_model) && isset($face_model['combined_features'])) {
                                if (count($probe) === count($face_model['combined_features'])) {
                                    $sim = $this->cosine_similarity_arrays($probe, $face_model['combined_features']);
                                    log_message('info', 'f2fAnalyze: 3D model match for ' . $r['name'] . ' - Score: ' . $sim);
                                    
                                    if ($sim !== null && $sim > $bestSim) {
                                        $bestSim = $sim;
                                        $best = [
                                            'id' => $r['staff_id'],
                                            'name' => $r['name'],
                                            'mobile' => null,
                                            'embedding' => json_encode($face_model['combined_features'])
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
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
    public function create()
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
    public function attach()
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
    public function attachImage()
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
    public function list()
    {
        header('Content-Type: application/json');
        
        try {
            if (!$this->db->table_exists('f2f_person')) {
                echo json_encode(['status'=>'success','data'=>[]]);
                exit;
            }
                
            // Get all registered people with their face embeddings
            $select = 'p.id as person_id, p.name, p.age, p.mobile, p.created_at';
            if ($this->db->table_exists('f2f_embedding')) {
                $select .= ', e.photo, e.embedding';
                $this->db->select($select);
                $this->db->from('f2f_person as p');
                $this->db->join('f2f_embedding as e','e.person_id = p.id','left');
            } else {
                $this->db->select($select);
                $this->db->from('f2f_person as p');
            }
            $this->db->order_by('p.id','DESC');
            $rows = $this->db->get()->result_array();
                
            // Process data to include embedding information
            foreach ($rows as &$r) {
                // Convert embedding from JSON string to array if needed
                if (isset($r['embedding']) && !empty($r['embedding'])) {
                    $embedding = is_array($r['embedding']) ? $r['embedding'] : json_decode($r['embedding'], true);
                    $r['embedding'] = $embedding;
                } else {
                    $r['embedding'] = null;
                }
            }
                
            echo json_encode(['status'=>'success','data'=>$rows]);
            exit;
                
        } catch (Exception $e) {
            log_message('error', 'F2F list error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to list people: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Face2Face: Check if a face is already registered
     * POST api/f2f/checkFace
     */
    public function checkFace()
    {
        header('Content-Type: application/json');
        
        try {
            // Get input data
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            $embedding = $data['embedding'] ?? null;

            if (!is_array($embedding) || empty($embedding)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Face embedding is required']);
                return;
            }

            // Check if f2f_embedding table exists
            if (!$this->db->table_exists('f2f_embedding')) {
                echo json_encode(['status' => 'success', 'exists' => false, 'message' => 'No registered faces']);
                return;
            }

            // Get all embeddings with limit to prevent performance issues
            $rows = $this->db->select('fe.person_id, fe.embedding, fp.name')
                ->from('f2f_embedding as fe')
                ->join('f2f_person as fp', 'fp.id = fe.person_id', 'left')
                ->limit(20) // Reduced limit for better performance
                ->get()->result();

            if (count($rows) === 0) {
                echo json_encode(['status' => 'success', 'exists' => false, 'message' => 'No registered faces']);
                return;
            }

            $best = null;
            $bestSim = -1.0;
            $threshold = 0.45;

            foreach ($rows as $r) {
                // Parse embedding safely
                $stored_embedding = null;
                if (is_array($r->embedding)) {
                    $stored_embedding = $r->embedding;
                } else {
                    $stored_embedding = json_decode($r->embedding, true);
                }
                
                if (!is_array($stored_embedding) || count($stored_embedding) === 0) {
                    continue;
                }

                // Check dimension compatibility
                if (count($embedding) !== count($stored_embedding)) {
                    continue;
                }

                // Calculate similarity with timeout protection
                $similarity = $this->cosine_similarity_arrays($embedding, $stored_embedding);
                
                if ($similarity !== null && $similarity > $bestSim) {
                    $bestSim = $similarity;
                    $best = $r;
                }

                // Early exit if we find a very high similarity
                if ($bestSim >= 0.9) {
                    break;
                }
            }

            $exists = ($best !== null && $bestSim >= $threshold);

            echo json_encode([
                'status' => 'success',
                'exists' => $exists,
                'name' => $exists ? $best->name : null,
                'person_id' => $exists ? intval($best->person_id) : null,
                'similarity' => $bestSim,
                'confidence' => round($bestSim * 100, 2),
                'message' => $exists ? "Face already linked to {$best->name}" : "Face not found - can proceed with registration"
            ]);

        } catch (Exception $e) {
            log_message('error', 'F2F check face error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to check face: ' . $e->getMessage()]);
        }
    }

    /**
     * Face2Face: Delete a registered person and their data
     * POST api/f2f/delete
     */
    public function delete()
    {
        // Debug logging
        log_message('info', 'F2F delete method called');
        error_log('F2F delete method called - Debug');
        
        header('Content-Type: application/json');
        
        // Allow all origins for F2F operations
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Authentication disabled for F2F operations
        // if (!is_loggedin()) {
        //     http_response_code(401);
        //     echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Login required.']);
        //     return;
        // }

        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        $person_id = isset($data['person_id']) ? intval($data['person_id']) : null;

        // Debug: Log the request
        log_message('info', 'F2F Delete: Raw input = ' . $raw);
        log_message('info', 'F2F Delete: Decoded data = ' . print_r($data, true));
        log_message('info', 'F2F Delete: Person ID = ' . $person_id);

        if ($person_id === null) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'person_id is required']);
            exit;
        }

        try {
            // Check if person exists
            if (!$this->db->table_exists('f2f_person')) {
                echo json_encode(['status' => 'error', 'message' => 'F2F person table does not exist']);
                exit;
            }

            $person = $this->db->get_where('f2f_person', ['id' => $person_id])->row();
            if (!$person) {
                echo json_encode(['status' => 'error', 'message' => 'Person not found']);
                exit;
            }

            // Delete embeddings first (if table exists)
            if ($this->db->table_exists('f2f_embedding')) {
                $this->db->delete('f2f_embedding', ['person_id' => $person_id]);
                log_message('info', 'F2F Delete: Deleted embeddings for person_id = ' . $person_id);
            }

            // Delete person
            $this->db->delete('f2f_person', ['id' => $person_id]);
            log_message('info', 'F2F Delete: Deleted person with id = ' . $person_id);

            echo json_encode([
                'status' => 'success', 
                'message' => 'Person and all associated data deleted successfully',
                'deleted_person_id' => $person_id,
                'deleted_name' => $person->name
            ]);
            exit;

        } catch (Exception $e) {
            log_message('error', 'F2F delete error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete person: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * Face2Face: Delete all F2F data
     * POST api/f2f/deleteAll
     */
    public function deleteAll()
    {
        // Debug logging
        log_message('info', 'F2F deleteAll method called');
        error_log('F2F deleteAll method called - Debug');
        
        header('Content-Type: application/json');
        
        // Allow all origins for F2F operations
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        try {
            log_message('info', 'F2F deleteAll: Starting deletion process');
            $deleted_count = 0;
            
            // Delete all embeddings first
            if ($this->db->table_exists('f2f_embedding')) {
                $this->db->empty_table('f2f_embedding');
                $deleted_count++;
            }
            
            // Delete all persons
            if ($this->db->table_exists('f2f_person')) {
                $this->db->empty_table('f2f_person');
                $deleted_count++;
            }
            
            // Delete uploaded photos
            $photo_dir = FCPATH . 'uploads/f2f/';
            if (is_dir($photo_dir)) {
                $files = glob($photo_dir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'All F2F data deleted successfully',
                'tables_cleared' => $deleted_count,
                'photos_deleted' => true
            ]);
            exit;

        } catch (Exception $e) {
            log_message('error', 'F2F delete all error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete all data: ' . $e->getMessage()]);
            exit;
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
}