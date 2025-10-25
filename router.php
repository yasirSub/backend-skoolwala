<?php
/**
 * PHP Built-in Server Router for CodeIgniter
 * This file handles URL routing for PHP's built-in development server
 */

// If the request is for a file that exists, serve it directly
if (file_exists(__DIR__ . $_SERVER['REQUEST_URI'])) {
    return false;
}

// Otherwise, route everything through index.php
require_once __DIR__ . '/index.php';
