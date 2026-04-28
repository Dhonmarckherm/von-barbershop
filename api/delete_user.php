<?php
/**
 * API Endpoint: Delete User
 * 
 * Accepts POST parameters:
 *   - user_id (int)
 * 
 * Admin access only. Deletes user and all their appointments.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
initializeSession();

header('Content-Type: application/json');

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Admin access required.']);
    exit;
}

// Validate input
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    echo json_encode(['error' => 'Invalid user ID.']);
    exit;
}

// Prevent admin from deleting themselves
if ($userId == $_SESSION['user_id']) {
    echo json_encode(['error' => 'You cannot delete your own account.']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['error' => 'User not found.']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Delete all appointments for this user
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);

    // Commit transaction
    $pdo->commit();

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
}
exit;
