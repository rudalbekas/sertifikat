<?php
/**
 * Application Configuration
 * This file contains all configuration settings for the Certificate Management System
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sertifikat_db');

// Application Settings
define('APP_NAME', 'Certificate Management System');
define('APP_URL', 'http://localhost/sertifikat');
define('APP_ENV', 'development'); // development, production

// Security Settings
define('SECRET_KEY', 'change-this-secret-key-in-production-' . bin2hex(random_bytes(16))); // For HMAC signatures
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600); // 1 hour

// Email Configuration (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'Certificate System');
define('SMTP_ENCRYPTION', 'tls'); // tls or ssl

// Certificate Settings
define('CERT_NUMBER_PREFIX', 'EVT');
define('CERT_NUMBER_YEAR', date('Y'));
define('CERT_NUMBER_PADDING', 6); // 000001

// File Paths
define('UPLOAD_CSV_PATH', __DIR__ . '/../uploads/csv/');
define('UPLOAD_TEMPLATE_PATH', __DIR__ . '/../uploads/templates/');
define('GENERATED_CERT_PATH', __DIR__ . '/../generated/certificates/');
define('GENERATED_QR_PATH', __DIR__ . '/../generated/qrcodes/');

// File Upload Limits
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_EXTENSIONS', ['csv', 'xlsx']);

// Rate Limiting (verification attempts)
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_TIME_WINDOW', 60); // seconds
define('CAPTCHA_THRESHOLD', 3); // Show CAPTCHA after 3 failed attempts

// Pagination
define('RECORDS_PER_PAGE', 20);

// PDF Settings
define('PDF_QUALITY', 'high'); // high, medium, low
define('PDF_ORIENTATION', 'L'); // L = Landscape, P = Portrait
define('PDF_PAGE_FORMAT', 'A4');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}
