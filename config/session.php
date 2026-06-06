<?php
/**
 * Centralized Session Configuration
 * This file should be included before any session_start() calls
 */

function initializeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Detect if we're on HTTPS (production) or HTTP (local)
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                   || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                   || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        
        // Use default session path unless we're on Render
        if (getenv('RENDER')) {
            $sessionPath = sys_get_temp_dir() . '/sessions';
            if (!is_dir($sessionPath)) {
                @mkdir($sessionPath, 0777, true);
            }
            if (is_dir($sessionPath) && is_writable($sessionPath)) {
                ini_set('session.save_path', $sessionPath);
            }
        }

        // Session cookie parameters
        // For PWAs on Safari, Lax is usually better than None
        session_set_cookie_params([
            'lifetime' => 86400 * 30,   // 30 days
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.gc_maxlifetime', 86400 * 30);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);

        // Start session
        if (!session_start()) {
            error_log('Failed to start session');
        }
    }
}

/**
 * Set persistent auth cookies as backup for sessions
 */
function setAuthCookies(int $userId, string $name, string $email, string $role): void {
    // ALWAYS use secure=true for production (Render uses HTTPS with reverse proxy)
    $isHttps = true; // Render always serves over HTTPS
    
    $cookieParams = [
        'expires' => time() + (86400 * 30), // 30 days
        'path' => '/',
        'domain' => '',
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
