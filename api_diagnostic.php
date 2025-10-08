<?php
/**
 * API Diagnostic Script
 * Checks if API system is properly configured
 */

header('Content-Type: application/json');

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// Check 1: PHP Version
$diagnostics['checks']['php_version'] = [
    'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'pass' : 'fail',
    'value' => PHP_VERSION,
    'required' => '7.4+'
];

// Check 2: Database Connection
try {
    require_once 'Connections/paymaster.php';
    $diagnostics['checks']['database_connection'] = [
        'status' => isset($conn) && $conn ? 'pass' : 'fail',
        'message' => isset($conn) ? 'Connected' : 'Connection object not found'
    ];
} catch (Exception $e) {
    $diagnostics['checks']['database_connection'] = [
        'status' => 'fail',
        'error' => $e->getMessage()
    ];
}

// Check 3: API Tables Exist
$requiredTables = [
    'api_organizations',
    'api_keys',
    'api_jwt_tokens',
    'api_rate_limits',
    'api_request_logs',
    'api_webhooks',
    'api_webhook_logs',
    'api_security_alerts'
];

$diagnostics['checks']['api_tables'] = [];
if (isset($conn)) {
    foreach ($requiredTables as $table) {
        try {
            $stmt = $conn->query("SELECT 1 FROM $table LIMIT 1");
            $diagnostics['checks']['api_tables'][$table] = 'exists';
        } catch (PDOException $e) {
            $diagnostics['checks']['api_tables'][$table] = 'missing';
        }
    }
}

// Check 4: API Files Exist
$requiredFiles = [
    'api/config/api_config.php',
    'api/auth/authenticate.php',
    'api/auth/jwt_handler.php',
    'api/auth/validate_key.php',
    'api/v1/index.php',
    'api/v1/payroll.php',
    'api/utils/response.php',
    'api/utils/logger.php',
    'api/middleware/rate_limiter.php'
];

$diagnostics['checks']['api_files'] = [];
foreach ($requiredFiles as $file) {
    $diagnostics['checks']['api_files'][$file] = file_exists($file) ? 'exists' : 'missing';
}

// Check 5: API Configuration
if (file_exists('api/config/api_config.php')) {
    define('API_ACCESS', true);
    require_once 'api/config/api_config.php';
    
    $diagnostics['checks']['api_config'] = [
        'REQUIRE_HTTPS' => REQUIRE_HTTPS ? 'enabled' : 'disabled',
        'REQUIRE_SIGNATURE' => REQUIRE_SIGNATURE ? 'enabled' : 'disabled',
        'RATE_LIMIT_ENABLED' => RATE_LIMIT_ENABLED ? 'enabled' : 'disabled',
        'JWT_EXPIRATION' => JWT_EXPIRATION . ' seconds',
        'DEFAULT_KEY_RATE_LIMIT' => DEFAULT_KEY_RATE_LIMIT . ' req/min'
    ];
}

// Check 6: .htaccess exists
$diagnostics['checks']['htaccess'] = [
    'api/.htaccess' => file_exists('api/.htaccess') ? 'exists' : 'missing'
];

// Check 7: Test API Key
if (isset($conn)) {
    try {
        $stmt = $conn->prepare('SELECT COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active FROM api_keys');
        $stmt->execute();
        $keyStats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $diagnostics['checks']['api_keys_count'] = [
            'total' => $keyStats['total'] ?? 0,
            'active' => $keyStats['active'] ?? 0
        ];
    } catch (PDOException $e) {
        $diagnostics['checks']['api_keys_count'] = [
            'error' => $e->getMessage()
        ];
    }
}

// Check 8: Error Log Location
$diagnostics['checks']['error_logging'] = [
    'error_log' => ini_get('error_log') ?: 'default',
    'display_errors' => ini_get('display_errors') ? 'on' : 'off',
    'log_errors' => ini_get('log_errors') ? 'on' : 'off'
];

// Overall status
$allPassed = true;
foreach ($diagnostics['checks'] as $check) {
    if (isset($check['status']) && $check['status'] === 'fail') {
        $allPassed = false;
        break;
    }
}

$diagnostics['overall_status'] = $allPassed ? 'ready' : 'needs_setup';

echo json_encode($diagnostics, JSON_PRETTY_PRINT);

