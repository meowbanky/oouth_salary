# OOUTH Salary Management System API

Version: 1.0.0  
Multi-tenant REST API with JWT authentication and webhook support

## 📋 Overview

The OOUTH Salary API provides secure, rate-limited access to payroll data for third-party integrations. Each API key is scoped to a specific allowance or deduction, ensuring data isolation and security.

## 🔐 Security Features

- **Multi-layered Authentication**: API Key + JWT Token
- **HMAC Request Signing**: Prevents tampering and replay attacks
- **Rate Limiting**: 100 requests/min per key, 500/min per organization
- **IP Whitelisting**: Optional IP-based access control
- **Audit Logging**: Complete request/response logging
- **Multi-tenant Isolation**: Organization-level data segregation
- **HTTPS Only**: SSL/TLS 1.3 required in production

## 🚀 Quick Start

### 1. Installation

Import the database schema:

```bash
mysql -u username -p database_name < api/schema/api_tables.sql
```

### 2. Generate API Key (Admin Only)

API keys are generated through the admin dashboard:

```
Format: oouth_{org_id}_{allow|deduc}_{ed_id}_{16-char-hash}
Example: oouth_001_allow_5_a8f3c9d2e1b4f6e7
```

### 3. Obtain JWT Token

```bash
POST /api/v1/auth/token
Content-Type: application/json

{
  "api_key": "oouth_001_allow_5_a8f3c9d2e1b4f6e7",
  "timestamp": 1696780800,
  "signature": "hmac_sha256_signature_here"
}
```

Response:

```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGc...",
    "refresh_token": "refresh_token_here",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

### 4. Make API Requests

```bash
GET /api/v1/payroll/allowances/5?period=44
Authorization: Bearer eyJhbGc...
X-API-Key: oouth_001_allow_5_a8f3c9d2e1b4f6e7
```

## 📡 API Endpoints

### Authentication

| Endpoint               | Method | Description        |
| ---------------------- | ------ | ------------------ |
| `/api/v1/auth/token`   | POST   | Generate JWT token |
| `/api/v1/auth/refresh` | POST   | Refresh JWT token  |
| `/api/v1/auth/revoke`  | POST   | Revoke JWT token   |

### Payroll Periods

| Endpoint                         | Method | Description               |
| -------------------------------- | ------ | ------------------------- |
| `/api/v1/payroll/periods`        | GET    | List all periods          |
| `/api/v1/payroll/periods/{id}`   | GET    | Get specific period       |
| `/api/v1/payroll/periods/active` | GET    | Get current active period |

### Allowances & Deductions

| Endpoint                          | Method | Description        |
| --------------------------------- | ------ | ------------------ |
| `/api/v1/payroll/allowances/{id}` | GET    | Get allowance data |
| `/api/v1/payroll/deductions/{id}` | GET    | Get deduction data |

Query Parameters:

- `period` - Period ID (defaults to active period)
- `page` - Page number for pagination
- `limit` - Records per page (max 1000)
- `format` - Response format (json, xml, csv)

### Webhooks

| Endpoint                     | Method | Description           |
| ---------------------------- | ------ | --------------------- |
| `/api/v1/webhooks`           | GET    | List webhooks         |
| `/api/v1/webhooks/register`  | POST   | Register webhook      |
| `/api/v1/webhooks/{id}`      | GET    | Get webhook details   |
| `/api/v1/webhooks/{id}`      | PUT    | Update webhook        |
| `/api/v1/webhooks/{id}`      | DELETE | Delete webhook        |
| `/api/v1/webhooks/{id}/test` | POST   | Test webhook delivery |

## 📊 Response Formats

### JSON (Default)

```json
{
  "success": true,
  "data": [
    {
      "staff_id": "EMP001",
      "name": "John Doe",
      "amount": 50000.0
    }
  ],
  "metadata": {
    "period": {
      "id": 44,
      "description": "October 2025",
      "year": 2025
    },
    "allowance_name": "Housing Allowance",
    "total_records": 150,
    "total_amount": 7500000.0
  }
}
```

### XML

Set `Accept: application/xml` header:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<response>
  <success>true</success>
  <data>
    <record>
      <staff_id>EMP001</staff_id>
      <name>John Doe</name>
      <amount>50000.00</amount>
    </record>
  </data>
</response>
```

### CSV

