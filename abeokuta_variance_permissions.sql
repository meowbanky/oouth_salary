-- Add Abeokuta Variance Tracking pages to the pages table
INSERT IGNORE INTO pages (url, name) VALUES ('abeokuta_variance_tracking.php', 'Abeokuta Variance Tracking');
INSERT IGNORE INTO pages (url, name) VALUES ('abeokuta_variance_tracking_enhanced.php', 'Abeokuta Variance Enhanced');
INSERT IGNORE INTO pages (url, name) VALUES ('abeokuta_variance_export.php', 'Abeokuta Variance Export');
INSERT IGNORE INTO pages (url, name) VALUES ('abeokuta_audit_report.php', 'Abeokuta Audit Report');

-- Grant permissions to all existing roles for the new pages
-- This will add permissions for all roles that exist in the system
INSERT IGNORE INTO permissions (role_id, page) 
SELECT r.role_id, 'abeokuta_variance_tracking.php'
FROM roles r
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p 
    WHERE p.role_id = r.role_id AND p.page = 'abeokuta_variance_tracking.php'
);

INSERT IGNORE INTO permissions (role_id, page) 
SELECT r.role_id, 'abeokuta_variance_tracking_enhanced.php'
FROM roles r
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p 
    WHERE p.role_id = r.role_id AND p.page = 'abeokuta_variance_tracking_enhanced.php'
);

INSERT IGNORE INTO permissions (role_id, page) 
SELECT r.role_id, 'abeokuta_variance_export.php'
FROM roles r
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p 
    WHERE p.role_id = r.role_id AND p.page = 'abeokuta_variance_export.php'
);

INSERT IGNORE INTO permissions (role_id, page) 
SELECT r.role_id, 'abeokuta_audit_report.php'
FROM roles r
WHERE NOT EXISTS (
    SELECT 1 FROM permissions p 
    WHERE p.role_id = r.role_id AND p.page = 'abeokuta_audit_report.php'
);

-- Alternative: If you want to grant permissions only to Admin roles
-- Uncomment the following lines and comment out the above INSERT statements

-- INSERT IGNORE INTO permissions (role_id, page) 
-- SELECT r.role_id, 'abeokuta_variance_tracking.php'
-- FROM roles r
-- WHERE r.role_name = 'Admin' OR r.role_name = 'admin' OR r.role_name = 'Administrator'
-- AND NOT EXISTS (
--     SELECT 1 FROM permissions p 
--     WHERE p.role_id = r.role_id AND p.page = 'abeokuta_variance_tracking.php'
-- );

-- INSERT IGNORE INTO permissions (role_id, page) 
-- SELECT r.role_id, 'abeokuta_variance_export.php'
-- FROM roles r
-- WHERE r.role_name = 'Admin' OR r.role_name = 'admin' OR r.role_name = 'Administrator'
-- AND NOT EXISTS (
--     SELECT 1 FROM permissions p 
--     WHERE p.role_id = r.role_id AND p.page = 'abeokuta_variance_export.php'
-- );
