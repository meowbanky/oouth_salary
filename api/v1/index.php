<?php
/**
 * OOUTH Salary API - Main Router
 * Version 1.0.0
 * Multi-tenant REST API with JWT authentication
 */

define('API_ACCESS', true);

// Load configuration
require_once dirname(__DIR__) . '/config/api_config.php';
require_once dirname(__DIR__) . '/utils/response.php';
require_once dirname(__DIR__) . '/utils/logger.php';
require_once dirname(__DIR__) . '/auth/jwt_handler.php';
require_once dirname(__DIR__) . '/auth/validate_key.php';
require_once dirname(__DIR__) . '/middleware/rate_limiter.php';

// Initialize logger
$logger = new ApiLogger();

// Handle HTTPS requirement
if (REQUIRE_HTTPS && (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
    $logger->logRequest(null, null, $_SERVER['REQUEST_URI'], 403, 'HTTPS_REQUIRED', 'HTTPS is required');
    apiError('FORBIDDEN', 'HTTPS is required', null, 403);
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Parse request URI
$requestUri = $_SERVER['REQUEST_URI'];
$scriptName = $_SERVER['SCRIPT_NAME']; // e.g., /api/v1/index.php

// Get the path after the script
$basePath = str_replace('/index.php', '', $scriptName); // e.g., /api/v1
$path = parse_url($requestUri, PHP_URL_PATH); // e.g., /api/v1/auth/token
$path = str_replace($basePath, '', $path); // e.g., /auth/token
$path = trim($path, '/'); // e.g., auth/token

// Split path into segments
$segments = array_filter(explode('/', $path));
$segments = array_values($segments); // Re-index

// Debug logging (can be disabled in production)
if (ENABLE_DEBUG_MODE) {
    logApiActivity('debug', 'Request routing', [
        'request_uri' => $requestUri,
        'script_name' => $scriptName,
        'base_path' => $basePath,
        'parsed_path' => $path,
        'segments' => $segments
    ]);
}

// Route the request
if (empty($segments)) {
    // Root endpoint - API info
    apiSuccess([
        'name' => 'OOUTH Salary API',
        'version' => API_VERSION,
        'status' => 'active',
        'documentation' => getApiBaseUrl() . '/docs',
        'endpoints' => API_ENDPOINTS
    ]);
}

// Get the main resource
$resource = $segments[0] ?? null;

switch ($resource) {
    case 'test':
        // Simple test endpoint (no auth required)
        require_once __DIR__ . '/test.php';
        break;
        
    case 'debug':
        // Debug endpoint (no auth required)
        require_once __DIR__ . '/debug.php';
        break;
        
    case 'auth':
        // Authentication endpoints (no JWT required)
        // Set PATH_INFO for the authenticate handler
        $_SERVER['PATH_INFO'] = '/' . ($segments[1] ?? '');
        require_once dirname(__DIR__) . '/auth/authenticate.php';
        break;
        
    case 'payroll':
        // Payroll endpoints (JWT required)
        require_once __DIR__ . '/payroll.php';
        break;
        
    case 'webhooks':
        // Webhook endpoints (JWT required)
        require_once __DIR__ . '/webhooks.php';
        break;
        
    case 'docs':
        // API documentation
        require_once __DIR__ . '/docs.php';
        break;
        
    default:
        $logger->logRequest(null, null, $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'Endpoint not found');
        apiError('NOT_FOUND', 'Endpoint not found', "Resource '$resource' does not exist. Available: test, auth, payroll, webhooks", 404);
}