Set `Accept: text/csv` header to download data as CSV.

## 🔒 Authentication Flow

### 1. Request Signing (HMAC-SHA256)

```php
$apiKey = 'oouth_001_allow_5_a8f3c9d2e1b4f6e7';
$apiSecret = 'your_api_secret_here';
$timestamp = time();
$signatureString = $apiKey . $timestamp;
$signature = hash_hmac('sha256', $signatureString, $apiSecret);
```

### 2. Headers Required

```
Authorization: Bearer {jwt_token}
X-API-Key: {api_key}
X-Timestamp: {unix_timestamp}
X-Signature: {hmac_signature}
X-Request-ID: {unique_request_id}
```

## ⚡ Rate Limiting

Rate limits are enforced per API key and organization:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1696780800
```

When exceeded:

```json
{
  "success": false,
  "error": {
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "Rate limit exceeded. Please try again later.",
    "request_id": "req_a8f3c9d2e1b4",
    "timestamp": "2025-10-08T12:34:56Z"
  }
}
```

## 🪝 Webhooks

Subscribe to real-time events:

### Available Events

- `payroll.period.activated` - New period activated
- `payroll.period.closed` - Period closed
- `payroll.processed` - Payroll processing completed
- `allowance.updated` - Allowance values updated
- `deduction.updated` - Deduction values updated
- `employee.added` - New employee added
- `employee.removed` - Employee removed

### Webhook Payload

```json
{
  "event": "payroll.period.activated",
  "timestamp": "2025-10-08T12:34:56Z",
  "organization_id": "001",
  "data": {
    "period_id": 45,
    "description": "November 2025",
    "year": 2025,
    "activated_at": "2025-11-01T00:00:00Z"
  },
  "signature": "hmac_sha256_signature"
}
```

### Webhook Verification

```php
$webhookSecret = 'your_webhook_secret';
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

if (hash_equals($expectedSignature, $signature)) {
    // Webhook is authentic
}
```

## 📈 Admin Dashboard

Access API management at: `/api_management.php`

Features:

- Generate API keys
- View usage statistics
- Monitor rate limits
- Manage webhooks
- View audit logs
- Security alerts

## 🛠️ Configuration

Edit `api/config/api_config.php`:

```php
// Enable/Disable Features
define('RATE_LIMIT_ENABLED', true);
define('REQUIRE_HTTPS', true);
define('REQUIRE_SIGNATURE', true);
define('WEBHOOK_ENABLED', true);

// Rate Limits
define('DEFAULT_ORG_RATE_LIMIT', 500);
define('DEFAULT_KEY_RATE_LIMIT', 100);

// JWT Settings
define('JWT_EXPIRATION', 900); // 15 minutes
```

## 🔧 Maintenance

### Clean up old logs

```php
// Run daily via cron
php -r "require 'api/utils/logger.php'; ApiLogger::cleanup();"
php -r "require 'api/auth/jwt_handler.php'; JWTHandler::cleanupExpiredTokens();"
```

### Monitor API Health

```php
$stats = ApiLogger::getStatistics($orgId, 7); // Last 7 days
echo "Total Requests: " . $stats['total_requests'];
echo "Avg Response Time: " . $stats['avg_response_time'] . "ms";
echo "Error Rate: " . ($stats['error_count'] / $stats['total_requests'] * 100) . "%";
```

## ❌ Error Codes

| Code                  | HTTP | Description                        |
| --------------------- | ---- | ---------------------------------- |
| `INVALID_API_KEY`     | 401  | API key invalid or inactive        |
| `EXPIRED_API_KEY`     | 401  | API key has expired                |
| `INVALID_SIGNATURE`   | 401  | HMAC signature verification failed |
| `INVALID_TIMESTAMP`   | 401  | Timestamp outside acceptable range |
| `RATE_LIMIT_EXCEEDED` | 429  | Too many requests                  |
| `UNAUTHORIZED`        | 401  | Authentication failed              |
| `FORBIDDEN`           | 403  | Access denied                      |
| `NOT_FOUND`           | 404  | Resource not found                 |
| `BAD_REQUEST`         | 400  | Invalid request parameters         |
| `INTERNAL_ERROR`      | 500  | Server error                       |

## 📞 Support

For API support, contact: api-support@oouth.edu.ng

## 📄 License

Copyright © 2025 OOUTH. All rights reserved.
