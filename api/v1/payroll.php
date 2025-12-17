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
     * Check if database connection is valid
     */
    private function hasConnection() {
        return $this->conn !== null;
    }
    
    /**
     * Prepare statement with error handling
     * @return \PDOStatement|false
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
                
            case 'staff-deduction':
                $staffId = $_GET['staffid'] ?? null;
                $deductionId = $_GET['deductionid'] ?? null;
                
                if (!$staffId) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 400, 'MISSING_PARAMETER', 'Staff ID is required');
                    apiError('MISSING_PARAMETER', 'Staff ID is required (use ?staffid=X)', null, 400);
                }
                
                if (!$deductionId) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 400, 'MISSING_PARAMETER', 'Deduction ID is required');
                    apiError('MISSING_PARAMETER', 'Deduction ID is required (use ?deductionid=Y)', null, 400);
                }
                
                $this->getStaffDeduction($staffId, $deductionId);
                break;
                
            case 'edit-deduction':
                // Only allow PUT and PATCH methods
                if ($method !== 'PUT' && $method !== 'PATCH') {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 405, 'METHOD_NOT_ALLOWED', 'Only PUT or PATCH methods allowed');
                    apiError('METHOD_NOT_ALLOWED', 'Only PUT or PATCH methods allowed', null, 405);
                }
                $this->editDeduction();
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
            
            // Count total records (all periods, not just payrollRun=1)
            $countStmt = $this->prepareStatement('SELECT COUNT(*) FROM payperiods');
            if (!$countStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $countStmt->execute();
            $totalRecords = $countStmt->fetchColumn();
            
            // Get periods (all periods)
            // Note: LIMIT and OFFSET cannot be bound parameters in MySQL, must use direct integers
            $stmt = $this->prepareStatement(sprintf('
                SELECT 
                    periodId as period_id,
                    description,
                    periodYear as year,
                    payrollRun as is_active
                FROM payperiods WHERE payperiods.active < 1
                ORDER BY periodId DESC
                LIMIT %d OFFSET %d
            ', $limit, $offset));
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $stmt->execute();
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
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', $e->getMessage());
            apiError('INTERNAL_ERROR', 'Failed to fetch periods', $e->getMessage(), 500);
        }
    }
    
    /**
     * Get specific period
     */
    private function getPeriod($periodId) {
        try {
            $stmt = $this->prepareStatement('
                SELECT 
                    periodId as period_id,
                    description,
                    periodYear as year,
                    payrollRun as is_active
                FROM payperiods
                WHERE periodId = ?
                LIMIT 1
            ');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
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
            $stmt = $this->prepareStatement('
                SELECT 
                    periodId as period_id,
                    description,
                    periodYear as year,
                    payrollRun as is_active
                FROM payperiods
                ORDER BY periodId DESC
                LIMIT 1
            ');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
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
                // Get active period (most recent period)
                $stmt = $this->prepareStatement('SELECT periodId FROM payperiods ORDER BY periodId DESC LIMIT 1');
                if (!$stmt) {
                    apiError('INTERNAL_ERROR', 'Database error', null, 500);
                }
                $stmt->execute();
                $periodId = $stmt->fetchColumn();
                
                if (!$periodId) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'No active period found');
                    apiError('NOT_FOUND', 'No active period found', null, 404);
                }
            }
            
            // Get period details
            $periodStmt = $this->prepareStatement('
                SELECT periodId as id, description, periodYear as year
                FROM payperiods WHERE periodId = ? LIMIT 1
            ');
            if (!$periodStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $periodStmt->execute([$periodId]);
            $period = $periodStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$period) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'INVALID_PERIOD', 'Invalid period');
                apiError('INVALID_PERIOD', 'Invalid or inactive period', null, 404);
            }
            
            // Get allowance name
            $nameStmt = $this->prepareStatement('SELECT ed FROM tbl_earning_deduction WHERE ed_id = ? LIMIT 1');
            if (!$nameStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $nameStmt->execute([$allowanceId]);
            $allowanceName = $nameStmt->fetchColumn();
            
            if (!$allowanceName) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'INVALID_ED_ID', 'Invalid allowance ID');
                apiError('INVALID_ED_ID', 'Invalid allowance ID', null, 404);
            }
            
            // Get allowance data for all staff (exclude zero amounts)
            $stmt = $this->prepareStatement('
                SELECT 
                    ms.staff_id,
                    ms.NAME as name,
                    tm.allow as amount
                FROM master_staff ms
                INNER JOIN tbl_master tm ON tm.staff_id = ms.staff_id 
                    AND tm.allow_id = ? 
                    AND tm.type = 1
                    AND tm.period = ?
                WHERE ms.period = ?
                  AND tm.allow > 0
                ORDER BY ms.NAME
            ');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
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
                // Get active period (most recent period)
                $stmt = $this->prepareStatement('SELECT periodId FROM payperiods ORDER BY periodId DESC LIMIT 1');
                if (!$stmt) {
                    apiError('INTERNAL_ERROR', 'Database error', null, 500);
                }
                $stmt->execute();
                $periodId = $stmt->fetchColumn();
                
                if (!$periodId) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'No active period found');
                    apiError('NOT_FOUND', 'No active period found', null, 404);
                }
            }
            
            // Get period details
            $periodStmt = $this->prepareStatement('
                SELECT periodId as id, description, periodYear as year
                FROM payperiods WHERE periodId = ? LIMIT 1
            ');
            if (!$periodStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $periodStmt->execute([$periodId]);
            $period = $periodStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$period) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'INVALID_PERIOD', 'Invalid period');
                apiError('INVALID_PERIOD', 'Invalid or inactive period', null, 404);
            }
            
            // Get deduction name
            $nameStmt = $this->prepareStatement('SELECT ed FROM tbl_earning_deduction WHERE ed_id = ? LIMIT 1');
            if (!$nameStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $nameStmt->execute([$deductionId]);
            $deductionName = $nameStmt->fetchColumn();
            
            if (!$deductionName) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'INVALID_ED_ID', 'Invalid deduction ID');
                apiError('INVALID_ED_ID', 'Invalid deduction ID', null, 404);
            }
            
            // Get deduction data for all staff (exclude zero amounts)
            $stmt = $this->prepareStatement('
                SELECT 
                    ms.staff_id,
                    ms.NAME as name,
                    tm.deduc as amount
                FROM master_staff ms
                INNER JOIN tbl_master tm ON tm.staff_id = ms.staff_id 
                    AND tm.allow_id = ? 
                    AND tm.type = 2
                    AND tm.period = ?
                WHERE ms.period = ?
                  AND tm.deduc > 0
                ORDER BY ms.NAME
            ');
            
            if (!$stmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
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
    
    /**
     * Get staff deduction and net pay
     * Returns staff_id, net_pay, and deduction_amount
     */
    private function getStaffDeduction($staffId, $deductionId) {
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
                // Get active period (most recent period)
                $stmt = $this->prepareStatement('SELECT periodId FROM payperiods ORDER BY periodId DESC LIMIT 1');
                if (!$stmt) {
                    apiError('INTERNAL_ERROR', 'Database error', null, 500);
                }
                $stmt->execute();
                $periodId = $stmt->fetchColumn();
                
                if (!$periodId) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'No active period found');
                    apiError('NOT_FOUND', 'No active period found', null, 404);
                }
            }
            
            // Verify staff exists in this period
            $staffStmt = $this->prepareStatement('
                SELECT staff_id, NAME
                FROM master_staff 
                WHERE staff_id = ? AND period = ?
                LIMIT 1
            ');
            if (!$staffStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $staffStmt->execute([$staffId, $periodId]);
            $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$staff) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'Staff not found in this period');
                apiError('NOT_FOUND', 'Staff not found in this period', null, 404);
            }
            
            // Get deduction amount for this staff and deduction ID
            $deductionStmt = $this->prepareStatement('
                SELECT deduc as amount
                FROM tbl_master
                WHERE staff_id = ? 
                  AND allow_id = ? 
                  AND type = 2 
                  AND period = ?
                LIMIT 1
            ');
            if (!$deductionStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $deductionStmt->execute([$staffId, $deductionId, $periodId]);
            $deductionRow = $deductionStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$deductionRow) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'Deduction not found for this staff');
                apiError('NOT_FOUND', 'Deduction not found for this staff', null, 404);
            }
            
            $deductionAmount = floatval($deductionRow['amount']);
            
            // Calculate net pay
            // 1. Get consolidated allowance (allow_id = 1, type = 1)
            $consolidatedStmt = $this->prepareStatement('
                SELECT allow as amount
                FROM tbl_master
                WHERE staff_id = ? 
                  AND allow_id = 1 
                  AND type = 1 
                  AND period = ?
                LIMIT 1
            ');
            if (!$consolidatedStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $consolidatedStmt->execute([$staffId, $periodId]);
            $consolidatedRow = $consolidatedStmt->fetch(PDO::FETCH_ASSOC);
            $consolidated = $consolidatedRow ? floatval($consolidatedRow['amount']) : 0;
            
            // 2. Get total of all other allowances (type = 1, allow_id != 1)
            $allowancesStmt = $this->prepareStatement('
                SELECT IFNULL(SUM(allow), 0) as total
                FROM tbl_master
                WHERE staff_id = ? 
                  AND type = 1 
                  AND allow_id != 1 
                  AND period = ?
            ');
            if (!$allowancesStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $allowancesStmt->execute([$staffId, $periodId]);
            $totalAllowances = floatval($allowancesStmt->fetchColumn());
            
            // 3. Get total of all deductions (type = 2)
            $deductionsStmt = $this->prepareStatement('
                SELECT IFNULL(SUM(deduc), 0) as total
                FROM tbl_master
                WHERE staff_id = ? 
                  AND type = 2 
                  AND period = ?
            ');
            if (!$deductionsStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            $deductionsStmt->execute([$staffId, $periodId]);
            $totalDeductions = floatval($deductionsStmt->fetchColumn());
            
            // Calculate net pay: Consolidated + Allowances - Deductions
            $netPay = $consolidated + $totalAllowances - $totalDeductions;
            
            // Prepare response data
            $data = [
                'staff_id' => intval($staffId),
                'net_pay' => round($netPay, 2),
                'deduction_amount' => round($deductionAmount, 2)
            ];
            
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
            
            // Return response
            apiSuccess($data);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to fetch staff deduction', ['error' => $e->getMessage()]);
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', 'Database error');
            apiError('INTERNAL_ERROR', 'Failed to fetch staff deduction', null, 500);
        }
    }
    
    /**
     * Edit deduction in deductiontable
     * PUT /api/v1/payroll/edit-deduction
     * Body: { "ded_id": 1, "value": 5000, "percentage": 5.5 }
     */
    private function editDeduction() {
        try {
            // Get request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 400, 'INVALID_REQUEST', 'Invalid JSON body');
                apiError('INVALID_REQUEST', 'Invalid or missing JSON body', null, 400);
            }
            
            // Validate ded_id
            $dedId = isset($input['ded_id']) ? intval($input['ded_id']) : null;
            if (!$dedId || $dedId <= 0) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 400, 'MISSING_PARAMETER', 'ded_id is required and must be positive');
                apiError('MISSING_PARAMETER', 'ded_id is required and must be a positive integer', null, 400);
            }
            
            // Validate at least one field to update
            $value = isset($input['value']) ? $input['value'] : null;
            $percentage = isset($input['percentage']) ? $input['percentage'] : null;
            
            if ($value === null && $percentage === null) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 400, 'MISSING_PARAMETER', 'At least one of value or percentage is required');
                apiError('MISSING_PARAMETER', 'At least one of value or percentage is required', null, 400);
            }
            
            // Validate value if provided
            if ($value !== null) {
                $value = floatval($value);
                if ($value < 0) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 400, 'INVALID_VALUE', 'Value must be non-negative');
                    apiError('INVALID_VALUE', 'Value must be a non-negative number', null, 400);
                }
            }
            
            // Validate percentage if provided
            if ($percentage !== null) {
                $percentage = floatval($percentage);
                if ($percentage < 0 || $percentage > 100) {
                    $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                        $_SERVER['REQUEST_URI'], 400, 'INVALID_VALUE', 'Percentage must be between 0 and 100');
                    apiError('INVALID_VALUE', 'Percentage must be between 0 and 100', null, 400);
                }
            }
            
            // Check if deduction exists and get allowcode
            $checkStmt = $this->prepareStatement('
                SELECT allowcode 
                FROM deductiontable 
                WHERE ded_id = ?
                LIMIT 1
            ');
            
            if (!$checkStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $checkStmt->execute([$dedId]);
            $deduction = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$deduction) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 404, 'NOT_FOUND', 'Deduction not found');
                apiError('NOT_FOUND', 'Deduction with ded_id ' . $dedId . ' not found', null, 404);
            }
            
            $allowcode = intval($deduction['allowcode']);
            
            // Verify API key has access to this deduction
            $accessCheck = $this->keyValidator->checkResourceAccess($this->keyData, $allowcode, 2);
            
            if (!$accessCheck['valid']) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 403, $accessCheck['error'], $accessCheck['message']);
                apiError($accessCheck['error'], $accessCheck['message'], null, 403);
            }
            
            // Build update query dynamically based on provided fields
            $updateFields = [];
            $updateParams = [];
            
            if ($value !== null) {
                $updateFields[] = '`value` = :value';
                $updateParams[':value'] = $value;
            }
            
            if ($percentage !== null) {
                $updateFields[] = 'percentage = :percentage';
                $updateParams[':percentage'] = $percentage;
            }
            
            if (empty($updateFields)) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 400, 'INVALID_REQUEST', 'No valid fields to update');
                apiError('INVALID_REQUEST', 'No valid fields to update', null, 400);
            }
            
            $updateParams[':ded_id'] = $dedId;
            
            $updateSql = 'UPDATE deductiontable SET ' . implode(', ', $updateFields) . ' WHERE ded_id = :ded_id';
            
            $updateStmt = $this->prepareStatement($updateSql);
            
            if (!$updateStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            foreach ($updateParams as $key => $val) {
                $updateStmt->bindValue($key, $val, is_float($val) ? PDO::PARAM_STR : PDO::PARAM_INT);
            }
            
            $updateStmt->execute();
            
            if ($updateStmt->rowCount() === 0) {
                $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                    $_SERVER['REQUEST_URI'], 400, 'UPDATE_FAILED', 'No rows updated');
                apiError('UPDATE_FAILED', 'No rows updated. Deduction may not exist or values unchanged.', null, 400);
            }
            
            // Get updated deduction data
            $getStmt = $this->prepareStatement('
                SELECT 
                    ded_id,
                    allowcode,
                    grade,
                    step,
                    `value`,
                    category,
                    ratetype,
                    percentage
                FROM deductiontable
                WHERE ded_id = ?
                LIMIT 1
            ');
            
            if (!$getStmt) {
                apiError('INTERNAL_ERROR', 'Database error', null, 500);
            }
            
            $getStmt->execute([$dedId]);
            $updatedDeduction = $getStmt->fetch(PDO::FETCH_ASSOC);
            
            // Log request
            $this->logger->logRequest(
                $this->keyData['org_id'],
                $this->keyData['api_key'],
                $_SERVER['REQUEST_URI'],
                200,
                null,
                null,
                null,
                1
            );
            
            // Return response
            $data = [
                'ded_id' => intval($updatedDeduction['ded_id']),
                'allowcode' => intval($updatedDeduction['allowcode']),
                'grade' => $updatedDeduction['grade'],
                'step' => $updatedDeduction['step'],
                'value' => $updatedDeduction['value'] !== null ? floatval($updatedDeduction['value']) : null,
                'category' => $updatedDeduction['category'],
                'ratetype' => $updatedDeduction['ratetype'] !== null ? intval($updatedDeduction['ratetype']) : null,
                'percentage' => $updatedDeduction['percentage'] !== null ? floatval($updatedDeduction['percentage']) : null
            ];
            
            apiSuccess($data, ['message' => 'Deduction updated successfully']);
            
        } catch (PDOException $e) {
            logApiActivity('error', 'Failed to update deduction', ['error' => $e->getMessage()]);
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', 'Database error');
            apiError('INTERNAL_ERROR', 'Failed to update deduction', null, 500);
        } catch (Exception $e) {
            $this->logger->logRequest($this->keyData['org_id'], $this->keyData['api_key'], 
                $_SERVER['REQUEST_URI'], 500, 'INTERNAL_ERROR', $e->getMessage());
            apiError('INTERNAL_ERROR', $e->getMessage(), null, 500);
        }
    }
}

// Handle request
$api = new PayrollAPI();
$api->handle();