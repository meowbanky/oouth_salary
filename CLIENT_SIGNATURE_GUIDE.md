# 🔐 Client-Side Signature Generation Guide

**How Vendors Generate HMAC Signatures for API Requests**

---

## 📋 Overview

Every API request requires an **HMAC-SHA256 signature** for security. This guide shows exactly how clients generate these signatures in different programming languages.

---

## 🔑 What You Need

Before generating signatures, you need:

1. **API Key** - Provided by OOUTH admin (e.g., `oouth_005_deduc_48_ed7dee3ccb995727`)
2. **API Secret** - 64-character secret from admin (shown once during key generation)
3. **Current Timestamp** - Unix timestamp (seconds since 1970)

**⚠️ IMPORTANT:** Store the API secret securely (environment variable, not in code!)

---

## 📐 Signature Formula

```
Step 1: Signature String = API_KEY + TIMESTAMP
Step 2: HMAC Signature = hash_hmac('sha256', Signature String, API_SECRET)
```

**Example:**
```
API Key: oouth_005_deduc_48_ed7dee3ccb995727
Timestamp: 1759954524
Secret: abc123... (64 chars)

Signature String = "oouth_005_deduc_48_ed7dee3ccb9957271759954524"
HMAC Signature = hash_hmac('sha256', Signature String, Secret)
Result: "3870c92a82da3d27e6117b0267cb410bdd12b99985ffe77f245362527890f3d1"
```

---

## 💻 Language-Specific Examples

### **1. PHP** ⭐ (Most Common)

```php
<?php
/**
 * Generate HMAC signature for OOUTH Salary API
 */

// Configuration (from environment variables)
$apiKey = getenv('OOUTH_API_KEY');        // 'oouth_005_deduc_48_...'
$apiSecret = getenv('OOUTH_API_SECRET');  // 64-character secret

// Step 1: Get current timestamp
$timestamp = time();  // e.g., 1759954524

// Step 2: Build signature string (API Key + Timestamp)
$signatureString = $apiKey . $timestamp;
// Result: "oouth_005_deduc_48_ed7dee3ccb9957271759954524"

// Step 3: Calculate HMAC-SHA256
$signature = hash_hmac('sha256', $signatureString, $apiSecret);
// Result: "3870c92a82da3d27e6117b0267cb410bdd12b99985ffe77f245362527890f3d1"

// Step 4: Make API request
$ch = curl_init('https://oouthsalary.com.ng/api/v1/auth/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey,
        'X-Timestamp: ' . $timestamp,
        'X-Signature: ' . $signature
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($data['success']) {
    echo "✅ Authentication successful!\n";
    echo "Access Token: " . $data['data']['access_token'] . "\n";
} else {
    echo "❌ Error: " . $data['error']['message'] . "\n";
}
```

**Environment Variables (.env):**
```bash
OOUTH_API_KEY=oouth_005_deduc_48_ed7dee3ccb995727
OOUTH_API_SECRET=your_64_character_secret_here
```

---

### **2. JavaScript / Node.js**

```javascript
const crypto = require('crypto');
const https = require('https');

/**
 * Generate HMAC signature for OOUTH Salary API
 */

// Configuration (from environment variables)
const apiKey = process.env.OOUTH_API_KEY;        // 'oouth_005_deduc_48_...'
const apiSecret = process.env.OOUTH_API_SECRET;  // 64-character secret

// Step 1: Get current timestamp
const timestamp = Math.floor(Date.now() / 1000);  // Unix timestamp

// Step 2: Build signature string (API Key + Timestamp)
const signatureString = apiKey + timestamp;
// "oouth_005_deduc_48_ed7dee3ccb9957271759954524"

// Step 3: Calculate HMAC-SHA256
const signature = crypto
    .createHmac('sha256', apiSecret)
    .update(signatureString)
    .digest('hex');
// "3870c92a82da3d27e6117b0267cb410bdd12b99985ffe77f245362527890f3d1"

// Step 4: Make API request
const options = {
    hostname: 'oouthsalary.com.ng',
    path: '/api/v1/auth/token',
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey,
        'X-Timestamp': timestamp.toString(),
        'X-Signature': signature
    }
};

const req = https.request(options, (res) => {
    let data = '';
    
    res.on('data', (chunk) => {
        data += chunk;
    });
    
    res.on('end', () => {
        const response = JSON.parse(data);
        
        if (response.success) {
            console.log('✅ Authentication successful!');
            console.log('Access Token:', response.data.access_token);
        } else {
            console.log('❌ Error:', response.error.message);
        }
    });
});

req.on('error', (error) => {
    console.error('Request failed:', error);
});

req.end();
```

