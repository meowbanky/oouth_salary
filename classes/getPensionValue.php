<?php
//getPensionValue.php - Optimized pension calculation
require_once('../Connections/paymaster.php');

try {
    // Debug: Check what's being received
    echo "DEBUG: POST data: " . print_r($_POST, true) . "\n";
    
    if (!isset($_POST['grade_level']) || !isset($_POST['step'])) {
        echo "DEBUG: Missing grade_level or step\n";
        echo '0';
        exit;
    }

    // Sanitize inputs but preserve original data types
    $grade_level = trim($_POST['grade_level']);
    $step = trim($_POST['step']);
    
    echo "DEBUG: Grade level after trim: '$grade_level'\n";
    echo "DEBUG: Step after trim: '$step'\n";

    if (empty($grade_level) || empty($step)) {
        echo "DEBUG: Empty grade_level or step after trim\n";
        echo '0';
        exit;
    }

	
    // Use prepared statements for security with string parameters
    $sql1 = "SELECT value FROM allowancetable WHERE allowcode = 1 AND grade = ? AND step = ?";
    $stmt = $salary->prepare($sql1);
    $stmt->bind_param("ss", $grade_level, $step);
    $stmt->execute();
    $result_consolidated = $stmt->get_result();

	// Debug output - visible text
	echo "DEBUG: SQL1: " . $sql1 . "\n";
	echo "DEBUG: Grade: " . $grade_level . ", Step: " . $step . "\n";
	echo "DEBUG: Num rows: " . $result_consolidated->num_rows . "\n";
	echo "DEBUG: Result Consolidated: " . print_r($result_consolidated, true) . "\n";
    
    if ($result_consolidated->num_rows === 0) {
        echo "DEBUG: No allowance found for grade '$grade_level' and step '$step'\n";
        echo '0';
        exit;
    }
    
    $row_consolidated = $result_consolidated->fetch_assoc();
	
	error_log("Row Consolidated: " . print_r($row_consolidated, true));

    $sql2 = "SELECT rate FROM pension";
    $stmt2 = $salary->prepare($sql2);
    $stmt2->execute();
    $result_pensionRate = $stmt2->get_result();
    
    // Debug output for pension query
    echo "DEBUG: SQL2: " . $sql2 . "\n";
    echo "DEBUG: Pension Num rows: " . $result_pensionRate->num_rows . "\n";
    
    if ($result_pensionRate->num_rows === 0) {
        echo "DEBUG: No pension rate found in pension table\n";
        echo '0';
        exit;
    }
    
    $row_pensionRate = $result_pensionRate->fetch_assoc();

    echo "DEBUG: Row Pension Rate: " . print_r($row_pensionRate, true) . "\n";
    echo "DEBUG: Allowance Value: " . $row_consolidated['value'] . "\n";
    echo "DEBUG: Pension Rate: " . $row_pensionRate['rate'] . "\n";

    $pension_value = ceil($row_consolidated['value'] * $row_pensionRate['rate']);
    
    echo "DEBUG: Calculated Pension Value: " . $pension_value . "\n";
    
    // Return simple numeric value as expected by frontend
    echo $pension_value;

} catch (Exception $e) {
    echo '0';
} catch (mysqli_sql_exception $e) {
    echo '0';
}