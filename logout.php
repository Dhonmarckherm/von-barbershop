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

header('Location: index.php');
exit;
