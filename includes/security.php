<?php
/**
 * Security Functions
 * CSRF protection, XSS prevention, rate limiting, HMAC signatures
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF Token Input Field (HTML)
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Sanitize input to prevent XSS
 */
function sanitize($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize($value);
        }
        return $data;
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Clean input (remove extra spaces, special chars)
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate HMAC signature for verification token
 */
function generateHMAC($data) {
    return hash_hmac('sha256', $data, SECRET_KEY);
}

/**
 * Verify HMAC signature
 */
function verifyHMAC($data, $signature) {
    $expected = generateHMAC($data);
    return hash_equals($expected, $signature);
}

/**
 * Generate verification token with HMAC
 */
function generateVerificationToken($certificateId) {
    $randomString = bin2hex(random_bytes(16));
    $data = $certificateId . '-' . $randomString;
    $signature = generateHMAC($data);
    return $data . '-' . $signature;
}

/**
 * Verify and extract certificate ID from token
 */
function verifyVerificationToken($token) {
    $parts = explode('-', $token);
    if (count($parts) !== 3) {
        return false;
    }
    
    $certificateId = $parts[0];
    $randomString = $parts[1];
    $signature = $parts[2];
    
    $data = $certificateId . '-' . $randomString;
    
    if (verifyHMAC($data, $signature)) {
        return $certificateId;
    }
    
    return false;
}

/**
 * Rate limiting for verification attempts
 * Returns true if rate limit exceeded
 */
function checkRateLimit($ipAddress) {
    $db = getDB();
    
    // Clean old attempts (older than time window)
    $cleanupTime = date('Y-m-d H:i:s', time() - RATE_LIMIT_TIME_WINDOW);
    $stmt = $db->prepare("DELETE FROM verification_attempts WHERE ip_address = ? AND attempted_at < ?");
    $stmt->execute([$ipAddress, $cleanupTime]);
    
    // Count recent attempts
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM verification_attempts WHERE ip_address = ? AND attempted_at >= ?");
    $stmt->execute([$ipAddress, $cleanupTime]);
    $result = $stmt->fetch();
    
    return $result['count'] >= RATE_LIMIT_MAX_ATTEMPTS;
}

/**
 * Log verification attempt
 */
function logVerificationAttempt($ipAddress, $certificateNumber, $success) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO verification_attempts (ip_address, certificate_number, success, attempted_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$ipAddress, $certificateNumber, $success ? 1 : 0]);
}

/**
 * Get failed attempts count for CAPTCHA threshold
 */
function getFailedAttempts($ipAddress) {
    $db = getDB();
    $cleanupTime = date('Y-m-d H:i:s', time() - RATE_LIMIT_TIME_WINDOW);
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM verification_attempts WHERE ip_address = ? AND success = 0 AND attempted_at >= ?");
    $stmt->execute([$ipAddress, $cleanupTime]);
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipAddress = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
    }
    return $ipAddress;
}

/**
 * Get user agent
 */
function getUserAgent() {
    return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedExtensions = ALLOWED_FILE_EXTENSIONS, $maxSize = MAX_FILE_SIZE) {
    $errors = [];
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = "No file uploaded";
        return $errors;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Upload error: " . $file['error'];
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = "File size exceeds maximum allowed size (" . ($maxSize / 1024 / 1024) . "MB)";
    }
    
    // Check file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions)) {
        $errors[] = "Invalid file type. Allowed types: " . implode(', ', $allowedExtensions);
    }
    
    return $errors;
}

/**
 * Generate secure filename
 */
function generateSecureFilename($originalFilename) {
    $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
    $randomName = bin2hex(random_bytes(16));
    return $randomName . '.' . $extension;
}

/**
 * Mask email for privacy
 */
function maskEmail($email) {
    if (empty($email) || !validateEmail($email)) {
        return $email;
    }
    
    $parts = explode('@', $email);
    $username = $parts[0];
    $domain = $parts[1];
    
    $usernameLength = strlen($username);
    if ($usernameLength <= 2) {
        $maskedUsername = $username[0] . '***';
    } else {
        $maskedUsername = $username[0] . str_repeat('*', $usernameLength - 2) . $username[$usernameLength - 1];
    }
    
    return $maskedUsername . '@' . $domain;
}

/**
 * Prevent directory traversal
 */
function sanitizePath($path) {
    $path = str_replace(['../', '..\\', '\\'], '', $path);
    return $path;
}
