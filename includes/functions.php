<?php
/**
 * Helper Functions
 * Common utility functions used throughout the application
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Redirect to another page
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Set flash message in session
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = 'alert-' . ($flash['type'] === 'error' ? 'danger' : $flash['type']);
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * Generate unique certificate number
 */
function generateCertificateNumber() {
    $db = getDB();
    
    // Get the last certificate number for current year
    $prefix = CERT_NUMBER_PREFIX . '-' . CERT_NUMBER_YEAR . '-';
    $stmt = $db->prepare("SELECT certificate_number FROM certificates WHERE certificate_number LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $lastCert = $stmt->fetch();
    
    if ($lastCert) {
        // Extract number and increment
        $lastNumber = (int) substr($lastCert['certificate_number'], strlen($prefix));
        $newNumber = $lastNumber + 1;
    } else {
        $newNumber = 1;
    }
    
    // Format with padding
    $paddedNumber = str_pad($newNumber, CERT_NUMBER_PADDING, '0', STR_PAD_LEFT);
    return $prefix . $paddedNumber;
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd F Y') {
    if (empty($date)) return '-';
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = 'd F Y H:i') {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    return date($format, $timestamp);
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status) {
    $badges = [
        'valid' => '<span class="badge bg-success">Valid ✅</span>',
        'revoked' => '<span class="badge bg-danger">Revoked ❌</span>',
        'expired' => '<span class="badge bg-warning">Expired ⏰</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
}

/**
 * Paginate results
 */
function paginate($totalRecords, $currentPage = 1, $recordsPerPage = RECORDS_PER_PAGE) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'records_per_page' => $recordsPerPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Generate pagination HTML
 */
function renderPagination($pagination, $baseUrl) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<nav><ul class="pagination">';
    
    // Previous button
    if ($pagination['has_prev']) {
        $prevPage = $pagination['current_page'] - 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $prevPage . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $pagination['current_page'] ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($pagination['has_next']) {
        $nextPage = $pagination['current_page'] + 1;
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $nextPage . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Log audit trail
 */
function logAudit($userId, $action, $tableName, $recordId, $oldValues = null, $newValues = null) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $userId,
        $action,
        $tableName,
        $recordId,
        $oldValues ? json_encode($oldValues) : null,
        $newValues ? json_encode($newValues) : null,
        getClientIP(),
        getUserAgent()
    ]);
}

/**
 * Parse template variables
 */
function parseTemplate($template, $variables) {
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value, $template);
    }
    return $template;
}

/**
 * Get file extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Get human readable file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Check if user has role
 */
function hasRole($requiredRoles) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    if (is_array($requiredRoles)) {
        return in_array($_SESSION['user_role'], $requiredRoles);
    }
    
    return $_SESSION['user_role'] === $requiredRoles;
}

/**
 * Debug helper (only in development)
 */
function dd($data) {
    if (APP_ENV === 'development') {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}
