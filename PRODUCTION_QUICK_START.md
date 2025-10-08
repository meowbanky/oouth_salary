# 🚀 Production API - Quick Start Guide

**OOUTH Salary API is now in PRODUCTION MODE with full security**

---

## 🔒 Current Security Status

```
✅ HTTPS Required
✅ HMAC Signatures Required
✅ JWT Tokens Required
✅ Rate Limiting Active
❌ IP Whitelisting (Optional per vendor)
```

**All API requests MUST include valid HMAC-SHA256 signatures**

---

## ⚡ Quick Start for Vendors

### **Step 1: Get Your API Secret**

**Admin Tool:** https://oouthsalary.com.ng/get_api_secret.php

1. Login to OOUTH Salary system
2. Visit the link above
3. Select your API key from dropdown
4. Copy your API secret (64 characters)
5. **Store it securely** (environment variable, secret manager)

---

### **Step 2: Generate Signature**

**Formula:**
```
Signature String = API_KEY + TIMESTAMP
HMAC Signature = hash_hmac('sha256', Signature String, API_SECRET)
```

**Example (PHP):**
```php
$apiKey = 'oouth_005_deduc_48_ed7dee3ccb995727';
$apiSecret = 'your_64_character_secret_from_step_1';
$timestamp = time();

$signatureString = $apiKey . $timestamp;
$signature = hash_hmac('sha256', $signatureString, $apiSecret);
```

**Example (Bash):**
```bash
API_KEY="oouth_005_deduc_48_ed7dee3ccb995727"
API_SECRET="your_64_character_secret"
TIMESTAMP=$(date +%s)

SIGNATURE=$(echo -n "${API_KEY}${TIMESTAMP}" | openssl dgst -sha256 -hmac "$API_SECRET" | cut -d' ' -f2)
```

---

### **Step 3: Make Request**

**Required Headers:**
- `X-API-Key`: Your API key
- `X-Timestamp`: Current Unix timestamp
- `X-Signature`: Calculated HMAC signature

**Example cURL:**
```bash
curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -H "X-API-Key: oouth_005_deduc_48_ed7dee3ccb995727" \
  -H "X-Timestamp: 1759954524" \
  -H "X-Signature: your_calculated_signature"
```

**Success Response:**
```json
{
  "success": true,
  "data": {
    "access_token": "eyJ0eXAi...",
    "refresh_token": "abc123...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

---

## 🛠️ Tools & Resources

### **For Testing:**
- **Get API Secret:** https://oouthsalary.com.ng/get_api_secret.php ⭐
- **Signature Generator:** https://oouthsalary.com.ng/generate_signature.php
- **Web API Tester:** https://oouthsalary.com.ng/api_test.php
- **System Diagnostics:** https://oouthsalary.com.ng/api_diagnostic.php

### **Documentation:**
- **Vendor Guide:** /API_VENDOR_GUIDE.md (complete integration guide)
- **Security Config:** /api/SECURITY_CONFIG.md (security settings)
- **Testing Guide:** /api/TESTING_GUIDE.md (how to test)
- **Webhook Guide:** /api/WEBHOOK_GUIDE.md (real-time events)

---

## 📋 Common Errors & Solutions

### **Error: "Timestamp and signature are required"**
**Problem:** Missing headers  
**Solution:** Include `X-Timestamp` and `X-Signature` headers

---

### **Error: "Request signature verification failed"**
**Problem:** Invalid signature calculation  
**Solution:** 
1. Verify you're using the correct API secret
2. Check signature formula: `API_KEY + TIMESTAMP` (no spaces)
3. Use HMAC-SHA256 algorithm
4. Use the signature generator tool to test

---

### **Error: "Request timestamp is outside acceptable range"**
**Problem:** Timestamp too old or too new (>5 minutes difference)  
**Solution:** Use current timestamp: `time()` in PHP or `date +%s` in bash

---

### **Error: "INVALID_API_KEY"**
**Problem:** API key not found or inactive  
**Solution:** Contact OOUTH admin to verify key status

---

## 🔐 Code Examples

### **PHP - Complete Example**
```php
<?php
// Configuration
$apiKey = 'oouth_005_deduc_48_ed7dee3ccb995727';
$apiSecret = getenv('OOUTH_API_SECRET'); // Store in environment variable
$baseUrl = 'https://oouthsalary.com.ng/api/v1';

// Generate signature
$timestamp = time();
$signatureString = $apiKey . $timestamp;
$signature = hash_hmac('sha256', $signatureString, $apiSecret);

