<?php
require_once 'config/session.php';
initializeSession();

echo "<h1>Cookie & Session Test</h1>";

// Check if cookies exist
echo "<h2>Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

// Check session
echo "<h2>Session:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Set a test cookie
$isHttps = true;
setcookie('test_cookie', 'working', time() + 3600, '/', '', $isHttps, true);
echo "<h2>Test cookie set: 'working'</h2>";

// Set auth cookies for testing
setcookie('auth_user_id', '999', time() + 3600, '/', '', $isHttps, true);
setcookie('auth_name', 'TestUser', time() + 3600, '/', '', $isHttps, true);
setcookie('auth_email', 'test@test.com', time() + 3600, '/', '', $isHttps, true);
setcookie('auth_role', 'admin', time() + 3600, '/', '', $isHttps, true);
echo "<h2>Auth cookies set for testing</h2>";

echo "<hr>";
echo "<a href='test_cookies.php'>Refresh to see if cookies persist</a><br>";
echo "<a href='index.php'>Go to Home</a><br>";
echo "<a href='login.php'>Go to Login</a>";
