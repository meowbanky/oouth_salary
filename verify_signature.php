<?php
/**
 * Signature Verification Tool
 * This helps verify if a signature calculation is correct
 */

require_once 'Connections/paymaster.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Signature Verification Tool</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .mono {
            font-family: 'Courier New', monospace;
            background: #2d3748;
            color: #68d391;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Signature Verification Tool</h1>
        
        <?php
        // Get API key from query or use default
        $apiKey = $_GET['api_key'] ?? 'oouth_005_deduc_48_ed7dee3ccb995727';
        $testTimestamp = $_GET['timestamp'] ?? time();
        
        try {
            // Fetch API key details from database
            $stmt = $conn->prepare("
                SELECT api_key, api_secret, org_id, ed_id, ed_name, is_active
                FROM api_keys
                WHERE api_key = ?
            ");
            $stmt->execute([$apiKey]);
            $keyData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$keyData) {
                echo '<div class="section error">';
                echo '<strong>❌ API Key Not Found</strong><br>';
                echo "The API key <code>{$apiKey}</code> does not exist in the database.";
                echo '</div>';
                exit;
            }
            
            // Display key information
            echo '<div class="section">';
            echo '<h3>API Key Information</h3>';
            echo '<table>';
            echo '<tr><th>Field</th><th>Value</th></tr>';
            echo '<tr><td>API Key</td><td><code>' . htmlspecialchars($keyData['api_key']) . '</code></td></tr>';
            echo '<tr><td>Organization ID</td><td>' . $keyData['org_id'] . '</td></tr>';
            echo '<tr><td>Resource</td><td>' . htmlspecialchars($keyData['ed_name']) . ' (ID: ' . $keyData['ed_id'] . ')</td></tr>';
            echo '<tr><td>Status</td><td>' . ($keyData['is_active'] ? '✅ Active' : '❌ Inactive') . '</td></tr>';
            echo '<tr><td>Secret Length</td><td>' . strlen($keyData['api_secret']) . ' characters</td></tr>';
            echo '</table>';
            echo '</div>';
            
            // Display API secret
            echo '<div class="section warning">';
            echo '<h3>⚠️ API Secret (Keep Secure!)</h3>';
            echo '<div class="mono">' . htmlspecialchars($keyData['api_secret']) . '</div>';
            echo '<p><small>This is the secret that should be in your config file.</small></p>';
            echo '</div>';
            
            // Generate correct signature
            $apiSecret = $keyData['api_secret'];
            $signatureString = $apiKey . $testTimestamp;
            $correctSignature = hash_hmac('sha256', $signatureString, $apiSecret);
            
            echo '<div class="section success">';
            echo '<h3>✅ Correct Signature Calculation</h3>';
            echo '<table>';
            echo '<tr><th>Step</th><th>Value</th></tr>';
            echo '<tr><td>1. Timestamp</td><td><code>' . $testTimestamp . '</code></td></tr>';
            echo '<tr><td>2. Signature String</td><td><div class="mono">' . htmlspecialchars($signatureString) . '</div></td></tr>';
            echo '<tr><td>3. HMAC-SHA256</td><td><div class="mono">' . $correctSignature . '</div></td></tr>';
            echo '</table>';
            echo '</div>';
            
            // Test with client's signature if provided
            if (isset($_GET['client_signature'])) {
                $clientSignature = $_GET['client_signature'];
                
                echo '<div class="section ' . ($clientSignature === $correctSignature ? 'success' : 'error') . '">';
                echo '<h3>Client Signature Comparison</h3>';
                echo '<table>';
                echo '<tr><td><strong>Client Signature:</strong></td><td><div class="mono">' . htmlspecialchars($clientSignature) . '</div></td></tr>';
                echo '<tr><td><strong>Expected Signature:</strong></td><td><div class="mono">' . $correctSignature . '</div></td></tr>';
                echo '<tr><td><strong>Match:</strong></td><td>' . ($clientSignature === $correctSignature ? '✅ YES' : '❌ NO') . '</td></tr>';
                echo '</table>';
                
                if ($clientSignature !== $correctSignature) {
                    echo '<p><strong>Issue:</strong> The client is using a different API secret than what\'s stored in the database.</p>';
                    echo '<p><strong>Solution:</strong> Update the client config with the correct secret shown above.</p>';
                }
                
                echo '</div>';
            }
            
            // Show cURL test command
            echo '<div class="section">';
            echo '<h3>🧪 Test with cURL</h3>';
            echo '<pre style="background: #2d3748; color: #68d391; padding: 15px; border-radius: 4px; overflow-x: auto;">curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \\
  -H "Content-Type: application/json" \\
  -H "X-API-Key: ' . $apiKey . '" \\
  -H "X-Timestamp: ' . $testTimestamp . '" \\
  -H "X-Signature: ' . $correctSignature . '"</pre>';
            echo '</div>';
            
            // PHP code example
            echo '<div class="section">';
            echo '<h3>📝 PHP Code Example</h3>';
            echo '<pre style="background: #2d3748; color: #68d391; padding: 15px; border-radius: 4px; overflow-x: auto;">$apiKey = \'' . $apiKey . '\';
$apiSecret = \'' . $apiSecret . '\';
$timestamp = time();

$signatureString = $apiKey . $timestamp;
$signature = hash_hmac(\'sha256\', $signatureString, $apiSecret);

// Use $signature in your request headers
// X-Timestamp: $timestamp
// X-Signature: $signature</pre>';
            echo '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="section error">';
            echo '<strong>❌ Database Error</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
        <div class="section">
            <h3>🔧 Troubleshooting Tips</h3>
            <ul>
                <li><strong>INVALID_SIGNATURE:</strong> The API secret in your config doesn't match the database. Copy the secret shown above.</li>
                <li><strong>INVALID_TIMESTAMP:</strong> Your system clock is off by more than 5 minutes. Sync your clock.</li>
                <li><strong>Signature String:</strong> Must be exactly <code>api_key + timestamp</code> with NO spaces or extra characters.</li>
                <li><strong>Algorithm:</strong> Must be HMAC-SHA256 (not SHA256, not MD5, not SHA1).</li>
            </ul>
        </div>
        
        <div class="section">
            <p><strong>Usage:</strong></p>
            <p>Add <code>?api_key=YOUR_KEY</code> to test different keys</p>
            <p>Add <code>&timestamp=1234567890</code> to use a specific timestamp</p>
            <p>Add <code>&client_signature=abc123...</code> to compare signatures</p>
        </div>
    </div>
</body>
</html>

