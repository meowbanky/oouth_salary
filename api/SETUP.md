# OOUTH Salary API - Setup Instructions

## 🚀 Quick Setup Guide

Follow these steps to get the API system up and running.

---

## Step 1: Import Database Schema

Run the SQL file to create all required API tables:

```bash
mysql -u oouthsal_root -p oouthsal_salary3 < api/schema/api_tables.sql
```

Or import via phpMyAdmin:

1. Open phpMyAdmin
2. Select database: `oouthsal_salary3`
3. Click "Import" tab
4. Choose file: `api/schema/api_tables.sql`
5. Click "Go"

**Tables Created:**

- `api_organizations` - Organization management
- `api_keys` - API key storage
- `api_jwt_tokens` - JWT token tracking
- `api_rate_limits` - Rate limiting data
- `api_request_logs` - Request audit logs
- `api_webhooks` - Webhook registrations
- `api_webhook_logs` - Webhook delivery logs
- `api_security_alerts` - Security event tracking

---

## Step 2: Verify Database Connection

The API uses the existing database connection from `Connections/paymaster.php`:

```php
Database: oouthsal_salary3
Username: oouthsal_root
Password: [Use environment variable DB_PASSWORD]
Host: localhost
```

**⚠️ SECURITY NOTE:** Database credentials should be loaded from environment variables, not hardcoded. See `.env.example` for configuration.

✅ **Already configured!** No changes needed.

---

## Step 3: Configure API Settings (Optional)

Edit `api/config/api_config.php` if you need to customize:

```php
// JWT Secret Key (change in production!)
define('JWT_SECRET_KEY', 'your_secure_secret_key_here');

// Rate Limits
define('DEFAULT_ORG_RATE_LIMIT', 500); // requests per minute per org
define('DEFAULT_KEY_RATE_LIMIT', 100); // requests per minute per key

// Security
define('REQUIRE_HTTPS', true); // Set to true in production
define('REQUIRE_SIGNATURE', true); // HMAC signature verification
define('ENABLE_IP_WHITELIST', true); // IP-based access control
```

---

## Step 4: Test API Access

### Test 1: Check API is Accessible

Open in browser or use curl:

```bash
curl http://localhost/oouthsalary/api/v1/
```

Expected response:

```json
{
  "name": "OOUTH Salary API",
  "version": "v1",
  "status": "active",
  "documentation": "http://localhost/oouthsalary/api/v1/docs",
  "endpoints": { ... }
}
```

---

## Step 5: Create Your First Organization

1. Login to admin dashboard: `http://localhost/oouthsalary/`
2. Navigate to: `http://localhost/oouthsalary/api_management.php`
3. Click "New Organization" button
4. Fill in details:
   - Organization Name: e.g., "Finance Department"
   - Organization Code: e.g., "FIN_DEPT"
   - Contact Email: Your email
   - Rate Limit: 500 (default)
5. Click "Save"

**Note:** The database already has 2 sample organizations:

- OOUTH Internal (org_id: 1)
- Sample Vendor (org_id: 2, inactive)

---

## Step 6: Generate API Key

### Method 1: Via Admin Dashboard (Recommended)

1. Go to API Management page
2. Click "Generate API Key" button
3. Select:
   - Organization
   - Allowance or Deduction (e.g., Housing Allowance)
   - Type (Allowance = 1, Deduction = 2)
4. Set expiration date (optional)
5. Click "Generate"

### Method 2: Direct Database Insert (for testing)

```sql
-- Example: Generate key for Housing Allowance (ed_id=5) for OOUTH Internal (org_id=1)
INSERT INTO api_keys (
    api_key,
    api_secret,
    org_id,
    ed_id,
    ed_type,
    ed_name,
    is_active,
    rate_limit_per_min,
    created_by
) VALUES (
    'oouth_001_allow_5_a8f3c9d2e1b4f6e7',
    SHA2('your_secret_key_here', 256),
    1,
    5,
    1,
    'Housing Allowance',
    1,
    100,
    1
);
```

**API Key Format:**

```
oouth_{org_id:3digits}_{allow|deduc}_{ed_id}_{16char_hash}

Examples:
- oouth_001_allow_5_a8f3c9d2e1b4f6e7  (Allowance)
- oouth_001_deduc_12_b9c4d3e2f5g8h1i2 (Deduction)
```

---

## Step 7: Test Authentication

### Generate JWT Token

```bash
curl -X POST http://localhost/oouthsalary/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "oouth_001_allow_5_a8f3c9d2e1b4f6e7",
    "timestamp": 1696780800,
    "signature": "calculated_hmac_signature"
  }'
```