**Environment Variables (.env):**
```bash
OOUTH_API_KEY=oouth_005_deduc_48_ed7dee3ccb995727
OOUTH_API_SECRET=your_64_character_secret_here
```

---

### **3. Python**

```python
import hmac
import hashlib
import time
import requests
import os

"""
Generate HMAC signature for OOUTH Salary API
"""

# Configuration (from environment variables)
api_key = os.getenv('OOUTH_API_KEY')        # 'oouth_005_deduc_48_...'
api_secret = os.getenv('OOUTH_API_SECRET')  # 64-character secret

# Step 1: Get current timestamp
timestamp = int(time.time())  # Unix timestamp

# Step 2: Build signature string (API Key + Timestamp)
signature_string = api_key + str(timestamp)
# "oouth_005_deduc_48_ed7dee3ccb9957271759954524"

# Step 3: Calculate HMAC-SHA256
signature = hmac.new(
    api_secret.encode('utf-8'),
    signature_string.encode('utf-8'),
    hashlib.sha256
).hexdigest()
# "3870c92a82da3d27e6117b0267cb410bdd12b99985ffe77f245362527890f3d1"

# Step 4: Make API request
headers = {
    'Content-Type': 'application/json',
    'X-API-Key': api_key,
    'X-Timestamp': str(timestamp),
    'X-Signature': signature
}

response = requests.post(
    'https://oouthsalary.com.ng/api/v1/auth/token',
    headers=headers
)

data = response.json()

if data.get('success'):
    print('✅ Authentication successful!')
    print(f"Access Token: {data['data']['access_token']}")
else:
    print(f"❌ Error: {data['error']['message']}")
```

**Environment Variables:**
```bash
export OOUTH_API_KEY="oouth_005_deduc_48_ed7dee3ccb995727"
export OOUTH_API_SECRET="your_64_character_secret_here"
```

---

### **4. C# / .NET**

```csharp
using System;
using System.Net.Http;
using System.Security.Cryptography;
using System.Text;
using System.Threading.Tasks;

class OouthApiClient
{
    private readonly string apiKey;
    private readonly string apiSecret;
    private readonly HttpClient httpClient;
    
    public OouthApiClient(string apiKey, string apiSecret)
    {
        this.apiKey = apiKey;
        this.apiSecret = apiSecret;
        this.httpClient = new HttpClient();
    }
    
    /// <summary>
    /// Generate HMAC-SHA256 signature
    /// </summary>
    private string GenerateSignature(long timestamp)
    {
        // Step 1: Build signature string
        string signatureString = apiKey + timestamp.ToString();
        
        // Step 2: Calculate HMAC-SHA256
        using (var hmac = new HMACSHA256(Encoding.UTF8.GetBytes(apiSecret)))
        {
            byte[] hashBytes = hmac.ComputeHash(Encoding.UTF8.GetBytes(signatureString));
            return BitConverter.ToString(hashBytes).Replace("-", "").ToLower();
        }
    }
    
    public async Task<string> AuthenticateAsync()
    {
        // Get current timestamp
        long timestamp = DateTimeOffset.UtcNow.ToUnixTimeSeconds();
        
        // Generate signature
        string signature = GenerateSignature(timestamp);
        
        // Create request
        var request = new HttpRequestMessage(HttpMethod.Post, 
            "https://oouthsalary.com.ng/api/v1/auth/token");
        
        request.Headers.Add("X-API-Key", apiKey);
        request.Headers.Add("X-Timestamp", timestamp.ToString());
        request.Headers.Add("X-Signature", signature);
        request.Content = new StringContent("{}", Encoding.UTF8, "application/json");
        
        // Send request
        var response = await httpClient.SendAsync(request);
        return await response.Content.ReadAsStringAsync();
    }
}

// Usage
class Program
{
    static async Task Main(string[] args)
    {
        string apiKey = Environment.GetEnvironmentVariable("OOUTH_API_KEY");
        string apiSecret = Environment.GetEnvironmentVariable("OOUTH_API_SECRET");
        
        var client = new OouthApiClient(apiKey, apiSecret);
        string response = await client.AuthenticateAsync();
        
        Console.WriteLine(response);
    }
}
```

