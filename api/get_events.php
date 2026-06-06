<?php
/**
 * API Endpoint: Get Events for FullCalendar
 * 
 * Returns appointment data in FullCalendar event format.
 * Admin access only.
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/auth_helper.php';

// Admin check
requireAdminAuth();

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
