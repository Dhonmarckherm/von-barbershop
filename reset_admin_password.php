<?php
// Quick password reset for admin
require_once __DIR__ . '/config/db.php';

$email = 'dhonmarck2004@gmail.com';
$newPassword = 'Admin123!';

// Hash the new password
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

// Update in database
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->execute([$hash, $email]);

if ($stmt->rowCount() > 0) {
    echo "✅ SUCCESS! Password has been reset.\n\n";
    echo "Email: $email\n";
    echo "New Password: $newPassword\n\n";
    echo "You can now login with these credentials!\n";
} else {
    echo "❌ ERROR: User not found with email: $email\n";
}
