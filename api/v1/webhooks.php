<?php
/**
 * Webhook API Endpoints
 * Manages webhook registrations and deliveries
 */

class WebhookAPI {
    
    private $conn;
    private $jwtHandler;
    private $keyValidator;
    private $rateLimiter;
    private $logger;
    private $tokenData;
    private $keyData;
    
    public function __construct() {
        $this->conn = getApiDatabaseConnection();
        $this->jwtHandler = new JWTHandler();
        $this->keyValidator = new ApiKeyValidator();
        $this->rateLimiter = new RateLimiter();
        $this->logger = new ApiLogger();
        
        // Authenticate request
        $this->authenticate();
    }
    
    /**
     * Check if database connection is valid
     */
    private function hasConnection() {
        return $this->conn !== null;
    }
    
    /**
     * Prepare statement with error handling
     */
    private function prepareStatement($sql) {
        if (!$this->hasConnection()) {
            return false;
        }
        /** @var \PDO $conn */
        $conn = $this->conn;
        return $conn->prepare($sql);
    }
    
    /**
     * Authenticate request (same as PayrollAPI)
     */
    private function authenticate() {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->logger->logRequest(null, null, $_SERVER['REQUEST_URI'], 401, 'UNAUTHORIZED', 'Missing Bearer token');
            apiError('UNAUTHORIZED', 'Bearer token is required', null, 401);
        }
        
        $token = $matches[1];
        $validation = $this->jwtHandler->validateToken($token);
        
        if (!$validation['valid']) {
            $this->logger->logRequest(null, null, $_SERVER['REQUEST_URI'], 401, $validation['error'], $validation['message']);
            apiError($validation['error'], $validation['message'], null, 401);
        }
        
        $this->tokenData = $validation['data'];
        $keyValidation = $this->keyValidator->validate($this->tokenData['api_key']);
        
        if (!$keyValidation['valid']) {
            $this->logger->logRequest(null, null, $_SERVER['REQUEST_URI'], 401, $keyValidation['error'], $keyValidation['message']);
            apiError($keyValidation['error'], $keyValidation['message'], null, 401);
        }
        
        $this->keyData = $keyValidation['data'];
        
        // Check rate limits
        $rateLimitCheck = $this->rateLimiter->checkLimit($this->keyData['api_key'], $this->keyData['rate_limit_per_min']);
        
