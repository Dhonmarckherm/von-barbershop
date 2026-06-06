<?php
/**
 * API Endpoint: Delete User
 * 
 * Accepts POST parameters:
 *   - user_id (int)
 * 
 * Admin access only. Deletes user and all their appointments.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
initializeSession();

header('Content-Type: application/json');

// Debug logging
error_log("Delete User API called");
error_log("Session data: " . print_r($_SESSION, true));

// Admin check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'barber')) {
    http_response_code(403);
    error_log("Delete failed: Not admin/barber. Role: " . ($_SESSION['role'] ?? 'not set'));
    echo json_encode(['error' => 'Forbidden. Admin access required. Current role: ' . ($_SESSION['role'] ?? 'not set')]);
    exit;
}

// Validate input
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    error_log("Delete failed: Invalid user ID. Received: " . ($_POST['user_id'] ?? 'null'));
    echo json_encode(['error' => 'Invalid user ID.']);
    exit;
}

// Prevent admin from deleting themselves
if ($userId == $_SESSION['user_id']) {
    error_log("Delete failed: Trying to delete self. User ID: {$userId}");
    echo json_encode(['error' => 'You cannot delete your own account.']);
    exit;
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    error_log("Delete failed: User not found. ID: {$userId}");
    echo json_encode(['error' => 'User not found.']);
    exit;
}

error_log("Attempting to delete user ID: {$userId}, Name: {$user['name']}");

try {
    // Start transaction
    $pdo->beginTransaction();
    error_log("Transaction started");

    // Delete all appointments for this user
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE user_id = ?");
    $stmt->execute([$userId]);
    $appointmentCount = $stmt->rowCount();
    error_log("Deleted {$appointmentCount} appointments");

    // Delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userCount = $stmt->rowCount();
    error_log("Deleted {$userCount} user(s)");

    // Commit transaction
    $pdo->commit();
    error_log("Transaction committed successfully");

    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
} catch (Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    error_log("Delete failed with exception: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
}
exit;
