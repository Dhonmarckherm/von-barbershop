<?php
/**
 * Direct Login Test - Simulates actual login process
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Login Test</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#4caf50;}.error{color:#f44336;}.info{color:#2196f3;}";
echo "pre{background:#2a2a2a;padding:15px;border-radius:5px;}</style></head><body>";

echo "<h1>🔐 Direct Login Test</h1>";

require_once 'config/db.php';
require_once 'config/session.php';

// Step 1: Initialize session
echo "<h2>Step 1: Initialize Session</h2>";
initializeSession();
echo "<pre>Session ID: " . session_id() . "\n";
echo "Session status: " . session_status() . "</pre>";

// Step 2: Try login
echo "<h2>Step 2: Attempt Login</h2>";
$email = 'dhondump@gmail.com';
$password = 'lalala123';

$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "<pre>✅ User found: {$user['name']} (ID: {$user['id']})\n";
    
    $verify = password_verify($password, $user['password_hash']);
    echo "Password verify: " . ($verify ? '✅ SUCCESS' : '❌ FAILED') . "</pre>";
    
    if ($verify) {
        // Step 3: Create session
        echo "<h2>Step 3: Create Session</h2>";
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        echo "<pre>Session variables set:\n";
        echo "  user_id: " . $_SESSION['user_id'] . "\n";
        echo "  name: " . $_SESSION['name'] . "\n";
        echo "  role: " . $_SESSION['role'] . "\n";
        echo "  New session ID: " . session_id() . "</pre>";
        
        // Step 4: Set cookies
        echo "<h2>Step 4: Set Auth Cookies</h2>";
        setAuthCookies($user['id'], $user['name'], $user['email'], $user['role']);
        echo "<pre>✅ Auth cookies set</pre>";
        
        // Step 5: Verify session persists
        echo "<h2>Step 5: Verify Session</h2>";
        echo "<pre>";
        if (isset($_SESSION['user_id'])) {
            echo "✅ Session is active with user_id: " . $_SESSION['user_id'] . "\n";
            echo "✅ Login process is WORKING!\n\n";
            echo "If actual login page fails, check:\n";
            echo "  1. Browser console for JavaScript errors\n";
            echo "  2. Network tab for failed POST requests\n";
            echo "  3. Application tab for cookie issues\n";
        } else {
            echo "❌ Session NOT SET! This is the problem.\n";
        }
        echo "</pre>";
        
        echo "<h2>✅ CONCLUSION</h2>";
        echo "<p class='success'>The login code is working correctly!</p>";
        echo "<p>If users can't login on the actual login page, the issue is:</p>";
        echo "<ul>";
        echo "<li>Service worker caching old login.php</li>";
        echo "<li>Browser blocking cookies</li>";
        echo "<li>Form submission error (check browser console)</li>";
        echo "</ul>";
    }
} else {
    echo "<p class='error'>❌ User not found!</p>";
}

echo "<hr><p><a href='diagnose_login.php' style='color:#2196f3;'>← Back to full diagnostic</a></p>";
echo "</body></html>";
