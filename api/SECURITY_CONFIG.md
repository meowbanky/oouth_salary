# 🔐 API Security Configuration Guide

**OOUTH Salary API - Security Settings Management**

---

## 📋 Current Security Status

### **Testing Mode (Current)** ⚠️

```php
REQUIRE_HTTPS = false        // HTTPS enforcement disabled
REQUIRE_SIGNATURE = false    // HMAC signature disabled
ENABLE_IP_WHITELIST = false  // IP restrictions disabled
```

**Purpose:** Easy vendor testing and integration during development

**Trade-off:** Lower security, but faster onboarding

---

## 🎯 Security Layers Overview

| Layer | Feature         | Testing     | Production  | Purpose                      |
| ----- | --------------- | ----------- | ----------- | ---------------------------- |
| 1     | API Key Auth    | ✅ Enabled  | ✅ Enabled  | Basic authentication         |
| 2     | JWT Tokens      | ✅ Enabled  | ✅ Enabled  | Session management           |
| 3     | HTTPS           | ❌ Disabled | ✅ Enable   | Encrypted transport          |
| 4     | HMAC Signatures | ❌ Disabled | ✅ Enable   | Request tampering prevention |
| 5     | IP Whitelist    | ❌ Disabled | 🔶 Optional | Network-level restriction    |
| 6     | Rate Limiting   | ✅ Enabled  | ✅ Enabled  | Abuse prevention             |

---

## ⚙️ Configuration File

**Location:** `/api/config/api_config.php`

### **Security Section (Lines 34-38):**

```php
// Security Configuration
define('REQUIRE_HTTPS', false);         // Set to true in production
define('REQUIRE_SIGNATURE', false);     // HMAC signature verification
define('ENABLE_IP_WHITELIST', false);   // IP whitelist checking
define('LOG_ALL_REQUESTS', true);       // Log every API request
```

---

## 🔧 How to Enable Each Layer

### **1. Enable HTTPS Enforcement**

**When:** When all vendors are ready for production

**How:**

```php
define('REQUIRE_HTTPS', true);
```

**Effect:** All HTTP requests will be rejected with 403 error

**Vendor Impact:** Must use `https://` URLs only

---

### **2. Enable HMAC Signature Verification**

**When:** After vendors have tested basic integration

**How:**

```php
define('REQUIRE_SIGNATURE', true);
```

**Effect:** Every request must include valid HMAC signature

**Vendor Requirements:**

- Must calculate HMAC-SHA256 signature
- Must include `X-Timestamp` header
- Must include `X-Signature` header

**Signature Calculation:**

```php
$apiKey = 'oouth_005_deduc_48_xxxxx';
$apiSecret = '64_char_secret_from_database';
$timestamp = time();

$signatureString = $apiKey . $timestamp;
$signature = hash_hmac('sha256', $signatureString, $apiSecret);
```

**Example Request with Signature:**

```bash
TIMESTAMP=$(date +%s)
API_KEY="oouth_005_deduc_48_xxxxx"
API_SECRET="your_64_char_secret"

# Calculate signature
SIGNATURE=$(echo -n "${API_KEY}${TIMESTAMP}" | openssl dgst -sha256 -hmac "$API_SECRET" | cut -d' ' -f2)

# Make request
curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $API_KEY" \
  -H "X-Timestamp: $TIMESTAMP" \
  -H "X-Signature: $SIGNATURE"
```

**Tools for Vendors:**

- Web Generator: https://oouthsalary.com.ng/generate_signature.php
- Code examples in: API_VENDOR_GUIDE.md

---

### **3. Enable IP Whitelisting**

**When:** For high-security vendors (banks, government agencies)

**How:**

```php
define('ENABLE_IP_WHITELIST', true);
```

**Effect:** Only requests from whitelisted IPs are allowed

**Configuration:**

#### **Organization-Level (All keys in org):**

```sql
UPDATE api_organizations
SET allowed_ips = '["10.0.0.1", "192.168.1.100", "203.0.113.5"]'
WHERE org_id = 5;
```

#### **Key-Level (Specific key only):**

