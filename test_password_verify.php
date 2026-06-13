<?php
// Test password verification
require_once __DIR__ . '/config/db.php';

$email = 'dhonmarck2004@gmail.com';

// Get user from database
$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found!\n");
}

echo "=== PASSWORD VERIFICATION TEST ===\n\n";
echo "User ID: {$user['id']}\n";
echo "Name: {$user['name']}\n";
echo "Email: {$user['email']}\n";
echo "Role: {$user['role']}\n";
echo "Password hash: {$user['password_hash']}\n";
echo "Hash length: " . strlen($user['password_hash']) . "\n\n";

// Test different passwords
$testPasswords = [
    'Sakuma@10',
    'admin123',
    'password',
    'admin',
];

echo "Testing common passwords:\n";
echo str_repeat("-", 50) . "\n";

foreach ($testPasswords as $testPass) {
    $result = password_verify($testPass, $user['password_hash']);
    $status = $result ? '✓ MATCH!' : '✗ No match';
    echo "Password: $testPass → $status\n";
}

echo "\n";
echo "If none of these work, you need to remember your actual password.\n";
echo "Or you can reset it using reset_password.php\n";
