<?php
/**
 * API Endpoint: Delete Multiple Appointments
 * 
 * Accepts POST parameter:
 *   - ids[] (array of appointment IDs)
 * 
 * Admin access only. Permanently deletes selected appointments.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
initializeSession();

header('Content-Type: application/json');

// Admin check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'barber')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Admin access required.']);
    exit;
}

// Check if IDs are provided
if (!isset($_POST['ids']) || !is_array($_POST['ids']) || empty($_POST['ids'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No appointments selected.']);
    exit;
}

$ids = $_POST['ids'];

// Validate all IDs are integers
$validIds = array_filter($ids, function($id) {
    return is_numeric($id) && intval($id) > 0;
});

if (empty($validIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid appointment IDs.']);
    exit;
}

try {
    // Prepare delete statement
    $placeholders = implode(',', array_fill(0, count($validIds), '?'));
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE id IN ($placeholders)");
    
    // Execute with integer casting for security
    $stmt->execute(array_map('intval', $validIds));
    
    $deletedCount = $stmt->rowCount();
    
    if ($deletedCount > 0) {
        echo json_encode([
            'success' => true,
            'message' => "Successfully deleted {$deletedCount} appointment(s).",
            'deleted_count' => $deletedCount
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No appointments were deleted. They may have already been removed.'
        ]);
    }
} catch (PDOException $e) {
    error_log("Delete appointments error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred. Please try again.'
    ]);
}

exit;