```sql
UPDATE api_keys
SET allowed_ips = '["10.0.0.1", "192.168.1.100"]'
WHERE api_key = 'oouth_005_deduc_48_xxxxx';
```

**Note:** If whitelist is empty (NULL or '[]'), all IPs are allowed

**Vendor Impact:** Must provide their server IP addresses

---

## 📊 Security Progression Plan

### **Phase 1: Testing (Current)** ⚠️

```
✅ API Key + JWT
❌ HTTPS
❌ Signatures
❌ IP Whitelist
```

**When:** Development and initial integration (1-2 weeks)

---

### **Phase 2: Soft Launch** 🔶

```
✅ API Key + JWT
✅ HTTPS
❌ Signatures (optional)
❌ IP Whitelist
```

**When:** Beta testing with select vendors (2-4 weeks)

**Enable:**

```php
define('REQUIRE_HTTPS', true);
```

---

### **Phase 3: Production** 🔒

```
✅ API Key + JWT
✅ HTTPS
✅ Signatures
🔶 IP Whitelist (for sensitive vendors)
```

**When:** Full production rollout

**Enable:**

```php
define('REQUIRE_HTTPS', true);
define('REQUIRE_SIGNATURE', true);
define('ENABLE_IP_WHITELIST', true); // Optional, per vendor
```

---

## 🚨 When to Enable Each Layer

### **REQUIRE_HTTPS: Enable Early**

- ✅ No vendor code changes needed
- ✅ Just use https:// instead of http://
- ✅ Minimal impact
- ⚠️ Ensure SSL certificate is valid

**Recommended:** Enable after first successful integration test

---

### **REQUIRE_SIGNATURE: Enable When Vendors Ready**

- ⚠️ Requires vendor code changes
- ⚠️ Vendors need API secret
- ⚠️ Vendors must implement HMAC calculation
- ✅ Provides strong security

**Recommended Timeline:**

1. Week 1-2: Testing without signatures
2. Week 3: Notify vendors, provide tools/examples
3. Week 4: Test signature implementation
4. Week 5: Enable in production

---

### **ENABLE_IP_WHITELIST: Optional Per Vendor**

- ⚠️ Vendors must provide static IPs
- ⚠️ Not suitable for dynamic IPs
- ⚠️ Can block legitimate requests if misconfigured
- ✅ Extra layer for sensitive data

**Recommended:** Only for banks, government agencies, tax authorities

---

## 📝 Vendor Communication Templates

### **Phase 1 → Phase 2 (Enable HTTPS)**

**Subject:** OOUTH Salary API - HTTPS Enforcement on [Date]

```
Dear Vendor,

Starting [DATE], the OOUTH Salary API will require HTTPS for all requests.

Action Required:
✅ Update your integration to use https:// URLs
✅ Ensure your system trusts standard SSL certificates

Base URL Change:
❌ http://oouthsalary.com.ng/api/v1/
✅ https://oouthsalary.com.ng/api/v1/

No other changes required. Your API keys remain the same.

Test now: https://oouthsalary.com.ng/api_test.php

Questions? Email: api-support@oouth.edu.ng
```

---

### **Phase 2 → Phase 3 (Enable Signatures)**

**Subject:** OOUTH Salary API - HMAC Signature Requirement on [Date]

```
Dear Vendor,

Starting [DATE], the OOUTH Salary API will require HMAC signatures for all requests.

Action Required:
1. ✅ Retrieve your API secret from the admin dashboard
2. ✅ Update your code to generate HMAC signatures
3. ✅ Include X-Timestamp and X-Signature headers

Resources:
📖 Integration Guide: https://oouthsalary.com.ng/API_VENDOR_GUIDE.md
🔧 Signature Generator: https://oouthsalary.com.ng/generate_signature.php
💻 Code Examples: PHP, Node.js, Python (see guide)

Signature Formula:
- Signature String = API_KEY + TIMESTAMP
- HMAC Signature = hash_hmac('sha256', Signature String, API_SECRET)

Testing Period:
- Now - [DATE-1WEEK]: Test with signatures (optional)
- [DATE]: Signatures become mandatory

Need help? Email: api-support@oouth.edu.ng
```

---

