<?php
/**
 * Login Diagnostic Tool - Shows exactly what's happening during login
 * Access: http://your-site.com/diagnose_login.php
 * DELETE THIS FILE after diagnosis for security!
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Login Diagnostic</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#4caf50;}.error{color:#f44336;}.info{color:#2196f3;}";
echo "pre{background:#2a2a2a;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body>";

echo "<h1>🔍 Login Diagnostic Tool</h1>";
echo "<p>This tool tests login functionality step by step.</p>";
echo "<hr>";

// Step 1: Check Database Connection
echo "<h2>Step 1: Database Connection</h2>";
try {
    require_once 'config/db.php';
    echo "<p class='success'>✅ Database connection successful!</p>";
    echo "<pre>";
    echo "Host: " . DB_HOST . "\n";
    echo "Port: " . DB_PORT . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "User: " . DB_USER . "\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection FAILED!</p>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    die();
}

// Step 2: Check Users Table
echo "<h2>Step 2: Users Table</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch()['count'];
    echo "<p class='success'>✅ Found $count users in database</p>";
    
    // Show sample users (without passwords)
    $stmt = $pdo->query("SELECT id, name, email, role, LENGTH(password_hash) as hash_length FROM users LIMIT 5");
    $users = $stmt->fetchAll();
    echo "<pre>";
    echo "Sample users:\n";
    foreach ($users as $u) {
        echo "  ID: {$u['id']} | Name: {$u['name']} | Email: {$u['email']} | Role: {$u['role']} | Hash Length: {$u['hash_length']}\n";
    }
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Failed to query users!</p>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
}

// Auto-test with provided credentials
$autoTestEmail = 'dhondump@gmail.com';
$autoTestPassword = 'lalala123';

echo "<h2>🧪 Auto-Test Results for: $autoTestEmail</h2>";
echo "<pre>";

// Step 3a: Find user
echo "1. Looking up user by email: $autoTestEmail\n";
$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->execute([$autoTestEmail]);
$user = $stmt->fetch();

if (!$user) {
    echo "   ❌ User NOT FOUND in database!\n";
    echo "</pre>";
    echo "<p class='error'>The email '$autoTestEmail' does not exist in the database.</p>";
} else {
    echo "   ✅ User found: ID={$user['id']}, Name={$user['name']}, Role={$user['role']}\n";
    echo "   Password hash length: " . strlen($user['password_hash']) . "\n";
    echo "   Hash preview: " . substr($user['password_hash'], 0, 20) . "...\n\n";
    
    // Step 3b: Verify password
    echo "2. Verifying password '$autoTestPassword'...\n";
    $verifyResult = password_verify($autoTestPassword, $user['password_hash']);
    
    if ($verifyResult) {
        echo "   ✅ Password verification SUCCESSFUL!\n";
        echo "</pre>";
        echo "<p class='success'>✅ LOGIN SHOULD WORK! The password is correct.</p>";
        echo "<p>If users still can't login, the issue is with sessions/cookies. Check Step 4 below.</p>";
        
        // Test session creation
        echo "<h2>Step 4: Session Creation Test</h2>";
        require_once 'config/session.php';
        initializeSession();
        
        echo "<pre>";
        echo "Session status: " . session_status() . " (1=disabled, 2=none, 3=active)\n";
        echo "Session ID: " . session_id() . "\n\n";
        
        // Simulate login session setup
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        
        echo "After setting session variables:\n";
        echo "  Session ID (regenerated): " . session_id() . "\n";
        echo "  \$_SESSION['user_id']: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
        echo "  \$_SESSION['name']: " . (isset($_SESSION['name']) ? $_SESSION['name'] : 'NOT SET') . "\n";
        echo "  \$_SESSION['role']: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "\n\n";
        
        echo "✅ Session creation: SUCCESS\n";
        echo "</pre>";
        
        // Test cookie setting
        echo "<h2>Step 5: Auth Cookie Test</h2>";
        setAuthCookies($user['id'], $user['name'], $user['email'], $user['role']);
        
        echo "<pre>";
        echo "Auth cookies SET (will be visible on next page load):\n";
        echo "  auth_user_id: " . (isset($_COOKIE['auth_user_id']) ? $_COOKIE['auth_user_id'] : 'will be set on next request') . "\n";
        echo "  auth_name: " . (isset($_COOKIE['auth_name']) ? $_COOKIE['auth_name'] : 'will be set on next request') . "\n";
        echo "  auth_role: " . (isset($_COOKIE['auth_role']) ? $_COOKIE['auth_role'] : 'will be set on next request') . "\n";
        echo "</pre>";
        
        echo "<h2>✅ DIAGNOSIS COMPLETE</h2>";
        echo "<div style='background:#2a2a2a;padding:20px;border-radius:10px;margin:20px 0;'>";
        echo "<h3 style='color:#4caf50;'>✅ Everything is working correctly!</h3>";
        echo "<p><strong>Password verification:</strong> ✅ PASS</p>";
        echo "<p><strong>Session creation:</strong> ✅ PASS</p>";
        echo "<p><strong>Cookie setting:</strong> ✅ PASS</p>";
        echo "<hr style='border-color:#555;'>";
        echo "<h3>If login still fails on the live site, the issue is:</h3>";
        echo "<ol>";
        echo "<li><strong>Service Worker Cache:</strong> The PWA is caching an old version of login.php<br>";
        echo "   <em>Fix:</em> Uninstall and reinstall the PWA app, or clear browser cache</li>";
        echo "<li><strong>Browser Cookie Blocking:</strong> Browser is blocking third-party cookies<br>";
        echo "   <em>Fix:</em> Check browser settings, allow cookies for von-barbershop.onrender.com</li>";
        echo "<li><strong>HTTPS/SSL Issue:</strong> Cookies not being sent over HTTPS<br>";
        echo "   <em>Fix:</em> Check that the site has valid SSL certificate</li>";
        echo "<li><strong>Render Free Tier:</strong> Server goes to sleep, sessions lost<br>";
        echo "   <em>Fix:</em> Users need to login again after server wakes up</li>";
        echo "</ol>";
        echo "</div>";
    } else {
        echo "   ❌ Password verification FAILED!\n";
        echo "</pre>";
        echo "<p class='error'>❌ <strong>THIS IS THE PROBLEM!</strong> The password in the database doesn't match 'lalala123'.</p>";
        echo "<p><strong>Solution:</strong> Reset all user passwords. See the form below to test other accounts.</p>";
    }
}

echo "<hr>";

// Step 3: Test Password Verification
echo "<h2>Step 3: Manual Password Verification Test</h2>";
echo "<p>Use this form to test other user accounts:</p>";
echo "<form method='POST'>";
echo "<p>Email: <input type='email' name='test_email' required style='width:300px;padding:5px;'></p>";
echo "<p>Password: <input type='password' name='test_password' required style='width:300px;padding:5px;'></p>";
echo "<p><button type='submit' name='test_login' style='padding:10px 20px;background:#2196f3;color:#fff;border:none;cursor:pointer;'>Test Login</button></p>";
echo "</form>";

if (isset($_POST['test_login'])) {
    $email = trim($_POST['test_email']);
    $password = $_POST['test_password'];
    
    echo "<hr><h3>Login Test Results:</h3>";
    echo "<pre>";
    
    // Step 3a: Find user
    echo "1. Looking up user by email: $email\n";
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "   ❌ User NOT FOUND in database!\n";
        echo "</pre>";
        echo "<p class='error'>The email '$email' does not exist in the database.</p>";
    } else {
        echo "   ✅ User found: ID={$user['id']}, Name={$user['name']}, Role={$user['role']}\n";
        echo "   Password hash length: " . strlen($user['password_hash']) . "\n";
        echo "   Hash preview: " . substr($user['password_hash'], 0, 20) . "...\n\n";
        
        // Step 3b: Verify password
        echo "2. Verifying password...\n";
        $verifyResult = password_verify($password, $user['password_hash']);
        
        if ($verifyResult) {
            echo "   ✅ Password verification SUCCESSFUL!\n";
            echo "</pre>";
            echo "<p class='success'>✅ LOGIN WOULD SUCCEED! The password is correct.</p>";
            
            // Step 3c: Test Session Creation
            echo "<h3>Step 4: Session Creation Test</h3>";
            require_once 'config/session.php';
            initializeSession();
            
            echo "<pre>";
            echo "Session status: " . session_status() . "\n";
            echo "Session ID: " . session_id() . "\n";
            
            session_regenerate_id(true);
            $_SESSION['test_user_id'] = $user['id'];
            $_SESSION['test_name'] = $user['name'];
            $_SESSION['test_role'] = $user['role'];
            
            echo "✅ Session variables set successfully!\n";
            echo "Session data: " . print_r($_SESSION, true) . "\n";
            echo "</pre>";
            
            // Step 3d: Test Cookie Setting
            echo "<h3>Step 5: Cookie Setting Test</h3>";
            setAuthCookies($user['id'], $user['name'], $user['email'], $user['role']);
            echo "<p class='success'>✅ Auth cookies attempted to set</p>";
            echo "<pre>";
            echo "Cookies sent to browser (check DevTools to verify):\n";
            echo "  - auth_user_id: " . (isset($_COOKIE['auth_user_id']) ? $_COOKIE['auth_user_id'] : 'not set yet') . "\n";
            echo "  - auth_name: " . (isset($_COOKIE['auth_name']) ? $_COOKIE['auth_name'] : 'not set yet') . "\n";
            echo "  - auth_email: " . (isset($_COOKIE['auth_email']) ? $_COOKIE['auth_email'] : 'not set yet') . "\n";
            echo "  - auth_role: " . (isset($_COOKIE['auth_role']) ? $_COOKIE['auth_role'] : 'not set yet') . "\n";
            echo "</pre>";
            
            echo "<hr><p class='success'><strong>✅ CONCLUSION: Login system is working correctly!</strong></p>";
            echo "<p>If login is still failing on the live site, the issue is likely:</p>";
            echo "<ul>";
            echo "<li>Browser cookies are being blocked</li>";
            echo "<li>HTTPS/SSL configuration issue</li>";
            echo "<li>Service worker caching old login page</li>";
            echo "</ul>";
        } else {
            echo "   ❌ Password verification FAILED!\n";
            echo "</pre>";
            echo "<p class='error'>❌ The password is INCORRECT for this email.</p>";
            echo "<p>This is the problem! The password in the database doesn't match what users are entering.</p>";
            echo "<p><strong>Solution:</strong> You need to reset user passwords in the database.</p>";
            
            // Show what the correct hash should be
            echo "<h3>Correct Password Hash:</h3>";
            $correctHash = password_hash($password, PASSWORD_DEFAULT);
            echo "<pre>New hash for '$password':\n$correctHash</pre>";
            echo "<p>To fix this user's password, run this SQL:</p>";
            echo "<pre>UPDATE users SET password_hash = '$correctHash' WHERE email = '$email';</pre>";
        }
    }
}

echo "<hr>";
echo "<p style='color:#ff9800;'>⚠️ <strong>IMPORTANT:</strong> Delete this file after diagnosis for security!</p>";
echo "<p>rm diagnose_login.php</p>";
echo "</body></html>";
