<?php
/**
 * API Test Script
 * Tests API key authentication and data retrieval
 */

// Configuration
$API_BASE_URL = 'https://oouthsalary.com.ng/api/v1';
$API_KEY = 'YOUR_API_KEY_HERE'; // Replace with your actual API key
$API_SECRET = 'YOUR_API_SECRET_HERE'; // Replace with your actual API secret

// Test mode (set REQUIRE_SIGNATURE to false in api/config/api_config.php for testing)
$USE_SIGNATURE = false;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Test Script - OOUTH Salary</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .test-result {
            max-height: 400px;
            overflow-y: auto;
        }
        pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
        }
    </style>
</head>
<body class="bg-gray-100 p-8">
    
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-vial mr-2 text-blue-600"></i>API Test Script
            </h1>
            <p class="text-gray-600">Test your OOUTH Salary API key and secret</p>
        </div>
        
        <!-- Configuration Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Configuration</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Base URL:</label>
                    <input type="text" id="apiBaseUrl" value="<?php echo $API_BASE_URL; ?>" 
                        class="w-full border rounded-lg px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Use Signature:</label>
                    <select id="useSignature" class="w-full border rounded-lg px-3 py-2">
                        <option value="false" selected>No (Testing Mode)</option>
                        <option value="true">Yes (Production Mode)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Key:</label>
                    <input type="text" id="apiKey" value="<?php echo $API_KEY; ?>" 
                        class="w-full border rounded-lg px-3 py-2 font-mono text-sm" 
                        placeholder="oouth_001_allow_5_...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">API Secret:</label>
                    <input type="text" id="apiSecret" value="<?php echo $API_SECRET; ?>" 
                        class="w-full border rounded-lg px-3 py-2 font-mono text-sm"
                        placeholder="64 character secret">
                </div>
            </div>
        </div>
        
        <!-- Test Buttons -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Tests</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="testAuthentication()" 
                    class="bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 flex items-center justify-center">
                    <i class="fas fa-key mr-2"></i>1. Test Authentication
                </button>
                <button onclick="testGetPeriods()" 
                    class="bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 flex items-center justify-center">
                    <i class="fas fa-calendar mr-2"></i>2. Get Periods
                </button>
                <button onclick="testGetData()" 
                    class="bg-purple-600 text-white px-4 py-3 rounded-lg hover:bg-purple-700 flex items-center justify-center">
                    <i class="fas fa-database mr-2"></i>3. Get Allowance/Deduction
                </button>
                <button onclick="testXML()" 
                    class="bg-orange-600 text-white px-4 py-3 rounded-lg hover:bg-orange-700 flex items-center justify-center">
                    <i class="fas fa-code mr-2"></i>4. Test XML Format
                </button>
                <button onclick="testRateLimit()" 
                    class="bg-red-600 text-white px-4 py-3 rounded-lg hover:bg-red-700 flex items-center justify-center">
                    <i class="fas fa-tachometer-alt mr-2"></i>5. Test Rate Limit
                </button>
                <button onclick="runAllTests()" 
                    class="bg-indigo-600 text-white px-4 py-3 rounded-lg hover:bg-indigo-700 flex items-center justify-center">
                    <i class="fas fa-play mr-2"></i>Run All Tests
                </button>
            </div>
        </div>
        
        <!-- Results Section -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Test Results</h2>
            <div id="results" class="test-result space-y-4">
                <p class="text-gray-500">Click a test button to begin...</p>
            </div>
        </div>
    </div>
    
    <script>
        let jwtToken = null;
        
        function addResult(title, status, data, error = null) {
            const resultsDiv = document.getElementById('results');
            
            // Clear "Click to begin" message
            if (resultsDiv.querySelector('.text-gray-500')) {
                resultsDiv.innerHTML = '';
            }
            
            const statusColor = status === 'success' ? 'green' : status === 'error' ? 'red' : 'yellow';
            const statusIcon = status === 'success' ? 'check-circle' : status === 'error' ? 'times-circle' : 'info-circle';
            
            const resultHtml = `
                <div class="border-l-4 border-${statusColor}-500 bg-${statusColor}-50 p-4">
                    <div class="flex items-center mb-2">
                        <i class="fas fa-${statusIcon} text-${statusColor}-600 mr-2"></i>
                        <h3 class="font-bold text-${statusColor}-900">${title}</h3>
                    </div>
                    ${error ? `<p class="text-${statusColor}-800 mb-2"><strong>Error:</strong> ${error}</p>` : ''}
                    ${data ? `<pre class="text-xs">${JSON.stringify(data, null, 2)}</pre>` : ''}
                </div>
            `;
            
            resultsDiv.insertAdjacentHTML('afterbegin', resultHtml);
        }
        
        // Test 1: Authentication
        async function testAuthentication() {
            const apiKey = document.getElementById('apiKey').value;
            const apiSecret = document.getElementById('apiSecret').value;
            const apiBaseUrl = document.getElementById('apiBaseUrl').value;
            const useSignature = document.getElementById('useSignature').value === 'true';
            
            if (!apiKey || apiKey === 'YOUR_API_KEY_HERE') {
                addResult('Authentication Test', 'error', null, 'Please enter your API key');
                return;
            }
            
            const timestamp = Math.floor(Date.now() / 1000);
            let signature = '';
            
            if (useSignature) {
                // Generate HMAC signature (requires crypto library)
                const signatureString = apiKey + timestamp;
                signature = await generateHMAC(apiSecret, signatureString);
            }
            
            const body = useSignature ? {
                api_key: apiKey,
                timestamp: timestamp,
                signature: signature
            } : {};
            
            try {
                const response = await fetch(`${apiBaseUrl}/auth/token`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify(body)
                });
                
                const data = await response.json();
                
                if (data.success && data.data && data.data.access_token) {
                    jwtToken = data.data.access_token;
                    addResult('✅ Authentication Test', 'success', data);
                } else {
                    addResult('❌ Authentication Test', 'error', data, data.error?.message || 'Authentication failed');
                }
            } catch (error) {
                addResult('❌ Authentication Test', 'error', null, error.message);
            }
        }
        
        // Test 2: Get Periods
        async function testGetPeriods() {
            if (!jwtToken) {
                addResult('Get Periods Test', 'error', null, 'Please authenticate first (Test 1)');
                return;
            }
            
            const apiKey = document.getElementById('apiKey').value;
            const apiBaseUrl = document.getElementById('apiBaseUrl').value;
            
            try {
                const response = await fetch(`${apiBaseUrl}/payroll/periods`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${jwtToken}`,
                        'X-API-Key': apiKey
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addResult('✅ Get Periods Test', 'success', data);
                } else {
                    addResult('❌ Get Periods Test', 'error', data, data.error?.message);
                }
            } catch (error) {
                addResult('❌ Get Periods Test', 'error', null, error.message);
            }
        }
        
        // Test 3: Get Allowance/Deduction Data
        async function testGetData() {
            if (!jwtToken) {
                addResult('Get Data Test', 'error', null, 'Please authenticate first (Test 1)');
                return;
            }
            
            const apiKey = document.getElementById('apiKey').value;
            const apiBaseUrl = document.getElementById('apiBaseUrl').value;
            
            // Extract resource type and ID from API key
            const keyParts = apiKey.match(/oouth_\d{3}_(allow|deduc)_(\d+)_/);
            if (!keyParts) {
                addResult('Get Data Test', 'error', null, 'Invalid API key format');
                return;
            }
            
            const resourceType = keyParts[1] === 'allow' ? 'allowances' : 'deductions';
            const resourceId = keyParts[2];
            
            try {
                const response = await fetch(`${apiBaseUrl}/payroll/${resourceType}/${resourceId}`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${jwtToken}`,
                        'X-API-Key': apiKey
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    addResult(`✅ Get ${resourceType.charAt(0).toUpperCase() + resourceType.slice(1)} Data`, 'success', data);
                } else {
                    addResult(`❌ Get ${resourceType} Data`, 'error', data, data.error?.message);
                }
            } catch (error) {
                addResult('❌ Get Data Test', 'error', null, error.message);
            }
        }
        
        // Test 4: XML Format
        async function testXML() {
            if (!jwtToken) {
                addResult('XML Format Test', 'error', null, 'Please authenticate first (Test 1)');
                return;
            }
            
            const apiKey = document.getElementById('apiKey').value;
            const apiBaseUrl = document.getElementById('apiBaseUrl').value;
            
            try {
                const response = await fetch(`${apiBaseUrl}/payroll/periods?format=xml`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${jwtToken}`,
                        'X-API-Key': apiKey,
                        'Accept': 'application/xml'
                    }
                });
                
                const xmlText = await response.text();
                
                addResult('✅ XML Format Test', 'success', null);
                document.getElementById('results').insertAdjacentHTML('afterbegin', `
                    <div class="border-l-4 border-green-500 bg-green-50 p-4">
                        <h3 class="font-bold text-green-900 mb-2">XML Response:</h3>
                        <pre class="text-xs">${escapeHtml(xmlText)}</pre>
                    </div>
                `);
            } catch (error) {
                addResult('❌ XML Format Test', 'error', null, error.message);
            }
        }
        
        // Test 5: Rate Limit
        async function testRateLimit() {
            if (!jwtToken) {
                addResult('Rate Limit Test', 'error', null, 'Please authenticate first (Test 1)');
                return;
            }
            
            const apiKey = document.getElementById('apiKey').value;
            const apiBaseUrl = document.getElementById('apiBaseUrl').value;
            
            addResult('Rate Limit Test', 'info', {message: 'Sending 10 rapid requests to test rate limiting...'});
            
            let successCount = 0;
            let rateLimitHit = false;
            let headers = {};
            
            for (let i = 1; i <= 10; i++) {
                try {
                    const response = await fetch(`${apiBaseUrl}/payroll/periods`, {
                        method: 'GET',
                        headers: {
                            'Authorization': `Bearer ${jwtToken}`,
                            'X-API-Key': apiKey
                        }
                    });
                    
                    // Capture rate limit headers
                    headers = {
                        limit: response.headers.get('X-RateLimit-Limit'),
                        remaining: response.headers.get('X-RateLimit-Remaining'),
                        reset: response.headers.get('X-RateLimit-Reset')
                    };
                    
                    if (response.status === 429) {
                        rateLimitHit = true;
                        break;
                    } else if (response.ok) {
                        successCount++;
                    }
                } catch (error) {
                    console.error('Request error:', error);
                }
            }
            
            addResult('✅ Rate Limit Test Complete', 'success', {
                requests_sent: successCount,
                rate_limit_hit: rateLimitHit,
                rate_limit_headers: headers,
                note: 'Rate limit is ' + (headers.limit || '100') + ' requests per minute'
            });
        }
        
        // Run all tests sequentially
        async function runAllTests() {
            document.getElementById('results').innerHTML = '<p class="text-gray-500">Running all tests...</p>';
            
            await testAuthentication();
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            if (jwtToken) {
                await testGetPeriods();
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                await testGetData();
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                await testXML();
            }
        }
        
        // Helper: Generate HMAC signature
        async function generateHMAC(secret, message) {
            const encoder = new TextEncoder();
            const keyData = encoder.encode(secret);
            const messageData = encoder.encode(message);
            
            const cryptoKey = await crypto.subtle.importKey(
                'raw',
                keyData,
                { name: 'HMAC', hash: 'SHA-256' },
                false,
                ['sign']
            );
            
            const signature = await crypto.subtle.sign('HMAC', cryptoKey, messageData);
            return Array.from(new Uint8Array(signature))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
        }
        
        // Helper: Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    
</body>
</html>

