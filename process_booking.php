<?php
/**
 * Process Booking Submission
 * 
 * Validates input, inserts appointment into database,
 * and sends email notifications via PHPMailer.
 */

require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: book.php');
    exit;
}

// Sanitize and validate inputs
$haircutDescription = trim($_POST['haircut_description'] ?? '');
$location = trim($_POST['location'] ?? '');
$date = $_POST['appointment_date'] ?? '';
$time = $_POST['appointment_time'] ?? '';

$errors = [];

if (empty($haircutDescription) || strlen($haircutDescription) < 2) {
    $errors[] = 'Please describe the haircut or style you want.';
}

if (empty($location) || strlen($location) < 3) {
    $errors[] = 'Please enter a valid location or address.';
}

if (empty($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $errors[] = 'Invalid date format.';
}

if (empty($time) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
    $errors[] = 'Invalid time format.';
}

// Check if slot is already booked (race condition protection)
if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'");
    $stmt->execute([$date, $time]);
    if ($stmt->fetch()) {
        $errors[] = 'This time slot has just been booked by someone else. Please choose another slot.';
    }
}

if (!empty($errors)) {
    $_SESSION['booking_errors'] = $errors;
    header('Location: book.php');
    exit;
}

// Insert appointment
$stmt = $pdo->prepare("INSERT INTO appointments (user_id, service_id, haircut_description, location, appointment_date, appointment_time, status) VALUES (?, NULL, ?, ?, ?, ?, 'pending')");
$stmt->execute([$_SESSION['user_id'], $haircutDescription, $location, $date, $time]);

// Get the appointment ID
$appointmentId = $pdo->lastInsertId();

// Fetch user details for push notification and email
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Send push notification to barber/admin about new booking
try {
    $barberStmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'barber') LIMIT 1");
    $barber = $barberStmt->fetch();
    if ($barber && $user) {
        $pushData = [
            'user_id' => $barber['id'],
            'title' => '📅 New Booking',
            'body' => "New appointment from {$user['name']} on {$date} at " . substr($time, 0, 5),
            'url' => '/admin_dashboard.php'
        ];
        $ch = curl_init('http://' . $_SERVER['HTTP_HOST'] . '/api/send_push_notification.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pushData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        curl_close($ch);
        error_log('Push notification sent to barber for new booking');
    }
} catch (Exception $e) {
    error_log('Barber push notification failed: ' . $e->getMessage());
}

if ($user) {
    $appointmentDetails = [
        'customer_name'  => $user['name'],
        'customer_email' => $user['email'],
        'service_name'   => $haircutDescription,
        'price'          => 'N/A',
        'date'           => $date,
        'time'           => $time,
        'location'       => $location,
    ];

    // Fetch barber/admin email
    $barberStmt = $pdo->query("SELECT email FROM users WHERE role = 'admin' OR role = 'barber' ORDER BY id ASC LIMIT 1");
    $barber = $barberStmt->fetch();
    $barberEmail = $barber ? $barber['email'] : 'dhonmarck2004@gmail.com';

    // Send emails via PHPMailer (with error handling)
    try {
        $emailSent = sendBookingEmails($user['email'], $user['name'], $appointmentDetails, $barberEmail);
        if ($emailSent) {
            error_log('Booking emails SENT successfully to customer and barber');
        } else {
            error_log('Booking emails returned false - sendBookingEmails failed');
        }
    } catch (Exception $e) {
        error_log('Booking email FAILED with exception: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        $emailSent = false;
    }
}

header('Location: my_appointments.php?booked=1&email=' . (isset($emailSent) && $emailSent ? 'sent' : 'failed'));

// Store notification for display in app
$_SESSION['push_notification'] = [
    'title' => '✅ Booking Confirmed',
    'body' => "Your appointment on {$date} at " . substr($time, 0, 5) . " has been booked!",
    'id' => $pdo->lastInsertId()
];

exit;
