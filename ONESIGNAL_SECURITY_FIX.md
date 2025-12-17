# 🚨 CRITICAL: OneSignal REST API Key Exposure

## ⚠️ SECURITY ALERT

**Date:** December 17, 2025  
**Severity:** CRITICAL  
**Issue:** OneSignal REST API Key was hardcoded in source files and exposed on GitHub

---

## 🔴 What Was Exposed

The OneSignal REST API Key was hardcoded in multiple PHP files and committed to GitHub:

**Exposed API Key:** `os_v2_app_ybfa6fphbngubi6gfbfrrgfvwynxspmvfroukifxxpmmrooq2lnc7nuy4rfvonjhqc6kbd5ziwsehccorfoo5w2b7zrispcuzvob2ha`

**Files Affected:**
1. `auth_api/onesignal/save_notification.php` (Lines 67, 92)
2. `auth_api/onesignal/device_list.php` (Line 8)

**OneSignal App ID:** `c04a0f15-e70b-4d40-a3c6-284b1898b5b6` (This is public, safe to keep)

---

## ✅ Actions Taken

1. **Updated Code to Use Environment Variables**
   - Modified `save_notification.php` to use `getenv('ONESIGNAL_REST_API_KEY')`
   - Modified `device_list.php` to use environment variables
   - Added error handling for missing API key

2. **OneSignal Has Already Deleted the Key**
   - OneSignal's automation detected the exposure
   - The exposed key has been automatically deleted
   - **You MUST generate a new REST API Key**

---

## 🔐 REQUIRED IMMEDIATE ACTIONS

### 1. **Generate New OneSignal REST API Key** ⚠️

1. Log into your OneSignal dashboard: https://onesignal.com/
2. Navigate to **Settings** → **Keys & IDs**
3. Click **Generate** next to "REST API Key"
4. **Copy the new key immediately** (you won't be able to see it again)

### 2. **Add to Environment Variables**

Add the new key to your `.env` file:

```bash
# OneSignal Configuration
ONESIGNAL_APP_ID=c04a0f15-e70b-4d40-a3c6-284b1898b5b6
ONESIGNAL_REST_API_KEY=your_new_rest_api_key_here
```

### 3. **Update .env.example**

If you have `.env.example`, add these variables (with placeholder values):

```bash
# OneSignal Configuration
ONESIGNAL_APP_ID=c04a0f15-e70b-4d40-a3c6-284b1898b5b6
ONESIGNAL_REST_API_KEY=your_onesignal_rest_api_key_here
```

### 4. **Load Environment Variables**

Ensure your PHP files load environment variables. Add this to your configuration:

```php
// Load .env file if using vlucas/phpdotenv
require_once __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../..');
$dotenv->load();

// Or manually load .env
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}
```

### 5. **Test the Application**

After updating:
1. Restart your web server/PHP-FPM
2. Test sending a notification
3. Verify notifications are working correctly

---

## 📋 Checklist

- [x] Code updated to use environment variables
- [x] Hardcoded keys removed from source files
- [ ] **Generate new OneSignal REST API Key** ⚠️
- [ ] **Add new key to .env file** ⚠️
- [ ] **Update .env.example** (if exists)
- [ ] **Test notification functionality**
- [ ] **Remove old key from Git history** (optional but recommended)

---

## 🔍 Additional Security Issues Found

While fixing OneSignal, also found:

### cPanel API Token Exposed

**File:** `auth_api/onesignal/create_email.php` (Line 54)  
**Token:** `7IJBJPYHKF15Z41YTPKPOBEN9BNHP7JL`

**Action Required:**
1. Rotate cPanel API token
2. Update code to use `getenv('CPANEL_API_TOKEN')`
3. Add to `.env` file

---

## 🛡️ Prevention Measures

### 1. **Never Hardcode API Keys**

Always use environment variables:
```php
// ❌ BAD
$apiKey = "os_v2_app_...";

// ✅ GOOD
$apiKey = getenv('ONESIGNAL_REST_API_KEY') ?: '';
if (empty($apiKey)) {
    error_log("ERROR: API key not configured!");
    return false;
}
```

### 2. **Use .env Files**

- Keep `.env` in `.gitignore`
- Use `.env.example` as template
- Never commit `.env` to Git

### 3. **Regular Security Audits**

- Scan repository for hardcoded credentials
- Use tools like `git-secrets` or `truffleHog`
- Review code before committing

### 4. **Pre-commit Hooks**

Add Git hooks to prevent committing secrets:
```bash
#!/bin/sh
# .git/hooks/pre-commit

if git diff --cached | grep -qE "(os_v2_app_|api[_-]?key|password|secret)" -i; then
    echo "ERROR: Potential API key or secret detected!"
    echo "Remove sensitive data before committing."
    exit 1
fi
```

---

## 📞 OneSignal Support

- **Documentation:** https://documentation.onesignal.com/docs/en/keys-and-ids
- **Dashboard:** https://onesignal.com/
- **Support:** Contact OneSignal support if you have questions

---

## ⏭️ Next Steps

1. **Generate new REST API Key** (do this now!)
2. Add to `.env` file
3. Test notifications
4. Remove old key from Git history (optional):
   ```bash
   git filter-branch --force --index-filter \
     'git rm --cached --ignore-unmatch auth_api/onesignal/save_notification.php auth_api/onesignal/device_list.php' \
     --prune-empty --tag-name-filter cat -- --all
   git push origin main --force
   ```

---

**Remember:** Security is critical. Exposed API keys can allow unauthorized access to your notification system. Act immediately!

