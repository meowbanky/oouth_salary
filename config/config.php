<?php
// Database configuration settings
define('DB_DSN', 'mysql:host=localhost;dbname=oouthsal_salary3');
define('DB_USERNAME', 'oouthsal_root');
define('DB_PASSWORD', 'Oluwaseyi@7980');

// Legacy constants for App.php compatibility
define('HOST', 'localhost');
define('DBNAME', 'oouthsal_salary3');
define('USER', 'oouthsal_root');
define('PASS', 'Oluwaseyi@7980');

// SMTP configuration settings
define('SMTP_HOST', 'mail.oouthsalary.com.ng');
define('SMTP_USERNAME', 'report@oouthsalary.com.ng');
define('SMTP_PASSWORD', 'b07NwW3_5WNr');
define('SMTP_SECURE', 'PHPMailer::ENCRYPTION_STARTTLS'); // Or 'ssl'PHPMailer::ENCRYPTION_STARTTLS;
define('SMTP_PORT', 587); // 465 for SSL, 587 for TLS
// define('SMTP_PORT', 465); // 465 for SSL, 587 for TLS
define('SMT_SMTPDebug',0);

// Email details
define('SMTP_FROM_EMAIL', 'report@oouthsalary.com.ng');
define('SMTP_FROM_NAME', 'Olabisi Onabanjo University Teaching Hospital, Sagamu');
define('SMTP_REPLYTO_EMAIL', 'report@oouthsalary.com.ng');
define('SMTP_REPLYTO_NAME', 'Olabisi Onabanjo University Teaching Hospital, Sagamu');