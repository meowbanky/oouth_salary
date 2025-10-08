<?php
/**
 * OOUTH Salary API Configuration
 * Multi-tenant REST API with JWT authentication
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('API_ACCESS')) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Direct access not allowed']]));
}

// API Version
define('API_VERSION', 'v1');

// JWT Configuration
define('JWT_SECRET_KEY', getenv('JWT_SECRET_KEY') ?: 'oouth_jwt_secret_2025_change_this_in_production');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 900); // 15 minutes
define('JWT_REFRESH_EXPIRATION', 86400); // 24 hours

// Rate Limiting Configuration
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_WINDOW', 60); // seconds
define('DEFAULT_ORG_RATE_LIMIT', 500); // requests per window per organization
define('DEFAULT_KEY_RATE_LIMIT', 100); // requests per window per API key

// Request Configuration
define('REQUEST_TIMEOUT', 30); // seconds
define('MAX_REQUEST_SIZE', 1048576); // 1MB
define('TIMESTAMP_TOLERANCE', 300); // 5 minutes

// Security Configuration
define('REQUIRE_HTTPS', false); // Set to true in production
define('REQUIRE_SIGNATURE', false); // HMAC signature verification
define('ENABLE_IP_WHITELIST', true); // IP whitelist checking
define('LOG_ALL_REQUESTS', true); // Log every API request

// Response Configuration
define('DEFAULT_RESPONSE_FORMAT', 'json'); // json, xml, csv
define('ENABLE_CORS', true);
define('CORS_ALLOWED_ORIGINS', '*'); // Comma-separated domains or * for all
define('CORS_ALLOWED_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_ALLOWED_HEADERS', 'Content-Type, Authorization, X-API-Key, X-Timestamp, X-Signature, X-Request-ID');

// Pagination Configuration
define('DEFAULT_PAGE_SIZE', 100);
define('MAX_PAGE_SIZE', 1000);

// Webhook Configuration
define('WEBHOOK_ENABLED', true);
define('WEBHOOK_TIMEOUT', 30); // seconds
define('WEBHOOK_RETRY_COUNT', 3);
define('WEBHOOK_RETRY_DELAY', 300); // 5 minutes between retries

// Logging Configuration
define('LOG_RETENTION_DAYS', 90); // Keep logs for 90 days
define('ENABLE_DEBUG_MODE', false); // Set to false in production

// Error Messages
define('ERROR_MESSAGES', [
    'INVALID_API_KEY' => 'The provided API key is invalid or inactive',
    'EXPIRED_API_KEY' => 'The API key has expired',
    'INVALID_SIGNATURE' => 'Request signature verification failed',
    'INVALID_TIMESTAMP' => 'Request timestamp is outside acceptable range',
    'RATE_LIMIT_EXCEEDED' => 'Rate limit exceeded. Please try again later',
    'UNAUTHORIZED' => 'Authentication failed',
    'FORBIDDEN' => 'Access denied',
    'NOT_FOUND' => 'Resource not found',
    'BAD_REQUEST' => 'Invalid request parameters',
    'INTERNAL_ERROR' => 'An internal error occurred',
    'ORGANIZATION_INACTIVE' => 'Organization account is inactive',
    'IP_NOT_ALLOWED' => 'Request from unauthorized IP address',
    'INVALID_TOKEN' => 'JWT token is invalid or expired',
    'TOKEN_EXPIRED' => 'JWT token has expired',
    'MISSING_PARAMETER' => 'Required parameter is missing',
    'INVALID_PERIOD' => 'Invalid or inactive payroll period',
    'INVALID_ED_ID' => 'Invalid allowance or deduction ID'
]);

// Webhook Event Types
define('WEBHOOK_EVENTS', [
    'payroll.period.activated',
    'payroll.period.closed',
    'payroll.processed',
    'allowance.updated',
    'deduction.updated',
    'employee.added',
    'employee.removed'
]);

// API Endpoints Configuration
define('API_ENDPOINTS', [
    'auth' => [
        'POST /auth/token' => 'Generate JWT token',
        'POST /auth/refresh' => 'Refresh JWT token',
        'POST /auth/revoke' => 'Revoke JWT token'
    ],
    'payroll' => [
        'GET /payroll/periods' => 'List all payroll periods',
        'GET /payroll/periods/:id' => 'Get specific period details',
        'GET /payroll/periods/active' => 'Get current active period',
        'GET /payroll/allowances/:id' => 'Get allowance data',
        'GET /payroll/deductions/:id' => 'Get deduction data'
    ],
    'webhooks' => [
        'GET /webhooks' => 'List registered webhooks',
        'POST /webhooks/register' => 'Register new webhook',
        'GET /webhooks/:id' => 'Get webhook details',
        'PUT /webhooks/:id' => 'Update webhook',
        'DELETE /webhooks/:id' => 'Delete webhook',
        'POST /webhooks/:id/test' => 'Test webhook delivery'
    ]
]);

// Security Headers
define('SECURITY_HEADERS', [
    'X-Content-Type-Options' => 'nosniff',
    'X-Frame-Options' => 'DENY',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'none'",
    'Referrer-Policy' => 'no-referrer'
]);

// Database Connection (use existing connection from main app)
function getApiDatabaseConnection() {
    static $connection = null;
    
    if ($connection === null) {
        require_once dirname(__DIR__, 2) . '/Connections/paymaster.php';
        // $conn is defined in paymaster.php but not in this scope
        // We need to access it from global scope or recreate connection
        global $conn;
        
        if (!isset($conn) || $conn === null) {
            // Create connection if global not available
            $hostname_salary = "localhost";
            $database_salary = "oouthsal_salary3";
            $username_salary = "oouthsal_root";
            $password_salary = "Oluwaseyi@7980";
            
            try {
                $connection = new PDO(
                    "mysql:host=$hostname_salary;dbname=$database_salary", 
                    $username_salary, 
                    $password_salary,
                    array(PDO::ATTR_PERSISTENT => true)
                );
                $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                error_log("API Database Connection Failed: " . $e->getMessage());
                return null;
            }
        } else {
            $connection = $conn;
        }
    }
    
    return $connection;
}

// Timezone Configuration
date_default_timezone_set('Africa/Lagos');

// Error Reporting (disable in production)
if (ENABLE_DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Helper Functions
function isProduction() {
    return !ENABLE_DEBUG_MODE && REQUIRE_HTTPS;
}

function getApiBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . '/api/' . API_VERSION;
}

function logApiActivity($level, $message, $context = []) {
    if (!ENABLE_DEBUG_MODE && $level === 'debug') {
        return;
    }
    
    $logFile = dirname(__DIR__) . '/logs/api_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] [$level] $message $contextStr" . PHP_EOL;
    
    error_log($logMessage, 3, $logFile);
}

// API Key Format: oouth_{org_id}_{type}_{ed_id}_{hash}
function generateApiKey($orgId, $edType, $edId) {
    $type = ($edType == 1) ? 'allow' : 'deduc';
    $hash = bin2hex(random_bytes(8)); // 16 character random hash
    return sprintf('oouth_%03d_%s_%d_%s', $orgId, $type, $edId, $hash);
}

// API Secret for HMAC signing (store hashed in database)
function generateApiSecret() {
    return bin2hex(random_bytes(32)); // 64 character secret
}

// Webhook Secret
function generateWebhookSecret() {
    return bin2hex(random_bytes(32));
}

// Request ID Generator
function generateRequestId() {
    return 'req_' . bin2hex(random_bytes(8));
}

return true;