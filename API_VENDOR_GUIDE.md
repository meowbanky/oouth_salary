# 🚀 OOUTH Salary API - Vendor Integration Guide

**For Third-Party Organizations and System Integrators**

---

## 📋 Table of Contents

1. [Getting Started](#getting-started)
2. [Authentication](#authentication)
3. [Making API Requests](#making-api-requests)
4. [Available Endpoints](#available-endpoints)
5. [Response Formats](#response-formats)
6. [Webhooks (Real-time Notifications)](#webhooks)
7. [Error Handling](#error-handling)
8. [Rate Limits](#rate-limits)
9. [Code Examples](#code-examples)
10. [Testing & Debugging](#testing--debugging)
11. [Best Practices](#best-practices)
12. [Support](#support)

---

## 🎯 Getting Started

### **Step 1: Request API Access**

Contact OOUTH Salary System Administrator:

- **Email:** api-support@oouth.edu.ng
- **Phone:** [Contact Number]

**You will need to provide:**

1. Your organization name
2. Contact email
3. Phone number
4. Which data you need access to (specific allowance or deduction)
5. Your server IP addresses (for IP whitelisting)

### **Step 2: Receive Your Credentials**

You will receive:

```
API Key: oouth_005_deduc_48_a1b2c3d4e5f6g7h8
API Secret: 64-character secret (keep this safe!)
Organization ID: 005
Resource Access: Pension Deduction (ID: 48)
```

**⚠️ IMPORTANT:** The API secret is shown only once. Save it securely!

### **Step 3: Understand Your Access**

Your API key is scoped to a specific resource:

- **Allowance** (e.g., Housing Allowance) - Staff receiving this allowance
- **Deduction** (e.g., Pension) - Staff with this deduction

**Example Key Breakdown:**

```
oouth_005_deduc_48_a1b2c3d4e5f6g7h8
      │     │    │
      │     │    └─ Deduction ID (48 = Pension)
      │     └─ Resource Type (deduc = Deduction)
      └─ Organization ID (005 = Your Organization)
```

---

## 🔐 Authentication

> **🔒 CURRENT MODE: PRODUCTION**  
> The API is now in **PRODUCTION MODE** with full security:
>
> - ✅ API Key + JWT required
> - ✅ HMAC Signatures **REQUIRED**
> - ✅ HTTPS **REQUIRED**
> - ❌ IP Whitelisting (Optional per vendor)
>
> **All requests must include valid HMAC-SHA256 signatures.**
> See: [Production Quick Start](/PRODUCTION_QUICK_START.md) for signature generation

### **Two-Step Authentication Process:**

#### **Step 1: Obtain JWT Token**

**Endpoint:** `POST /api/v1/auth/token`

**Production Request (Signature Required):**

```bash
curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY_HERE" \
  -H "X-Timestamp: 1696780800" \
  -H "X-Signature: calculated_hmac_signature" \
  -d '{
    "api_key": "YOUR_API_KEY_HERE",
    "timestamp": 1696780800,
    "signature": "calculated_hmac_signature"
  }'
```

**Response:**

```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGc...",
    "refresh_token": "abc123...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

**Important:**

- Token expires in 15 minutes (900 seconds)
- Use the `access_token` for all subsequent requests
- Use `refresh_token` to get a new token without re-authenticating

---

#### **Step 2: Use JWT Token for Requests**

Include the token in every request:

```bash
curl https://oouthsalary.com.ng/api/v1/payroll/periods \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

### **Generating HMAC Signatures (Production)**

**Tool:** https://oouthsalary.com.ng/generate_signature.php

1. Enter your API key
2. Enter your API secret
3. Click "Generate Signature"
4. Copy the generated values

**Manual Calculation:**

```php
$apiKey = 'your_api_key';
$apiSecret = 'your_64_char_secret';
$timestamp = time();

$signatureString = $apiKey . $timestamp;
$signature = hash_hmac('sha256', $signatureString, $apiSecret);
```

See [Code Examples](#code-examples) section for more languages.

---

## 📡 Making API Requests

### **Base URL:**

```
https://oouthsalary.com.ng/api/v1
```

### **Required Headers:**

```
Authorization: Bearer YOUR_JWT_TOKEN
X-API-Key: YOUR_API_KEY
Content-Type: application/json (for POST/PUT)
```

### **Optional Headers:**

```
Accept: application/json (or application/xml, text/csv)
X-Request-ID: unique_identifier (for tracking)
```

---

## 📊 Available Endpoints

### **1. Authentication**

#### **Generate Token**

```
POST /auth/token
```

Returns JWT token for authentication.

#### **Refresh Token**

```
POST /auth/refresh
Body: { "refresh_token": "your_refresh_token" }
```

Get a new JWT token without re-authenticating.

---

### **2. Payroll Periods**

#### **List All Periods**

```
GET /payroll/periods
Query Parameters:
  - page: Page number (default: 1)
  - limit: Records per page (default: 100, max: 1000)
```

**Example:**

```bash
curl "https://oouthsalary.com.ng/api/v1/payroll/periods?page=1&limit=50" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-Key: YOUR_KEY"
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "period_id": 44,
      "description": "October",
      "year": "2025",
      "is_active": 1
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total_pages": 1,
    "total_records": 45
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": null
  }
}
```

---

#### **Get Active Period**

```
GET /payroll/periods/active
```

Returns the current active payroll period.

**Example:**

```bash
curl "https://oouthsalary.com.ng/api/v1/payroll/periods/active" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-Key: YOUR_KEY"
```

**Response:**

```json
{
  "success": true,
  "data": {
    "period": {
      "period_id": 44,
      "description": "October",
      "year": "2025",
      "is_active": 1
    }
  }
}
```

---

#### **Get Specific Period**

```
GET /payroll/periods/{period_id}
```

**Example:**

```bash
curl "https://oouthsalary.com.ng/api/v1/payroll/periods/44" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-Key: YOUR_KEY"
```

---

### **3. Allowance Data** (If your key is for allowances)

```
GET /payroll/allowances/{allowance_id}?period={period_id}
```

**Example:**

```bash
# Get Housing Allowance (ID: 5) for October 2025 (Period: 44)
curl "https://oouthsalary.com.ng/api/v1/payroll/allowances/5?period=44" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-Key: YOUR_KEY"
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "staff_id": "900",
      "name": "Salami Funmilola Idiat",
      "amount": 50000.0
    },
    {
      "staff_id": "1200",
      "name": "Ogunleye Taiwo",
      "amount": 45000.0
    }
  ],
  "metadata": {
    "period": {
      "id": 44,
      "description": "October",
      "year": "2025"
    },
    "allowance_name": "HOUSING ALLOWANCE",
    "total_records": 150,
    "total_amount": 7500000.0
  }
}
```

**Note:** Only returns staff with amounts > 0

---

### **4. Deduction Data** (If your key is for deductions)

```
GET /payroll/deductions/{deduction_id}?period={period_id}
```

**Example:**

```bash
# Get Pension Deduction (ID: 48) for October 2025 (Period: 44)
curl "https://oouthsalary.com.ng/api/v1/payroll/deductions/48?period=44" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-API-Key: YOUR_KEY"
```

**Response:**

```json
{
  "success": true,
  "data": [
    {
      "staff_id": "900",
      "name": "Salami Funmilola Idiat",
      "amount": 5000.0
    },
    {
      "staff_id": "1200",
      "name": "Ogunleye Taiwo",
      "amount": 4500.0
    }
  ],
  "metadata": {
    "period": {
      "id": 44,
      "description": "October",
      "year": "2025"
    },
    "deduction_name": "PENSION",
    "total_records": 850,
    "total_amount": 4250000.0
  }
}
```

**Note:** Only returns staff with amounts > 0

---

## 📦 Response Formats

### **JSON (Default)**

```bash
curl "https://oouthsalary.com.ng/api/v1/payroll/periods" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### **XML**

```bash
curl "https://oouthsalary.com.ng/api/v1/payroll/periods" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/xml"
```

**XML Response:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<response>
  <success>1</success>
  <data>
    <record>
      <period_id>44</period_id>
      <description>October</description>
      <year>2025</year>
    </record>
  </data>
</response>
```

### **CSV**

```bash
curl "https://oouthsalary.com.ng/api/v1/payroll/deductions/48" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: text/csv" \
  -o deductions.csv
```

Downloads a CSV file with the data.

---

## 🔔 Webhooks (Real-time Notifications)

Webhooks allow your system to receive real-time notifications when events occur.

### **Step 1: Register Your Webhook**

```bash
curl -X POST https://oouthsalary.com.ng/api/v1/webhooks/register \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_KEY" \
  -d '{
    "name": "Pension System Webhook",
    "url": "https://your-system.com/oouth/webhook",
    "events": [
      "payroll.processed",
      "payroll.period.activated"
    ]
  }'
```

**Response:**

```json
{
  "success": true,
  "data": {
    "webhook_id": 1,
    "name": "Pension System Webhook",
    "url": "https://your-system.com/oouth/webhook",
    "events": ["payroll.processed", "payroll.period.activated"],
    "secret": "webhook_secret_64_chars...",
    "message": "Webhook registered successfully..."
  }
}
```

**⚠️ Save the webhook secret!** You need it to verify webhook signatures.

---

### **Step 2: Implement Webhook Receiver**

Create an endpoint at your registered URL that:

1. Receives POST requests
2. Verifies HMAC signature
3. Processes the event
4. Returns HTTP 200

**PHP Example:**

```php
<?php
// https://your-system.com/oouth/webhook

$webhookSecret = 'your_webhook_secret_from_registration';
$payload = file_get_contents('php://input');
$receivedSignature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

// Verify signature
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (hash_equals($expectedSignature, $receivedSignature)) {
    // Authentic webhook
    $data = json_decode($payload, true);

    switch ($data['event']) {
        case 'payroll.processed':
            // Payroll has been processed
            $periodId = $data['data']['period_id'];
            $description = $data['data']['description'];

            // Fetch updated data from API
            fetchPensionData($periodId);
            break;

        case 'payroll.period.activated':
            // New period activated
            $periodId = $data['data']['period_id'];
            // Prepare for upcoming data
            break;
    }

    // Respond with success
    http_response_code(200);
    echo json_encode(['status' => 'received']);
} else {
    // Invalid signature
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
}
```

---

### **Available Webhook Events:**

| Event                      | When It Fires                | Use Case                     |
| -------------------------- | ---------------------------- | ---------------------------- |
| `payroll.processed`        | Payroll processing completes | Fetch latest deduction data  |
| `payroll.period.activated` | New period is activated      | Prepare for incoming data    |
| `employee.added`           | New employee added           | Update your employee records |
| `allowance.updated`        | Allowance values changed     | Sync updated values          |
| `deduction.updated`        | Deduction values changed     | Sync updated values          |

**Webhook Payload Example:**

```json
{
  "event": "payroll.processed",
  "timestamp": "2025-10-08T16:30:00+01:00",
  "organization_id": "005",
  "data": {
    "period_id": 44,
    "description": "October",
    "year": "2025",
    "processed_at": "2025-10-08T16:30:00+01:00",
    "processed_by": "Admin User"
  }
}
```

---

## 💼 Complete Integration Workflow

### **Scenario: Pension Administrator**

#### **1. Initial Setup (One-time)**

```bash
# 1a. Get JWT token
TOKEN=$(curl -s -X POST https://oouthsalary.com.ng/api/v1/auth/token \
  -H "X-API-Key: YOUR_KEY" | jq -r '.data.access_token')

# 1b. Register webhook for payroll notifications
curl -X POST https://oouthsalary.com.ng/api/v1/webhooks/register \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_KEY" \
  -d '{
    "name": "Pension Webhook",
    "url": "https://pension-system.com/oouth/webhook",
    "events": ["payroll.processed"]
  }'
```

#### **2. Receive Webhook Notification**

When OOUTH processes payroll, your endpoint receives:

```json
{
  "event": "payroll.processed",
  "timestamp": "2025-10-31T09:00:00+01:00",
  "organization_id": "005",
  "data": {
    "period_id": 45,
    "description": "October",
    "year": "2025",
    "processed_at": "2025-10-31T09:00:00+01:00"
  }
}
```

#### **3. Fetch Updated Pension Data**

```bash
# Get fresh JWT token
TOKEN=$(curl -s -X POST https://oouthsalary.com.ng/api/v1/auth/token \
  -H "X-API-Key: YOUR_KEY" | jq -r '.data.access_token')

# Fetch pension deductions for the processed period
curl "https://oouthsalary.com.ng/api/v1/payroll/deductions/48?period=45" \
  -H "Authorization: Bearer $TOKEN" \
  -H "X-API-Key: YOUR_KEY" \
  -H "Accept: text/csv" \
  -o pension_october_2025.csv
```

#### **4. Process in Your System**

- Import CSV into your pension management system
- Calculate employer contributions
- Generate remittance schedules
- Send confirmation to OOUTH

---

## 🔢 Code Examples

### **PHP - Complete Integration Class**

```php
<?php
class OOUTHSalaryAPIClient {

    private $baseUrl = 'https://oouthsalary.com.ng/api/v1';
    private $apiKey;
    private $apiSecret;
    private $jwtToken = null;

    public function __construct($apiKey, $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    /**
     * Authenticate and get JWT token
     */
    public function authenticate() {
        $timestamp = time();
        $signatureString = $this->apiKey . $timestamp;
        $signature = hash_hmac('sha256', $signatureString, $this->apiSecret);

        $response = $this->request('POST', '/auth/token', [
            'api_key' => $this->apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature
        ], [
            'X-Timestamp' => $timestamp,
            'X-Signature' => $signature
        ]);

        if ($response['success']) {
            $this->jwtToken = $response['data']['access_token'];
            return true;
        }

        return false;
    }

    /**
     * Get payroll periods
     */
    public function getPeriods($page = 1, $limit = 100) {
        return $this->request('GET', "/payroll/periods?page=$page&limit=$limit");
    }

    /**
     * Get active period
     */
    public function getActivePeriod() {
        return $this->request('GET', '/payroll/periods/active');
    }

    /**
     * Get deduction data
     */
    public function getDeductions($deductionId, $periodId = null) {
        $url = "/payroll/deductions/$deductionId";
        if ($periodId) {
            $url .= "?period=$periodId";
        }
        return $this->request('GET', $url);
    }

    /**
     * Get allowance data
     */
    public function getAllowances($allowanceId, $periodId = null) {
        $url = "/payroll/allowances/$allowanceId";
        if ($periodId) {
            $url .= "?period=$periodId";
        }
        return $this->request('GET', $url);
    }

    /**
     * Make HTTP request
     */
    private function request($method, $endpoint, $body = null, $extraHeaders = []) {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
        ];

        if ($this->jwtToken) {
            $headers[] = 'Authorization: Bearer ' . $this->jwtToken;
        }

        $headers = array_merge($headers, array_map(
            fn($k, $v) => "$k: $v",
            array_keys($extraHeaders),
            $extraHeaders
        ));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($body) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return json_decode($response, true);
    }
}

// Usage Example:
$client = new OOUTHSalaryAPIClient(
    'oouth_005_deduc_48_a1b2c3d4e5f6g7h8',
    'your_64_char_secret_here'
);

// Authenticate
if ($client->authenticate()) {
    echo "✅ Authenticated successfully!\n";

    // Get active period
    $activePeriod = $client->getActivePeriod();
    $periodId = $activePeriod['data']['period']['period_id'];

    // Get pension deductions
    $deductions = $client->getDeductions(48, $periodId);

    foreach ($deductions['data'] as $staff) {
        echo "{$staff['staff_id']}: {$staff['name']} - ₦{$staff['amount']}\n";
    }

    echo "\nTotal: ₦" . number_format($deductions['metadata']['total_amount']) . "\n";
    echo "Records: " . $deductions['metadata']['total_records'] . "\n";
} else {
    echo "❌ Authentication failed\n";
}
```

---

### **Node.js - Complete Integration Class**

```javascript
const crypto = require("crypto");
const https = require("https");

class OOUTHSalaryAPIClient {
  constructor(apiKey, apiSecret) {
    this.baseUrl = "https://oouthsalary.com.ng/api/v1";
    this.apiKey = apiKey;
    this.apiSecret = apiSecret;
    this.jwtToken = null;
  }

  // Authenticate and get JWT token
  async authenticate() {
    const timestamp = Math.floor(Date.now() / 1000);
    const signatureString = this.apiKey + timestamp;
    const signature = crypto
      .createHmac("sha256", this.apiSecret)
      .update(signatureString)
      .digest("hex");

    const response = await this.request(
      "POST",
      "/auth/token",
      {
        api_key: this.apiKey,
        timestamp: timestamp,
        signature: signature,
      },
      {
        "X-Timestamp": timestamp,
        "X-Signature": signature,
      }
    );

    if (response.success) {
      this.jwtToken = response.data.access_token;
      return true;
    }

    return false;
  }

  // Get payroll periods
  async getPeriods(page = 1, limit = 100) {
    return await this.request(
      "GET",
      `/payroll/periods?page=${page}&limit=${limit}`
    );
  }

  // Get active period
  async getActivePeriod() {
    return await this.request("GET", "/payroll/periods/active");
  }

  // Get deduction data
  async getDeductions(deductionId, periodId = null) {
    let url = `/payroll/deductions/${deductionId}`;
    if (periodId) url += `?period=${periodId}`;
    return await this.request("GET", url);
  }

  // Make HTTP request
  async request(method, endpoint, body = null, extraHeaders = {}) {
    const url = new URL(endpoint, this.baseUrl);

    const headers = {
      "Content-Type": "application/json",
      "X-API-Key": this.apiKey,
      ...extraHeaders,
    };

    if (this.jwtToken) {
      headers["Authorization"] = `Bearer ${this.jwtToken}`;
    }

    const options = {
      method,
      headers,
    };

    if (body) {
      options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    return await response.json();
  }
}

// Usage Example:
const client = new OOUTHSalaryAPIClient(
  "oouth_005_deduc_48_a1b2c3d4e5f6g7h8",
  "your_64_char_secret_here"
);

// Authenticate and fetch data
async function fetchPensionData() {
  if (await client.authenticate()) {
    console.log("✅ Authenticated successfully!");

    const activePeriod = await client.getActivePeriod();
    const periodId = activePeriod.data.period.period_id;

    const deductions = await client.getDeductions(48, periodId);

    console.log(
      `\nPension Deductions for ${deductions.metadata.period.description} ${deductions.metadata.period.year}:`
    );
    console.log(
      `Total Amount: ₦${deductions.metadata.total_amount.toLocaleString()}`
    );
    console.log(`Total Staff: ${deductions.metadata.total_records}`);

    deductions.data.forEach((staff) => {
      console.log(`${staff.staff_id}: ${staff.name} - ₦${staff.amount}`);
    });
  } else {
    console.log("❌ Authentication failed");
  }
}

fetchPensionData();
```

---

### **Python - Complete Integration Class**

```python
import hmac
import hashlib
import time
import requests
import json

class OOUTHSalaryAPIClient:
    def __init__(self, api_key, api_secret):
        self.base_url = 'https://oouthsalary.com.ng/api/v1'
        self.api_key = api_key
        self.api_secret = api_secret
        self.jwt_token = None

    def authenticate(self):
        """Authenticate and get JWT token"""
        timestamp = int(time.time())
        signature_string = self.api_key + str(timestamp)
        signature = hmac.new(
            self.api_secret.encode('utf-8'),
            signature_string.encode('utf-8'),
            hashlib.sha256
        ).hexdigest()

        response = self._request('POST', '/auth/token', {
            'api_key': self.api_key,
            'timestamp': timestamp,
            'signature': signature
        }, {
            'X-Timestamp': str(timestamp),
            'X-Signature': signature
        })

        if response.get('success'):
            self.jwt_token = response['data']['access_token']
            return True

        return False

    def get_periods(self, page=1, limit=100):
        """Get payroll periods"""
        return self._request('GET', f'/payroll/periods?page={page}&limit={limit}')

    def get_active_period(self):
        """Get current active period"""
        return self._request('GET', '/payroll/periods/active')

    def get_deductions(self, deduction_id, period_id=None):
        """Get deduction data"""
        url = f'/payroll/deductions/{deduction_id}'
        if period_id:
            url += f'?period={period_id}'
        return self._request('GET', url)

    def _request(self, method, endpoint, body=None, extra_headers=None):
        """Make HTTP request"""
        url = self.base_url + endpoint

        headers = {
            'Content-Type': 'application/json',
            'X-API-Key': self.api_key
        }

        if self.jwt_token:
            headers['Authorization'] = f'Bearer {self.jwt_token}'

        if extra_headers:
            headers.update(extra_headers)

        if method == 'GET':
            response = requests.get(url, headers=headers)
        else:
            response = requests.post(url, json=body, headers=headers)

        return response.json()


# Usage Example:
client = OOUTHSalaryAPIClient(
    'oouth_005_deduc_48_a1b2c3d4e5f6g7h8',
    'your_64_char_secret_here'
)

# Authenticate and fetch data
if client.authenticate():
    print('✅ Authenticated successfully!')

    # Get active period
    active_period = client.get_active_period()
    period_id = active_period['data']['period']['period_id']

    # Get pension deductions
    deductions = client.get_deductions(48, period_id)

    print(f"\nPension Deductions for {deductions['metadata']['period']['description']} {deductions['metadata']['period']['year']}:")
    print(f"Total Amount: ₦{deductions['metadata']['total_amount']:,}")
    print(f"Total Staff: {deductions['metadata']['total_records']}")

    for staff in deductions['data']:
        print(f"{staff['staff_id']}: {staff['name']} - ₦{staff['amount']:,}")
else:
    print('❌ Authentication failed')
```

---

## ⚠️ Error Handling

### **Common Errors:**

| Error Code            | HTTP | Meaning                       | Solution                                   |
| --------------------- | ---- | ----------------------------- | ------------------------------------------ |
| `INVALID_API_KEY`     | 401  | API key not found or inactive | Check your key, contact admin              |
| `EXPIRED_API_KEY`     | 401  | API key has expired           | Request new key from admin                 |
| `TOKEN_EXPIRED`       | 401  | JWT token expired             | Get new token or refresh                   |
| `RATE_LIMIT_EXCEEDED` | 429  | Too many requests             | Wait and retry (check headers)             |
| `FORBIDDEN`           | 403  | No access to this resource    | You can only access your assigned resource |
| `NOT_FOUND`           | 404  | Resource not found            | Check endpoint URL and IDs                 |
| `INVALID_SIGNATURE`   | 401  | HMAC signature mismatch       | Check secret and calculation               |

### **Error Response Format:**

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Please try again later.",
    "details": "Retry after 45 seconds",
    "request_id": "req_abc123",
    "timestamp": "2025-10-08T16:30:00+01:00"
  }
}
```

### **Best Practice Error Handling:**

```php
$response = $client->getDeductions(48, 44);

if (!$response['success']) {
    $error = $response['error'];

    switch ($error['code']) {
        case 'TOKEN_EXPIRED':
            // Re-authenticate
            $client->authenticate();
            $response = $client->getDeductions(48, 44);
            break;

        case 'RATE_LIMIT_EXCEEDED':
            // Wait and retry
            sleep(60);
            $response = $client->getDeductions(48, 44);
            break;

        default:
            // Log error
            error_log("API Error: {$error['code']} - {$error['message']}");
    }
}
```

---

## ⚡ Rate Limits

### **Limits:**

- **Per API Key:** 100 requests per minute
- **Per Organization:** 500 requests per minute (all keys combined)

### **Rate Limit Headers:**

Every response includes:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1696780860
```

### **Handling Rate Limits:**

```php
$response = $client->getPeriods();
$headers = $response['headers'] ?? [];

if (isset($headers['X-RateLimit-Remaining'])) {
    $remaining = $headers['X-RateLimit-Remaining'];

    if ($remaining < 10) {
        // Approaching limit, slow down
        sleep(1);
    }

    if ($remaining == 0) {
        // Hit limit, wait for reset
        $resetTime = $headers['X-RateLimit-Reset'];
        $waitSeconds = $resetTime - time();
        sleep($waitSeconds);
    }
}
```

---

## 🧪 Testing & Debugging

### **1. Use the Web Tester**

https://oouthsalary.com.ng/api_test.php

- No coding required
- Visual interface
- Test all endpoints
- See responses in real-time

### **2. Check System Status**

https://oouthsalary.com.ng/api_diagnostic.php

- Verify API is operational
- Check your API key status
- View configuration

### **3. Generate Signatures**

https://oouthsalary.com.ng/generate_signature.php

- Calculate HMAC signatures
- Get ready-to-use cURL commands
- Test authentication

### **4. Monitor Your Usage**

Contact OOUTH admin to access:

- Request logs for your organization
- Rate limit usage
- Error rates
- Performance metrics

---

## 📋 Best Practices

### **Security:**

1. ✅ Store API secret in environment variables, never in code
2. ✅ Use HTTPS only (enforced by API)
3. ✅ Implement signature verification for webhooks
4. ✅ Rotate API keys periodically (request from admin)
5. ✅ Use IP whitelisting if available

### **Performance:**

1. ✅ Cache JWT tokens (valid for 15 minutes)
2. ✅ Use refresh tokens to avoid re-authentication
3. ✅ Respect rate limits (check headers)
4. ✅ Request only data you need (use period parameter)
5. ✅ Use webhooks instead of polling

### **Reliability:**

1. ✅ Implement retry logic with exponential backoff
2. ✅ Log all API interactions
3. ✅ Monitor error rates
4. ✅ Have fallback mechanisms
5. ✅ Validate responses before processing

### **Data Handling:**

1. ✅ Store data securely (encryption at rest)
2. ✅ Comply with data protection regulations
3. ✅ Implement audit trails
4. ✅ Handle PII appropriately
5. ✅ Delete data when no longer needed

---

## 🔄 Typical Workflows

### **Monthly Data Sync (Automated)**

```php
// Run this script monthly via cron

$client = new OOUTHSalaryAPIClient($apiKey, $apiSecret);

// 1. Authenticate
$client->authenticate();

// 2. Get active period
$period = $client->getActivePeriod();
$periodId = $period['data']['period']['period_id'];

// 3. Fetch deduction data
$deductions = $client->getDeductions(48, $periodId);

// 4. Save to your database
foreach ($deductions['data'] as $staff) {
    saveToDatabase([
        'staff_id' => $staff['staff_id'],
        'name' => $staff['name'],
        'amount' => $staff['amount'],
        'period' => $periodId,
        'synced_at' => date('Y-m-d H:i:s')
    ]);
}

// 5. Generate reports
generateMonthlyReport($periodId);

echo "✅ Sync completed: {$deductions['metadata']['total_records']} records\n";
```

---

### **Real-time Sync (Webhook-Based)**

```php
// Your webhook receiver endpoint

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];

// Verify signature
if (verifyWebhookSignature($payload, $signature, $webhookSecret)) {
    $data = json_decode($payload, true);

    if ($data['event'] === 'payroll.processed') {
        // Payroll just processed, fetch fresh data
        $periodId = $data['data']['period_id'];

        $client = new OOUTHSalaryAPIClient($apiKey, $apiSecret);
        $client->authenticate();
        $deductions = $client->getDeductions(48, $periodId);

        // Process immediately
        processNewPayrollData($deductions);

        http_response_code(200);
        echo json_encode(['status' => 'processed']);
    }
}
```

---

## 📞 Support

### **Technical Support:**

- **Email:** api-support@oouth.edu.ng
- **Phone:** [Contact Number]
- **Hours:** Monday-Friday, 8:00 AM - 5:00 PM WAT

### **Resources:**

- **API Documentation:** https://oouthsalary.com.ng/api/README.md
- **Webhook Guide:** https://oouthsalary.com.ng/api/WEBHOOK_GUIDE.md
- **Web Tester:** https://oouthsalary.com.ng/api_test.php
- **Signature Generator:** https://oouthsalary.com.ng/generate_signature.php

### **Issues to Report:**

- API downtime or errors
- Rate limit concerns
- Data discrepancies
- Performance issues
- Security concerns

---

## 📝 Checklist for Go-Live

Before integrating into production:

- [ ] Received API key and secret from OOUTH
- [ ] Tested authentication successfully
- [ ] Verified you can fetch data
- [ ] Implemented error handling
- [ ] Set up webhook receiver (if using webhooks)
- [ ] Tested webhook delivery
- [ ] Implemented signature verification
- [ ] Set up monitoring and logging
- [ ] Documented your integration
- [ ] Tested with production-like data volumes
- [ ] Have rollback plan ready

---

## 🎯 Quick Start Summary

1. **Get Credentials** → Contact OOUTH admin
2. **Authenticate** → POST /auth/token
3. **Get Active Period** → GET /payroll/periods/active
4. **Fetch Your Data** → GET /payroll/deductions/{id} or /allowances/{id}
5. **Set Up Webhooks** → POST /webhooks/register (optional but recommended)
6. **Monitor** → Check logs and rate limits

---

## 📊 Sample Response Times

- Authentication: ~200ms
- Get Periods: ~150ms
- Get Deduction Data (1000 records): ~500ms
- Webhook Delivery: ~100-500ms (depends on your endpoint)

---

**Welcome to the OOUTH Salary API!** 🎉

For any questions or issues, don't hesitate to contact our support team.

---

**Document Version:** 1.0.0  
**Last Updated:** October 8, 2025  
**API Version:** v1
