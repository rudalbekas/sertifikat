<?php
/**
 * Authentication and Authorization Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Start secure session
 */
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > SESSION_LIFETIME) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Login user
 */
function loginUser($username, $password) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        // Regenerate session ID on login
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Log the login
        logAudit($user['id'], 'login', 'users', $user['id']);
        
        return true;
    }
    
    return false;
}

/**
 * Logout user
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        logAudit($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
    }
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/**
 * Require specific role
 */
function requireRole($roles) {
    requireLogin();
    
    if (is_string($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($_SESSION['user_role'], $roles)) {
        setFlashMessage('error', 'You do not have permission to access this page');
        redirect('../index.php');
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current username
 */
function getCurrentUsername() {
    return isset($_SESSION['username']) ? $_SESSION['username'] : null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

/**
 * Check if current user has role
 */
function currentUserHasRole($role) {
    if (is_array($role)) {
        return in_array(getCurrentUserRole(), $role);
    }
    return getCurrentUserRole() === $role;
}

/**
 * Register new user (admin only)
 */
function registerUser($username, $email, $password, $role = 'verifikator') {
    $db = getDB();
    
    // Check if username or email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$username, $email, $hashedPassword, $role])) {
        $userId = $db->lastInsertId();
        logAudit(getCurrentUserId(), 'create', 'users', $userId, null, ['username' => $username, 'role' => $role]);
        return ['success' => true, 'user_id' => $userId];
    }
    
    return ['success' => false, 'message' => 'Failed to create user'];
}

/**
 * Update user password
 */
function updatePassword($userId, $newPassword) {
    $db = getDB();
    
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    
    if ($stmt->execute([$hashedPassword, $userId])) {
        logAudit(getCurrentUserId(), 'update', 'users', $userId, null, ['password_changed' => true]);
        return true;
    }
    
    return false;
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, role, created_at, updated_at FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get all users
 */
function getAllUsers($limit = null, $offset = 0) {
    $db = getDB();
    $sql = "SELECT id, username, email, role, created_at, updated_at FROM users ORDER BY created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT ? OFFSET ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$limit, $offset]);
    } else {
        $stmt = $db->query($sql);
    }
    
    return $stmt->fetchAll();
}

/**
 * Count total users
 */
function countUsers() {
    $db = getDB();
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    return $result['count'];
}

/**
 * Delete user
 */
function deleteUser($userId) {
    $db = getDB();
    
    // Get user data before deletion
    $user = getUserById($userId);
    
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$userId])) {
        logAudit(getCurrentUserId(), 'delete', 'users', $userId, $user);
        return true;
    }
    
    return false;
}

/**
 * Update user
 */
function updateUser($userId, $username, $email, $role) {
    $db = getDB();
    
    // Get old values
    $oldUser = getUserById($userId);
    
    $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt->execute([$username, $email, $role, $userId])) {
        logAudit(getCurrentUserId(), 'update', 'users', $userId, $oldUser, ['username' => $username, 'email' => $email, 'role' => $role]);
        return true;
    }
    
    return false;
}
