<?php
/**
 * API Management Data Handler
 * AJAX endpoint for loading dashboard data
 */

session_start();

// Use the correct database connection
require_once 'Connections/paymaster.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized', 'data' => []]);
    exit();
}

header('Content-Type: application/json');

// Check if database connection exists
if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed', 'data' => []]);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'organizations':
            getOrganizations();
            break;
            
        case 'apiKeys':
            getApiKeys();
            break;
            
        case 'webhooks':
            getWebhooks();
            break;
            
        case 'logs':
            getLogs();
            break;
            
        case 'alerts':
            getAlerts();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action, 'data' => []]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'data' => []]);
}

function getOrganizations() {
    global $conn;
    
    try {
        $stmt = $conn->prepare('
            SELECT 
                org_id,
                org_name,
                org_code,
                contact_email,
                rate_limit_per_min,
                is_active,
                created_at
            FROM api_organizations
            ORDER BY created_at DESC
        ');
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare statement', 'data' => []]);
            return;
        }
        
        $stmt->execute();
        $organizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $organizations]);
    } catch (PDOException $e) {
        // Table might not exist yet
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No data available. Please run the API setup SQL file.']);
    }
}

function getApiKeys() {
    global $conn;
    
    try {
        $stmt = $conn->prepare('
            SELECT 
                ak.key_id,
                ak.api_key,
                ao.org_name,
                ak.ed_name,
                CASE WHEN ak.ed_type = 1 THEN "Allowance" ELSE "Deduction" END as resource_type,
                ak.is_active,
                ak.total_requests,
                ak.last_used_at,
                ak.expires_at
            FROM api_keys ak
            JOIN api_organizations ao ON ak.org_id = ao.org_id
            ORDER BY ak.created_at DESC
        ');
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare statement', 'data' => []]);
            return;
        }
        
        $stmt->execute();
        $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $keys]);
    } catch (PDOException $e) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No data available']);
    }
}

function getWebhooks() {
    global $conn;
    
    try {
        $stmt = $conn->prepare('
            SELECT 
                w.webhook_id,
                w.webhook_name,
                ao.org_name,
                w.url,
                w.events,
                w.is_active,
                w.total_deliveries,
                w.failed_deliveries,
                CASE 
                    WHEN w.total_deliveries > 0 
                    THEN ROUND((w.total_deliveries - w.failed_deliveries) / w.total_deliveries * 100, 1)
                    ELSE 0 
                END as success_rate,
                w.last_delivery_at
            FROM api_webhooks w
            JOIN api_organizations ao ON w.org_id = ao.org_id
            ORDER BY w.created_at DESC
        ');
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare statement', 'data' => []]);
            return;
        }
        
        $stmt->execute();
        $webhooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode events JSON for display
        foreach ($webhooks as &$webhook) {
            $webhook['events'] = json_decode($webhook['events'], true);
        }
        
        echo json_encode(['success' => true, 'data' => $webhooks]);
    } catch (PDOException $e) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No data available']);
    }
}

function getLogs() {
    global $conn;
    
    try {
        // Get filter parameters
        $orgFilter = $_GET['org'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        
        $query = '
            SELECT 
                rl.request_id,
                ao.org_name,
                rl.endpoint,
                rl.method,
                rl.response_status,
                rl.response_time_ms,
                rl.ip_address,
                rl.request_timestamp
            FROM api_request_logs rl
            LEFT JOIN api_organizations ao ON rl.org_id = ao.org_id
            WHERE 1=1
        ';
        
        $params = [];
        
        if ($orgFilter) {
            $query .= ' AND rl.org_id = ?';
            $params[] = $orgFilter;
        }
        
        if ($statusFilter) {
            $query .= ' AND rl.response_status = ?';
            $params[] = $statusFilter;
        }
        
        $query .= ' ORDER BY rl.request_timestamp DESC LIMIT 100';
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare statement', 'data' => []]);
            return;
        }
        
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $logs]);
    } catch (PDOException $e) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No data available']);
    }
}

function getAlerts() {
    global $conn;
    
    try {
        $stmt = $conn->prepare('
            SELECT 
                sa.alert_id,
                sa.severity,
                sa.alert_type,
                ao.org_name,
                sa.description,
                sa.ip_address,
                CASE WHEN sa.is_resolved = 1 THEN "Resolved" ELSE "Pending" END as status,
                sa.created_at
            FROM api_security_alerts sa
            LEFT JOIN api_organizations ao ON sa.org_id = ao.org_id
            ORDER BY sa.created_at DESC
            LIMIT 100
        ');
        
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Failed to prepare statement', 'data' => []]);
            return;
        }
        
        $stmt->execute();
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $alerts]);
    } catch (PDOException $e) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'No data available']);
    }
}