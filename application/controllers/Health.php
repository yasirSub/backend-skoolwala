<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Health extends CI_Controller {
    
    public function index() {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'ok',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0',
                'environment' => getenv('CI_ENV') ?: 'production'
            ]));
    }
    
    public function test() {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => 'ok',
                'message' => 'Health check passed',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
    }
}
