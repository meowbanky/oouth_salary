<?php
/**
 * JWT Token Handler
 * Handles JWT generation, validation, and refresh
 */

class JWTHandler {
    
    private $secretKey;
    private $algorithm;
    
    public function __construct() {
        $this->secretKey = JWT_SECRET_KEY;
        $this->algorithm = JWT_ALGORITHM;
    }
    
    /**
     * Generate JWT token
     */
    public function generateToken($apiKey, $orgId, $edId, $edType, $expiresIn = null) {
        $expiresIn = $expiresIn ?? JWT_EXPIRATION;
        $issuedAt = time();
        $expiresAt = $issuedAt + $expiresIn;
        
        $header = $this->base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ]));
        
        $payload = $this->base64UrlEncode(json_encode([
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'api_key' => $apiKey,
            'org_id' => $orgId,
            'ed_id' => $edId,
            'ed_type' => $edType,
            'ip' => $_SERVER['REMOTE_ADDR']
        ]));
        
        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secretKey, true)
        );
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Generate refresh token
     */
    public function generateRefreshToken() {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Validate and decode JWT token
     */
    public function validateToken($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return ['valid' => false, 'error' => 'INVALID_TOKEN', 'message' => 'Malformed JWT token'];
        }
        
        list($header, $payload, $signature) = $parts;
        
        // Verify signature
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secretKey, true)
        );
        
        if (!hash_equals($signature, $expectedSignature)) {
            return ['valid' => false, 'error' => 'INVALID_TOKEN', 'message' => 'Invalid signature'];
        }
        
        // Decode payload
        $payloadData = json_decode($this->base64UrlDecode($payload), true);
        
        if (!$payloadData) {
            return ['valid' => false, 'error' => 'INVALID_TOKEN', 'message' => 'Invalid payload'];
        }
        
        // Check expiration
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return ['valid' => false, 'error' => 'TOKEN_EXPIRED', 'message' => 'Token has expired'];
        }
        
        // Check if token is blacklisted
        if ($this->isTokenRevoked($token)) {
            return ['valid' => false, 'error' => 'INVALID_TOKEN', 'message' => 'Token has been revoked'];
        }
        
        return ['valid' => true, 'data' => $payloadData];
    }
    
    /**
     * Store token in database
     */
    public function storeToken($apiKey, $token, $refreshToken, $expiresAt) {
        try {
            $conn = getApiDatabaseConnection();
            if (!$conn) {
                return false;
            }
            
            $tokenHash = hash('sha256', $token);
            $ipAddress = $_SERVER['REMOTE_ADDR'];
            
            $stmt = $conn->prepare('
                INSERT INTO api_jwt_tokens (api_key, token_hash, refresh_token, expires_at, ip_address)
                VALUES (?, ?, ?, ?, ?)
            ');
            
            if (!$stmt) {
                return false;
            }
            
            return $stmt->execute([
                $apiKey,
                $tokenHash,
                $refreshToken,
                date('Y-m-d H:i:s', $expiresAt),
                $ipAddress
            ]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to store JWT token', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Revoke token
     */
    public function revokeToken($token) {
        try {
            $conn = getApiDatabaseConnection();
            if (!$conn) {
                return false;
            }
            
            $tokenHash = hash('sha256', $token);
            
            $stmt = $conn->prepare('
                UPDATE api_jwt_tokens 
                SET is_revoked = 1, revoked_at = NOW()
                WHERE token_hash = ?
            ');
            
            if (!$stmt) {
                return false;
            }
            
            return $stmt->execute([$tokenHash]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to revoke JWT token', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Check if token is revoked
     */
    private function isTokenRevoked($token) {
        try {
            $conn = getApiDatabaseConnection();
            if (!$conn) {
                return false;
            }
            
            $tokenHash = hash('sha256', $token);
            
            $stmt = $conn->prepare('
                SELECT is_revoked FROM api_jwt_tokens 
                WHERE token_hash = ?
                LIMIT 1
            ');
            
            if (!$stmt) {
                return false;
            }
            
            $stmt->execute([$tokenHash]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['is_revoked'] == 1;
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to check token revocation', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Refresh JWT token using refresh token
     */
    public function refreshToken($refreshToken) {
        try {
            $conn = getApiDatabaseConnection();
            if (!$conn) {
                return ['success' => false, 'error' => 'INTERNAL_ERROR', 'message' => 'Database connection failed'];
            }
            
            $stmt = $conn->prepare('
                SELECT jt.api_key, ak.org_id, ak.ed_id, ak.ed_type
                FROM api_jwt_tokens jt
                JOIN api_keys ak ON jt.api_key = ak.api_key
                WHERE jt.refresh_token = ? 
                  AND jt.is_revoked = 0
                  AND jt.expires_at > NOW()
                LIMIT 1
            ');
            
            if (!$stmt) {
                return ['success' => false, 'error' => 'INTERNAL_ERROR', 'message' => 'Failed to prepare statement'];
            }
            
            $stmt->execute([$refreshToken]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return ['success' => false, 'error' => 'INVALID_TOKEN', 'message' => 'Invalid or expired refresh token'];
            }
            
            // Generate new token
            $newToken = $this->generateToken(
                $result['api_key'],
                $result['org_id'],
                $result['ed_id'],
                $result['ed_type']
            );
            
            $newRefreshToken = $this->generateRefreshToken();
            $expiresAt = time() + JWT_EXPIRATION;
            
            // Store new token
            $this->storeToken($result['api_key'], $newToken, $newRefreshToken, $expiresAt);
            
            return [
                'success' => true,
                'access_token' => $newToken,
                'refresh_token' => $newRefreshToken,
                'expires_in' => JWT_EXPIRATION,
                'token_type' => 'Bearer'
            ];
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to refresh JWT token', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => 'INTERNAL_ERROR', 'message' => 'Failed to refresh token'];
        }
    }
    
    /**
     * Clean up expired tokens
     */
    public static function cleanupExpiredTokens() {
        try {
            $conn = getApiDatabaseConnection();
            if (!$conn) {
                return 0;
            }
            
            $stmt = $conn->prepare('
                DELETE FROM api_jwt_tokens 
                WHERE expires_at < NOW() OR is_revoked = 1
            ');
            
            if (!$stmt) {
                return 0;
            }
            
            $stmt->execute();
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to cleanup expired tokens', ['error' => $e->getMessage()]);
            return 0;
        }
    }
    
    /**
     * Base64 URL encode
     */
    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL decode
     */
    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}