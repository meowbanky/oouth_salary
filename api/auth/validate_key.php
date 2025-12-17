<?php
/**
 * API Key Validator
 * Validates API keys and checks permissions
 */

class ApiKeyValidator {
    
    private $conn;
    
    public function __construct() {
        $this->conn = getApiDatabaseConnection();
    }
    
    /**
     * Validate API key and return key details
     */
    public function validate($apiKey) {
        try {
            // Basic format validation
            if (!$this->validateKeyFormat($apiKey)) {
                return $this->errorResponse('INVALID_API_KEY', 'API key format is invalid');
            }
            
            // Fetch key from database with organization details
            $stmt = $this->conn->prepare('
                SELECT 
                    ak.key_id,
                    ak.api_key,
                    ak.api_secret,
                    ak.org_id,
                    ak.ed_id,
                    ak.ed_type,
                    ak.ed_name,
                    ak.is_active,
                    ak.expires_at,
                    ak.rate_limit_per_min,
                    ak.allowed_ips,
                    ao.org_name,
                    ao.org_code,
                    ao.is_active as org_is_active,
                    ao.rate_limit_per_min as org_rate_limit,
                    ao.allowed_ips as org_allowed_ips
                FROM api_keys ak
                JOIN api_organizations ao ON ak.org_id = ao.org_id
                WHERE ak.api_key = ?
                LIMIT 1
            ');
            
            $stmt->execute([$apiKey]);
            $keyData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$keyData) {
                return $this->errorResponse('INVALID_API_KEY', 'API key not found');
            }
            
            // Check if organization is active
            if ($keyData['org_is_active'] != 1) {
                $this->logSecurityAlert($keyData['org_id'], $apiKey, 'ORGANIZATION_INACTIVE', 
                    'Attempt to use API key from inactive organization');
                return $this->errorResponse('ORGANIZATION_INACTIVE', 'Organization account is inactive');
            }
            
            // Check if key is active
            if ($keyData['is_active'] != 1) {
                $this->logSecurityAlert($keyData['org_id'], $apiKey, 'INACTIVE_KEY', 
                    'Attempt to use inactive API key');
                return $this->errorResponse('INVALID_API_KEY', 'API key is inactive');
            }
            
            // Check expiration
            if ($keyData['expires_at'] && strtotime($keyData['expires_at']) < time()) {
                $this->logSecurityAlert($keyData['org_id'], $apiKey, 'EXPIRED_KEY', 
                    'Attempt to use expired API key');
                return $this->errorResponse('EXPIRED_API_KEY', 'API key has expired');
            }
            
            // Check IP whitelist (if enabled)
            if (ENABLE_IP_WHITELIST) {
                $ipAllowed = $this->checkIpWhitelist(
                    $_SERVER['REMOTE_ADDR'],
                    $keyData['allowed_ips'],
                    $keyData['org_allowed_ips']
                );
                
                if (!$ipAllowed) {
                    $this->logSecurityAlert($keyData['org_id'], $apiKey, 'IP_NOT_ALLOWED', 
                        'Request from unauthorized IP: ' . $_SERVER['REMOTE_ADDR']);
                    return $this->errorResponse('IP_NOT_ALLOWED', 'Request from unauthorized IP address');
                }
            }
            
            // Update last used timestamp
            $this->updateLastUsed($apiKey);
            
            return [
                'valid' => true,
                'data' => $keyData
            ];
            
        } catch (PDOException $e) {
            logApiActivity('error', 'API key validation failed', ['error' => $e->getMessage()]);
            return $this->errorResponse('INTERNAL_ERROR', 'Validation error occurred');
        }
    }
    
    /**
     * Validate API key format
     * Format: oouth_{org_id}_{type}_{ed_id}_{hash}
     */
    private function validateKeyFormat($apiKey) {
        return preg_match('/^oouth_\d{3}_(allow|deduc)_\d+_[a-f0-9]{16}$/', $apiKey);
    }
    
    /**
     * Check IP whitelist
     */
    private function checkIpWhitelist($clientIp, $keyAllowedIps, $orgAllowedIps) {
        // If no whitelist is set, allow all
        if (empty($keyAllowedIps) && empty($orgAllowedIps)) {
            return true;
        }
        
        // Check key-specific whitelist first
        if (!empty($keyAllowedIps)) {
            $keyIps = json_decode($keyAllowedIps, true);
            if (is_array($keyIps) && in_array($clientIp, $keyIps)) {
                return true;
            }
        }
        
        // Check organization whitelist
        if (!empty($orgAllowedIps)) {
            $orgIps = json_decode($orgAllowedIps, true);
            if (is_array($orgIps) && in_array($clientIp, $orgIps)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Update last used timestamp
     */
    private function updateLastUsed($apiKey) {
        try {
            $stmt = $this->conn->prepare('
                UPDATE api_keys 
                SET last_used_at = NOW(), total_requests = total_requests + 1
                WHERE api_key = ?
            ');
            
            $stmt->execute([$apiKey]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to update last used timestamp', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Verify request signature (HMAC)
     */
    public function verifySignature($apiSecret, $requestData, $timestamp, $signature) {
        if (!REQUIRE_SIGNATURE) {
            return ['valid' => true];
        }
        
        // Validate timestamp (prevent replay attacks)
        $currentTime = time();
        if (abs($currentTime - $timestamp) > TIMESTAMP_TOLERANCE) {
            return $this->errorResponse('INVALID_TIMESTAMP', 
                'Request timestamp is outside acceptable range');
        }
        
        // Build signature string
        $signatureString = $requestData . $timestamp;
        
        // Calculate expected signature
        $expectedSignature = hash_hmac('sha256', $signatureString, $apiSecret);
        
        // Compare signatures
        if (!hash_equals($expectedSignature, $signature)) {
            return $this->errorResponse('INVALID_SIGNATURE', 
                'Request signature verification failed');
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check if API key has access to specific resource
     */
    public function checkResourceAccess($keyData, $requestedEdId, $requestedEdType) {
        // Key must match the requested resource
        if ($keyData['ed_id'] != $requestedEdId || $keyData['ed_type'] != $requestedEdType) {
            $this->logSecurityAlert($keyData['org_id'], $keyData['api_key'], 'UNAUTHORIZED_ACCESS', 
                "Attempt to access ED ID $requestedEdId (type $requestedEdType) with key for ED ID {$keyData['ed_id']} (type {$keyData['ed_type']})");
            return $this->errorResponse('FORBIDDEN', 'API key does not have access to this resource');
        }
        
        return ['valid' => true];
    }
    
    /**
     * Log security alert
     */
    private function logSecurityAlert($orgId, $apiKey, $alertType, $description) {
        try {
            $stmt = $this->conn->prepare('
                INSERT INTO api_security_alerts (org_id, api_key, alert_type, ip_address, description)
                VALUES (?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $orgId,
                $apiKey,
                $alertType,
                $_SERVER['REMOTE_ADDR'],
                $description
            ]);
            
            logApiActivity('security', $alertType, [
                'org_id' => $orgId,
                'api_key' => $apiKey,
                'description' => $description
            ]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to log security alert', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Error response helper
     */
    private function errorResponse($code, $message) {
        return [
            'valid' => false,
            'error' => $code,
            'message' => $message
        ];
    }
}

