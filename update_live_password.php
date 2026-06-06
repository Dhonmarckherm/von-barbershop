<?php
/**
 * Update password on LIVE Render database
 * Run this ONCE via Render shell or locally if you have DB access
 */

// Use the same DB connection as your app
require 'config/db.php';

$email = 'dhondump@gmail.com';
$newPassword = 'lalala123';

// Generate new password hash
$passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "Updating password on LIVE database for: $email\n";
echo "===============================================\n\n";

try {
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
    $stmt->execute([$passwordHash, $email]);
    
    if ($stmt->rowCount() > 0) {
        echo "✓ SUCCESS! Password updated on live database.\n";
        echo "  Email: $email\n";
        echo "  Password: $newPassword\n";
        echo "  You can now login on von-barbershop.onrender.com\n\n";
        
        // Verify it works
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (password_verify($newPassword, $user['password_hash'])) {
            echo "✓ VERIFIED: Password works correctly!\n";
        }
    } else {
        echo "✗ No rows updated. User may not exist.\n";
    }
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
