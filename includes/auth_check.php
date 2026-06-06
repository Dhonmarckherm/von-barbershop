<?php
/**
 * Authentication Guard
 * Redirects unauthenticated users to the login page.
 */
require_once __DIR__ . '/../config/session.php';
initializeSession();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Security: Verify session role matches actual user role from database
// This prevents role escalation from stale cookies
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    require_once __DIR__ . '/../config/db.php';
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $actualUser = $stmt->fetch();
    
    if ($actualUser && $actualUser['role'] !== $_SESSION['role']) {
        // Role mismatch detected - clear session and force re-login
        error_log('SECURITY: Role mismatch detected for user ' . $_SESSION['user_id'] . '. Session: ' . $_SESSION['role'] . ', DB: ' . $actualUser['role']);
        session_destroy();
        clearAuthCookies();
        header('Location: login.php?error=role_mismatch');
        exit;
    }
}
