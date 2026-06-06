<?php
/**
 * Check actual user role in database
 */
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';

echo "<!DOCTYPE html><html><head><title>Check User Role</title>";
echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
echo "pre{background:#2a2a2a;padding:15px;}</style></head><body>";

echo "<h1>🔍 User Role Check</h1><hr>";

// Check dhondump@gmail.com
$email = 'dhondump@gmail.com';
$stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    echo "<h2>User: {$user['name']}</h2>";
    echo "<pre>";
    echo "ID: {$user['id']}\n";
    echo "Email: {$user['email']}\n";
    echo "Role (from DB): '{$user['role']}'\n";
    echo "Role length: " . strlen($user['role']) . "\n";
    echo "Role hex: " . bin2hex($user['role']) . "\n";
    echo "</pre>";
    
    // Check what the redirect logic will do
    echo "<h2>Redirect Logic Test:</h2>";
    echo "<pre>";
    echo "Check: role === 'admin' → " . ($user['role'] === 'admin' ? 'TRUE' : 'FALSE') . "\n";
    echo "Check: role === 'barber' → " . ($user['role'] === 'barber' ? 'TRUE' : 'FALSE') . "\n";
    echo "Check: role === 'customer' → " . ($user['role'] === 'customer' ? 'TRUE' : 'FALSE') . "\n";
    echo "Check: role === 'Customer' → " . ($user['role'] === 'Customer' ? 'TRUE' : 'FALSE') . "\n";
    echo "</pre>";
    
    if ($user['role'] === 'admin' || $user['role'] === 'barber') {
        echo "<p style='color:#ff0;'>⚠️ Would redirect to: <strong>admin_dashboard.php</strong></p>";
    } else {
        echo "<p style='color:#4f4;'>✅ Would redirect to: <strong>index.php</strong> (customer homepage)</p>";
    }
    
    // Check all users
    echo "<hr><h2>All Users in Database:</h2>";
    $stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id");
    $allUsers = $stmt->fetchAll();
    
    echo "<pre>";
    foreach ($allUsers as $u) {
        $roleLower = strtolower($u['role']);
        $isAdmin = ($roleLower === 'admin' || $roleLower === 'barber');
        echo sprintf("ID:%-3d | %-25s | %-35s | Role: '%s' | Is Admin: %s\n", 
            $u['id'], $u['name'], $u['email'], $u['role'], $isAdmin ? 'YES' : 'NO');
    }
    echo "</pre>";
    
} else {
    echo "<p style='color:#f44;'>❌ User not found!</p>";
}

echo "</body></html>";
