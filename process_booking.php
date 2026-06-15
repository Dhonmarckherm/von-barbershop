<?php
/**
 * Process Booking Submission
 * 
 * Validates input, inserts appointment into database,
 * and sends email notifications via PHPMailer.
 */

// Start output buffering to prevent headers already sent errors
ob_start();

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
    $stmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status NOT IN ('cancelled', 'completed')");
    $stmt->execute([$date, $time]);
    if ($stmt->fetch()) {
        $errors[] = 'This time slot has just been booked by someone else. Please choose another slot.';
    }
}

if (!empty($errors)) {
    error_log("[Booking] Validation errors: " . implode(', ', $errors));
    $_SESSION['booking_errors'] = $errors;
    $_SESSION['booking_old_input'] = $_POST; // Preserve user input
    header('Location: book.php');
    exit;
}

// Insert appointment
try {
    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, service_id, haircut_description, location, appointment_date, appointment_time, status) VALUES (?, NULL, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$_SESSION['user_id'], $haircutDescription, $location, $date, $time]);
    
    error_log("[Booking] INSERT successful for user " . $_SESSION['user_id']);
} catch (PDOException $e) {
    error_log("[Booking] INSERT failed: " . $e->getMessage());
    $errors[] = 'Failed to create booking. Please try again.';
    $_SESSION['booking_errors'] = $errors;
    header('Location: book.php');
    exit;
}

// Get the appointment ID
$appointmentId = $pdo->lastInsertId();

// Fetch user details for push notification and email
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Send push notification to barber/admin about new booking
require_once __DIR__ . '/includes/push_helper.php';
$barberStmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'barber') LIMIT 1");
$barber = $barberStmt->fetch();
if ($barber && $user) {
    sendPushNotification($pdo, $barber['id'], '📅 New Booking', "New appointment from {$user['name']} on {$date} at " . substr($time, 0, 5), '/admin_dashboard.php');
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

// Store notification for display in app BEFORE redirect
$_SESSION['push_notification'] = [
    'title' => '✅ Booking Confirmed',
    'body' => "Your appointment on {$date} at " . substr($time, 0, 5) . " has been booked!",
    'id' => $appointmentId
];

// Debug logging
error_log("[Booking] Appointment inserted successfully. ID: $appointmentId");
error_log("[Booking] User ID: " . $_SESSION['user_id']);
error_log("[Booking] Redirecting to my_appointments.php");

// Check if this is user's first booking (for biometric enrollment)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$bookingCount = $stmt->fetch()['count'];
$showBiometricPrompt = ($bookingCount <= 1) ? '&biometric_prompt=1' : '';

error_log("[Booking] User has $bookingCount bookings. Biometric prompt: " . ($showBiometricPrompt ? 'YES' : 'NO'));

// Redirect to success page
$redirectUrl = 'my_appointments.php?booked=1&email=' . (isset($emailSent) && $emailSent ? 'sent' : 'failed') . $showBiometricPrompt;
error_log("[Booking] Final redirect URL: $redirectUrl");

header('Location: ' . $redirectUrl);
exit;
