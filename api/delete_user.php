<?php
/**
 * API Endpoint: Delete User
 * 
 * Accepts POST parameters:
 *   - user_id (int)
 * 
 * Admin access only. Deletes user and all their appointments.
 */

// Prevent any HTML output - API must return clean JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/auth_helper.php';

// Admin check
requireAdminAuth();

// Debug logging (only to server logs, not output)
error_log("Delete User API called");
error_log("POST data: " . print_r($_POST, true));
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("Cookies: " . print_r($_COOKIE, true));

// Validate input - use $_POST directly instead of filter_input
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($userId <= 0) {
    error_log("Delete failed: Invalid user ID. Received: " . ($_POST['user_id'] ?? 'null'));
    echo json_encode(['error' => 'Invalid user ID.']);
    exit;
}

error_log("Attempting to delete user ID: {$userId}");

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
    error_log("Delete failed: User not found in database. ID: {$userId}");
    echo json_encode(['error' => 'User not found. ID: ' . $userId]);
    exit;
}

error_log("User found: {$user['name']} (ID: {$userId})");

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
