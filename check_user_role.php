<?php
require_once 'config/db.php';
$email = 'dhondump@gmail.com';
$stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
echo "User: " . print_r($user, true);
