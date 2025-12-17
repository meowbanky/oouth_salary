# 🧪 API Testing Guide

Complete guide to test your OOUTH Salary API key and secret.

---

## 📋 Prerequisites

1. ✅ API tables imported (`api/schema/api_tables.sql`)
2. ✅ Organization created via `/api_management.php`
3. ✅ API key generated (you should have both key and secret)
4. ✅ Testing mode enabled (see Configuration below)

---

## ⚙️ Configuration for Testing

Edit `api/config/api_config.php`:

```php
// Disable security features for testing
define('REQUIRE_HTTPS', false);     // Allow HTTP for local testing
define('REQUIRE_SIGNATURE', false); // Skip HMAC signature validation
```

**⚠️ Important:** Re-enable these in production!

---

## 🎯 Method 1: Web Interface (Easiest)

### Step 1: Open Test Page

Visit: `https://oouthsalary.com.ng/api_test.php`

### Step 2: Enter Credentials

- **API Key**: Paste your generated key (e.g., `oouth_005_deduc_48_ed7dee3ccb995727`)
- **API Secret**: Paste your secret (if using signature mode)
- **Use Signature**: Select "No (Testing Mode)"

### Step 3: Run Tests

Click the test buttons in order:

1. **Test Authentication** - Should return JWT token ✅
2. **Get Periods** - Should list payroll periods ✅
3. **Get Allowance/Deduction** - Should show staff data ✅
4. **Test XML Format** - Should return XML ✅
5. **Test Rate Limit** - Should show rate limit headers ✅

Or click **"Run All Tests"** to execute all at once.

### Step 4: Review Results

- ✅ Green = Success
- ❌ Red = Error
- ℹ️ Yellow = Info

---

## 🖥️ Method 2: Command Line (For Developers)

### Step 1: Make Script Executable

```bash
chmod +x test_api.sh
```

### Step 2: Run Tests

```bash
./test_api.sh oouth_005_deduc_48_ed7dee3ccb995727
```

### Expected Output:

```
╔════════════════════════════════════════════════════════════╗
║         OOUTH Salary API Test Script                      ║
╚════════════════════════════════════════════════════════════╝

API Key: oouth_005_deduc_48_ed7dee3ccb995727
Base URL: https://oouthsalary.com.ng/api/v1

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Test 1: Authentication
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{
  "success": true,
  "data": {
    "access_token": "eyJhbGc...",
    "refresh_token": "...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
✅ Authentication successful!

...more tests...
```

---

## 📮 Method 3: Postman/Insomnia (Best for API Development)

### Step 1: Import Collection

1. Open Postman (or Insomnia, Thunder Client)
2. Click "Import"
3. Select file: `api/docs/OOUTH_Salary_API.postman_collection.json`

### Step 2: Set Variables

1. Click on the collection
2. Go to "Variables" tab
3. Set `api_key` to your actual API key
4. `jwt_token` will be auto-filled after authentication

### Step 3: Run Requests

Execute in this order:

1. **1. Authentication → Generate JWT Token**
   - JWT token auto-saved to variable ✅
2. **2. Payroll Periods → List All Periods**
3. **2. Payroll Periods → Get Active Period**
4. **3. Allowances → Get Allowance Data**
   - Replace ID with your allowance ID
5. **4. Deductions → Get Deduction Data**
   - Replace ID with your deduction ID

---

## 🔍 Method 4: Manual cURL Testing

### Test 1: Authentication

```bash
curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -H "X-API-Key: oouth_005_deduc_48_ed7dee3ccb995727"
```

**Expected Response:**