**Note:** For testing, you can temporarily disable signature verification:
Edit `api/config/api_config.php`:

```php
define('REQUIRE_SIGNATURE', false); // Only for testing!
```

Then test without signature:

```bash
curl -X POST http://localhost/oouthsalary/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -H "X-API-Key: oouth_001_allow_5_a8f3c9d2e1b4f6e7"
```

Expected response:

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

---

## Step 8: Make API Request

### Get Payroll Periods

```bash
curl http://localhost/oouthsalary/api/v1/payroll/periods \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-API-Key: oouth_001_allow_5_a8f3c9d2e1b4f6e7"
```

### Get Allowance Data

```bash
curl http://localhost/oouthsalary/api/v1/payroll/allowances/5?period=44 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-API-Key: oouth_001_allow_5_a8f3c9d2e1b4f6e7"
```

Response:

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

---

## Step 9: Setup Webhooks (Optional)

Webhooks allow real-time notifications when events occur.

### Register a Webhook

```bash
curl -X POST http://localhost/oouthsalary/api/v1/webhooks/register \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Payroll Period Activated",
    "url": "https://vendor-system.com/webhook/payroll",
    "events": ["payroll.period.activated", "payroll.processed"],
    "secret": "webhook_secret_for_verification"
  }'
```

### Available Events:

- `payroll.period.activated`
- `payroll.period.closed`
- `payroll.processed`
- `allowance.updated`
- `deduction.updated`
- `employee.added`
- `employee.removed`

---

## Step 10: Monitor API Usage

### Access Admin Dashboard

Visit: `http://localhost/oouthsalary/api_management.php`

**Features:**

- View API key statistics
- Monitor request logs
- Check rate limit usage
- Review security alerts
- Manage webhooks
- View analytics

### Check Request Logs

```sql
-- View recent API requests
SELECT
    request_id,
    endpoint,
    method,
    response_status,
    response_time_ms,
    ip_address,
    request_timestamp
FROM api_request_logs
ORDER BY request_timestamp DESC
LIMIT 50;
```

### Check Rate Limit Status

```sql
-- View current rate limits
SELECT
    ak.api_key,
    ao.org_name,
    rl.request_count,
    rl.window_start,
    rl.window_end
FROM api_rate_limits rl
JOIN api_keys ak ON rl.api_key = ak.api_key
JOIN api_organizations ao ON ak.org_id = ao.org_id
WHERE rl.window_end > NOW();
```

---

## 🔧 Troubleshooting

### Issue: "Database connection failed"

- Check `Connections/paymaster.php` credentials
- Verify MySQL service is running
- Ensure database `oouthsal_salary3` exists

### Issue: "API key invalid"

- Check API key format is correct
- Verify key exists in `api_keys` table
- Ensure key `is_active = 1`
- Check organization `is_active = 1`

### Issue: "Rate limit exceeded"

- Wait 60 seconds for window to reset
- Check current usage in `api_rate_limits` table
- Increase limit in `api_keys.rate_limit_per_min`

### Issue: "HTTPS required"

- Temporarily disable: `define('REQUIRE_HTTPS', false);`
- Or setup SSL certificate for production

### Issue: "Invalid signature"

- Temporarily disable: `define('REQUIRE_SIGNATURE', false);`
- Or implement proper HMAC signing in client

---

## 🔒 Security Checklist (Production)

Before deploying to production:

- [ ] Change JWT secret key in `api_config.php`
- [ ] Enable HTTPS: `define('REQUIRE_HTTPS', true);`
- [ ] Enable signature verification: `define('REQUIRE_SIGNATURE', true);`
- [ ] Remove test/sample API keys from database
- [ ] Set up proper SSL certificate
- [ ] Configure IP whitelisting for sensitive keys
- [ ] Set up automated log cleanup (cron job)
- [ ] Review and set appropriate rate limits
- [ ] Enable error logging, disable display errors
- [ ] Set up monitoring and alerting
- [ ] Document all API keys and their purposes
- [ ] Create backup strategy for API logs

---

## 📚 Additional Resources

- **Full Documentation:** `api/README.md`
- **API Configuration:** `api/config/api_config.php`
- **Database Schema:** `api/schema/api_tables.sql`
- **Admin Dashboard:** `/api_management.php`

---

## 🆘 Support

For technical support or questions:

- Email: api-support@oouth.edu.ng
- Documentation: Check `api/README.md`
- Admin Dashboard: View logs and alerts

---

**Setup Complete!** 🎉

Your API is now ready to accept requests from third-party vendors and integrations.