---

### **5. Java**

```java
import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.net.http.*;
import java.net.URI;
import java.nio.charset.StandardCharsets;
import java.time.Instant;

public class OouthApiClient {
    
    private final String apiKey;
    private final String apiSecret;
    private final HttpClient httpClient;
    
    public OouthApiClient(String apiKey, String apiSecret) {
        this.apiKey = apiKey;
        this.apiSecret = apiSecret;
        this.httpClient = HttpClient.newHttpClient();
    }
    
    /**
     * Generate HMAC-SHA256 signature
     */
    private String generateSignature(long timestamp) throws Exception {
        // Step 1: Build signature string
        String signatureString = apiKey + timestamp;
        
        // Step 2: Calculate HMAC-SHA256
        Mac sha256Hmac = Mac.getInstance("HmacSHA256");
        SecretKeySpec secretKey = new SecretKeySpec(
            apiSecret.getBytes(StandardCharsets.UTF_8), 
            "HmacSHA256"
        );
        sha256Hmac.init(secretKey);
        
        byte[] hash = sha256Hmac.doFinal(signatureString.getBytes(StandardCharsets.UTF_8));
        
        // Convert to hex
        StringBuilder hexString = new StringBuilder();
        for (byte b : hash) {
            String hex = Integer.toHexString(0xff & b);
            if (hex.length() == 1) hexString.append('0');
            hexString.append(hex);
        }
        
        return hexString.toString();
    }
    
    public String authenticate() throws Exception {
        // Get current timestamp
        long timestamp = Instant.now().getEpochSecond();
        
        // Generate signature
        String signature = generateSignature(timestamp);
        
        // Create request
        HttpRequest request = HttpRequest.newBuilder()
            .uri(URI.create("https://oouthsalary.com.ng/api/v1/auth/token"))
            .header("Content-Type", "application/json")
            .header("X-API-Key", apiKey)
            .header("X-Timestamp", String.valueOf(timestamp))
            .header("X-Signature", signature)
            .POST(HttpRequest.BodyPublishers.ofString("{}"))
            .build();
        
        // Send request
        HttpResponse<String> response = httpClient.send(
            request, 
            HttpResponse.BodyHandlers.ofString()
        );
        
        return response.body();
    }
    
    public static void main(String[] args) {
        try {
            String apiKey = System.getenv("OOUTH_API_KEY");
            String apiSecret = System.getenv("OOUTH_API_SECRET");
            
            OouthApiClient client = new OouthApiClient(apiKey, apiSecret);
            String response = client.authenticate();
            
            System.out.println(response);
        } catch (Exception e) {
            e.printStackTrace();
        }
    }
}
```

---

### **6. Ruby**

```ruby
require 'openssl'
require 'net/http'
require 'uri'
require 'json'

# Configuration (from environment variables)
api_key = ENV['OOUTH_API_KEY']        # 'oouth_005_deduc_48_...'
api_secret = ENV['OOUTH_API_SECRET']  # 64-character secret

# Step 1: Get current timestamp
timestamp = Time.now.to_i

# Step 2: Build signature string
signature_string = "#{api_key}#{timestamp}"

# Step 3: Calculate HMAC-SHA256
signature = OpenSSL::HMAC.hexdigest(
  OpenSSL::Digest.new('sha256'),
  api_secret,
  signature_string
)

# Step 4: Make API request
uri = URI.parse('https://oouthsalary.com.ng/api/v1/auth/token')
http = Net::HTTP.new(uri.host, uri.port)
http.use_ssl = true

request = Net::HTTP::Post.new(uri.path)
request['Content-Type'] = 'application/json'
request['X-API-Key'] = api_key
request['X-Timestamp'] = timestamp.to_s
request['X-Signature'] = signature
request.body = '{}'

response = http.request(request)
data = JSON.parse(response.body)

if data['success']
  puts "✅ Authentication successful!"
  puts "Access Token: #{data['data']['access_token']}"
else
  puts "❌ Error: #{data['error']['message']}"
end
```

---

### **7. Go (Golang)**

