<?php
/**
 * Payroll API Endpoints
 * Handles periods, allowances, and deductions
 */

class PayrollAPI {
    
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
     * Authenticate request
     */
    private function authenticate() {
        // Get JWT token from Authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $this->logger->logRequest(null, null, $_SERVER['REQUEST_URI'], 401, 'UNAUTHORIZED', 'Missing Bearer token');
            apiError('UNAUTHORIZED', 'Bearer token is required', null, 401);
        }
        
        $token = $matches[1];
        
        // Validate JWT token
        $validation = $this->jwtHandler->validateToken($token);
        
        if (!$validation['valid']) {
            $this->logger->logRequest(null, null, $_SERVER['REQUEST_URI'], 401, $validation['error'], $validation['message']);
            apiError($validation['error'], $validation['message'], null, 401);
        }
        
        $this->tokenData = $validation['data'];
        
        // Validate API key from token
        $keyValidation = $this->keyValidator->validate($this->tokenData['api_key']);
        
        if (!$keyValidation['valid']) {
            $this->logger->logRequest(null, null, $_SERVER['REQUEST_URI'], 401, $keyValidation['error'], $keyValidation['message']);
            apiError($keyValidation['error'], $keyValidation['message'], null, 401);
        }
        
        $this->keyData = $keyValidation['data'];
        
        // Check rate limits
        $rateLimitCheck = $this->rateLimiter->checkLimit(
            $this->keyData['api_key'],
            $this->keyData['rate_limit_per_min']
        );
        
        if (!$rateLimitCheck['allowed']) {
            $this->logger->logRequest(
                $this->keyData['org_id'],
                $this->keyData['api_key'],
                $_SERVER['REQUEST_URI'],
                429,
                $rateLimitCheck['error'],
                $rateLimitCheck['message']
            );
            apiError($rateLimitCheck['error'], $rateLimitCheck['message'], 
                'Retry after ' . $rateLimitCheck['retry_after'] . ' seconds', 429);
        }
        
        // Check organization rate limit
        $orgRateLimitCheck = $this->rateLimiter->checkOrganizationLimit(
            $this->keyData['org_id'],
            $this->keyData['org_rate_limit']
        );
        
