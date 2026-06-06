<?php
/**
 * SIMPLE Login Test - Completely independent, no session initialization
 * Shows if login works at the database level
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User submitted the form
    require_once 'config/db.php';
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    echo "<!DOCTYPE html><html><head><title>Login Test Result</title>";
    echo "<style>body{font-family:monospace;background:#0a0a0a;color:#0f0;padding:40px;font-size:16px;line-height:1.8;}";
    echo ".success{color:#4f4;font-size:24px;font-weight:bold;}";
    echo ".error{color:#f44;font-size:24px;font-weight:bold;}";
    echo "pre{background:#1a1a1a;padding:20px;margin:20px 0;border-left:4px solid #333;}</style></head><body>";
    
    echo "<h1>🔍 LOGIN TEST - STEP BY STEP</h1><hr>";
    
    // Step 1: Query user
    echo "<h2 style='color:#ff0;'>Step 1: Finding user in database...</h2>";
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p class='error'>❌ FAIL: User not found: $email</p>";
        echo "</body></html>";
        exit;
    }
    echo "<p class='success'>✅ PASS: User found</p>";
    echo "<pre>Name: {$user['name']}\nEmail: {$user['email']}\nRole: {$user['role']}\nID: {$user['id']}</pre>";
    
    // Step 2: Verify password
    echo "<h2 style='color:#ff0;'>Step 2: Verifying password...</h2>";
    if (!password_verify($password, $user['password_hash'])) {
        echo "<p class='error'>❌ FAIL: Wrong password!</p>";
        echo "<p>Entered: '$password'</p>";
        echo "</body></html>";
        exit;
    }
    echo "<p class='success'>✅ PASS: Password verified correctly!</p>";
    
    // Step 3: Check role
    echo "<h2 style='color:#ff0;'>Step 3: Checking role for redirect...</h2>";
    echo "<pre>Role: '{$user['role']}'\n";
    echo "Is admin? " . ($user['role'] === 'admin' ? 'YES' : 'NO') . "\n";
    echo "Is barber? " . ($user['role'] === 'barber' ? 'YES' : 'NO') . "\n";
    echo "Is customer? " . ($user['role'] === 'customer' ? 'YES' : 'NO') . "\n";
    echo "Is Customer? " . ($user['role'] === 'Customer' ? 'YES' : 'NO') . "\n";
    
    $redirectUrl = ($user['role'] === 'admin' || $user['role'] === 'barber') ? 'admin_dashboard.php' : 'index.php';
    echo "\nWould redirect to: $redirectUrl\n";
    echo "Should go to: " . (($user['role'] === 'admin' || $user['role'] === 'barber') ? 'ADMIN DASHBOARD' : 'CUSTOMER HOMEPAGE') . "\n";
    echo "</pre>";
    
    // Final result
    echo "<hr>";
    echo "<div style='background:#1a3a1a;padding:30px;border:3px solid #4f4;margin:30px 0;'>";
    echo "<h2 class='success'>✅✅✅ ALL TESTS PASSED! ✅✅✅</h2>";
    echo "<p style='color:#fff;font-size:18px;'>Login is working at the database level!</p>";
    echo "<p style='color:#fff;'>If the actual login.php page still refreshes/redirects, the issue is:</p>";
    echo "<ol style='color:#fff;'>";
    echo "<li>Browser cache - Press Ctrl+Shift+R to hard refresh</li>";
    echo "<li>Service worker cache - Clear all site data</li>";
    echo "<li>Code not deployed yet - Wait 2-3 minutes for Render to rebuild</li>";
    echo "<li>Session not working - Check Render logs</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<hr>";
    echo "<p><a href='simple_login_test.php' style='color:#48f;font-size:18px;'>← Test Again</a></p>";
    echo "<p><a href='login.php' style='color:#48f;font-size:18px;margin-left:30px;'>Go to Actual Login Page</a></p>";
    echo "</body></html>";
    
} else {
    // Show login form - NO session, NO initialization, NO redirects
    echo "<!DOCTYPE html><html><head><title>Simple Login Test</title>";
    echo "<style>body{font-family:monospace;background:#0a0a0a;color:#fff;padding:40px;}";
    echo "form{background:#1a1a1a;padding:40px;max-width:450px;margin:50px auto;border-radius:8px;border:2px solid #48f;}";
    echo "input{width:100%;padding:12px;margin:10px 0;background:#333;color:#fff;border:1px solid #555;font-size:16px;}";
    echo "button{width:100%;padding:15px;background:#48f;color:#fff;border:none;cursor:pointer;font-size:18px;font-weight:bold;margin-top:15px;}";
    echo "button:hover{background:#59f;}";
    echo "h1{color:#48f;text-align:center;}";
    echo "p{color:#888;text-align:center;}</style></head><body>";
    
    echo "<h1>🔐 SIMPLE LOGIN TEST</h1>";
    echo "<p>This tests ONLY the database login - no sessions, no redirects</p>";
    echo "<form method='POST'>";
    echo "<label style='color:#aaa;'>Email:</label>";
    echo "<input type='email' name='email' value='dhondump@gmail.com' required>";
    echo "<label style='color:#aaa;'>Password:</label>";
    echo "<input type='password' name='password' value='lalala123' required>";
    echo "<button type='submit'>🔍 TEST LOGIN (No Redirects)</button>";
    echo "</form>";
    
    echo "<p style='margin-top:30px;'>Click the button to see step-by-step if login works</p>";
    echo "<p>Will NOT redirect - shows results on this page</p>";
}