```go
package main

import (
    "crypto/hmac"
    "crypto/sha256"
    "encoding/hex"
    "fmt"
    "io/ioutil"
    "net/http"
    "os"
    "strconv"
    "time"
)

func generateSignature(apiKey, apiSecret string, timestamp int64) string {
    // Build signature string
    signatureString := apiKey + strconv.FormatInt(timestamp, 10)
    
    // Calculate HMAC-SHA256
    h := hmac.New(sha256.New, []byte(apiSecret))
    h.Write([]byte(signatureString))
    signature := hex.EncodeToString(h.Sum(nil))
    
    return signature
}

func main() {
    // Configuration
    apiKey := os.Getenv("OOUTH_API_KEY")
    apiSecret := os.Getenv("OOUTH_API_SECRET")
    
    // Get current timestamp
    timestamp := time.Now().Unix()
    
    // Generate signature
    signature := generateSignature(apiKey, apiSecret, timestamp)
    
    // Create request
    client := &http.Client{}
    req, _ := http.NewRequest("POST", 
        "https://oouthsalary.com.ng/api/v1/auth/token", nil)
    
    req.Header.Set("Content-Type", "application/json")
    req.Header.Set("X-API-Key", apiKey)
    req.Header.Set("X-Timestamp", strconv.FormatInt(timestamp, 10))
    req.Header.Set("X-Signature", signature)
    
    // Send request
    resp, err := client.Do(req)
    if err != nil {
        fmt.Println("Error:", err)
        return
    }
    defer resp.Body.Close()
    
    body, _ := ioutil.ReadAll(resp.Body)
    fmt.Println(string(body))
}
```

---

### **8. Bash / Shell Script**

```bash
#!/bin/bash

# Configuration (from environment variables)
API_KEY="${OOUTH_API_KEY}"        # 'oouth_005_deduc_48_...'
API_SECRET="${OOUTH_API_SECRET}"  # 64-character secret

# Step 1: Get current timestamp
TIMESTAMP=$(date +%s)

# Step 2: Build signature string
SIGNATURE_STRING="${API_KEY}${TIMESTAMP}"

# Step 3: Calculate HMAC-SHA256
SIGNATURE=$(echo -n "${SIGNATURE_STRING}" | \
    openssl dgst -sha256 -hmac "${API_SECRET}" | \
    cut -d' ' -f2)

# Step 4: Make API request
curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \
    -H "Content-Type: application/json" \
    -H "X-API-Key: ${API_KEY}" \
    -H "X-Timestamp: ${TIMESTAMP}" \
    -H "X-Signature: ${SIGNATURE}"
```

**Usage:**
```bash
export OOUTH_API_KEY="oouth_005_deduc_48_ed7dee3ccb995727"
export OOUTH_API_SECRET="your_64_character_secret_here"
./authenticate.sh
```

---

## 🔍 Step-by-Step Breakdown

### **Step 1: Get Current Timestamp**
```
Purpose: Prevent replay attacks
Format: Unix timestamp (seconds since Jan 1, 1970)
Tolerance: ±5 minutes from server time
```

**Examples:**
- PHP: `time()`
- JavaScript: `Math.floor(Date.now() / 1000)`
- Python: `int(time.time())`
- Bash: `date +%s`

---

### **Step 2: Build Signature String**
```
Formula: API_KEY + TIMESTAMP (concatenate with NO spaces)
```

**Example:**
```
API Key: oouth_005_deduc_48_ed7dee3ccb995727
Timestamp: 1759954524

Signature String: oouth_005_deduc_48_ed7dee3ccb9957271759954524
                  └─────────── API Key ──────────┘└─ Timestamp ─┘
```

**⚠️ Common Mistakes:**
- ❌ Adding spaces: `api_key + " " + timestamp`
- ❌ Using milliseconds instead of seconds
- ❌ Converting to string incorrectly

---

### **Step 3: Calculate HMAC-SHA256**
```
Algorithm: HMAC-SHA256
Key: API Secret (64 characters)
Message: Signature String
Output: Hexadecimal string (64 characters)
```

**Libraries:**
- PHP: `hash_hmac('sha256', $message, $secret)`
- JavaScript: `crypto.createHmac('sha256', secret).update(message).digest('hex')`
- Python: `hmac.new(secret.encode(), message.encode(), hashlib.sha256).hexdigest()`

