<?php
require_once 'config/db.php';

// Generate new password hash
$new_hash = password_hash('admin123', PASSWORD_BCRYPT);

// Update admin password
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = 'admin@barbershop.com'");
$stmt->execute([$new_hash]);

echo "✅ Admin password updated successfully!\n";
echo "Email: admin@barbershop.com\n";
echo "Password: admin123\n";
?>
