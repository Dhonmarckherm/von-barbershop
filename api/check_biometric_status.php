<?php
/**
 * Check Biometric Status for Current User
 * Returns whether the logged-in user has enrolled biometric credentials
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

initializeSession();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'hasCredentials' => false,
        'error' => 'Not logged in'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Check if THIS user has ANY biometric credentials enrolled
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM user_passkeys 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    $hasCredentials = ($result['count'] > 0);
    
    error_log("[Biometric Check] User $userId has credentials: " . ($hasCredentials ? 'YES' : 'NO'));
    
    echo json_encode([
        'hasCredentials' => $hasCredentials,
        'credentialCount' => (int)$result['count'],
        'userId' => $userId
    ]);
    
} catch (PDOException $e) {
    error_log("[Biometric Check] Database error: " . $e->getMessage());
    echo json_encode([
        'hasCredentials' => false,
        'error' => 'Database error'
    ]);
}
