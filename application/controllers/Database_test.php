<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Database_test extends CI_Controller {
    
    public function index() {
        try {
            // Test database connection
            $this->load->database();
            
            if ($this->db->conn_id) {
                $this->output
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => 'success',
                        'message' => 'Database connected successfully',
                        'driver' => $this->db->platform(),
                        'database' => $this->db->database,
                        'timestamp' => date('Y-m-d H:i:s')
                    ]));
            } else {
                throw new Exception('Database connection failed');
            }
        } catch (Exception $e) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'message' => 'Database connection failed: ' . $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]));
        }
    }
}
