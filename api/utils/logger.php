<?php
/**
 * API Request Logger
 * Logs all API requests to database
 */

class ApiLogger {
    
    private $conn;
    private $requestId;
    private $startTime;
    
    public function __construct() {
        $this->conn = getApiDatabaseConnection();
        $this->requestId = generateRequestId();
        $this->startTime = microtime(true);
        
        // Set request ID in server variable for response handler
        $_SERVER['HTTP_X_REQUEST_ID'] = $this->requestId;
    }
    
    /**
     * Log API request
     */
    public function logRequest($orgId, $apiKey, $endpoint, $responseStatus, $errorCode = null, $errorMessage = null, $periodAccessed = null, $recordsReturned = null) {
        if (!LOG_ALL_REQUESTS) {
            return;
        }
        
        try {
            $responseTime = round((microtime(true) - $this->startTime) * 1000); // milliseconds
            
            $stmt = $this->conn->prepare('
                INSERT INTO api_request_logs (
                    request_id,
                    org_id,
                    api_key,
                    endpoint,
                    method,
                    ip_address,
                    user_agent,
                    response_status,
                    response_time_ms,
                    error_code,
                    error_message,
                    period_accessed,
                    records_returned
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            
            $stmt->execute([
                $this->requestId,
                $orgId,
                $apiKey,
                $endpoint,
                $_SERVER['REQUEST_METHOD'],
                $_SERVER['REMOTE_ADDR'],
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $responseStatus,
                $responseTime,
                $errorCode,
                $errorMessage,
                $periodAccessed,
                $recordsReturned
            ]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to log API request', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Get request ID
     */
    public function getRequestId() {
        return $this->requestId;
    }
    
    /**
     * Get response time
     */
    public function getResponseTime() {
        return round((microtime(true) - $this->startTime) * 1000);
    }
    
    /**
     * Get request statistics
     */
    public static function getStatistics($orgId = null, $days = 7) {
        try {
            $conn = getApiDatabaseConnection();
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
            
            $query = '
                SELECT 
                    COUNT(*) as total_requests,
                    AVG(response_time_ms) as avg_response_time,
                    MIN(response_time_ms) as min_response_time,
                    MAX(response_time_ms) as max_response_time,
                    SUM(CASE WHEN response_status >= 200 AND response_status < 300 THEN 1 ELSE 0 END) as success_count,
                    SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as error_count,
                    COUNT(DISTINCT api_key) as unique_keys
                FROM api_request_logs
                WHERE request_timestamp > ?
            ';
            
            $params = [$cutoffDate];
            
            if ($orgId) {
                $query .= ' AND org_id = ?';
                $params[] = $orgId;
            }
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to get request statistics', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Get top endpoints
     */
    public static function getTopEndpoints($orgId = null, $days = 7, $limit = 10) {
        try {
            $conn = getApiDatabaseConnection();
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
            
            $query = '
                SELECT 
                    endpoint,
                    COUNT(*) as request_count,
                    AVG(response_time_ms) as avg_response_time,
                    SUM(CASE WHEN response_status >= 400 THEN 1 ELSE 0 END) as error_count
                FROM api_request_logs
                WHERE request_timestamp > ?
            ';
            
            $params = [$cutoffDate];
            
            if ($orgId) {
                $query .= ' AND org_id = ?';
                $params[] = $orgId;
            }
            
            $query .= ' GROUP BY endpoint ORDER BY request_count DESC LIMIT ' . (int)$limit;
            
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to get top endpoints', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Clean up old logs
     */
    public static function cleanup() {
        try {
            $conn = getApiDatabaseConnection();
            $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . LOG_RETENTION_DAYS . ' days'));
            
            $stmt = $conn->prepare('
                DELETE FROM api_request_logs 
                WHERE request_timestamp < ?
            ');
            
            $stmt->execute([$cutoffDate]);
            
            $deleted = $stmt->rowCount();
            
            logApiActivity('info', 'Cleaned up old API logs', ['deleted_count' => $deleted]);
            
            return $deleted;
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to cleanup old logs', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}