// Make request
$ch = curl_init("$baseUrl/auth/token");
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
    $accessToken = $data['data']['access_token'];
    echo "✅ Authentication successful!\n";
    echo "Token: $accessToken\n";
} else {
    echo "❌ Error: {$data['error']['message']}\n";
}
```

---

### **Node.js - Complete Example**
```javascript
const crypto = require('crypto');
const https = require('https');

const apiKey = 'oouth_005_deduc_48_ed7dee3ccb995727';
const apiSecret = process.env.OOUTH_API_SECRET;
const timestamp = Math.floor(Date.now() / 1000);

// Generate signature
const signatureString = apiKey + timestamp;
const signature = crypto
    .createHmac('sha256', apiSecret)
    .update(signatureString)
    .digest('hex');

// Make request
const options = {
    hostname: 'oouthsalary.com.ng',
    path: '/api/v1/auth/token',
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': apiKey,
        'X-Timestamp': timestamp,
        'X-Signature': signature
    }
};

const req = https.request(options, (res) => {
    let data = '';
    
    res.on('data', (chunk) => data += chunk);
    res.on('end', () => {
        const response = JSON.parse(data);
        if (response.success) {
            console.log('✅ Authentication successful!');
            console.log('Token:', response.data.access_token);
        } else {
            console.log('❌ Error:', response.error.message);
        }
    });
});

req.on('error', (error) => console.error('Request failed:', error));
req.end();
```

---

### **Python - Complete Example**
```python
import hmac
import hashlib
import time
import requests
import os

# Configuration
api_key = 'oouth_005_deduc_48_ed7dee3ccb995727'
api_secret = os.getenv('OOUTH_API_SECRET')
base_url = 'https://oouthsalary.com.ng/api/v1'
timestamp = int(time.time())

# Generate signature
signature_string = api_key + str(timestamp)
signature = hmac.new(
    api_secret.encode('utf-8'),
    signature_string.encode('utf-8'),
    hashlib.sha256
).hexdigest()

# Make request
headers = {
    'Content-Type': 'application/json',
    'X-API-Key': api_key,
    'X-Timestamp': str(timestamp),
    'X-Signature': signature
}

response = requests.post(f'{base_url}/auth/token', headers=headers)
data = response.json()

if data.get('success'):
    print('✅ Authentication successful!')
    print(f"Token: {data['data']['access_token']}")
else:
    print(f"❌ Error: {data['error']['message']}")
```

---

## 🎯 Integration Checklist

- [ ] Retrieved API secret from admin tool
- [ ] Stored API secret securely (environment variable)
- [ ] Implemented signature generation
- [ ] Tested authentication with signature
- [ ] Updated all API calls to include signature
- [ ] Implemented error handling
- [ ] Tested with production endpoints
- [ ] Documented signature process in your codebase

---

## 📞 Support

**Need Help?**
- **Email:** api-support@oouth.edu.ng
- **Phone:** [Contact Number]
- **Hours:** Monday-Friday, 8:00 AM - 5:00 PM WAT

**Common Issues:**
1. Can't generate valid signature → Use get_api_secret.php tool
2. Timestamp errors → Ensure system time is synchronized
3. API key issues → Contact admin to verify key status

---

## 🔑 Security Best Practices

1. ✅ **Never** commit API secrets to version control
2. ✅ Store secrets in environment variables or secret managers
3. ✅ Generate fresh signatures for each request
4. ✅ Use current timestamps (within 5 minutes)
5. ✅ Implement signature verification for webhooks
6. ✅ Log signature verification failures
7. ✅ Rotate API keys periodically (contact admin)

---

## 📊 Signature Validation Rules

| Rule | Requirement | Tolerance |
|------|-------------|-----------|
| Timestamp | Current Unix timestamp | ±5 minutes |
| Algorithm | HMAC-SHA256 | Exact |
| Signature String | API_KEY + TIMESTAMP | Exact (no spaces) |
| Headers | X-Timestamp, X-Signature | Both required |

---

## ⚡ Quick Test

**Test your signature generation:**

```bash
# Visit the tool
open https://oouthsalary.com.ng/get_api_secret.php

# Or use signature generator
open https://oouthsalary.com.ng/generate_signature.php
```

**Both tools provide:**
- Real-time signature calculation
- Copy-paste cURL commands
- Instant testing

---

## 🎉 You're Ready!

Your API integration is now production-ready with enterprise-grade security:

✅ HTTPS encryption  
✅ HMAC signature verification  
✅ JWT token authentication  
✅ Rate limiting protection  
✅ Complete audit logging  

**Welcome to the OOUTH Salary API!**

---

**Document Version:** 1.0.0  
**Last Updated:** October 8, 2025  
**API Status:** 🟢 PRODUCTION