        if (!$orgRateLimitCheck['allowed']) {
            $this->logger->logRequest(
                $this->keyData['org_id'],
                $this->keyData['api_key'],
                $_SERVER['REQUEST_URI'],
                429,
                $orgRateLimitCheck['error'],
                $orgRateLimitCheck['message']
            );
            apiError($orgRateLimitCheck['error'], $orgRateLimitCheck['message'], null, 429);
        }
    }
    
    /**
     * Handle payroll request
     */
    public function handle() {
        global $segments;
        
        $action = $segments[1] ?? null;
        $id = $segments[2] ?? null;
        $method = $_SERVER['REQUEST_METHOD'];
        
        switch ($action) {
            case 'periods':
                if ($id === 'active') {
                    $this->getActivePeriod();
                } elseif ($id) {
                    $this->getPeriod($id);
                } else {
                    $this->getPeriods();
                }
                break;
                
            case 'allowances':
                if (!$id) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 400, 'MISSING_PARAMETER', 'Allowance ID is required');
                    apiError('MISSING_PARAMETER', 'Allowance ID is required', null, 400);
                }
                $this->getAllowanceData($id);
                break;
                
            case 'deductions':
                if (!$id) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 400, 'MISSING_PARAMETER', 'Deduction ID is required');
                    apiError('MISSING_PARAMETER', 'Deduction ID is required', null, 400);
                }
                $this->getDeductionData($id);
                break;
                
            default:
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'Endpoint not found');
                apiError('NOT_FOUND', 'Endpoint not found', null, 404);
        }
    }
    
    /**
     * Get all payroll periods
     */
    private function getPeriods() {
        try {
            // Get pagination parameters
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(MAX_PAGE_SIZE, max(1, (int)($_GET['limit'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $limit;
            
            // Count total records
            $countStmt = $this->conn->prepare('SELECT COUNT(*) FROM payperiods WHERE payrollRun = 1');
            $countStmt->execute();
            $totalRecords = $countStmt->fetchColumn();
            
            // Get periods
            $stmt = $this->conn->prepare('
                SELECT 
                    periodId as period_id,
                    description,
                    periodYear as year,
                    payrollRun as is_active,
                    remark
                FROM payperiods
                WHERE payrollRun = 1
                ORDER BY periodId DESC
                LIMIT ? OFFSET ?
            ');
            
            $stmt->execute([$limit, $offset]);
            $periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log request
            $this->logger->logRequest(
                $this->keyData['org_id'],
                $this->keyData['api_key'],
                $_SERVER['REQUEST_URI'],
                200,
                null,
                null,
                null,
                count($periods)
            );
            
            // Return paginated response
            apiPaginated($periods, $page, $limit, $totalRecords);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to fetch periods', ['error' => $e->getMessage()]);
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', 'Database error');
            apiError('INTERNAL_ERROR', 'Failed to fetch periods', null, 500);
        }
    }
    
    /**
     * Get specific period
     */
    private function getPeriod($periodId) {
        try {
            $stmt = $this->conn->prepare('
                SELECT 
                    periodId as period_id,
                    description,
                    periodYear as year,
                    payrollRun as is_active,
                    remark
                FROM payperiods
                WHERE periodId = ? AND payrollRun = 1
                LIMIT 1
            ');
            
            $stmt->execute([$periodId]);
            $period = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$period) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'Period not found');
                apiError('NOT_FOUND', 'Period not found', null, 404);
            }
            
            // Log request
            $this->logger->logRequest(
                $this->keyData['org_id'],
                $this->keyData['api_key'],
                $_SERVER['REQUEST_URI'],
                200,
                null,
                null,
                $periodId,
                1
            );
            
            apiSuccess(['period' => $period]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to fetch period', ['error' => $e->getMessage()]);
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', 'Database error');
            apiError('INTERNAL_ERROR', 'Failed to fetch period', null, 500);
        }
    }
    
    /**
     * Get active period
     */
    private function getActivePeriod() {
        try {
            $stmt = $this->conn->prepare('
                SELECT 
                    periodId as period_id,
                    description,
                    periodYear as year,
                    payrollRun as is_active,
                    remark
                FROM payperiods
                WHERE payrollRun = 1
                ORDER BY periodId DESC
                LIMIT 1
            ');
            
            $stmt->execute();
            $period = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$period) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'No active period found');
                apiError('NOT_FOUND', 'No active period found', null, 404);
            }
            
            // Log request
            $this->logger->logRequest(
                $this->keyData['org_id'],
                $this->keyData['api_key'],
                $_SERVER['REQUEST_URI'],
                200,
                null,
                null,
                $period['period_id'],
                1
            );
            
            apiSuccess(['period' => $period]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to fetch active period', ['error' => $e->getMessage()]);
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', 'Database error');
            apiError('INTERNAL_ERROR', 'Failed to fetch active period', null, 500);
        }
    }
    
    /**
     * Get allowance data
     */
    private function getAllowanceData($allowanceId) {
        // Verify API key has access to this allowance
        $accessCheck = $this->keyValidator->checkResourceAccess($this->keyData, $allowanceId, 1);
        
        if (!$accessCheck['valid']) {
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 403, $accessCheck['error'], $accessCheck['message']);
            apiError($accessCheck['error'], $accessCheck['message'], null, 403);
        }
        
        try {
            // Get period from query parameter (default to active period)
            $periodId = $_GET['period'] ?? null;
            
            if (!$periodId) {
                // Get active period
                $stmt = $this->conn->prepare('SELECT periodId FROM payperiods WHERE payrollRun = 1 ORDER BY periodId DESC LIMIT 1');
                $stmt->execute();
                $periodId = $stmt->fetchColumn();
                
                if (!$periodId) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'No active period found');
                    apiError('NOT_FOUND', 'No active period found', null, 404);
                }
            }
            
            // Get period details
            $periodStmt = $this->conn->prepare('
                SELECT periodId as id, description, periodYear as year
                FROM payperiods WHERE periodId = ? AND payrollRun = 1 LIMIT 1
            ');
            $periodStmt->execute([$periodId]);
            $period = $periodStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$period) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'INVALID_PERIOD', 'Invalid period');
                apiError('INVALID_PERIOD', 'Invalid or inactive period', null, 404);
            }
            
            // Get allowance name
            $nameStmt = $this->conn->prepare('SELECT ed_name FROM tbl_earning_deduction WHERE ed_id = ? LIMIT 1');
            $nameStmt->execute([$allowanceId]);
            $allowanceName = $nameStmt->fetchColumn();
            
            if (!$allowanceName) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'INVALID_ED_ID', 'Invalid allowance ID');
                apiError('INVALID_ED_ID', 'Invalid allowance ID', null, 404);
            }
            
            // Get allowance data for all staff
            $stmt = $this->conn->prepare('
                SELECT 
                    ms.staff_id,
                    ms.NAME as name,
                    COALESCE(tm.allow, 0) as amount
                FROM master_staff ms
                LEFT JOIN tbl_master tm ON tm.staff_id = ms.staff_id 
                    AND tm.allow_id = ? 
                    AND tm.type = 1
                    AND tm.period = ?
                WHERE ms.period = ?
                ORDER BY ms.NAME
            ');
            
            $stmt->execute([$allowanceId, $periodId, $periodId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total
            $totalAmount = array_sum(array_column($data, 'amount'));
            
            // Log request
            $this->logger->logRequest(
                $this->keyData['org_id'],
                $this->keyData['api_key'],
                $_SERVER['REQUEST_URI'],
                200,
                null,
                null,
                $periodId,
                count($data)
            );
            
            // Return response
            apiSuccess($data, [
                'period' => $period,
                'allowance_name' => $allowanceName,
                'total_records' => count($data),
                'total_amount' => $totalAmount
            ]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to fetch allowance data', ['error' => $e->getMessage()]);
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', 'Database error');
            apiError('INTERNAL_ERROR', 'Failed to fetch allowance data', null, 500);
        }
    }
    
    /**
     * Get deduction data
     */
    private function getDeductionData($deductionId) {
        // Verify API key has access to this deduction
        $accessCheck = $this->keyValidator->checkResourceAccess($this->keyData, $deductionId, 2);
        
        if (!$accessCheck['valid']) {
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 403, $accessCheck['error'], $accessCheck['message']);
            apiError($accessCheck['error'], $accessCheck['message'], null, 403);
        }
        
        try {
            // Get period from query parameter (default to active period)
            $periodId = $_GET['period'] ?? null;
            
            if (!$periodId) {
                // Get active period
                $stmt = $this->conn->prepare('SELECT periodId FROM payperiods WHERE payrollRun = 1 ORDER BY periodId DESC LIMIT 1');
                $stmt->execute();
                $periodId = $stmt->fetchColumn();
                
                if (!$periodId) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'No active period found');
                    apiError('NOT_FOUND', 'No active period found', null, 404);
                }
            }
            
            // Get period details
            $periodStmt = $this->conn->prepare('
                SELECT periodId as id, description, periodYear as year
                FROM payperiods WHERE periodId = ? AND payrollRun = 1 LIMIT 1
            ');
            $periodStmt->execute([$periodId]);
            $period = $periodStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$period) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'INVALID_PERIOD', 'Invalid period');
                apiError('INVALID_PERIOD', 'Invalid or inactive period', null, 404);
            }
            
            // Get deduction name
            $nameStmt = $this->conn->prepare('SELECT ed_name FROM tbl_earning_deduction WHERE ed_id = ? LIMIT 1');
            $nameStmt->execute([$deductionId]);
            $deductionName = $nameStmt->fetchColumn();
            
            if (!$deductionName) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'INVALID_ED_ID', 'Invalid deduction ID');
                apiError('INVALID_ED_ID', 'Invalid deduction ID', null, 404);
            }
            
            // Get deduction data for all staff
            $stmt = $this->conn->prepare('
                SELECT 
                    ms.staff_id,
                    ms.NAME as name,
                    COALESCE(tm.deduc, 0) as amount
                FROM master_staff ms
                LEFT JOIN tbl_master tm ON tm.staff_id = ms.staff_id 
                    AND tm.allow_id = ? 
                    AND tm.type = 2
                    AND tm.period = ?
                WHERE ms.period = ?
                ORDER BY ms.NAME
            ');
            
            $stmt->execute([$deductionId, $periodId, $periodId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate total
            $totalAmount = array_sum(array_column($data, 'amount'));
            
            // Log request
            $this->logger->logRequest(
                $this->keyData['org_id'],
                $this->keyData['api_key'],
                $_SERVER['REQUEST_URI'],
                200,
                null,
                null,
                $periodId,
                count($data)
            );
            
            // Return response
            apiSuccess($data, [
                'period' => $period,
                'deduction_name' => $deductionName,
                'total_records' => count($data),
                'total_amount' => $totalAmount
            ]);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to fetch deduction data', ['error' => $e->getMessage()]);
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', 'Database error');
            apiError('INTERNAL_ERROR', 'Failed to fetch deduction data', null, 500);
        }
    }
}

// Handle request
$api = new PayrollAPI();
$api->handle();