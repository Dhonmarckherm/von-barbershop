<?php
/**
 * Authentication Guard
 * Redirects unauthenticated users to the login page.
 */
require_once __DIR__ . '/../config/session.php';
initializeSession();

// Check session first, fallback to auth cookies
if (!isset($_SESSION['user_id']) && isset($_COOKIE['auth_user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['auth_user_id'];
    $_SESSION['name'] = $_COOKIE['auth_name'] ?? '';
    $_SESSION['email'] = $_COOKIE['auth_email'] ?? '';
    $_SESSION['role'] = $_COOKIE['auth_role'] ?? 'customer';
    $_SESSION['login_time'] = time();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Security: Verify user still exists and role matches
// This prevents access after account deletion and role escalation
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    try {
        require_once __DIR__ . '/../config/db.php';
        $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $actualUser = $stmt->fetch();
        
        // Check if user was deleted from database
        if (!$actualUser) {
            // User no longer exists - destroy session and force logout
            error_log('SECURITY: Deleted user session detected for user ID ' . $_SESSION['user_id'] . '. Destroying session.');
            session_destroy();
            clearAuthCookies();
            header('Location: login.php?error=account_deleted');
            exit;
        }
        
        // Check if role matches (case-insensitive comparison)
        if (strtolower($actualUser['role']) !== strtolower($_SESSION['role'])) {
            // Role mismatch detected - clear session and force re-login
            error_log('SECURITY: Role mismatch detected for user ' . $_SESSION['user_id'] . '. Session: ' . $_SESSION['role'] . ', DB: ' . $actualUser['role']);
            session_destroy();
            clearAuthCookies();
            header('Location: login.php?error=role_mismatch');
            exit;
        }
    } catch (Exception $e) {
        // Database error - log but DON'T redirect to prevent login loop
        error_log('AUTH_CHECK: Database error during user verification: ' . $e->getMessage());
        // Continue allowing access - don't break login flow
    }
}
