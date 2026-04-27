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
