-- ============================================
-- OOUTH Salary API System Database Schema
-- Multi-tenant API with webhook support
-- Created: October 2025
-- ============================================

-- 1. API Organizations Table
CREATE TABLE IF NOT EXISTS `api_organizations` (
  `org_id` INT(11) NOT NULL AUTO_INCREMENT,
  `org_name` VARCHAR(255) NOT NULL,
  `org_code` VARCHAR(50) NOT NULL UNIQUE,
  `contact_email` VARCHAR(255) NOT NULL,
  `contact_phone` VARCHAR(50) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `rate_limit_per_min` INT(11) NOT NULL DEFAULT 500,
  `allowed_ips` TEXT DEFAULT NULL COMMENT 'JSON array of allowed IP addresses',
  `metadata` TEXT DEFAULT NULL COMMENT 'Additional organization metadata (JSON)',
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`org_id`),
  INDEX `idx_org_code` (`org_code`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. API Keys Table
CREATE TABLE IF NOT EXISTS `api_keys` (
  `key_id` INT(11) NOT NULL AUTO_INCREMENT,
  `api_key` VARCHAR(100) NOT NULL UNIQUE,
  `api_secret` VARCHAR(255) NOT NULL COMMENT 'Hashed secret for HMAC signing',
  `org_id` INT(11) NOT NULL,
  `ed_id` INT(11) NOT NULL COMMENT 'Allowance or Deduction ID',
  `ed_type` TINYINT(1) NOT NULL COMMENT '1=Allowance, 2=Deduction',
  `ed_name` VARCHAR(255) NOT NULL COMMENT 'Cached name for quick lookup',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `expires_at` DATETIME DEFAULT NULL,
  `rate_limit_per_min` INT(11) NOT NULL DEFAULT 100,
  `allowed_ips` TEXT DEFAULT NULL COMMENT 'JSON array of allowed IP addresses',
  `created_by` INT(11) NOT NULL COMMENT 'Admin user who created the key',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `total_requests` BIGINT(20) NOT NULL DEFAULT 0,
  `notes` TEXT DEFAULT NULL,
  PRIMARY KEY (`key_id`),
  UNIQUE KEY `idx_api_key` (`api_key`),
  INDEX `idx_org_id` (`org_id`),
  INDEX `idx_ed_id_type` (`ed_id`, `ed_type`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_expires_at` (`expires_at`),
  FOREIGN KEY (`org_id`) REFERENCES `api_organizations`(`org_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. API Request Logs Table
CREATE TABLE IF NOT EXISTS `api_request_logs` (
  `log_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `request_id` VARCHAR(50) NOT NULL UNIQUE,
  `org_id` INT(11) DEFAULT NULL,
  `api_key` VARCHAR(100) DEFAULT NULL,
  `endpoint` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL,
  `ip_address` VARCHAR(50) NOT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `request_timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `response_status` INT(11) NOT NULL,
  `response_time_ms` INT(11) DEFAULT NULL,
  `error_code` VARCHAR(50) DEFAULT NULL,
  `error_message` TEXT DEFAULT NULL,
  `period_accessed` INT(11) DEFAULT NULL,
  `records_returned` INT(11) DEFAULT NULL,
  PRIMARY KEY (`log_id`),
  UNIQUE KEY `idx_request_id` (`request_id`),
  INDEX `idx_org_id` (`org_id`),
  INDEX `idx_api_key` (`api_key`),
  INDEX `idx_endpoint` (`endpoint`),
  INDEX `idx_request_timestamp` (`request_timestamp`),
  INDEX `idx_response_status` (`response_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. API Rate Limits Table (Sliding Window)
CREATE TABLE IF NOT EXISTS `api_rate_limits` (
  `limit_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `api_key` VARCHAR(100) NOT NULL,
  `window_start` TIMESTAMP NOT NULL,
  `window_end` TIMESTAMP NOT NULL,
  `request_count` INT(11) NOT NULL DEFAULT 1,
  `last_request_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`limit_id`),
  UNIQUE KEY `idx_api_key_window` (`api_key`, `window_start`),
  INDEX `idx_window_end` (`window_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. API Webhooks Table
CREATE TABLE IF NOT EXISTS `api_webhooks` (
  `webhook_id` INT(11) NOT NULL AUTO_INCREMENT,
  `org_id` INT(11) NOT NULL,
  `webhook_name` VARCHAR(255) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `events` TEXT NOT NULL COMMENT 'JSON array of subscribed events',
  `secret` VARCHAR(255) NOT NULL COMMENT 'Secret for HMAC signature verification',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `retry_count` TINYINT(2) NOT NULL DEFAULT 3,
  `timeout_seconds` TINYINT(2) NOT NULL DEFAULT 30,
  `last_delivery_at` TIMESTAMP NULL DEFAULT NULL,
  `last_delivery_status` VARCHAR(50) DEFAULT NULL,
  `failed_deliveries` INT(11) NOT NULL DEFAULT 0,
  `total_deliveries` INT(11) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`webhook_id`),
  INDEX `idx_org_id` (`org_id`),
  INDEX `idx_is_active` (`is_active`),
  FOREIGN KEY (`org_id`) REFERENCES `api_organizations`(`org_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. API Webhook Delivery Logs Table
CREATE TABLE IF NOT EXISTS `api_webhook_logs` (
  `log_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `webhook_id` INT(11) NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `payload` TEXT NOT NULL COMMENT 'JSON payload sent',
  `delivery_status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `response_code` INT(11) DEFAULT NULL,
  `response_body` TEXT DEFAULT NULL,
  `delivered_at` TIMESTAMP NULL DEFAULT NULL,
  `retry_attempt` TINYINT(2) NOT NULL DEFAULT 0,
  `error_message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  INDEX `idx_webhook_id` (`webhook_id`),
  INDEX `idx_event_type` (`event_type`),
  INDEX `idx_delivery_status` (`delivery_status`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`webhook_id`) REFERENCES `api_webhooks`(`webhook_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. API JWT Tokens Table (for token blacklisting and refresh)
CREATE TABLE IF NOT EXISTS `api_jwt_tokens` (
  `token_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `api_key` VARCHAR(100) NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL COMMENT 'SHA256 hash of JWT',
  `refresh_token` VARCHAR(255) DEFAULT NULL,
  `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `is_revoked` TINYINT(1) NOT NULL DEFAULT 0,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL,
  `ip_address` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`token_id`),
  INDEX `idx_token_hash` (`token_hash`),
  INDEX `idx_api_key` (`api_key`),
  INDEX `idx_expires_at` (`expires_at`),
  INDEX `idx_is_revoked` (`is_revoked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. API Security Alerts Table
CREATE TABLE IF NOT EXISTS `api_security_alerts` (
  `alert_id` BIGINT(20) NOT NULL AUTO_INCREMENT,
  `org_id` INT(11) DEFAULT NULL,
  `api_key` VARCHAR(100) DEFAULT NULL,
  `alert_type` VARCHAR(100) NOT NULL COMMENT 'RATE_LIMIT_EXCEEDED, INVALID_SIGNATURE, SUSPICIOUS_IP, etc.',
  `severity` VARCHAR(20) NOT NULL DEFAULT 'medium' COMMENT 'low, medium, high, critical',
  `ip_address` VARCHAR(50) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `metadata` TEXT DEFAULT NULL COMMENT 'Additional alert data (JSON)',
  `is_resolved` TINYINT(1) NOT NULL DEFAULT 0,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `resolved_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`alert_id`),
  INDEX `idx_org_id` (`org_id`),
  INDEX `idx_alert_type` (`alert_type`),
  INDEX `idx_severity` (`severity`),
  INDEX `idx_is_resolved` (`is_resolved`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Insert Default/Sample Data
-- ============================================

-- Sample organization (can be modified or deleted)
INSERT INTO `api_organizations` (`org_name`, `org_code`, `contact_email`, `is_active`, `rate_limit_per_min`, `created_by`) 
VALUES 
  ('OOUTH Internal', 'OOUTH_INTERNAL', 'admin@oouth.edu.ng', 1, 500, 1),
  ('Sample Vendor', 'VENDOR_SAMPLE', 'vendor@example.com', 0, 100, 1);

-- ============================================
-- Cleanup/Maintenance Queries (for reference)
-- ============================================

-- Delete expired JWT tokens (run periodically via cron)
-- DELETE FROM api_jwt_tokens WHERE expires_at < NOW() AND is_revoked = 0;

-- Delete old request logs (keep last 90 days)
-- DELETE FROM api_request_logs WHERE request_timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Delete old webhook logs (keep last 90 days)
-- DELETE FROM api_webhook_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Archive old rate limit records (keep last 24 hours)
-- DELETE FROM api_rate_limits WHERE window_end < DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- ============================================
-- Useful Queries
-- ============================================

-- View all active API keys with organization info
-- SELECT ak.api_key, ak.ed_name, ao.org_name, ak.total_requests, ak.last_used_at
-- FROM api_keys ak
-- JOIN api_organizations ao ON ak.org_id = ao.org_id
-- WHERE ak.is_active = 1 AND ao.is_active = 1;

-- View API usage statistics per organization
-- SELECT ao.org_name, COUNT(*) as total_requests, 
--        AVG(arl.response_time_ms) as avg_response_time,
--        SUM(CASE WHEN arl.response_status >= 400 THEN 1 ELSE 0 END) as error_count
-- FROM api_request_logs arl
-- JOIN api_organizations ao ON arl.org_id = ao.org_id
-- WHERE arl.request_timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
-- GROUP BY ao.org_id, ao.org_name;

-- View webhook delivery success rate
-- SELECT w.webhook_name, w.total_deliveries, w.failed_deliveries,
--        ROUND((w.total_deliveries - w.failed_deliveries) / w.total_deliveries * 100, 2) as success_rate
-- FROM api_webhooks w
-- WHERE w.total_deliveries > 0;

