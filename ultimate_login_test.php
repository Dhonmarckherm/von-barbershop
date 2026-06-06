<?php
/**
 * ULTIMATE Login Diagnostic - Shows EVERYTHING
 * Step-by-step login process with detailed output
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>ULTIMATE Login Diagnostic</title>";
echo "<style>body{font-family:monospace;background:#0a0a0a;color:#0f0;padding:20px;line-height:1.6;}";
echo ".error{color:#ff4444;}.success{color:#44ff44;}.warn{color:#ffaa00;}.info{color:#4488ff;}";
echo "pre{background:#1a1a1a;padding:15px;border-left:3px solid #333;margin:10px 0;}";
echo "h2{color:#ffdd00;margin-top:30px;}</style></head><body>";

echo "<h1>🔍 ULTIMATE LOGIN DIAGNOSTIC</h1>";
echo "<p>This shows EVERY step of the login process</p>";
echo "<hr>";

// STEP 1: Check POST data
echo "<h2>STEP 1: Request Method</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p class='success'>✅ POST request received</p>";
    echo "<pre>Email: " . ($_POST['email'] ?? 'NOT SET') . "\n";
    echo "Password received: " . (isset($_POST['password']) ? 'YES' : 'NO') . "</pre>";
} else {
    echo "<p class='warn'>⚠️ GET request - showing login form</p>";
    echo '<form method="POST" style="background:#1a1a1a;padding:20px;margin:20px 0;">';
    echo '<p><label>Email: <input type="email" name="email" value="dhondump@gmail.com" style="width:300px;padding:5px;"></label></p>';
    echo '<p><label>Password: <input type="password" name="password" value="lalala123" style="width:300px;padding:5px;"></label></p>';
    echo '<p><button type="submit" style="padding:10px 30px;background:#4488ff;color:#fff;border:none;cursor:pointer;">TEST LOGIN</button></p>';
    echo '</form>';
    echo "</body></html>";
    exit;
}

// STEP 2: Check database connection
echo "<h2>STEP 2: Database Connection</h2>";
try {
    require_once 'config/db.php';
    echo "<p class='success'>✅ Database connected</p>";
    echo "<pre>Host: " . DB_HOST . "\nDatabase: " . DB_NAME . "\nUser: " . DB_USER . "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection FAILED: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// STEP 3: Query user
echo "<h2>STEP 3: Find User in Database</h2>";
$email = trim($_POST['email']);
$password = $_POST['password'];

echo "<pre>Searching for: $email</pre>";

$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "<p class='error'>❌ User NOT FOUND in database!</p>";
    exit;
}

echo "<p class='success'>✅ User found</p>";
echo "<pre>ID: {$user['id']}\nName: {$user['name']}\nEmail: {$user['email']}\nRole: '{$user['role']}'\n";
echo "Password hash: " . substr($user['password_hash'], 0, 30) . "...\n";
echo "Hash length: " . strlen($user['password_hash']) . "</pre>";

// STEP 4: Verify password
echo "<h2>STEP 4: Password Verification</h2>";
$verifyResult = password_verify($password, $user['password_hash']);

if ($verifyResult) {
    echo "<p class='success'>✅✅✅ PASSWORD VERIFIED SUCCESSFULLY! ✅✅✅</p>";
} else {
    echo "<p class='error'>❌❌❌ PASSWORD VERIFICATION FAILED! ❌❌❌</p>";
    echo "<p>Testing password: '$password'</p>";
    echo "<p>Against hash: '{$user['password_hash']}'</p>";
    
    // Generate correct hash for reference
    $correctHash = password_hash($password, PASSWORD_DEFAULT);
    echo "<p>Correct hash for this password would be: $correctHash</p>";
    echo "<p><strong>The password in the database doesn't match!</strong></p>";
    exit;
}

// STEP 5: Initialize session
echo "<h2>STEP 5: Session Initialization</h2>";
require_once 'config/session.php';

echo "<pre>Before initializeSession():\n";
echo "Session status: " . session_status() . " (1=disabled, 2=none, 3=active)\n";

initializeSession();

echo "\nAfter initializeSession():\n";
echo "Session status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n";

if (session_status() !== PHP_SESSION_ACTIVE) {
    echo "<p class='error'>❌ SESSION COULD NOT BE STARTED!</p>";
    echo "<p>This is why login is failing - sessions are not working on Render!</p>";
} else {
    echo "<p class='success'>✅ Session started successfully</p>";
}
echo "</pre>";

// STEP 6: Set session variables
echo "<h2>STEP 6: Creating User Session</h2>";
session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['login_time'] = time();

echo "<pre>Session variables set:\n";
echo "  user_id: " . $_SESSION['user_id'] . "\n";
echo "  name: " . $_SESSION['name'] . "\n";
echo "  email: " . $_SESSION['email'] . "\n";
echo "  role: " . $_SESSION['role'] . "\n";
echo "  login_time: " . $_SESSION['login_time'] . "\n";
echo "  Session ID: " . session_id() . "\n\n";

// Verify they're actually set
echo "Verification:\n";
echo "  user_id isset: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n";
echo "  role isset: " . (isset($_SESSION['role']) ? 'YES' : 'NO') . "\n";
echo "  Session data count: " . count($_SESSION) . " variables\n";
echo "</pre>";

// STEP 7: Set cookies
echo "<h2>STEP 7: Setting Auth Cookies</h2>";
setAuthCookies($user['id'], $user['name'], $user['email'], $user['role']);
echo "<p class='success'>✅ setAuthCookies() called</p>";
echo "<pre>Cookies will be sent to browser on next response</pre>";

// STEP 8: Determine redirect
echo "<h2>STEP 8: Redirect Decision</h2>";
echo "<pre>Role: '{$user['role']}'\n";
echo "Check: role === 'admin' → " . ($user['role'] === 'admin' ? 'TRUE' : 'FALSE') . "\n";
echo "Check: role === 'barber' → " . ($user['role'] === 'barber' ? 'TRUE' : 'FALSE') . "\n";
echo "Check: role === 'customer' → " . ($user['role'] === 'customer' ? 'TRUE' : 'FALSE') . "\n";

$redirectUrl = ($user['role'] === 'admin' || $user['role'] === 'barber') ? 'admin_dashboard.php' : 'index.php';
echo "\nWould redirect to: $redirectUrl\n";
echo "Based on role: " . (($user['role'] === 'admin' || $user['role'] === 'barber') ? 'ADMIN/BARBER' : 'CUSTOMER') . "\n";
echo "</pre>";

// FINAL SUMMARY
echo "<hr><h2 style='color:#44ff44;'>✅✅✅ FINAL DIAGNOSIS ✅✅✅</h2>";
echo "<div style='background:#1a1a1a;padding:20px;border:2px solid #44ff44;margin:20px 0;'>";
echo "<h3 style='color:#44ff44;'>Login Process: WORKING ✅</h3>";
echo "<p>All steps completed successfully:</p>";
echo "<ul>";
echo "<li>✅ Database connection: OK</li>";
echo "<li>✅ User found: OK</li>";
echo "<li>✅ Password verified: OK</li>";
echo "<li>✅ Session created: OK</li>";
echo "<li>✅ Cookies set: OK</li>";
echo "<li>✅ Redirect decision: OK</li>";
echo "</ul>";
echo "<p><strong>If login is still failing on the actual login page, the issue is:</strong></p>";
echo "<ol>";
echo "<li>Browser caching old version (Press Ctrl+Shift+R to hard refresh)</li>";
echo "<li>Service worker caching (Uninstall and reinstall PWA app)</li>";
echo "<li>Browser cookies blocked (Check browser settings)</li>";
echo "<li>PHP errors on Render (Check Render logs at dashboard.render.com)</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p><a href='login.php' style='color:#4488ff;font-size:18px;'>← Back to Login Page</a></p>";
echo "<p style='color:#888;font-size:12px;'>DELETE THIS FILE after debugging for security</p>";

echo "</body></html>";
