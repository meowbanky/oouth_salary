<?php
/**
 * Rate Limiter Middleware
 * Implements sliding window rate limiting
 */

class RateLimiter {
    
    private $conn;
    
    public function __construct() {
        $this->conn = getApiDatabaseConnection();
    }
    
    /**
     * Check if database connection is valid
     */
    private function hasConnection() {
        return $this->conn !== null;
    }
    
    /**
     * Check rate limit for API key
     */
    public function checkLimit($apiKey, $limit) {
        if (!RATE_LIMIT_ENABLED || !$this->hasConnection()) {
            return ['allowed' => true];
        }
        
        try {
            $windowStart = date('Y-m-d H:i:s', time() - RATE_LIMIT_WINDOW);
            $windowEnd = date('Y-m-d H:i:s', time());
            
            // Clean up old windows first
            $this->cleanupOldWindows();
            
            // Get or create current window
            $stmt = $this->conn->prepare('
                SELECT request_count, window_start, window_end
                FROM api_rate_limits
                WHERE api_key = ? 
                  AND window_end > ?
                ORDER BY window_start DESC
                LIMIT 1
            ');
            
            if (!$stmt) {
                return ['allowed' => true];
            }
            
            $stmt->execute([$apiKey, $windowStart]);
            $window = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($window) {
                $requestCount = $window['request_count'];
                
                // Check if limit exceeded
                if ($requestCount >= $limit) {
                    $resetTime = strtotime($window['window_end']);
                    
                    // Set rate limit headers
                    $_SERVER['X_RATE_LIMIT_LIMIT'] = $limit;
                    $_SERVER['X_RATE_LIMIT_REMAINING'] = 0;
                    $_SERVER['X_RATE_LIMIT_RESET'] = $resetTime;
                    
                    // Log rate limit violation
                    logApiActivity('warning', 'Rate limit exceeded', [
                        'api_key' => $apiKey,
                        'limit' => $limit,
                        'window' => $window
                    ]);
                    
                    return [
                        'allowed' => false,
                        'error' => 'RATE_LIMIT_EXCEEDED',
                        'message' => 'Rate limit exceeded. Please try again later.',
                        'retry_after' => $resetTime - time()
                    ];
                }
                
                // Increment count
                $this->incrementCount($apiKey, $window['window_start']);
                $requestCount++;
                
            } else {
                // Create new window
                $this->createWindow($apiKey, $windowStart, $windowEnd);
                $requestCount = 1;
            }
            
            // Set rate limit headers
            $_SERVER['X_RATE_LIMIT_LIMIT'] = $limit;
            $_SERVER['X_RATE_LIMIT_REMAINING'] = max(0, $limit - $requestCount);
            $_SERVER['X_RATE_LIMIT_RESET'] = time() + RATE_LIMIT_WINDOW;
            
            return ['allowed' => true];
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Rate limit check failed', ['error' => $e->getMessage()]);
            // Allow request if rate limiter fails (fail open)
            return ['allowed' => true];
        }
    }
    
    /**
     * Create new rate limit window
     */
    private function createWindow($apiKey, $windowStart, $windowEnd) {
        if (!$this->hasConnection()) {
            return;
        }
        
        try {
            $stmt = $this->conn->prepare('
                INSERT INTO api_rate_limits (api_key, window_start, window_end, request_count)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    request_count = request_count + 1,
                    last_request_at = NOW()
            ');
            
            if (!$stmt) {
                return;
            }
            
            $stmt->execute([$apiKey, $windowStart, $windowEnd]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to create rate limit window', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Increment request count in current window
     */
    private function incrementCount($apiKey, $windowStart) {
        if (!$this->hasConnection()) {
            return;
        }
        
        try {
            $stmt = $this->conn->prepare('
                UPDATE api_rate_limits 
                SET request_count = request_count + 1,
                    last_request_at = NOW()
                WHERE api_key = ? AND window_start = ?
            ');
            
            if (!$stmt) {
                return;
            }
            
            $stmt->execute([$apiKey, $windowStart]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to increment rate limit count', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Clean up old rate limit windows
     */
    private function cleanupOldWindows() {
        if (!$this->hasConnection()) {
            return;
        }
        
        try {
            $cutoffTime = date('Y-m-d H:i:s', time() - (RATE_LIMIT_WINDOW * 2));
            
            $stmt = $this->conn->prepare('
                DELETE FROM api_rate_limits 
                WHERE window_end < ?
            ');
            
            if (!$stmt) {
                return;
            }
            
            $stmt->execute([$cutoffTime]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to cleanup rate limit windows', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Check organization rate limit
     */
    public function checkOrganizationLimit($orgId, $limit) {
        if (!RATE_LIMIT_ENABLED || !$this->hasConnection()) {
            return ['allowed' => true];
        }
        
        try {
            $windowStart = date('Y-m-d H:i:s', time() - RATE_LIMIT_WINDOW);
            
            // Count all requests from organization's keys in the window
            $stmt = $this->conn->prepare('
                SELECT COALESCE(SUM(rl.request_count), 0) as total_requests
                FROM api_rate_limits rl
                JOIN api_keys ak ON rl.api_key = ak.api_key
                WHERE ak.org_id = ? 
                  AND rl.window_end > ?
            ');
            
            if (!$stmt) {
                return ['allowed' => true];
            }
            
            $stmt->execute([$orgId, $windowStart]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalRequests = $result['total_requests'] ?? 0;
            
            if ($totalRequests >= $limit) {
                logApiActivity('warning', 'Organization rate limit exceeded', [
                    'org_id' => $orgId,
                    'limit' => $limit,
                    'total_requests' => $totalRequests
                ]);
                
                return [
                    'allowed' => false,
                    'error' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Organization rate limit exceeded. Please try again later.'
                ];
            }
            
            return ['allowed' => true];
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Organization rate limit check failed', ['error' => $e->getMessage()]);
            // Allow request if rate limiter fails (fail open)
            return ['allowed' => true];
        }
    }
    
    /**
     * Get rate limit status for API key
     */
    public function getStatus($apiKey, $limit) {
        if (!$this->hasConnection()) {
            return [
                'limit' => $limit,
                'remaining' => $limit,
                'reset' => time() + RATE_LIMIT_WINDOW
            ];
        }
        
        try {
            $windowStart = date('Y-m-d H:i:s', time() - RATE_LIMIT_WINDOW);
            
            $stmt = $this->conn->prepare('
                SELECT request_count, window_end
                FROM api_rate_limits
                WHERE api_key = ? 
                  AND window_end > ?
                ORDER BY window_start DESC
                LIMIT 1
            ');
            
            if (!$stmt) {
                return [
                    'limit' => $limit,
                    'remaining' => $limit,
                    'reset' => time() + RATE_LIMIT_WINDOW
                ];
            }
            
            $stmt->execute([$apiKey, $windowStart]);
            $window = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($window) {
                return [
                    'limit' => $limit,
                    'remaining' => max(0, $limit - $window['request_count']),
                    'reset' => strtotime($window['window_end'])
                ];
            }
            
            return [
                'limit' => $limit,
                'remaining' => $limit,
                'reset' => time() + RATE_LIMIT_WINDOW
            ];
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to get rate limit status', ['error' => $e->getMessage()]);
            return [
                'limit' => $limit,
                'remaining' => $limit,
                'reset' => time() + RATE_LIMIT_WINDOW
            ];
        }
    }
}