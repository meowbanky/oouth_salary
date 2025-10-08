<?php
/**
 * API Management Actions Handler
 * Handles CRUD operations for organizations, API keys, and webhooks
 */

session_start();
require_once 'Connections/paymaster.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['SESS_MEMBER_ID']) || $_SESSION['role'] !== 'Admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Check if database connection exists
if (!isset($conn) || $conn === null) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_organization':
            createOrganization();
            break;
            
        case 'generate_api_key':
            generateApiKey();
            break;
            
        case 'revoke_api_key':
            revokeApiKey();
            break;
            
        case 'toggle_org_status':
            toggleOrgStatus();
            break;
            
        case 'get_allowances_deductions':
            getAllowancesDeductions();
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Create new organization
 */
function createOrganization() {
    global $conn;
    
    // Validate required fields
    $orgName = trim($_POST['org_name'] ?? '');
    $orgCode = strtoupper(trim($_POST['org_code'] ?? ''));
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $contactPhone = trim($_POST['contact_phone'] ?? '');
    $rateLimit = (int)($_POST['rate_limit'] ?? 500);
    $allowedIps = trim($_POST['allowed_ips'] ?? '');
    
    if (empty($orgName) || empty($orgCode) || empty($contactEmail)) {
        echo json_encode(['success' => false, 'error' => 'Organization name, code, and email are required']);
        return;
    }
    
    // Validate email
    if (!filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email address']);
        return;
    }
    
    // Parse allowed IPs if provided
    $allowedIpsArray = null;
    if (!empty($allowedIps)) {
        $ips = array_map('trim', explode(',', $allowedIps));
        $allowedIpsArray = json_encode($ips);
    }
    
    try {
        // Check if org code already exists
        $checkStmt = $conn->prepare('SELECT org_id FROM api_organizations WHERE org_code = ?');
        $checkStmt->execute([$orgCode]);
        
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Organization code already exists']);
            return;
        }
        
        // Insert new organization
        $stmt = $conn->prepare('
            INSERT INTO api_organizations (
                org_name, 
                org_code, 
                contact_email, 
                contact_phone,
                rate_limit_per_min, 
                allowed_ips,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $orgName,
            $orgCode,
            $contactEmail,
            $contactPhone,
            $rateLimit,
            $allowedIpsArray,
            $_SESSION['SESS_MEMBER_ID']
        ]);
        
        $orgId = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Organization created successfully',
            'org_id' => $orgId
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Generate API key for allowance/deduction
 */
function generateApiKey() {
    global $conn;
    
    // Validate required fields
    $orgId = (int)($_POST['org_id'] ?? 0);
    $edId = (int)($_POST['ed_id'] ?? 0);
    $edType = (int)($_POST['ed_type'] ?? 0);
    $expiresAt = trim($_POST['expires_at'] ?? '');
    $rateLimit = (int)($_POST['rate_limit'] ?? 100);
    $allowedIps = trim($_POST['allowed_ips'] ?? '');
    
    if ($orgId <= 0 || $edId <= 0 || !in_array($edType, [1, 2])) {
        echo json_encode(['success' => false, 'error' => 'Invalid organization, resource, or type']);
        return;
    }
    
    try {
        // Get organization details
        $orgStmt = $conn->prepare('SELECT org_code, is_active FROM api_organizations WHERE org_id = ?');
        $orgStmt->execute([$orgId]);
        $org = $orgStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$org) {
            echo json_encode(['success' => false, 'error' => 'Organization not found']);
            return;
        }
        
        if ($org['is_active'] != 1) {
            echo json_encode(['success' => false, 'error' => 'Organization is inactive']);
            return;
        }
        
        // Get allowance/deduction name
        $edStmt = $conn->prepare('SELECT ed FROM tbl_earning_deduction WHERE ed_id = ?');
        $edStmt->execute([$edId]);
        $edName = $edStmt->fetchColumn();
        
        if (!$edName) {
            echo json_encode(['success' => false, 'error' => 'Allowance/Deduction not found']);
            return;
        }
        
        // Check if key already exists for this resource
        $checkStmt = $conn->prepare('
            SELECT api_key FROM api_keys 
            WHERE org_id = ? AND ed_id = ? AND ed_type = ? AND is_active = 1
        ');
        $checkStmt->execute([$orgId, $edId, $edType]);
        
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'An active API key already exists for this resource']);
            return;
        }
        
        // Generate API key and secret
        $apiKey = generateApiKeyString($orgId, $edType, $edId);
        $apiSecret = bin2hex(random_bytes(32)); // 64 character secret
        $apiSecretHash = hash('sha256', $apiSecret);
        
        // Parse allowed IPs if provided
        $allowedIpsArray = null;
        if (!empty($allowedIps)) {
            $ips = array_map('trim', explode(',', $allowedIps));
            $allowedIpsArray = json_encode($ips);
        }
        
        // Insert API key
        $stmt = $conn->prepare('
            INSERT INTO api_keys (
                api_key,
                api_secret,
                org_id,
                ed_id,
                ed_type,
                ed_name,
                is_active,
                rate_limit_per_min,
                allowed_ips,
                expires_at,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)
        ');
        
        $stmt->execute([
            $apiKey,
            $apiSecretHash,
            $orgId,
            $edId,
            $edType,
            $edName,
            $rateLimit,
            $allowedIpsArray,
            $expiresAt ? $expiresAt : null,
            $_SESSION['SESS_MEMBER_ID']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'API key generated successfully',
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'warning' => 'Save the secret now! It will not be shown again.'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Generate API key string
 * Format: oouth_{org_id}_{allow|deduc}_{ed_id}_{hash}
 */
function generateApiKeyString($orgId, $edType, $edId) {
    $type = ($edType == 1) ? 'allow' : 'deduc';
    $hash = bin2hex(random_bytes(8)); // 16 character random hash
    return sprintf('oouth_%03d_%s_%d_%s', $orgId, $type, $edId, $hash);
}

/**
 * Revoke API key
 */
function revokeApiKey() {
    global $conn;
    
    $keyId = (int)($_POST['key_id'] ?? 0);
    
    if ($keyId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid API key ID']);
        return;
    }
    
    try {
        $stmt = $conn->prepare('UPDATE api_keys SET is_active = 0 WHERE key_id = ?');
        $stmt->execute([$keyId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'API key revoked successfully'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Toggle organization status
 */
function toggleOrgStatus() {
    global $conn;
    
    $orgId = (int)($_POST['org_id'] ?? 0);
    $isActive = (int)($_POST['is_active'] ?? 0);
    
    if ($orgId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid organization ID']);
        return;
    }
    
    try {
        $newStatus = $isActive == 1 ? 0 : 1; // Toggle
        $stmt = $conn->prepare('UPDATE api_organizations SET is_active = ? WHERE org_id = ?');
        $stmt->execute([$newStatus, $orgId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Organization status updated successfully',
            'new_status' => $newStatus
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

/**
 * Get allowances and deductions for dropdown
 */
function getAllowancesDeductions() {
    global $conn;
    
    try {
        $stmt = $conn->prepare('
            SELECT 
                ed_id,
                ed as ed_name,
                edType as types
            FROM tbl_earning_deduction
            WHERE status = "Active"
            ORDER BY ed
        ');
        
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Separate into allowances and deductions
        $allowances = [];
        $deductions = [];
        
        foreach ($items as $item) {
            if ($item['types'] == 1) {
                $allowances[] = $item;
            } else {
                $deductions[] = $item;
            }
        }
        
        echo json_encode([
            'success' => true,
            'allowances' => $allowances,
            'deductions' => $deductions
        ]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