```json
{
  "success": true,
  "data": {
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "refresh_token": "abc123...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

### Test 2: Get Periods

```bash
curl -X GET "https://oouthsalary.com.ng/api/v1/payroll/periods" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-API-Key: oouth_005_deduc_48_ed7dee3ccb995727"
```

### Test 3: Get Deduction Data (for deduction ID 48)

```bash
curl -X GET "https://oouthsalary.com.ng/api/v1/payroll/deductions/48?period=44" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "X-API-Key: oouth_005_deduc_48_ed7dee3ccb995727"
```

**Expected Response:**

```json
{
  "success": true,
  "data": [
    {
      "staff_id": "EMP001",
      "name": "John Doe",
      "amount": 5000.00
    },
    ...
  ],
  "metadata": {
    "period": {
      "id": 44,
      "description": "October 2025",
      "year": 2025
    },
    "deduction_name": "PENSION",
    "total_records": 150,
    "total_amount": 750000.00
  }
}
```

---

## ❌ Common Errors & Solutions

### Error: "Endpoint not found"

**Solution:**

- Check `.htaccess` is uploaded to `/api/` folder
- Verify mod_rewrite is enabled on server
- Check that URL is exactly: `/api/v1/auth/token`

### Error: "HTTPS is required"

**Solution:**

```php
// In api/config/api_config.php
define('REQUIRE_HTTPS', false); // For testing only
```

### Error: "Invalid signature"

**Solution:**

```php
// In api/config/api_config.php
define('REQUIRE_SIGNATURE', false); // For testing only
```

### Error: "Invalid API key"

**Solutions:**

- Verify key exists in database: `SELECT * FROM api_keys WHERE api_key = 'YOUR_KEY'`
- Check `is_active = 1`
- Check organization `is_active = 1`
- Verify key hasn't expired

### Error: "Rate limit exceeded"

**Solution:**

- Wait 60 seconds for window to reset
- Or increase limit in database:

```sql
UPDATE api_keys SET rate_limit_per_min = 1000 WHERE api_key = 'YOUR_KEY';
```

### Error: "Forbidden - API key does not have access"

**Solution:**

- Make sure you're accessing the correct resource
- If key is for deduction ID 48, use `/payroll/deductions/48`
- If key is for allowance ID 5, use `/payroll/allowances/5`

---

## 📊 Understanding Your API Key

Your API key format tells you what it can access:

```
oouth_005_deduc_48_ed7dee3ccb995727
       │    │     │          │
       │    │     │          └─ Random hash (security)
       │    │     └─ Resource ID (deduction #48)
       │    └─ Resource type (deduc = Deduction)
       └─ Organization ID (005)
```

**This key can only access:**

- ✅ Deduction ID 48
- ❌ Other deductions
- ❌ Any allowances

---

## 🎯 Test Checklist

- [ ] Authentication works (returns JWT token)
- [ ] Can list payroll periods
- [ ] Can get active period
- [ ] Can retrieve allowance/deduction data
- [ ] Rate limit headers present
- [ ] XML format works
- [ ] CSV format works
- [ ] Error responses are clear
- [ ] Token expires after 15 minutes
- [ ] Refresh token works

---

## 📈 Monitoring

### Check API Usage

```sql
-- View all requests from your API key
SELECT
    request_id,
    endpoint,
    method,
    response_status,
    response_time_ms,
    request_timestamp
FROM api_request_logs
WHERE api_key = 'YOUR_API_KEY'
ORDER BY request_timestamp DESC
LIMIT 20;
```

### Check Rate Limit Status

```sql
-- Current rate limit usage
SELECT
    request_count,
    window_start,
    window_end
FROM api_rate_limits
WHERE api_key = 'YOUR_API_KEY'
  AND window_end > NOW();
```

---

## 🔒 Production Checklist

Before going to production:

- [ ] Re-enable HTTPS: `define('REQUIRE_HTTPS', true);`
- [ ] Re-enable signatures: `define('REQUIRE_SIGNATURE', true);`
- [ ] Implement HMAC signing in client
- [ ] Set appropriate rate limits
- [ ] Configure IP whitelists if needed
- [ ] Set expiration dates on keys
- [ ] Monitor security alerts
- [ ] Set up log rotation

---

## 📞 Need Help?

- Check `api/README.md` for full documentation
- Check `api/SETUP.md` for setup instructions
- View logs in `/api_management.php` → Request Logs tab
- Check security alerts in Security Alerts tab

---

**Happy Testing!** 🚀
