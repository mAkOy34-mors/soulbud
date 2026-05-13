<?php
// ─────────────────────────────────────────
// SOULBUD.CO — Database Configuration
// ─────────────────────────────────────────

define('DB_HOST', 'localhost');
define('DB_NAME', 'soulbud_db');
define('DB_USER', 'root');          // Change to your MySQL user
define('DB_PASS', '');              // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// App settings
define('APP_NAME', 'SOULBUD.CO');
define('APP_URL', 'https://unilluded-sulphurous-estrella.ngrok-free.dev/soulbud');
define('APP_EMAIL', 'noreply@soulbud.co');

// Email (PHPMailer / SMTP)
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'michomoreno34@gmail.com');    // Change
define('SMTP_PASS',     'ewha ngym merc fsef'); // Change
define('SMTP_FROM',     'michomoreno34@gmail.com');
define('SMTP_FROM_NAME', 'SOULBUD.CO');

// Payment details
define('GCASH_NUMBER',  '09XX-XXX-XXXX');
define('BANK_NAME',     'BDO Unibank');
define('BANK_ACCOUNT',  '0000-0000-0000');
define('BANK_ACCT_NAME','SOULBUD Photography');

// Session timeout (seconds)
define('SESSION_TIMEOUT', 3600);

// Upload limits
define('MAX_UPLOAD_MB', 5);
define('ALLOWED_IMG_TYPES', ['image/jpeg','image/png','image/webp']);
