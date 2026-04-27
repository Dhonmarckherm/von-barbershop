<?php
/**
 * Centralized Session Configuration
 * This file should be included before any session_start() calls
 */

function initializeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Session cookie parameters MUST be set before session_start()
        session_set_cookie_params([
            'lifetime' => 86400,        // 24 hours
            'path' => '/',
            'domain' => '',
            'secure' => false,          // Set to true if using HTTPS
            'httponly' => true,         // Prevent JavaScript access
            'samesite' => 'Lax'         // CSRF protection
        ]);

        // Garbage collection settings
        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.use_strict_mode', 1);
        
        // Start the session
        session_start();
    }
}