---

### **Step 4: Include in Request Headers**
```
X-API-Key: your_api_key
X-Timestamp: your_timestamp
X-Signature: calculated_signature
```

All three headers are **required** for every request.

---

## 🧪 Testing Your Implementation

### **Test 1: Check Signature Generation**

Use this test to verify your signature calculation:

```
API Key: test_key_123
API Secret: test_secret_456
Timestamp: 1234567890

Expected Signature String: test_key_1231234567890
Expected HMAC: (calculate with your code)
```

### **Test 2: Use OOUTH Tools**

1. **Get API Secret Tool:**
   - Visit: https://oouthsalary.com.ng/get_api_secret.php
   - Select your API key
   - Generate signature
   - Compare with your code's output

2. **Signature Generator:**
   - Visit: https://oouthsalary.com.ng/generate_signature.php
   - Enter API key and secret
   - Compare signatures

---

## ❌ Common Errors & Solutions

### **Error: "Request signature verification failed"**

**Possible Causes:**
1. Wrong API secret
2. Timestamp not concatenated correctly
3. Using milliseconds instead of seconds
4. Extra spaces in signature string
5. Wrong HMAC algorithm

**Debug:**
```php
// Log signature details
echo "API Key: " . $apiKey . "\n";
echo "Timestamp: " . $timestamp . "\n";
echo "Signature String: " . ($apiKey . $timestamp) . "\n";
echo "Signature: " . hash_hmac('sha256', $apiKey . $timestamp, $apiSecret) . "\n";
```

---

### **Error: "Request timestamp is outside acceptable range"**

**Causes:**
- System clock is wrong (±5 minutes)
- Using milliseconds instead of seconds
- Old timestamp cached

**Solution:**
```bash
# Check system time
date +%s

# Sync system clock (Linux)
sudo ntpdate pool.ntp.org
```

---

## 🔐 Security Best Practices

### **1. Store Secrets Securely**
```bash
# ✅ Good: Environment variables
export OOUTH_API_SECRET="abc123..."

# ❌ Bad: Hardcoded in source
$apiSecret = "abc123...";  // DON'T DO THIS!
```

---

### **2. Generate Fresh Signatures**
```php
// ✅ Good: Generate new signature for each request
$timestamp = time();
$signature = hash_hmac('sha256', $apiKey . $timestamp, $apiSecret);

// ❌ Bad: Reuse old signature
$signature = "cached_signature";  // DON'T DO THIS!
```

---

### **3. Use HTTPS Only**
```php
// ✅ Good
$url = "https://oouthsalary.com.ng/api/v1/auth/token";

// ❌ Bad
$url = "http://oouthsalary.com.ng/api/v1/auth/token";
```

---

## 📊 Signature Validation Flowchart

```
[Client Request]
      ↓
[Get Current Timestamp]
      ↓
[Build: API_KEY + TIMESTAMP]
      ↓
[Calculate HMAC-SHA256 with Secret]
      ↓
[Add Headers: X-API-Key, X-Timestamp, X-Signature]
      ↓
[Send HTTPS Request]
      ↓
[Server Validates Signature]
      ↓
[Success] or [Error]
```

---

## 🎯 Quick Reference

| What | Where | How |
|------|-------|-----|
| Get API Key | Admin provides | Store in environment |
| Get API Secret | https://oouthsalary.com.ng/get_api_secret.php | Store securely |
| Test Signature | https://oouthsalary.com.ng/generate_signature.php | Compare output |
| Signature Formula | `hash_hmac('sha256', API_KEY + TIMESTAMP, SECRET)` | No spaces |
| Headers Required | `X-API-Key`, `X-Timestamp`, `X-Signature` | All three |
| Timestamp Format | Unix seconds | Not milliseconds |

---

## 📞 Support

**Need Help with Signature Generation?**
- Email: api-support@oouth.edu.ng
- Phone: [Contact Number]
- Testing Tool: https://oouthsalary.com.ng/get_api_secret.php

**Include in Your Support Request:**
1. Programming language you're using
2. Sample code (without secret!)
3. Error message received
4. Timestamp and signature you generated

---

**Document Version:** 1.0.0  
**Last Updated:** October 8, 2025  
**Related Docs:** PRODUCTION_QUICK_START.md, API_VENDOR_GUIDE.md

