<?php
/**
 * Authentication Endpoint
 * Handles token generation, refresh, and revocation
 */

// Note: This file is included from api/v1/index.php which already loads required files
// Check if we need to define the class or if it's already available

if (!class_exists('AuthenticationHandler')) {
    class AuthenticationHandler {
    
    private $jwtHandler;
    private $keyValidator;
    
    public function __construct() {
        $this->jwtHandler = new JWTHandler();
        $this->keyValidator = new ApiKeyValidator();
    }
    
    /**
     * Handle authentication request
     */
    public function handle() {
        $method = $_SERVER['REQUEST_METHOD'];
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        
        // Remove leading slash
        $pathInfo = ltrim($pathInfo, '/');
        
        // Default to 'token' if no path specified
        if (empty($pathInfo)) {
            $pathInfo = 'token';
        }
        
        switch ($pathInfo) {
            case 'token':
                if ($method === 'POST') {
                    $this->generateToken();
                } else {
                    apiError('BAD_REQUEST', 'Method not allowed', null, 405);
                }
                break;
                
            case 'refresh':
                if ($method === 'POST') {
                    $this->refreshToken();
                } else {
                    apiError('BAD_REQUEST', 'Method not allowed', null, 405);
                }
                break;
                
            case 'revoke':
                if ($method === 'POST') {
                    $this->revokeToken();
                } else {
                    apiError('BAD_REQUEST', 'Method not allowed', null, 405);
                }
                break;
                
            default:
                apiError('NOT_FOUND', 'Auth endpoint not found', "Valid endpoints: /auth/token, /auth/refresh, /auth/revoke", 404);
        }
    }
    
    /**
     * Generate JWT token from API key
     */
    private function generateToken() {
        // Get request body
        $input = json_decode(file_get_contents('php://input'), true);
        
        $apiKey = $input['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;
        $timestamp = $input['timestamp'] ?? $_SERVER['HTTP_X_TIMESTAMP'] ?? null;
        $signature = $input['signature'] ?? $_SERVER['HTTP_X_SIGNATURE'] ?? null;
        
        // Validate required fields
        if (!$apiKey) {
            apiError('MISSING_PARAMETER', 'API key is required', null, 400);
        }
        
        if (REQUIRE_SIGNATURE && (!$timestamp || !$signature)) {
            apiError('MISSING_PARAMETER', 'Timestamp and signature are required', null, 400);
        }
        
        // Validate API key
        $validation = $this->keyValidator->validate($apiKey);
        
        if (!$validation['valid']) {
            apiError($validation['error'], $validation['message'], null, 401);
        }
        
        $keyData = $validation['data'];
        
        // Verify signature if required
        if (REQUIRE_SIGNATURE) {
            $requestData = $apiKey; // Only API key is used for signature
            $signatureValidation = $this->keyValidator->verifySignature(
                $keyData['api_secret'],
                $requestData,
                $timestamp,
                $signature
            );
            
            if (!$signatureValidation['valid']) {
                apiError($signatureValidation['error'], $signatureValidation['message'], null, 401);
            }
        }
        
        // Generate JWT token
        $accessToken = $this->jwtHandler->generateToken(
            $apiKey,
            $keyData['org_id'],
            $keyData['ed_id'],
            $keyData['ed_type']
        );
        
        $refreshToken = $this->jwtHandler->generateRefreshToken();
        $expiresAt = time() + JWT_EXPIRATION;
        
        // Store token
        $this->jwtHandler->storeToken($apiKey, $accessToken, $refreshToken, $expiresAt);
        
        // Log successful authentication
        logApiActivity('info', 'JWT token generated', [
            'org_id' => $keyData['org_id'],
            'api_key' => $apiKey
        ]);
        
        // Return token
        apiSuccess([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => JWT_EXPIRATION
        ]);
    }
    
    /**
     * Refresh JWT token
     */
    private function refreshToken() {
        // Get request body
        $input = json_decode(file_get_contents('php://input'), true);
        
        $refreshToken = $input['refresh_token'] ?? null;
        
        if (!$refreshToken) {
            apiError('MISSING_PARAMETER', 'Refresh token is required', null, 400);
        }
        
        // Attempt to refresh token
        $result = $this->jwtHandler->refreshToken($refreshToken);
        
        if (!$result['success']) {
            apiError($result['error'], $result['message'], null, 401);
        }
        
        // Log token refresh
        logApiActivity('info', 'JWT token refreshed');
        
        // Return new token
        unset($result['success']);
        apiSuccess($result);
    }
    
    /**
     * Revoke JWT token
     */
    private function revokeToken() {
        // Get token from Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            apiError('MISSING_PARAMETER', 'Bearer token is required', null, 400);
        }
        
        $token = $matches[1];
        
        // Validate token first
        $validation = $this->jwtHandler->validateToken($token);
        
        if (!$validation['valid']) {
            apiError($validation['error'], $validation['message'], null, 401);
        }
        
        // Revoke token
        $this->jwtHandler->revokeToken($token);
        
        // Log token revocation
        logApiActivity('info', 'JWT token revoked');
        
        apiSuccess(['message' => 'Token revoked successfully']);
    }
    }
} // End of class_exists check

// Handle request (HTTPS and OPTIONS already checked in main router)
$handler = new AuthenticationHandler();
$handler->handle();