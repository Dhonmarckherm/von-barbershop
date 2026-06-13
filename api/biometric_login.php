<?php
/**
 * Biometric Login - Generate challenge for authentication
 */
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/db.php';

// Get all registered credentials
$stmt = $pdo->query("
    SELECT up.credential_id, up.transports, u.id as user_id, u.name, u.email 
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.last_used_at DESC
");
$credentials = $stmt->fetchAll();

if (empty($credentials)) {
    echo json_encode(['error' => 'No biometric credentials registered']);
    exit;
}

// Generate cryptographic challenge
$challenge = random_bytes(32);
$challengeBase64 = rtrim(strtr(base64_encode($challenge), '+/', '-_'), '=');

// Store challenge in session
session_start();
$_SESSION['webauthn_challenge'] = bin2hex($challenge);

// Format credentials for WebAuthn
$allowCredentials = array_map(function($cred) {
    // Trim any whitespace and ensure proper base64url format
    $credentialId = trim($cred['credential_id']);
    
    error_log("[Biometric Login API] Raw credential_id: '" . $cred['credential_id'] . "'");
    error_log("[Biometric Login API] Trimmed credential_id: '" . $credentialId . "'");
    error_log("[Biometric Login API] Credential ID length: " . strlen($credentialId));
    
    return [
        'id' => $credentialId,
        'type' => 'public-key',
        'transports' => $cred['transports'] ? explode(',', $cred['transports']) : ['internal']
    ];
}, $credentials);

echo json_encode([
    'challenge' => $challengeBase64,
    'allowCredentials' => $allowCredentials
]);

error_log('Biometric login challenge generated. Credentials count: ' . count($allowCredentials));
error_log('First credential ID: ' . ($allowCredentials[0]['id'] ?? 'none'));
