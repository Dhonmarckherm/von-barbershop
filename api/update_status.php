<?php
/**
 * API Endpoint: Update Appointment Status
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 *   - status (pending|accepted|completed|cancelled)
 * 
 * Admin access only. Updates status immediately.
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

// Validate inputs
$appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';

if (!$appointmentId) {
    echo json_encode(['error' => 'Invalid appointment ID.']);
    exit;
}

$allowedStatuses = ['pending', 'accepted', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses, true)) {
    echo json_encode(['error' => 'Invalid status value.']);
    exit;
}

// Fetch appointment to verify it exists
$stmt = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ?");
$stmt->execute([$appointmentId]);
$appt = $stmt->fetch();

if (!$appt) {
    echo json_encode(['error' => 'Appointment not found.']);
    exit;
}

// Update status
$stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
$stmt->execute([$status, $appointmentId]);

if ($stmt->rowCount() > 0 || $appt['status'] === $status) {
    // Status updated successfully - email notifications disabled for performance
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['error' => 'No changes made. Appointment may not exist.']);
}
exit;
