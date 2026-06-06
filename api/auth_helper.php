<?php
/**
 * Common Authentication Helper for API Endpoints
 * Include this file at the top of all API endpoints
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
initializeSession();

header('Content-Type: application/json');

/**
 * Verify admin/barber access with session and cookie fallback
 * Returns true if authorized, exits with 403 if not
 */
function requireAdminAuth() {
    // Try session first
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'barber')) {
        return true;
    }
    
    // Fallback to auth cookies
    if (isset($_COOKIE['auth_role']) && ($_COOKIE['auth_role'] === 'admin' || $_COOKIE['auth_role'] === 'barber')) {
        // Restore session from cookies
        $_SESSION['user_id'] = $_COOKIE['auth_user_id'] ?? 0;
        $_SESSION['name'] = $_COOKIE['auth_name'] ?? '';
        $_SESSION['email'] = $_COOKIE['auth_email'] ?? '';
        $_SESSION['role'] = $_COOKIE['auth_role'] ?? '';
        return true;
    }
    
    // Not authorized
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Admin access required.']);
    exit;
}

/**
 * Verify customer access (logged in user)
 * Returns true if authorized, exits with 403 if not
 */
function requireCustomerAuth() {
    // Try session first
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Fallback to auth cookies
    if (isset($_COOKIE['auth_user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['auth_user_id'];
        $_SESSION['name'] = $_COOKIE['auth_name'] ?? '';
        $_SESSION['email'] = $_COOKIE['auth_email'] ?? '';
        $_SESSION['role'] = $_COOKIE['auth_role'] ?? 'customer';
        return true;
    }
    
    // Not authorized
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Please log in.']);
    exit;
}
