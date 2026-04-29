<?php
/**
 * API Endpoint: Reschedule Appointment
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 *   - new_date (YYYY-MM-DD)
 *   - new_time (HH:MM)
 * 
 * Admin access only. Updates appointment immediately.
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
$newDate = $_POST['new_date'] ?? '';
$newTime = $_POST['new_time'] ?? '';

if (!$appointmentId) {
    echo json_encode(['error' => 'Invalid appointment ID.']);
    exit;
}

if (empty($newDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
    echo json_encode(['error' => 'Invalid date format.']);
    exit;
}

if (empty($newTime) || !preg_match('/^\d{2}:\d{2}$/', $newTime)) {
    echo json_encode(['error' => 'Invalid time format.']);
    exit;
}

// Fetch current appointment details
$stmt = $pdo->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE id = ?");
$stmt->execute([$appointmentId]);
$appt = $stmt->fetch();

if (!$appt) {
    echo json_encode(['error' => 'Appointment not found.']);
    exit;
}

// Check if new slot is already booked (excluding current appointment)
$stmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled' AND id != ?");
$stmt->execute([$newDate, $newTime, $appointmentId]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'The selected time slot is already booked.']);
    exit;
}

// Update appointment
$stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE id = ?");
$stmt->execute([$newDate, $newTime, $appointmentId]);

if ($stmt->rowCount() > 0) {
    // Reschedule successful - email notifications disabled for performance
    echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully']);
} else {
    echo json_encode(['error' => 'No changes made.']);
}
exit;
