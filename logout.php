<?php
/**
 * Logout Script
 * Destroys the session and redirects to the homepage.
 */
require_once 'config/session.php';
initializeSession();

// Log out info for debugging (optional)
if (isset($_SESSION['user_id'])) {
    error_log("User {$_SESSION['user_id']} ({$_SESSION['email']}) logged out");
}

// Clear auth cookies FIRST before destroying session
$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' 
           || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';

setcookie('auth_user_id', '', time() - 3600, '/', '', $isHttps, true);
setcookie('auth_name', '', time() - 3600, '/', '', $isHttps, true);
setcookie('auth_email', '', time() - 3600, '/', '', $isHttps, true);
setcookie('auth_role', '', time() - 3600, '/', '', $isHttps, true);

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Clear the session cookie
if (isset($_COOKIE[session_name()])) {
    unset($_COOKIE[session_name()]);
}

// Clear all auth cookie variables
unset($_COOKIE['auth_user_id']);
unset($_COOKIE['auth_name']);
unset($_COOKIE['auth_email']);
unset($_COOKIE['auth_role']);

header('Location: login.php?logout=1');
exit;
