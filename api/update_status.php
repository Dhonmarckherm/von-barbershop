<?php
/**
 * API Endpoint: Update Appointment Status
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 *   - status (pending|accepted|completed|cancelled)
 * 
 * Admin access only. Sends email notification to customer on accept/cancel.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';
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

// Fetch current status and customer details before updating
$stmt = $pdo->prepare("SELECT a.status AS current_status, a.appointment_date, a.appointment_time, a.haircut_description, a.location, u.name AS customer_name, u.email AS customer_email FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
$stmt->execute([$appointmentId]);
$appt = $stmt->fetch();

if (!$appt) {
    echo json_encode(['error' => 'Appointment not found.']);
    exit;
}

// Update status
$stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
$stmt->execute([$status, $appointmentId]);

if ($stmt->rowCount() > 0 || $appt['current_status'] === $status) {
    $details = [
        'customer_name'  => $appt['customer_name'],
        'customer_email' => $appt['customer_email'],
        'service_name'   => $appt['haircut_description'],
        'location'       => $appt['location'],
        'date'           => $appt['appointment_date'],
        'time'           => $appt['appointment_time'],
    ];

    // Send email notification on status change
    if ($status === 'accepted' && $appt['current_status'] !== 'accepted') {
        sendAcceptanceEmail($appt['customer_email'], $appt['customer_name'], $details);
    } elseif ($status === 'cancelled' && $appt['current_status'] !== 'cancelled') {
        sendCancellationEmail($appt['customer_email'], $appt['customer_name'], $details);
    }

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'No changes made. Appointment may not exist.']);
}
exit;
