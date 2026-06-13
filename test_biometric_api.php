<?php
/**
 * Test Biometric Login API Directly
 * Simulates what happens when user clicks "Login with Biometrics"
 */
header('Content-Type: application/json');
require_once __DIR__ . '/config/db.php';

// Test 1: Check if credentials exist in database
$stmt = $pdo->query("
    SELECT 
        up.credential_id,
        up.transports,
        up.user_id,
        u.email,
        u.name,
        LENGTH(up.credential_id) as cred_length,
        HEX(up.credential_id) as cred_hex
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.created_at DESC
");
$credentials = $stmt->fetchAll();

$testResults = [
    'total_credentials' => count($credentials),
    'credentials' => [],
    'issues' => []
];

foreach ($credentials as $index => $cred) {
    $credData = [
        'index' => $index,
        'user_id' => $cred['user_id'],
        'email' => $cred['email'],
        'name' => $cred['name'],
        'credential_id' => $cred['credential_id'],
        'trimmed' => trim($cred['credential_id']),
        'length' => $cred['cred_length'],
        'hex_preview' => substr($cred['cred_hex'], 0, 100),
        'transports' => $cred['transports']
    ];
    
    // Check for common issues
    if (strlen($cred['credential_id']) !== strlen(trim($cred['credential_id']))) {
        $testResults['issues'][] = "Credential #$index has whitespace (length: " . strlen($cred['credential_id']) . " vs trimmed: " . strlen(trim($cred['credential_id'])) . ")";
    }
    
    // Check if it's valid base64url
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $cred['credential_id'])) {
        $testResults['issues'][] = "Credential #$index is NOT valid base64url format";
    }
    
    $testResults['credentials'][] = $credData;
}

if (count($credentials) === 0) {
    $testResults['issues'][] = "NO credentials found in database! Users need to enroll first.";
}

echo json_encode($testResults, JSON_PRETTY_PRINT);
