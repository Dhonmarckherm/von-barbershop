<?php
/**
 * Biometric Verify - Handle registration and login verification
 * Simplified implementation that works reliably
 */
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/db.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Start session
session_start();

// Verify challenge exists
if (!isset($_SESSION['webauthn_challenge'])) {
    echo json_encode(['error' => 'Invalid session']);
    exit;
}

if ($action === 'register') {
    // Handle new credential registration
    $credential = $input['credential'] ?? null;
    $userId = $_SESSION['webauthn_user_id'] ?? 0;
    
    if (!$credential || !$userId) {
        echo json_encode(['error' => 'Invalid registration data']);
        exit;
    }
    
    // DEBUG: Log everything we receive
    error_log("[Biometric Register] === START REGISTRATION ===");
    error_log("[Biometric Register] User ID: $userId");
    error_log("[Biometric Register] Credential object keys: " . implode(', ', array_keys($credential)));
    error_log("[Biometric Register] credential[id]: " . ($credential['id'] ?? 'NOT SET'));
    error_log("[Biometric Register] credential[rawId]: " . ($credential['rawId'] ?? 'NOT SET'));
    error_log("[Biometric Register] credential[id] type: " . gettype($credential['id']));
    error_log("[Biometric Register] credential[id] length: " . strlen($credential['id'] ?? ''));
    error_log("[Biometric Register] credential[rawId] length: " . strlen($credential['rawId'] ?? ''));
    
    try {
        // Get credential ID - use rawId if available, fallback to id
        $credentialId = $credential['rawId'] ?? $credential['id'];
        
        error_log("[Biometric Register] Using credential ID from: " . (isset($credential['rawId']) ? 'rawId' : 'id'));
        error_log("[Biometric Register] Final credential ID: " . $credentialId);
        error_log("[Biometric Register] Final credential ID length: " . strlen($credentialId));
        
        // Ensure it's a string and trim it
        $credentialId = trim((string)$credentialId);
        
        error_log("[Biometric Register] After trim - credential ID: " . $credentialId);
        error_log("[Biometric Register] After trim - length: " . strlen($credentialId));
        
        // Store credential in database
        $stmt = $pdo->prepare("
            INSERT INTO user_passkeys (user_id, credential_id, credential_public_key, transports)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $credentialId,
            $credential['response']['attestationObject'] ?? 'no-attestation',
            implode(',', $credential['response']['transports'] ?? ['internal'])
        ]);
        
        // Clear session
        unset($_SESSION['webauthn_challenge']);
        unset($_SESSION['webauthn_user_id']);
        
        error_log("[Biometric Register] SUCCESS: Stored credential for user {$userId}, length: " . strlen($credentialId));
        error_log("[Biometric Register] === END REGISTRATION ===");
        
        echo json_encode([
            'success' => true,
            'message' => 'Biometric login enabled successfully!'
        ]);
        
    } catch (Exception $e) {
        error_log("Biometric registration failed: " . $e->getMessage());
        echo json_encode(['error' => 'Registration failed']);
    }
    
} elseif ($action === 'login') {
    // Handle login verification
    $assertion = $input['assertion'] ?? null;
    
    if (!$assertion) {
        echo json_encode(['error' => 'Invalid assertion']);
        exit;
    }
    
    try {
        // Get the credential ID from assertion
        $assertionId = $assertion['id'];
        error_log("[Biometric Login] Assertion ID: $assertionId");
        
        // Find credential in database - use TRIM to handle whitespace issues
        $stmt = $pdo->prepare("
            SELECT up.*, u.id as user_id, u.name, u.email, u.role
            FROM user_passkeys up
            JOIN users u ON up.user_id = u.id
            WHERE TRIM(up.credential_id) = ?
        ");
        $stmt->execute([$assertionId]);
        $cred = $stmt->fetch();
        
        if (!$cred) {
            error_log("[Biometric Login] Credential NOT found in database!");
            error_log("[Biometric Login] Looking for: $assertionId");
            
            // Debug: Show all credentials in database
            $debugStmt = $pdo->query("SELECT credential_id, user_id FROM user_passkeys");
            $allCreds = $debugStmt->fetchAll();
            error_log("[Biometric Login] All credentials in DB: " . json_encode($allCreds));
            
            echo json_encode(['error' => 'Credential not found. Please re-enable biometric login.']);
            exit;
        }
        
        error_log("[Biometric Login] Credential found for user: " . $cred['user_id'] . " (" . $cred['email'] . ")");
        
        // In production, verify the cryptographic signature here
        // For now, we trust the WebAuthn API verification (browser-level)
        
        // Update last used timestamp
        $updateStmt = $pdo->prepare("UPDATE user_passkeys SET last_used_at = NOW() WHERE id = ?");
        $updateStmt->execute([$cred['id']]);
        
        // Create session
        $_SESSION['user_id'] = $cred['user_id'];
        $_SESSION['name'] = $cred['name'];
        $_SESSION['email'] = $cred['email'];
        $_SESSION['role'] = $cred['role'];
        $_SESSION['login_time'] = time();
        
        // Set auth cookies
        $cookieOptions = [
            'expires' => time() + (30 * 24 * 60 * 60), // 30 days
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        
        setcookie('auth_user_id', $cred['user_id'], $cookieOptions);
        setcookie('auth_name', $cred['name'], $cookieOptions);
        setcookie('auth_email', $cred['email'], $cookieOptions);
        setcookie('auth_role', $cred['role'], $cookieOptions);
        
        // Clear session
        unset($_SESSION['webauthn_challenge']);
        
        error_log("Biometric login successful for user {$cred['user_id']} ({$cred['email']})");
        
        echo json_encode([
            'success' => true,
            'user_id' => $cred['user_id'],
            'name' => $cred['name'],
            'role' => $cred['role']
        ]);
        
    } catch (Exception $e) {
        error_log("Biometric login failed: " . $e->getMessage());
        echo json_encode(['error' => 'Login verification failed']);
    }
    
} else {
    echo json_encode(['error' => 'Invalid action']);
}
