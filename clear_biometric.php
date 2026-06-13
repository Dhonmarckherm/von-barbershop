<?php
// Clear biometric credentials for testing
require_once __DIR__ . '/config/db.php';

if (!isset($_GET['email'])) {
    die("Usage: clear_biometric.php?email=your@email.com");
}

$email = $_GET['email'];

// Delete biometric credentials
$stmt = $pdo->prepare("DELETE FROM user_credentials WHERE user_id = (SELECT id FROM users WHERE email = ?)");
$stmt->execute([$email]);

$deleted = $stmt->rowCount();

echo "✅ Biometric credentials cleared for: $email\n";
echo "Deleted records: $deleted\n\n";
echo "Now login again and the 'Enable Quick Login' popup should appear!";
