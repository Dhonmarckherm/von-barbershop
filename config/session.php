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
        
        // Session cookie parameters MUST be set before session_start()
        session_set_cookie_params([
            'lifetime' => 86400,        // 24 hours
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,       // Auto-detect HTTPS
            'httponly' => true,         // Prevent JavaScript access
            'samesite' => 'None'        // Required for cross-site requests on Render
        ]);

        // Garbage collection settings
        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.use_strict_mode', 0);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        
        // Start the session
        session_start();
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}