        if (!$rateLimitCheck['allowed']) {
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], $_SERVER['REQUEST_URI'], 429, $rateLimitCheck['error'], $rateLimitCheck['message']);
            apiError($rateLimitCheck['error'], $rateLimitCheck['message'], 'Retry after ' . $rateLimitCheck['retry_after'] . ' seconds', 429);
        }
    }
    
    /**
     * Handle webhook request
     */
    public function handle() {
        global $segments;
        
        $action = $segments[1] ?? null;
        $id = $segments[2] ?? null;
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($action) {
            case 'register':
                if ($method === 'POST') {
                    $this->registerWebhook();
                } else {
                    apiError('BAD_REQUEST', 'Method not allowed', null, 405);
                }
                break;
                
            case null:
                // List all webhooks
                if ($method === 'GET') {
                    $this->listWebhooks();
                } else {
                    apiError('BAD_REQUEST', 'Method not allowed', null, 405);
                }
                break;
                
            default:
                // Specific webhook operations
                if (!is_numeric($action)) {
                    apiError('BAD_REQUEST', 'Invalid webhook ID', null, 400);
                }
                
                $webhookId = (int)$action;
                $subAction = $segments[2] ?? null;
                
                if ($subAction === 'test' && $method === 'POST') {
                    $this->testWebhook($webhookId);
                } elseif ($subAction === null && $method === 'GET') {
                    $this->getWebhook($webhookId);
                } elseif ($subAction === null && $method === 'PUT') {
                    $this->updateWebhook($webhookId);
                } elseif ($subAction === null && $method === 'DELETE') {
                    $this->deleteWebhook($webhookId);
                } else {
                    apiError('NOT_FOUND', 'Endpoint not found', null, 404);
                }
        }
    }
    
    /**
     * Register new webhook
     */
    private function registerWebhook() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $webhookName = trim($input['name'] ?? '');
        $url = trim($input['url'] ?? '');
        $events = $input['events'] ?? [];
        
        if (empty($webhookName) || empty($url) || empty($events)) {
            apiError('MISSING_PARAMETER', 'Name, URL, and events are required', null, 400);
        }
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            apiError('BAD_REQUEST', 'Invalid webhook URL', null, 400);
        }
        
        // Validate events
        $validEvents = WEBHOOK_EVENTS;
        foreach ($events as $event) {
            if (!in_array($event, $validEvents)) {
                apiError('BAD_REQUEST', "Invalid event: $event", 'Valid events: ' . implode(', ', $validEvents), 400);
            }
        }
        
        try {
            // Generate webhook secret
            $secret = bin2hex(random_bytes(32));
            
            $stmt = $this->prepareStatement('
                INSERT INTO api_webhooks (
                    org_id,
                    webhook_name,
                    url,
                    events,
                    secret,
                    is_active,
                    retry_count
                ) VALUES (?, ?, ?, ?, ?, 1, ?)
            ');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $stmt->execute([
                $this->keyData['org_id'],
                $webhookName,
                $url,
                json_encode($events),
                $secret,
                $input['retry_count'] ?? 3
            ]);
            
            $webhookId = $this->conn->lastInsertId();
            
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], $_SERVER['REQUEST_URI'], 201, null, null, null, 1);
            
            apiSuccess([
                'webhook_id' => $webhookId,
                'name' => $webhookName,
                'url' => $url,
                'events' => $events,
                'secret' => $secret,
                'message' => 'Webhook registered successfully. Save the secret for signature verification.'
            ], [], 201);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to register webhook', ['error' => $e->getMessage()]);
            apiError('INTERNAL_ERROR', 'Failed to register webhook', $e->getMessage(), 500);
        }
    }
    
    /**
     * List all webhooks for organization
     */
    private function listWebhooks() {
        try {
            $stmt = $this->prepareStatement('
                SELECT 
                    webhook_id,
                    webhook_name,
                    url,
                    events,
                    is_active,
                    total_deliveries,
                    failed_deliveries,
                    last_delivery_at,
                    created_at
                FROM api_webhooks
                WHERE org_id = ?
                ORDER BY created_at DESC
            ');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $stmt->execute([$this->keyData['org_id']]);
            $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode events JSON
            foreach ($webhooks as &$webhook) {
                $webhook['events'] = json_decode($webhook['events'], true);
            }
            
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], $_SERVER['REQUEST_URI'], 200, null, null, null, count($webhooks));
            
            apiSuccess($webhooks);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to list webhooks', ['error' => $e->getMessage()]);
            apiError('INTERNAL_ERROR', 'Failed to list webhooks', $e->getMessage(), 500);
        }
    }
    
    /**
     * Get specific webhook
     */
    private function getWebhook($webhookId) {
        try {
            $stmt = $this->prepareStatement('
                SELECT 
                    webhook_id,
                    webhook_name,
                    url,
                    events,
                    is_active,
                    retry_count,
                    timeout_seconds,
                    total_deliveries,
                    failed_deliveries,
                    last_delivery_at,
                    last_delivery_status,
                    created_at,
                    updated_at
                FROM api_webhooks
                WHERE webhook_id = ? AND org_id = ?
                LIMIT 1
            ');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $stmt->execute([$webhookId, $this->keyData['org_id']]);
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$webhook) {
                apiError('NOT_FOUND', 'Webhook not found', null, 404);
            }
            
            $webhook['events'] = json_decode($webhook['events'], true);
            
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], $_SERVER['REQUEST_URI'], 200, null, null, null, 1);
            
            apiSuccess($webhook);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to get webhook', ['error' => $e->getMessage()]);
            apiError('INTERNAL_ERROR', 'Failed to get webhook', $e->getMessage(), 500);
        }
    }
    
    /**
     * Update webhook
     */
    private function updateWebhook($webhookId) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            // Verify webhook belongs to organization
            $checkStmt = $this->prepareStatement('SELECT webhook_id FROM api_webhooks WHERE webhook_id = ? AND org_id = ?');
            if (!$checkStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $checkStmt->execute([$webhookId, $this->keyData['org_id']]);
            if (!$checkStmt->fetch()) {
                apiError('NOT_FOUND', 'Webhook not found', null, 404);
            }
            
            // Build update query dynamically based on provided fields
            $updates = [];
            $params = [];
            
            if (isset($input['name'])) {
                $updates[] = 'webhook_name = ?';
                $params[] = $input['name'];
            }
            if (isset($input['url'])) {
                if (!filter_var($input['url'], FILTER_VALIDATE_URL)) {
                    apiError('BAD_REQUEST', 'Invalid webhook URL', null, 400);
                }
                $updates[] = 'url = ?';
                $params[] = $input['url'];
            }
            if (isset($input['events'])) {
                $updates[] = 'events = ?';
                $params[] = json_encode($input['events']);
            }
            if (isset($input['is_active'])) {
                $updates[] = 'is_active = ?';
                $params[] = (int)$input['is_active'];
            }
            
            if (empty($updates)) {
                apiError('BAD_REQUEST', 'No fields to update', null, 400);
            }
            
            $params[] = $webhookId;
            $params[] = $this->keyData['org_id'];
            
            $sql = 'UPDATE api_webhooks SET ' . implode(', ', $updates) . ' WHERE webhook_id = ? AND org_id = ?';
            $stmt = $this->prepareStatement($sql);
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $stmt->execute($params);
            
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], $_SERVER['REQUEST_URI'], 200, null, null, null, 1);
            
            apiSuccess(['message' => 'Webhook updated successfully']);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to update webhook', ['error' => $e->getMessage()]);
            apiError('INTERNAL_ERROR', 'Failed to update webhook', $e->getMessage(), 500);
        }
    }
    
    /**
     * Delete webhook
     */
    private function deleteWebhook($webhookId) {
        try {
            $stmt = $this->prepareStatement('DELETE FROM api_webhooks WHERE webhook_id = ? AND org_id = ?');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $stmt->execute([$webhookId, $this->keyData['org_id']]);
            
            if ($stmt->rowCount() === 0) {
                apiError('NOT_FOUND', 'Webhook not found', null, 404);
            }
            
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], $_SERVER['REQUEST_URI'], 200, null, null, null, 1);
            
            apiSuccess(['message' => 'Webhook deleted successfully']);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to delete webhook', ['error' => $e->getMessage()]);
            apiError('INTERNAL_ERROR', 'Failed to delete webhook', $e->getMessage(), 500);
        }
    }
    
    /**
     * Test webhook delivery
     */
    private function testWebhook($webhookId) {
        try {
            // Get webhook details
            $stmt = $this->prepareStatement('
                SELECT url, secret, events
                FROM api_webhooks
                WHERE webhook_id = ? AND org_id = ?
                LIMIT 1
            ');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $stmt->execute([$webhookId, $this->keyData['org_id']]);
            $webhook = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$webhook) {
                apiError('NOT_FOUND', 'Webhook not found', null, 404);
            }
            
            // Create test payload
            $payload = [
                'event' => 'webhook.test',
                'timestamp' => date('c'),
                'organization_id' => str_pad($this->keyData['org_id'], 3, '0', STR_PAD_LEFT),
                'data' => [
                    'message' => 'This is a test webhook delivery',
                    'webhook_id' => $webhookId,
                    'test' => true
                ]
            ];
            
            // Deliver webhook
            $result = $this->deliverWebhook($webhook['url'], $webhook['secret'], $payload);
            
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], $_SERVER['REQUEST_URI'], 200, null, null, null, 1);
            
            apiSuccess([
                'message' => 'Test webhook delivered',
                'delivery_result' => $result
            ]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to test webhook', ['error' => $e->getMessage()]);
            apiError('INTERNAL_ERROR', 'Failed to test webhook', $e->getMessage(), 500);
        }
    }
    
    /**
     * Deliver webhook payload to URL
     */
    private function deliverWebhook($url, $secret, $payload) {
        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $secret);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_TIMEOUT, WEBHOOK_TIMEOUT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Webhook-Signature: ' . $signature,
            'X-Webhook-Event: ' . ($payload['event'] ?? 'unknown'),
            'User-Agent: OOUTH-Salary-Webhook/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        return [
            'status' => $httpCode >= 200 && $httpCode < 300 ? 'success' : 'failed',
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error ?: null
        ];
    }
}

// Handle request
$api = new WebhookAPI();
$api->handle();