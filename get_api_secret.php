<?php
/**
 * Get API Secret for Signature Generation
 * This tool helps you retrieve API secrets from the database
 */

session_start();
require_once 'Connections/paymaster.php';

// Check if user is logged in (basic security)
if (!isset($_SESSION['MM_Username'])) {
    die("Access denied. Please login first.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get API Secret - OOUTH Salary</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .search-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .search-box label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .search-box input, .search-box select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .result-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #667eea;
        }
        
        .result-item {
            margin-bottom: 15px;
        }
        
        .result-item label {
            display: block;
            font-weight: 600;
            color: #666;
            margin-bottom: 5px;
            font-size: 12px;
            text-transform: uppercase;
        }
        
        .result-item .value {
            background: white;
            padding: 12px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            word-break: break-all;
            border: 1px solid #e0e0e0;
            position: relative;
        }
        
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .copy-btn:hover {
            background: #5568d3;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border-left: 4px solid #1976d2;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #388e3c;
            border-left: 4px solid #388e3c;
        }
        
        .alert-warning {
            background: #fff3e0;
            color: #f57c00;
            border-left: 4px solid #f57c00;
        }
        
        .signature-generator {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .signature-generator h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .test-section {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        code {
            background: #2d3748;
            color: #68d391;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Get API Secret</h1>
            <p>Retrieve API secrets for signature generation</p>
        </div>
        
        <div class="content">
            <div class="alert alert-warning">
                <strong>⚠️ Security Notice:</strong> API secrets are highly sensitive. Never share them or commit them to version control.
            </div>
            
            <div class="search-box">
                <label>Select API Key:</label>
                <select id="apiKeySelect" onchange="getSecret()">
                    <option value="">-- Select an API Key --</option>
                    <?php
                    try {
                        $stmt = $conn->prepare("
                            SELECT 
                                ak.api_key,
                                ak.api_secret,
                                ao.org_name,
                                ak.ed_name,
                                CASE 
                                    WHEN ak.ed_type = 1 THEN 'Allowance'
                                    WHEN ak.ed_type = 2 THEN 'Deduction'
                                    ELSE 'Unknown'
                                END as type_name,
                                ak.is_active
                            FROM api_keys ak
                            JOIN api_organizations ao ON ak.org_id = ao.org_id
                            ORDER BY ao.org_name, ak.ed_name
                        ");
                        $stmt->execute();
                        $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($keys as $key) {
                            $status = $key['is_active'] ? '✅' : '❌';
                            echo "<option value='" . htmlspecialchars(json_encode($key)) . "'>";
                            echo "$status {$key['org_name']} - {$key['ed_name']} ({$key['type_name']})";
                            echo "</option>";
                        }
                    } catch (PDOException $e) {
                        echo "<option value=''>Error loading keys</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div id="resultSection" style="display: none;">
                <div class="result-box">
                    <div class="result-item">
                        <label>Organization</label>
                        <div class="value" id="orgName"></div>
                    </div>
                    
                    <div class="result-item">
                        <label>Resource</label>
                        <div class="value" id="resourceName"></div>
                    </div>
                    
                    <div class="result-item">
                        <label>API Key</label>
                        <div class="value" style="position: relative;">
                            <span id="apiKey"></span>
                            <button class="copy-btn" onclick="copyToClipboard('apiKey')">Copy</button>
                        </div>
                    </div>
                    
                    <div class="result-item">
                        <label>API Secret</label>
                        <div class="value" style="position: relative;">
                            <span id="apiSecret"></span>
                            <button class="copy-btn" onclick="copyToClipboard('apiSecret')">Copy</button>
                        </div>
                    </div>
                </div>
                
                <div class="signature-generator">
                    <h3>📝 Generate Test Signature</h3>
                    
                    <div class="test-section">
                        <label>Timestamp (Unix):</label>
                        <input type="text" id="timestamp" readonly style="margin-bottom: 10px;">
                        <button class="btn" onclick="generateSignature()" style="margin-bottom: 15px;">Generate Signature</button>
                        
                        <div id="signatureResult" style="display: none;">
                            <div class="result-item">
                                <label>Signature String</label>
                                <div class="value" id="signatureString"></div>
                            </div>
                            
                            <div class="result-item">
                                <label>HMAC-SHA256 Signature</label>
                                <div class="value" style="position: relative;">
                                    <span id="signature"></span>
                                    <button class="copy-btn" onclick="copyToClipboard('signature')">Copy</button>
                                </div>
                            </div>
                            
                            <div class="alert alert-success" style="margin-top: 15px;">
                                <strong>✅ Test Request:</strong>
                                <pre id="curlCommand" style="white-space: pre-wrap; margin-top: 10px; font-family: monospace; font-size: 12px;"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentKey = null;
        
        // Update timestamp every second
        setInterval(() => {
            document.getElementById('timestamp').value = Math.floor(Date.now() / 1000);
        }, 1000);
        
        function getSecret() {
            const select = document.getElementById('apiKeySelect');
            const selectedValue = select.value;
            
            if (!selectedValue) {
                document.getElementById('resultSection').style.display = 'none';
                return;
            }
            
            currentKey = JSON.parse(selectedValue);
            
            document.getElementById('orgName').textContent = currentKey.org_name;
            document.getElementById('resourceName').textContent = `${currentKey.ed_name} (${currentKey.type_name})`;
            document.getElementById('apiKey').textContent = currentKey.api_key;
            document.getElementById('apiSecret').textContent = currentKey.api_secret;
            document.getElementById('timestamp').value = Math.floor(Date.now() / 1000);
            
            document.getElementById('resultSection').style.display = 'block';
            document.getElementById('signatureResult').style.display = 'none';
        }
        
        async function generateSignature() {
            if (!currentKey) return;
            
            const apiKey = currentKey.api_key;
            const apiSecret = currentKey.api_secret;
            const timestamp = document.getElementById('timestamp').value;
            
            // Build signature string
            const signatureString = apiKey + timestamp;
            document.getElementById('signatureString').textContent = signatureString;
            
            // Calculate HMAC-SHA256
            const encoder = new TextEncoder();
            const keyData = encoder.encode(apiSecret);
            const messageData = encoder.encode(signatureString);
            
            const cryptoKey = await crypto.subtle.importKey(
                'raw',
                keyData,
                { name: 'HMAC', hash: 'SHA-256' },
                false,
                ['sign']
            );
            
            const signature = await crypto.subtle.sign('HMAC', cryptoKey, messageData);
            const signatureHex = Array.from(new Uint8Array(signature))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
            
            document.getElementById('signature').textContent = signatureHex;
            
            // Generate cURL command
            const curlCommand = `curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \\
  -H "Content-Type: application/json" \\
  -H "X-API-Key: ${apiKey}" \\
  -H "X-Timestamp: ${timestamp}" \\
  -H "X-Signature: ${signatureHex}"`;
            
            document.getElementById('curlCommand').textContent = curlCommand;
            document.getElementById('signatureResult').style.display = 'block';
        }
        
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                const btn = element.parentElement.querySelector('.copy-btn');
                const originalText = btn.textContent;
                btn.textContent = '✓ Copied!';
                btn.style.background = '#4caf50';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '#667eea';
                }, 2000);
            });
        }
        
        // Initialize timestamp
        document.getElementById('timestamp').value = Math.floor(Date.now() / 1000);
    </script>
</body>
</html>

