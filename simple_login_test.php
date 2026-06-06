<?php
/**
 * SIMPLE Login Test - Just shows if login works or not
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User submitted the form
    require_once 'config/db.php';
    require_once 'config/session.php';
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    echo "<!DOCTYPE html><html><head><title>Login Test Result</title>";
    echo "<style>body{font-family:Arial;background:#0a0a0a;color:#0f0;padding:40px;font-size:16px;}";
    echo ".success{color:#4f4;font-size:20px;font-weight:bold;}";
    echo ".error{color:#f44;font-size:20px;font-weight:bold;}";
    echo "pre{background:#1a1a1a;padding:15px;margin:15px 0;}</style></head><body>";
    
    echo "<h1>LOGIN TEST RESULT</h1><hr>";
    
    // Step 1: Query user
    echo "<h2>Step 1: Finding user...</h2>";
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "<p class='error'>❌ User not found: $email</p>";
        exit;
    }
    echo "<p class='success'>✅ User found: {$user['name']} (Role: {$user['role']})</p>";
    
    // Step 2: Verify password
    echo "<h2>Step 2: Verifying password...</h2>";
    if (!password_verify($password, $user['password_hash'])) {
        echo "<p class='error'>❌ Wrong password!</p>";
        exit;
    }
    echo "<p class='success'>✅ Password correct!</p>";
    
    // Step 3: Start session
    echo "<h2>Step 3: Starting session...</h2>";
    initializeSession();
    
    if (session_status() !== PHP_SESSION_ACTIVE) {
        echo "<p class='error'>❌ FAILED: Session could not start! This is the problem!</p>";
        echo "<pre>Session status: " . session_status() . " (should be 3)\n";
        echo "PHP version: " . PHP_VERSION . "\n";
        echo "Session save path: " . ini_get('session.save_path') . "</pre>";
        exit;
    }
    echo "<p class='success'>✅ Session started (ID: " . session_id() . ")</p>";
    
    // Step 4: Set session data
    echo "<h2>Step 4: Setting session data...</h2>";
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['login_time'] = time();
    
    echo "<p class='success'>✅ Session data set:</p>";
    echo "<pre>";
    echo "  user_id: {$_SESSION['user_id']}\n";
    echo "  name: {$_SESSION['name']}\n";
    echo "  email: {$_SESSION['email']}\n";
    echo "  role: {$_SESSION['role']}\n";
    echo "</pre>";
    
    // Step 5: Set cookies
    echo "<h2>Step 5: Setting cookies...</h2>";
    setAuthCookies($user['id'], $user['name'], $user['email'], $user['role']);
    echo "<p class='success'>✅ Cookies set</p>";
    
    // Step 6: Redirect decision
    echo "<h2>Step 6: Redirect decision...</h2>";
    $redirectUrl = ($user['role'] === 'admin' || $user['role'] === 'barber') ? 'admin_dashboard.php' : 'index.php';
    echo "<p class='success'>✅ Will redirect to: <strong>$redirectUrl</strong></p>";
    
    echo "<hr><h2 class='success'>✅✅✅ LOGIN SUCCESSFUL! ✅✅✅</h2>";
    echo "<p>You are now logged in as: <strong>{$user['name']}</strong> ({$user['role']})</p>";
    echo "<p>Redirecting in 3 seconds... or <a href='$redirectUrl' style='color:#48f'>click here</a></p>";
    
    echo "<meta http-equiv='refresh' content='3;url=$redirectUrl'>";
    
} else {
    // Show login form
    echo "<!DOCTYPE html><html><head><title>Simple Login Test</title>";
    echo "<style>body{font-family:Arial;background:#0a0a0a;color:#fff;padding:40px;}";
    echo "form{background:#1a1a1a;padding:30px;max-width:400px;margin:0 auto;border-radius:8px;}";
    echo "input{width:100%;padding:10px;margin:10px 0;background:#333;color:#fff;border:1px solid #555;}";
    echo "button{width:100%;padding:12px;background:#48f;color:#fff;border:none;cursor:pointer;font-size:16px;font-weight:bold;}";
    echo "h1{color:#48f;}</style></head><body>";
    
    echo "<h1>SIMPLE LOGIN TEST</h1>";
    echo "<form method='POST'>";
    echo "<label>Email:</label>";
    echo "<input type='email' name='email' value='dhondump@gmail.com' required>";
    echo "<label>Password:</label>";
    echo "<input type='password' name='password' value='lalala123' required>";
    echo "<button type='submit'>TEST LOGIN</button>";
    echo "</form>";
    
    echo "<p style='margin-top:20px;color:#888;'>This will show you EXACTLY what happens when you login</p>";
}
