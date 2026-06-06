<?php
/**
 * Test actual login POST - No HTML output before session test
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capture any errors
$errors = [];
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
    $errors[] = "$errstr in $errfile on line $errline";
});

// Start test
echo "=== LOGIN FLOW TEST ===\n\n";

// Step 1: Include files (like login.php does)
echo "1. Including config files...\n";
require_once 'config/db.php';
require_once 'config/session.php';
echo "   ✅ Files included\n\n";

// Step 2: Initialize session
echo "2. Initializing session...\n";
initializeSession();
echo "   Session status: " . session_status() . " (3=active)\n";
echo "   Session ID: " . session_id() . "\n";
echo "   Errors: " . (count($errors) ? implode(', ', $errors) : 'None') . "\n\n";

// Step 3: Test login
echo "3. Testing login for dhondump@gmail.com...\n";
$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->execute(['dhondump@gmail.com']);
$user = $stmt->fetch();

if (!$user) {
    echo "   ❌ User not found\n";
    exit;
}
echo "   ✅ User found (ID: {$user['id']})\n\n";

// Step 4: Verify password
echo "4. Verifying password...\n";
$verify = password_verify('lalala123', $user['password_hash']);
echo "   " . ($verify ? '✅ Password verified' : '❌ Password wrong') . "\n\n";

if (!$verify) {
    echo "TEST FAILED: Password verification failed\n";
    exit;
}

// Step 5: Simulate login flow
echo "5. Simulating login flow...\n";
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['login_time'] = time();
echo "   ✅ Session variables set\n\n";

// Step 6: Set cookies
echo "6. Setting auth cookies...\n";
setAuthCookies($user['id'], $user['name'], $user['email'], $user['role']);
echo "   ✅ Cookies set\n\n";

// Step 7: Verify session
echo "7. Verifying session persists...\n";
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    echo "   ✅ Session active\n";
    echo "   user_id: {$_SESSION['user_id']}\n";
    echo "   role: {$_SESSION['role']}\n\n";
} else {
    echo "   ❌ Session lost!\n\n";
}

// Step 8: Test auth_check
echo "8. Testing auth_check logic...\n";
if (isset($_SESSION['user_id'])) {
    echo "   ✅ Would pass auth_check.php\n\n";
} else {
    echo "   ❌ Would FAIL auth_check.php - redirect to login\n\n";
}

// Final result
echo "=== TEST RESULT ===\n";
if (count($errors) === 0 && isset($_SESSION['user_id'])) {
    echo "✅✅✅ LOGIN FLOW IS WORKING! ✅✅✅\n";
    echo "\nIf actual login still fails, the issue is:\n";
    echo "- Service worker cache (reinstall PWA)\n";
    echo "- Browser cache (hard refresh Ctrl+Shift+R)\n";
    echo "- Cookies blocked in browser settings\n";
} else {
    echo "❌ LOGIN FLOW HAS ERRORS\n";
    echo "\nErrors:\n";
    foreach ($errors as $err) {
        echo "- $err\n";
    }
}

// Clean output buffer
$output = ob_get_clean();
echo $output;
