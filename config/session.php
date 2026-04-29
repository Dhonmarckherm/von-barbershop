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
        
        // Session cookie parameters
        session_set_cookie_params([
            'lifetime' => 86400,        // 24 hours
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.use_strict_mode', 0);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        
        session_start();
        
        // Fallback: If session is empty but we have auth cookies, restore from cookies
        if (!isset($_SESSION['user_id']) && isset($_COOKIE['auth_user_id'])) {
            $_SESSION['user_id'] = $_COOKIE['auth_user_id'];
            $_SESSION['name'] = $_COOKIE['auth_name'] ?? '';
            $_SESSION['email'] = $_COOKIE['auth_email'] ?? '';
            $_SESSION['role'] = $_COOKIE['auth_role'] ?? 'customer';
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
        'secure' => $isHttps,
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
