<?php
/**
 * Simple Test Endpoint
 * Minimal endpoint to test if API routing works
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'API is working!',
    'timestamp' => date('Y-m-d H:i:s'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'request_uri' => $_SERVER['REQUEST_URI'],
    'script_name' => $_SERVER['SCRIPT_NAME'],
    'path_info' => $_SERVER['PATH_INFO'] ?? 'not set',
    'php_version' => PHP_VERSION
], JSON_PRETTY_PRINT);