<?php
/**
 * API Endpoint: Get Available Time Slots
 * 
 * Accepts GET parameter:
 *   - date (YYYY-MM-DD)
 * 
 * Returns JSON array of available time slots.
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Validate inputs
$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['error' => 'Date is required.']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}

// Fetch booked time slots for the selected date
$stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE appointment_date = ? AND status != 'cancelled'");
$stmt->execute([$date]);
$bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Convert booked times to array of strings (H:i format)
$bookedTimes = array_map(function($time) {
    return substr($time, 0, 5); // Convert HH:MM:SS to HH:MM
}, $bookedSlots);

// Generate all possible slots: 09:00 to 17:00 (5:00 PM), 30-minute intervals
$start = strtotime('09:00');
$end = strtotime('17:00');
$allSlots = [];

for ($t = $start; $t < $end; $t += (30 * 60)) {
    $slotTime = date('H:i', $t);
    $allSlots[] = [
        'time' => $slotTime,
        'available' => !in_array($slotTime, $bookedTimes)
    ];
}

// Return structured data with both available and booked slots
echo json_encode([
    'slots' => $allSlots,
    'date' => $date
]);
exit;
