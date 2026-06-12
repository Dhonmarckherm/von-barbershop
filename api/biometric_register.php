<?php
/**
 * Biometric Registration - Generate challenge for new passkey
 */
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/db.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$userId = $input['user_id'] ?? 0;

if (!$email || !$userId) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Verify user exists
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ? AND email = ?");
$stmt->execute([$userId, $email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Generate cryptographic challenge
$challenge = random_bytes(32);

// Store challenge in session for verification
session_start();
$_SESSION['webauthn_challenge'] = bin2hex($challenge);
$_SESSION['webauthn_user_id'] = $userId;

// Return registration options
echo json_encode([
    'challenge' => $challenge,
    'user_id' => (string)$userId,
    'email' => $user['email'],
    'display_name' => $user['name'],
    'rp' => [
        'name' => 'VON BARBER STUDIO',
        'id' => $_SERVER['HTTP_HOST']
    ]
]);
