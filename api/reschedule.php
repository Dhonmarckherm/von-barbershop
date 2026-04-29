<?php
/**
 * API Endpoint: Reschedule Appointment
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 *   - new_date (YYYY-MM-DD)
 *   - new_time (HH:MM)
 * 
 * Admin access only. Sends email notification to customer.
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
$stmt = $pdo->prepare("SELECT a.appointment_date, a.appointment_time, a.haircut_description, a.location, u.name AS customer_name, u.email AS customer_email FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
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
    // Send reschedule email asynchronously
    $details = [
        'customer_name'  => $appt['customer_name'],
        'customer_email' => $appt['customer_email'],
        'service_name'   => $appt['haircut_description'],
        'location'       => $appt['location'],
        'date'           => $newDate,
        'time'           => $newTime,
        'old_date'       => $appt['appointment_date'],
        'old_time'       => $appt['appointment_time'],
    ];

    // Send response first, then email
    if (function_exists('fastcgi_finish_request')) {
        echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully']);
        fastcgi_finish_request();
    } else {
        ignore_user_abort(true);
        set_time_limit(30);
        echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully']);
        if (ob_get_level() > 0) { ob_end_flush(); }
        flush();
    }

    // Send email after response
    try {
        require_once __DIR__ . '/../config/mailer.php';
        @sendRescheduleEmail($appt['customer_email'], $appt['customer_name'], $details);
    } catch (Exception $e) {
        error_log('Reschedule email failed: ' . $e->getMessage());
    } catch (Error $e) {
        error_log('Reschedule email error: ' . $e->getMessage());
    }
    exit;
} else {
    echo json_encode(['error' => 'No changes made.']);
}
exit;
