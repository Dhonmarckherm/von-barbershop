<?php
/**
 * Centralized Session Configuration
 * This file should be included before any session_start() calls
 */

function initializeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Detect if we're on HTTPS (production) or HTTP (local)
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' 
                   || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
        
        // Set session save path for Render compatibility
        $sessionPath = sys_get_temp_dir() . '/sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0777, true);
        }
        ini_set('session.save_path', $sessionPath);
        
        // Session cookie parameters
        session_set_cookie_params([
            'lifetime' => 86400,        // 24 hours
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,  // Use HTTPS in production for security
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.use_strict_mode', 0);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        
        // Start session with error handling
        $sessionStarted = @session_start();
        
        if (!$sessionStarted) {
            // If session fails, try to start it anyway without custom settings
            error_log('Session failed with custom settings, trying default...');
            session_start();
        }
        
        // Verify session is actually active
        if (session_status() !== PHP_SESSION_ACTIVE) {
            error_log('CRITICAL: Session could not be started! Login will fail.');
        }
        
        // Fallback: If session is empty but we have auth cookies, restore from cookies
        // Skip restore if user just logged out
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['auth_user_id'])) {
            $_SESSION['user_id'] = $_COOKIE['auth_user_id'];
            $_SESSION['name'] = $_COOKIE['auth_name'] ?? '';
            $_SESSION['email'] = $_COOKIE['auth_email'] ?? '';
            $_SESSION['role'] = $_COOKIE['auth_role'] ?? 'customer';
            error_log('Session restored from cookie for user: ' . $_SESSION['user_id']);
        }
    }
}

/**
 * Set persistent auth cookies as backup for sessions
 */
function setAuthCookies(int $userId, string $name, string $email, string $role): void {
    $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' 
               || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
    
    $cookieParams = [
        'expires' => time() + 86400, // 24 hours
        'path' => '/',
        'secure' => $isHttps,  // Use HTTPS in production for security
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    setcookie('auth_user_id', (string)$userId, $cookieParams);
    setcookie('auth_name', $name, $cookieParams);
    setcookie('auth_email', $email, $cookieParams);
    setcookie('auth_role', $role, $cookieParams);
}

/**
 * Clear auth cookies on logout
 */
function clearAuthCookies(): void {
    setcookie('auth_user_id', '', time() - 3600, '/');
    setcookie('auth_name', '', time() - 3600, '/');
    setcookie('auth_email', '', time() - 3600, '/');
    setcookie('auth_role', '', time() - 3600, '/');
}
