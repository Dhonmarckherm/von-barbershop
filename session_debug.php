<?php
/**
 * Session Debug - Shows current session state
 * Shows EXACTLY what role the session has
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Session Debug</title>";
echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
echo ".error{color:#f44;}.success{color:#4f4;}.warn{color:#ff0;}</style></head><body>";

echo "<h1>🔍 SESSION DEBUG - CURRENT STATE</h1>";
echo "<hr>";

require_once 'config/session.php';
initializeSession();

echo "<h2>Current Session Data:</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . " (3=active)\n\n";

echo "Session Variables:\n";
echo "  user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
echo "  name: " . (isset($_SESSION['name']) ? $_SESSION['name'] : 'NOT SET') . "\n";
echo "  email: " . (isset($_SESSION['email']) ? $_SESSION['email'] : 'NOT SET') . "\n";
echo "  role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET') . "\n";
echo "  login_time: " . (isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'NOT SET') . "\n";
echo "</pre>";

// Check cookies
echo "<h2>Auth Cookies:</h2>";
echo "<pre>";
echo "  auth_user_id: " . (isset($_COOKIE['auth_user_id']) ? $_COOKIE['auth_user_id'] : 'NOT SET') . "\n";
echo "  auth_name: " . (isset($_COOKIE['auth_name']) ? $_COOKIE['auth_name'] : 'NOT SET') . "\n";
echo "  auth_email: " . (isset($_COOKIE['auth_email']) ? $_COOKIE['auth_email'] : 'NOT SET') . "\n";
echo "  auth_role: " . (isset($_COOKIE['auth_role']) ? $_COOKIE['auth_role'] : 'NOT SET') . "\n";
echo "</pre>";

// Check what header.php will show
echo "<h2>What Header Will Display:</h2>";
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'barber');

echo "<pre>";
echo "  isLoggedIn: " . ($isLoggedIn ? 'TRUE' : 'FALSE') . "\n";
echo "  isAdmin: " . ($isAdmin ? 'TRUE' : 'FALSE') . "\n";
echo "  Will show: " . ($isAdmin ? 'ADMIN MENU (Dashboard, Manage Users, Settings)' : 'CUSTOMER MENU (Book Now, My Appointments)') . "\n";
echo "</pre>";

// Check database
if (isset($_SESSION['user_id'])) {
    require_once 'config/db.php';
    $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $dbUser = $stmt->fetch();
    
    echo "<h2>Database Check:</h2>";
    echo "<pre>";
    echo "  DB Role: " . ($dbUser ? $dbUser['role'] : 'User not found!') . "\n";
    echo "  Session Role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
    
    if ($dbUser && $dbUser['role'] !== ($_SESSION['role'] ?? '')) {
        echo "\n";
        echo "  ⚠️⚠️⚠️ ROLE MISMATCH DETECTED! ⚠️⚠️⚠️\n";
        echo "  Session role doesn't match database role!\n";
        echo "  This is the BUG!\n";
    } else {
        echo "  ✅ Session matches database\n";
    }
    echo "</pre>";
}

echo "<hr>";
echo "<h2>Actions:</h2>";
echo '<a href="login.php" style="color:#4f4;margin-right:20px;">Login Page</a>';
echo '<a href="logout.php" style="color:#f44;margin-right:20px;">Logout (Clear Session)</a>';
echo '<a href="index.php" style="color:#2196f3;">Go to Homepage</a>';

echo "</body></html>";
