<?php
/**
 * OOUTH Salary API Client - Usage Example
 * This file demonstrates how to use the API client
 */

require_once(__DIR__ . '/OOUTHSalaryAPIClient.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>OOUTH Salary API Client - Example Usage</h2>\n\n";

// Initialize the client
$client = new OOUTHSalaryAPIClient();

echo "<h3>Step 1: Authenticate</h3>\n";
if ($client->authenticate()) {
    echo "✅ <strong>Authentication successful!</strong><br>\n\n";
    
    // Example 1: Get Active Period
    echo "<h3>Step 2: Get Active Period</h3>\n";
    $activePeriod = $client->getActivePeriod();
    
    if ($activePeriod && $activePeriod['success']) {
        $period = $activePeriod['data']['period'];
        echo "Active Period: {$period['description']} {$period['year']} (ID: {$period['period_id']})<br>\n";
        $periodId = $period['period_id'];
        
        // Example 2: Get Resource Data (Deduction/Allowance)
        echo "\n<h3>Step 3: Get Resource Data</h3>\n";
        $resourceData = $client->getResourceData($periodId);
        
        if ($resourceData && $resourceData['success']) {
            $metadata = $resourceData['metadata'];
            $data = $resourceData['data'];
            
            echo "Resource: {$metadata['deduction_name'] ?? $metadata['allowance_name']}<br>\n";
            echo "Period: {$metadata['period']['description']} {$metadata['period']['year']}<br>\n";
            echo "Total Records: {$metadata['total_records']}<br>\n";
            echo "Total Amount: ₦" . number_format($metadata['total_amount']) . "<br>\n\n";
            
            echo "<h4>Sample Data (First 5 records):</h4>\n";
            echo "<table border='1' cellpadding='5'>\n";
            echo "<tr><th>Staff ID</th><th>Name</th><th>Amount</th></tr>\n";
            
            $count = 0;
            foreach ($data as $record) {
                if ($count >= 5) break;
                echo "<tr>";
                echo "<td>{$record['staff_id']}</td>";
                echo "<td>{$record['name']}</td>";
                echo "<td>₦" . number_format($record['amount']) . "</td>";
                echo "</tr>\n";
                $count++;
            }
            
            echo "</table>\n\n";
            
            if (count($data) > 5) {
                echo "<p><em>... and " . (count($data) - 5) . " more records</em></p>\n";
            }
            
        } else {
            echo "❌ Failed to get resource data<br>\n";
            echo "Error: " . json_encode($resourceData) . "<br>\n";
        }
        
        // Example 3: Get All Periods
        echo "\n<h3>Step 4: Get All Periods</h3>\n";
        $periods = $client->getPeriods(1, 10);
        
        if ($periods && $periods['success']) {
            echo "Total Periods: {$periods['pagination']['total_records']}<br>\n";
            echo "<ul>\n";
            foreach ($periods['data'] as $p) {
                $active = $p['is_active'] ? '✓ Active' : '';
                echo "<li>{$p['description']} {$p['year']} (ID: {$p['period_id']}) {$active}</li>\n";
            }
            echo "</ul>\n";
        }
        
    } else {
        echo "❌ Failed to get active period<br>\n";
    }
    
} else {
    echo "❌ <strong>Authentication failed!</strong><br>\n";
    echo "Check your API credentials in config/api_config.php<br>\n";
    echo "Error logs may contain more details.<br>\n";
}

echo "\n<hr>\n";
echo "<p><strong>Next Steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>Check error logs if authentication failed</li>\n";
echo "<li>Verify API key and secret in config/api_config.php</li>\n";
echo "<li>Use the client methods in your application</li>\n";
echo "</ul>\n";

/*
 * Available Client Methods:
 * 
 * Authentication:
 * - $client->authenticate()                        // Returns bool
 * 
 * Periods:
 * - $client->getPeriods($page, $limit)            // Get all periods (paginated)
 * - $client->getActivePeriod()                    // Get current active period
 * - $client->getPeriod($periodId)                 // Get specific period
 * 
 * Deductions:
 * - $client->getDeductions($deductionId, $periodId)  // Get deduction data
 * 
 * Allowances:
 * - $client->getAllowances($allowanceId, $periodId)  // Get allowance data
 * 
 * Resource (Automatic based on config):
 * - $client->getResourceData($periodId)           // Get configured resource data
 * 
 * All methods return array with structure:
 * [
 *   'success' => true/false,
 *   'data' => [...],
 *   'error' => [...] (if failed)
 * ]
 */

