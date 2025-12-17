# 🔔 OOUTH Salary Webhook Guide

Real-time notifications for payroll events.

---

## 📋 Overview

Webhooks allow third-party systems to receive real-time notifications when specific events occur in the OOUTH Salary system.

---

## 🎯 Available Events

| Event                      | Description       | Triggered When                   |
| -------------------------- | ----------------- | -------------------------------- |
| `payroll.period.activated` | Period activated  | Admin activates a payroll period |
| `payroll.processed`        | Payroll processed | Payroll processing completes     |
| `employee.added`           | Employee created  | New employee is added to system  |
| `payroll.period.closed`    | Period closed     | Payroll period is closed         |
| `allowance.updated`        | Allowance changed | Allowance values are modified    |
| `deduction.updated`        | Deduction changed | Deduction values are modified    |
| `employee.removed`         | Employee deleted  | Employee is removed from system  |

---

## 🔗 Registering a Webhook

### **Endpoint:**

```
POST /api/v1/webhooks/register
```

### **Request:**

```bash
curl -X POST https://oouthsalary.com.ng/api/v1/webhooks/register \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "name": "Pension Administrator Webhook",
    "url": "https://vendor-system.com/oouth/webhook",
    "events": [
      "payroll.processed",
      "payroll.period.activated"
    ],
    "retry_count": 3
  }'
```

### **Response:**

```json
{
  "success": true,
  "data": {
    "webhook_id": 1,
    "name": "Pension Administrator Webhook",
    "url": "https://vendor-system.com/oouth/webhook",
    "events": ["payroll.processed", "payroll.period.activated"],
    "secret": "a1b2c3d4e5f6...64-char-secret",
    "message": "Webhook registered successfully. Save the secret for signature verification."
  }
}
```

**⚠️ IMPORTANT:** Save the `secret` - it won't be shown again!

---

## 📨 Webhook Payload Format

When an event occurs, we'll POST to your URL:

### **Headers:**

```
Content-Type: application/json
X-Webhook-Signature: hmac_sha256_signature
X-Webhook-Event: payroll.processed
X-Webhook-Delivery: 1
User-Agent: OOUTH-Salary-Webhook/1.0
```

### **Payload:**

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

## 🔐 Verifying Webhook Signatures

To verify the webhook is authentic:

### **PHP Example:**

```php
<?php
$webhookSecret = 'your_webhook_secret_here';
$payload = file_get_contents('php://input');
$receivedSignature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

// Calculate expected signature
$expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

// Verify signature
if (hash_equals($expectedSignature, $receivedSignature)) {
    // Webhook is authentic
    $data = json_decode($payload, true);

    // Process the event
    switch ($data['event']) {
        case 'payroll.processed':
            // Handle payroll processed event
            break;
        case 'employee.added':
            // Handle employee added event
            break;
    }

    // Return 200 OK
    http_response_code(200);
    echo json_encode(['status' => 'received']);
} else {
    // Invalid signature
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
}
```

### **Node.js Example:**

```javascript
const crypto = require("crypto");

app.post("/webhook", (req, res) => {
  const webhookSecret = "your_webhook_secret_here";
  const receivedSignature = req.headers["x-webhook-signature"];
  const payload = JSON.stringify(req.body);

  // Calculate expected signature
  const expectedSignature = crypto
    .createHmac("sha256", webhookSecret)
    .update(payload)
    .digest("hex");

  // Verify signature
  if (receivedSignature === expectedSignature) {
    // Webhook is authentic
    const { event, data } = req.body;

    // Process event
    console.log(`Received ${event}:`, data);

    res.status(200).json({ status: "received" });
  } else {
    res.status(401).json({ error: "Invalid signature" });
  }
});
```

---

## 📊 Event Payload Examples

### **payroll.processed**

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

### **payroll.period.activated**

```json
{
  "event": "payroll.period.activated",
  "timestamp": "2025-10-08T10:00:00+01:00",
  "organization_id": "005",
  "data": {
    "period_id": 45,
    "description": "November",
    "year": "2025",
    "activated_at": "2025-10-08T10:00:00+01:00",
    "activated_by": "Admin User"
  }
}
```

### **employee.added**

```json
{
  "event": "employee.added",
  "timestamp": "2025-10-08T14:00:00+01:00",
  "organization_id": "005",
  "data": {
    "staff_id": "EMP12345",
    "name": "John Doe",
    "email": "john.doe@oouth.edu.ng",
    "department": "Finance",
    "added_at": "2025-10-08T14:00:00+01:00",
    "added_by": "HR Admin"
  }
}
```

---

## 🔄 Retry Mechanism

If your endpoint fails (returns non-2xx status code), we'll retry:

- **Retry 1:** Immediately after failure
- **Retry 2:** 2 seconds later
- **Retry 3:** 4 seconds after retry 2
- **Retry 4:** 8 seconds after retry 3 (if retry_count = 4)

**Total retries:** Configurable (default 3)

### **Delivery Tracking:**

Every delivery attempt is logged with:

- HTTP status code
- Response body
- Error message (if any)
- Retry attempt number
- Timestamp

---

## 📡 Managing Webhooks

### **List Your Webhooks**

```bash
GET /api/v1/webhooks
Authorization: Bearer YOUR_JWT_TOKEN
X-API-Key: YOUR_API_KEY
```

### **Get Webhook Details**

```bash
GET /api/v1/webhooks/{webhook_id}
```

### **Update Webhook**

```bash
PUT /api/v1/webhooks/{webhook_id}
Content-Type: application/json

{
  "url": "https://new-url.com/webhook",
  "events": ["payroll.processed"],
  "is_active": 1
}
```

### **Delete Webhook**

```bash
DELETE /api/v1/webhooks/{webhook_id}
```

### **Test Webhook Delivery**

```bash
POST /api/v1/webhooks/{webhook_id}/test
```

Sends a test payload to verify your endpoint is working.

---

## 🧪 Testing Your Webhook

### **Method 1: Use RequestBin**

1. Go to https://requestbin.com/
2. Create a new bin
3. Copy the bin URL
4. Register webhook with that URL
5. Trigger an event (process payroll, add employee)
6. View the payload in RequestBin

### **Method 2: Local Testing with ngrok**

```bash
# Start local server
php -S localhost:8000 webhook_receiver.php

# Expose to internet with ngrok
ngrok http 8000

# Use ngrok URL to register webhook
https://abc123.ngrok.io/webhook
```

### **Method 3: Use API Test Endpoint**

```bash
POST /api/v1/webhooks/{id}/test
```

This sends a test event to your webhook URL.

---

## 📊 Monitoring Webhooks

### **View Delivery Logs in Dashboard**

1. Go to `/api_management.php`
2. Click "Webhooks" tab
3. View delivery statistics:
   - Total deliveries
   - Failed deliveries
   - Success rate
   - Last delivery time

### **Check Delivery Logs (SQL)**

```sql
SELECT
    wl.event_type,
    wl.delivery_status,
    wl.response_code,
    wl.retry_attempt,
    wl.delivered_at,
    wl.error_message
FROM api_webhook_logs wl
JOIN api_webhooks w ON wl.webhook_id = w.webhook_id
WHERE w.webhook_id = 1
ORDER BY wl.created_at DESC
LIMIT 20;
```

---

## ⚠️ Best Practices

### **Your Webhook Endpoint Should:**

1. **Respond Quickly** (< 5 seconds)
2. **Return 2xx Status** to indicate success
3. **Verify Signature** before processing
4. **Be Idempotent** (handle duplicate deliveries)
5. **Log Received Events** for debugging

### **Example Endpoint:**

```php
<?php
// webhook_receiver.php

// 1. Verify signature
$secret = 'your_webhook_secret';
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';

if (hash_equals(hash_hmac('sha256', $payload, $secret), $signature)) {

    // 2. Parse payload
    $data = json_decode($payload, true);

    // 3. Process event (queue for background processing)
    file_put_contents('webhook_queue.json', $payload . PHP_EOL, FILE_APPEND);

    // 4. Return success immediately
    http_response_code(200);
    echo json_encode(['status' => 'queued']);

} else {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
}
```

---

## 🔧 Troubleshooting

### **Webhook Not Firing:**

- Check webhook is `is_active = 1`
- Verify event is in subscribed events list
- Check payroll process completed successfully
- Review delivery logs for errors

### **Signature Verification Fails:**

- Ensure you're using the exact webhook secret
- Verify you're calculating HMAC-SHA256 correctly
- Check you're using raw request body (not parsed JSON)
- Use `hash_equals()` to prevent timing attacks

### **Delivery Failures:**

- Check your endpoint URL is accessible
- Verify endpoint returns 2xx status code
- Check firewall/security settings
- Review webhook logs for error details
- Ensure endpoint responds within timeout (30s)

---

## 📈 Webhook Statistics

### **View in Dashboard:**

- Total deliveries
- Failed deliveries
- Success rate percentage
- Last delivery time
- Last delivery status

### **Query Database:**

```sql
-- Webhook success rate
SELECT
    w.webhook_name,
    w.total_deliveries,
    w.failed_deliveries,
    ROUND((w.total_deliveries - w.failed_deliveries) / w.total_deliveries * 100, 2) as success_rate
FROM api_webhooks w
WHERE w.org_id = 5;
```

---

## 🚀 Quick Start

1. **Generate API key** via dashboard
2. **Authenticate** to get JWT token
3. **Register webhook** with your URL
4. **Save the secret** provided
5. **Implement endpoint** to receive webhooks
6. **Verify signature** in your endpoint
7. **Test delivery** using test endpoint
8. **Monitor** via dashboard

---

## 📞 Support

For webhook issues:

- Check delivery logs in dashboard
- Review webhook documentation
- Test with `/webhooks/{id}/test` endpoint
- Contact: api-support@oouth.edu.ng

---

**Webhooks enable real-time integration with your systems!** 🎉
