<?php
// api/payroll/pension_report.php

// Clear any previous output and start fresh
ob_clean();

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=UTF-8');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Include required files
    require_once '../../config/Database.php';
    require_once '../../utils/JWTHandler.php';

    // Get JWT token from header
    $headers = apache_request_headers();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

    if (!$auth_header || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        throw new Exception('No token provided or invalid format', 401);
    }

    $token = $matches[1];

    // Verify token
    $jwt = new JWTHandler();
    $token = $jwt->validateToken($token);

    if (!$token) {
        throw new Exception('Invalid token', 401);
    }

    // Get parameters from query params
    $userId = isset($_GET['userId']) ? filter_var($_GET['userId'], FILTER_VALIDATE_INT) : null;
    $periodFrom = isset($_GET['periodFrom']) ? filter_var($_GET['periodFrom'], FILTER_VALIDATE_INT) : null;
    $periodTo = isset($_GET['periodTo']) ? filter_var($_GET['periodTo'], FILTER_VALIDATE_INT) : null;

    if (!$userId) {
        throw new Exception('User ID is required', 400);
    }

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get employee details
    $employeeQuery = "SELECT 
        master_staff.NAME, 
        master_staff.staff_id,
        employee.PFACODE,
        employee.PFAACCTNO
    FROM master_staff
    LEFT JOIN employee ON master_staff.staff_id = employee.staff_id
    WHERE master_staff.staff_id = :staff_id
    LIMIT 1";

    $empStmt = $db->prepare($employeeQuery);
    $empStmt->bindParam(':staff_id', $userId);
    $empStmt->execute();

    $employee = $empStmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        throw new Exception('Employee not found', 404);
    }

    // Get PFA name if PFACODE exists
    $pfaName = '';
    if (!empty($employee['PFACODE'])) {
        $pfaQuery = "SELECT PFANAME FROM tbl_pfa WHERE PFACODE = :pfacode LIMIT 1";
        $pfaStmt = $db->prepare($pfaQuery);
        $pfaStmt->bindParam(':pfacode', $employee['PFACODE']);
        $pfaStmt->execute();
        $pfaResult = $pfaStmt->fetch(PDO::FETCH_ASSOC);
        if ($pfaResult) {
            $pfaName = $pfaResult['PFANAME'];
        }
    }

    // Build pension query
    $pensionQuery = "SELECT 
        tbl_master.deduc as pension_amount,
        tbl_master.period,
        payperiods.description,
        payperiods.periodYear
    FROM tbl_master 
    INNER JOIN payperiods ON tbl_master.period = payperiods.periodId
    WHERE tbl_master.staff_id = :staff_id 
        AND tbl_master.allow_id = 50
        AND tbl_master.deduc IS NOT NULL
        AND tbl_master.deduc > 0
    ";

    $params = [':staff_id' => $userId];

    // Add period filters if provided
    if ($periodFrom !== null && $periodTo !== null) {
        // Ensure periodFrom <= periodTo
        if ($periodFrom > $periodTo) {
            $temp = $periodFrom;
            $periodFrom = $periodTo;
            $periodTo = $temp;
        }
        $pensionQuery .= " AND tbl_master.period >= :period_from AND tbl_master.period <= :period_to";
        $params[':period_from'] = $periodFrom;
        $params[':period_to'] = $periodTo;
    }

    $pensionQuery .= " ORDER BY tbl_master.period ASC";

    $pensionStmt = $db->prepare($pensionQuery);
    $pensionStmt->execute($params);

    $pensionData = [];
    $totalPension = 0;

    while ($row = $pensionStmt->fetch(PDO::FETCH_ASSOC)) {
        $pensionAmount = floatval($row['pension_amount']);
        
        // Only add if pension amount is greater than 0
        if ($pensionAmount > 0) {
            $totalPension += $pensionAmount;
            
            $pensionData[] = [
                'period' => intval($row['period']),
                'periodDescription' => $row['description'],
                'periodYear' => intval($row['periodYear']),
                'pensionAmount' => $pensionAmount,
                'periodText' => trim($row['description'] . ' ' . $row['periodYear'])
            ];
        }
    }
    
    // Log for debugging
    error_log("Pension report for staff_id $userId: Found " . count($pensionData) . " pension records");

    // Prepare response
    $response = [
        'success' => true,
        'data' => [
            'employeeInfo' => [
                'name' => $employee['NAME'],
                'staffId' => $employee['staff_id'],
                'pfaCode' => $employee['PFACODE'] ?? '',
                'pfaAccountNo' => $employee['PFAACCTNO'] ?? '',
                'pfaName' => $pfaName
            ],
            'pensionData' => $pensionData,
            'summary' => [
                'totalContributions' => count($pensionData),
                'totalAmount' => $totalPension,
                'averageAmount' => count($pensionData) > 0 ? $totalPension / count($pensionData) : 0
            ]
        ]
    ];

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Pension report error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    $status_code = $e->getCode();
    if (!is_int($status_code) || $status_code < 100 || $status_code > 599) {
        $status_code = 400;
    }

    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}