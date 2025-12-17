<?php
/**
 * Webhook Dispatcher
 * Triggers webhook deliveries for payroll events
 */

class WebhookDispatcher {
    
    private $conn;
    
    public function __construct() {
        require_once dirname(__DIR__) . '/config/api_config.php';
        $this->conn = getApiDatabaseConnection();
    }
    
    /**
     * Trigger webhook event
     */
    public static function trigger($eventType, $data) {
        if (!WEBHOOK_ENABLED) {
            return;
        }
        
        $dispatcher = new self();
        $dispatcher->dispatchEvent($eventType, $data);
    }
    
    /**
     * Dispatch event to all subscribed webhooks
     */
    private function dispatchEvent($eventType, $data) {
        if (!$this->conn) {
            logApiActivity('error', 'Webhook dispatch failed', ['error' => 'No database connection']);
            return;
        }
        
        try {
            // Get all active webhooks subscribed to this event
            /** @var \PDO $pdo */
            $pdo = $this->conn;
            $stmt = $pdo->prepare('
                SELECT 
                    webhook_id,
                    org_id,
                    url,
                    secret,
                    retry_count,
                    timeout_seconds
                FROM api_webhooks
                WHERE is_active = 1
                  AND JSON_CONTAINS(events, ?)
            ');
            
            if (!$stmt) {
                return;
            }
            
            $stmt->execute([json_encode($eventType)]);
            $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($webhooks as $webhook) {
                $this->queueDelivery($webhook, $eventType, $data);
            }
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Webhook dispatch error', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Queue webhook delivery
     */
    private function queueDelivery($webhook, $eventType, $data) {
        // Create payload
        $payload = [
            'event' => $eventType,
            'timestamp' => date('c'),
            'organization_id' => str_pad($webhook['org_id'], 3, '0', STR_PAD_LEFT),
            'data' => $data
        ];
        
        // Deliver immediately (can be changed to queue for background processing)
        $this->deliverWebhook($webhook, $payload);
    }
    
    /**
     * Deliver webhook to endpoint
     */
    private function deliverWebhook($webhook, $payload) {
        $payloadJson = json_encode($payload);
        $signature = hash_hmac('sha256', $payloadJson, $webhook['secret']);
        
        $maxRetries = $webhook['retry_count'] ?? 3;
        $timeout = $webhook['timeout_seconds'] ?? 30;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init($webhook['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-Webhook-Signature: ' . $signature,
                'X-Webhook-Event: ' . $payload['event'],
                'X-Webhook-Delivery: ' . $attempt,
                'User-Agent: OOUTH-Salary-Webhook/1.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            $success = $httpCode >= 200 && $httpCode < 300;
            
            // Log delivery attempt
            $this->logDelivery(
                $webhook['webhook_id'],
                $payload['event'],
                $payloadJson,
                $success ? 'success' : 'failed',
                $httpCode,
                $response,
                $attempt,
                $error
            );
            
            // Update webhook stats
            $this->updateWebhookStats($webhook['webhook_id'], $success);
            
            if ($success) {
                break; // Success, no need to retry
            }
            
            // Wait before retry (exponential backoff)
            if ($attempt < $maxRetries) {
                sleep(pow(2, $attempt)); // 2, 4, 8 seconds
            }
        }
    }
    
    /**
     * Log webhook delivery
     */
    private function logDelivery($webhookId, $eventType, $payload, $status, $httpCode, $response, $attempt, $error) {
        if (!$this->conn) {
            return;
        }
        
        try {
            /** @var \PDO $pdo */
            $pdo = $this->conn;
            $stmt = $pdo->prepare('
                INSERT INTO api_webhook_logs (
                    webhook_id,
                    event_type,
                    payload,
                    delivery_status,
                    response_code,
                    response_body,
                    retry_attempt,
                    error_message,
                    delivered_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');
            
            if ($stmt) {
                $stmt->execute([
                    $webhookId,
                    $eventType,
                    $payload,
                    $status,
                    $httpCode,
                    substr($response, 0, 1000), // Limit response size
                    $attempt,
                    $error
                ]);
            }
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to log webhook delivery', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Update webhook statistics
     */
    private function updateWebhookStats($webhookId, $success) {
        if (!$this->conn) {
            return;
        }
        
        try {
            /** @var \PDO $pdo */
            $pdo = $this->conn;
            
            if ($success) {
                $stmt = $pdo->prepare('
                    UPDATE api_webhooks
                    SET total_deliveries = total_deliveries + 1,
                        last_delivery_at = NOW(),
                        last_delivery_status = "success"
                    WHERE webhook_id = ?
                ');
            } else {
                $stmt = $pdo->prepare('
                    UPDATE api_webhooks
                    SET total_deliveries = total_deliveries + 1,
                        failed_deliveries = failed_deliveries + 1,
                        last_delivery_at = NOW(),
                        last_delivery_status = "failed"
                    WHERE webhook_id = ?
                ');
            }
            
            if ($stmt) {
                $stmt->execute([$webhookId]);
            }
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to update webhook stats', ['error' => $e->getMessage()]);
        }
    }
}

/**
 * Helper function to trigger webhooks from anywhere in the app
 */
function triggerWebhook($eventType, $data) {
    WebhookDispatcher::trigger($eventType, $data);
}