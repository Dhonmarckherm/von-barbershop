<?php
/**
 * CRITICAL BUG TEST - Shows if session persists after redirect
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Session Redirect Test</title>";
echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
echo ".error{color:#f44;}.success{color:#4f4;}</style></head><body>";

echo "<h1>🔴 SESSION REDIRECT BUG TEST</h1>";
echo "<hr>";

require_once 'config/db.php';
require_once 'config/session.php';

// Simulate login
$email = 'dhondump@gmail.com';
$password = 'lalala123';

$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    echo "<p class='error'>❌ Login failed</p>";
    exit;
}

echo "<h2>Step 1: Login successful for {$user['name']}</h2>";
echo "<pre>Role: {$user['role']}\nID: {$user['id']}</pre>";

// Initialize session
initializeSession();
echo "<h2>Step 2: Session initialized</h2>";
echo "<pre>Session ID: " . session_id() . "\n";
echo "Session status: " . session_status() . "</pre>";

// Set session variables (like login.php does)
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

echo "<h2>Step 3: Session variables SET</h2>";
echo "<pre>user_id: " . $_SESSION['user_id'] . "\n";
echo "role: " . $_SESSION['role'] . "\n";
echo "Session ID: " . session_id() . "</pre>";

// Set cookies
setAuthCookies($user['id'], $user['name'], $user['email'], $user['role']);
echo "<h2>Step 4: Auth cookies SET</h2>";

// NOW TEST: Can we read the session immediately?
echo "<h2>Step 5: Reading session back</h2>";
echo "<pre>user_id from session: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "role from session: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "</pre>";

// NOW TEST: What if we include auth_check.php?
echo "<h2>Step 6: Testing auth_check.php</h2>";
echo "<p>Simulating what admin_dashboard.php does...</p>";

// Check if session would pass the auth_check
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✅ auth_check.php would PASS</p>";
} else {
    echo "<p class='error'>❌ auth_check.php would FAIL - redirect to login.php!</p>";
    echo "<p><strong>THIS IS THE BUG!</strong> Session is lost after redirect.</p>";
}

// Check role
echo "<h2>Step 7: Testing admin_check.php</h2>";
if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'barber')) {
    echo "<p class='success'>✅ admin_check.php would PASS</p>";
} else {
    echo "<p class='error'>❌ admin_check.php would FAIL - redirect to index.php!</p>";
    echo "<p>Role is: " . ($_SESSION['role'] ?? 'NOT SET') . "</p>";
}

echo "<hr><h2>🎯 CONCLUSION</h2>";
echo "<p>If all checks pass above, the issue is:</p>";
echo "<ul>";
echo "<li>Service worker caching old pages</li>";
echo "<li>Browser cookies blocked</li>";
echo "<li>Render free tier losing sessions on sleep</li>";
echo "</ul>";

echo "<hr><p><a href='index.php'>Go to homepage</a></p>";
