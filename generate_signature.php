<?php
/**
 * HMAC Signature Generator
 * Helps generate signatures for API authentication
 */

// Your credentials (replace with actual values)
$apiKey = 'oouth_005_deduc_48_ed7dee3ccb995727';
$apiSecret = 'YOUR_64_CHARACTER_SECRET_HERE'; // The secret you received when generating the API key

// Current timestamp
$timestamp = time();

// Create signature string
$signatureString = $apiKey . $timestamp;

// Calculate HMAC-SHA256 signature
$signature = hash_hmac('sha256', $signatureString, $apiSecret);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HMAC Signature Generator - OOUTH Salary API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-100 p-8">

    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-key mr-2 text-blue-600"></i>HMAC Signature Generator
            </h1>
            <p class="text-gray-600">Generate HMAC-SHA256 signatures for API authentication</p>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Generate Signature</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">API Key:</label>
                <input type="text" id="apiKey" value="<?php echo $apiKey; ?>"
                    class="w-full border rounded-lg px-3 py-2 font-mono text-sm" placeholder="oouth_001_allow_5_...">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">API Secret (64 characters):</label>
                <input type="password" id="apiSecret" value="<?php echo $apiSecret; ?>"
                    class="w-full border rounded-lg px-3 py-2 font-mono text-sm"
                    placeholder="Enter your 64-character secret">
                <button onclick="toggleSecret()" class="text-sm text-blue-600 mt-1">
                    <i class="fas fa-eye"></i> Show/Hide
                </button>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Timestamp:</label>
                <input type="text" id="timestamp" value="<?php echo $timestamp; ?>"
                    class="w-full border rounded-lg px-3 py-2 font-mono text-sm" readonly>
                <button onclick="refreshTimestamp()" class="text-sm text-blue-600 mt-1">
                    <i class="fas fa-sync"></i> Refresh Timestamp
                </button>
            </div>

            <button onclick="generateSignature()"
                class="w-full bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700">
                <i class="fas fa-signature mr-2"></i>Generate Signature
            </button>
        </div>

        <div id="results" class="bg-white rounded-lg shadow-lg p-6" style="display: none;">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Generated Values</h2>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Signature String:</label>
                <pre id="signatureString" class="bg-gray-100 p-3 rounded text-sm overflow-x-auto"></pre>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">HMAC-SHA256 Signature:</label>
                <pre id="signature" class="bg-gray-900 text-green-400 p-3 rounded text-sm overflow-x-auto"></pre>
                <button onclick="copySignature()" class="text-sm text-blue-600 mt-1">
                    <i class="fas fa-copy"></i> Copy Signature
                </button>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Complete cURL Command:</label>
                <pre id="curlCommand" class="bg-gray-900 text-green-400 p-3 rounded text-sm overflow-x-auto"></pre>
                <button onclick="copyCurl()" class="text-sm text-blue-600 mt-1">
                    <i class="fas fa-copy"></i> Copy cURL Command
                </button>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">JSON Request Body:</label>
                <pre id="jsonBody" class="bg-gray-900 text-green-400 p-3 rounded text-sm overflow-x-auto"></pre>
                <button onclick="copyJson()" class="text-sm text-blue-600 mt-1">
                    <i class="fas fa-copy"></i> Copy JSON
                </button>
            </div>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-6">
            <h3 class="font-bold text-blue-900 mb-2"><i class="fas fa-info-circle mr-2"></i>How HMAC Signatures Work
            </h3>
            <ol class="text-blue-800 text-sm space-y-1 ml-4 list-decimal">
                <li>Combine API key and timestamp: <code>api_key + timestamp</code></li>
                <li>Calculate HMAC-SHA256 hash using your API secret</li>
                <li>Send signature in request headers or body</li>
                <li>Server verifies signature matches before processing</li>
            </ol>
            <p class="text-blue-800 text-sm mt-2">
                <strong>Why?</strong> This prevents request tampering and ensures authenticity.
            </p>
        </div>
    </div>

    <script>
    function refreshTimestamp() {
        document.getElementById('timestamp').value = Math.floor(Date.now() / 1000);
        generateSignature();
    }

    function toggleSecret() {
        const input = document.getElementById('apiSecret');
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    async function generateSignature() {
        const apiKey = document.getElementById('apiKey').value;
        const apiSecret = document.getElementById('apiSecret').value;
        const timestamp = document.getElementById('timestamp').value;

        if (!apiKey || !apiSecret || !timestamp) {
            alert('Please fill in all fields');
            return;
        }

        if (apiSecret === 'YOUR_64_CHARACTER_SECRET_HERE') {
            alert('Please enter your actual API secret');
            return;
        }

        // Create signature string
        const signatureString = apiKey + timestamp;

        // Calculate HMAC-SHA256 signature using Web Crypto API
        const encoder = new TextEncoder();
        const keyData = encoder.encode(apiSecret);
        const messageData = encoder.encode(signatureString);

        const cryptoKey = await crypto.subtle.importKey(
            'raw',
            keyData, {
                name: 'HMAC',
                hash: 'SHA-256'
            },
            false,
            ['sign']
        );

        const signatureBuffer = await crypto.subtle.sign('HMAC', cryptoKey, messageData);
        const signature = Array.from(new Uint8Array(signatureBuffer))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');

        // Display results
        document.getElementById('signatureString').textContent = signatureString;
        document.getElementById('signature').textContent = signature;

        // Generate cURL command
        const curlCommand = `curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \\
  -H "Content-Type: application/json" \\
  -H "X-API-Key: ${apiKey}" \\
  -H "X-Timestamp: ${timestamp}" \\
  -H "X-Signature: ${signature}"`;

        document.getElementById('curlCommand').textContent = curlCommand;

        // Generate JSON body
        const jsonBody = JSON.stringify({
            api_key: apiKey,
            timestamp: parseInt(timestamp),
            signature: signature
        }, null, 2);

        document.getElementById('jsonBody').textContent = jsonBody;

        // Show results
        document.getElementById('results').style.display = 'block';
    }

    function copySignature() {
        const signature = document.getElementById('signature').textContent;
        navigator.clipboard.writeText(signature);
        alert('Signature copied to clipboard!');
    }

    function copyCurl() {
        const curl = document.getElementById('curlCommand').textContent;
        navigator.clipboard.writeText(curl);
        alert('cURL command copied to clipboard!');
    }

    function copyJson() {
        const json = document.getElementById('jsonBody').textContent;
        navigator.clipboard.writeText(json);
        alert('JSON copied to clipboard!');
    }
    </script>

</body>

</html>