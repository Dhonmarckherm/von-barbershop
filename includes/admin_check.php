<?php
/**
 * Admin Role Guard
 * Redirects non-admin users to the index page.
 * Must be included AFTER auth_check.php.
 */
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'barber')) {
    header('Location: index.php');
    exit;
}