## 🧪 Testing Security Layers

### **Test HTTPS Enforcement:**

```bash
# Should fail with 403
curl http://oouthsalary.com.ng/api/v1/

# Should succeed
curl https://oouthsalary.com.ng/api/v1/
```

---

### **Test Signature Verification:**

```bash
# Should fail with INVALID_SIGNATURE
curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \
  -H "X-API-Key: oouth_005_deduc_48_xxxxx"

# Should succeed (with correct signature)
curl -X POST https://oouthsalary.com.ng/api/v1/auth/token \
  -H "X-API-Key: oouth_005_deduc_48_xxxxx" \
  -H "X-Timestamp: $(date +%s)" \
  -H "X-Signature: calculated_signature"
```

Use: https://oouthsalary.com.ng/generate_signature.php

---

### **Test IP Whitelist:**

```bash
# Configure whitelist for test key
mysql> UPDATE api_keys
       SET allowed_ips = '["10.0.0.1"]'
       WHERE api_key = 'test_key';

# Should fail from other IPs with IP_NOT_ALLOWED
# Should succeed from 10.0.0.1
```

---

## 📊 Security Monitoring

### **Check Security Alerts:**

```sql
SELECT * FROM api_security_alerts
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;
```

### **Common Alert Types:**

- `INVALID_SIGNATURE` - Wrong signature calculation
- `INVALID_TIMESTAMP` - Request timestamp too old/new
- `IP_NOT_ALLOWED` - Request from non-whitelisted IP
- `EXPIRED_KEY` - Using expired API key
- `UNAUTHORIZED_ACCESS` - Accessing wrong resource

### **Monitor Via Dashboard:**

https://oouthsalary.com.ng/api_management.php

- View recent alerts
- See failed authentication attempts
- Track security incidents

---

## ⚡ Quick Reference

### **Current Settings (Testing Mode):**

```bash
curl https://oouthsalary.com.ng/api/v1/auth/token \
  -H "X-API-Key: YOUR_KEY"
# ✅ Works - No signature needed
```

### **Production Settings:**

```bash
curl https://oouthsalary.com.ng/api/v1/auth/token \
  -H "X-API-Key: YOUR_KEY" \
  -H "X-Timestamp: 1759954524" \
  -H "X-Signature: calculated_hmac"
# ✅ Works - Signature required
```

### **Enable Production Security:**

1. Edit: `/api/config/api_config.php`
2. Change:
   ```php
   define('REQUIRE_HTTPS', true);
   define('REQUIRE_SIGNATURE', true);
   ```
3. Save and test
4. Notify all vendors

---

## 🛡️ Best Practices

### **For OOUTH Admin:**

1. ✅ Start with relaxed security for onboarding
2. ✅ Enable HTTPS early (low vendor impact)
3. ✅ Give 2-week notice before enabling signatures
4. ✅ Provide testing tools and examples
5. ✅ Monitor security alerts daily
6. ✅ Respond to vendor questions quickly

### **For Vendors:**

1. ✅ Test without signatures first
2. ✅ Implement signature logic early
3. ✅ Store API secrets securely (env vars)
4. ✅ Handle signature errors gracefully
5. ✅ Provide static IPs if using whitelist
6. ✅ Monitor your integration logs

---

## 📞 Support

**Security Questions:**

- Email: api-support@oouth.edu.ng
- Phone: [Contact Number]

**Resources:**

- Vendor Guide: /API_VENDOR_GUIDE.md
- Testing Guide: /api/TESTING_GUIDE.md
- Signature Generator: https://oouthsalary.com.ng/generate_signature.php
- Web Tester: https://oouthsalary.com.ng/api_test.php

---

## 🎯 Summary

**Current:** Easy testing mode for vendor onboarding
**Goal:** Full production security when vendors are ready
**Approach:** Gradual enablement with vendor communication

**Timeline:**

- **Week 1-2:** Testing mode (current)
- **Week 3-4:** HTTPS only
- **Week 5+:** Full security (HTTPS + Signatures)

---

**Document Version:** 1.0.0  
**Last Updated:** October 8, 2025  
**Next Review:** When first vendor goes live
