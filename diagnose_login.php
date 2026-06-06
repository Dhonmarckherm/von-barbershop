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

// Step 3: Test Password Verification
echo "<h2>Step 3: Test Password Verification</h2>";
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
