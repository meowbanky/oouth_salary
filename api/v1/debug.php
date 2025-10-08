<?php
/**
 * Debug Endpoint - Shows PHP errors
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

echo "=== API Debug Information ===\n\n";

// Define API_ACCESS before loading config
define('API_ACCESS', true);

echo "1. Testing requires:\n";
try {
    require_once dirname(__DIR__) . '/config/api_config.php';
    echo "✅ api_config.php loaded\n";
} catch (Exception $e) {
    echo "❌ api_config.php error: " . $e->getMessage() . "\n";
}

try {
    require_once dirname(__DIR__) . '/utils/response.php';
    echo "✅ response.php loaded\n";
} catch (Exception $e) {
    echo "❌ response.php error: " . $e->getMessage() . "\n";
}

try {
    require_once dirname(__DIR__) . '/auth/jwt_handler.php';
    echo "✅ jwt_handler.php loaded\n";
} catch (Exception $e) {
    echo "❌ jwt_handler.php error: " . $e->getMessage() . "\n";
}

try {
    require_once dirname(__DIR__) . '/auth/validate_key.php';
    echo "✅ validate_key.php loaded\n";
} catch (Exception $e) {
    echo "❌ validate_key.php error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing class instantiation:\n";
try {
    $jwt = new JWTHandler();
    echo "✅ JWTHandler class works\n";
} catch (Exception $e) {
    echo "❌ JWTHandler error: " . $e->getMessage() . "\n";
}

try {
    $validator = new ApiKeyValidator();
    echo "✅ ApiKeyValidator class works\n";
} catch (Exception $e) {
    echo "❌ ApiKeyValidator error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing database connection:\n";
try {
    $conn = getApiDatabaseConnection();
    if ($conn) {
        echo "✅ Database connection works\n";
        
        // Test query
        $stmt = $conn->query("SELECT COUNT(*) FROM api_keys");
        $count = $stmt->fetchColumn();
        echo "✅ Query works - Found $count API keys\n";
    } else {
        echo "❌ Database connection returned null\n";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing authentication flow:\n";
try {
    require_once dirname(__DIR__) . '/auth/authenticate.php';
    echo "✅ authenticate.php loaded\n";
    
    if (class_exists('AuthenticationHandler')) {
        echo "✅ AuthenticationHandler class exists\n";
        $handler = new AuthenticationHandler();
        echo "✅ AuthenticationHandler instantiated\n";
    } else {
        echo "❌ AuthenticationHandler class not found\n";
    }
} catch (Exception $e) {
    echo "❌ Authentication error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== End Debug ===\n";