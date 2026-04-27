<?php
/**
 * API Endpoint: Get Events for FullCalendar
 * 
 * Returns appointment data in FullCalendar event format.
 * Admin access only.
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

// Fetch all appointments with customer details
$stmt = $pdo->query("SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.haircut_description, a.location, u.name AS customer_name FROM appointments a JOIN users u ON a.user_id = u.id ORDER BY a.appointment_date, a.appointment_time");
$appointments = $stmt->fetchAll();

$events = [];
foreach ($appointments as $appt) {
    // Combine date and time into ISO 8601 format for FullCalendar
    $start = $appt['appointment_date'] . 'T' . $appt['appointment_time'];
    $title = htmlspecialchars($appt['haircut_description']) . ' - ' . htmlspecialchars($appt['customer_name']);

    $events[] = [
        'id' => $appt['id'],
        'title' => $title,
        'start' => $start,
        'extendedProps' => [
            'status'   => $appt['status'],
            'customer' => htmlspecialchars($appt['customer_name']),
            'haircut'  => htmlspecialchars($appt['haircut_description']),
            'location' => htmlspecialchars($appt['location']),
            'time'     => date('g:i A', strtotime($appt['appointment_time'])),
        ]
    ];
}

echo json_encode($events);
exit;
