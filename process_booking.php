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

/**
 * Check if address is incomplete/abbreviated
 * Returns true if address appears to be just a shortcut/abbreviation
 */
function isIncompleteAddress(string $address): bool {
    $address = trim($address);
    $addressUpper = strtoupper($address);
    
    // Only reject VERY short or obviously incomplete addresses
    
    // Reject if less than 8 characters (definitely too short)
    if (strlen($address) < 8) {
        return true;
    }
    
    // Reject exact 2-letter abbreviations
    $twoLetterShortcuts = ['SJ', 'CD', 'MB', 'BV', 'SB', 'LB', 'CB', 'NB', 'TB', 'PB', 'SN', 'ST'];
    if (strlen($address) <= 3 && in_array($addressUpper, $twoLetterShortcuts)) {
        return true;
    }
    
    // Reject single generic words (very short)
    $genericWords = ['HOME', 'HOUSE', 'NEAR', 'CHURCH', 'PLAZA', 'MALL', 'CENTER', 'PUBLIC'];
    if (strlen($address) <= 10 && in_array($addressUpper, $genericWords)) {
        return true;
    }
    
    // ACCEPT: If it has commas (indicates multiple parts like "Brgy, City")
    if (strpos($address, ',') !== false) {
        return false; // VALID - has structure
    }
    
    // ACCEPT: If it mentions barangay/brgy (common Filipino format)
    if (stripos($address, 'brgy') !== false || stripos($address, 'barangay') !== false) {
        return false; // VALID
    }
    
    // ACCEPT: If it mentions street/st/avenue/road (has street info)
    if (preg_match('/\b(street|st\.?|avenue|ave\.?|road|rd\.?|blvd|boulevard|lane|ln\.?)\b/i', $address)) {
        return false; // VALID
    }
    
    // ACCEPT: If it mentions subdivision/village/phase (has subdivision info)
    if (preg_match('/\b(subd\.?|subdivision|village|phase|block|lot)\b/i', $address)) {
        return false; // VALID
    }
    
    // ACCEPT: If it has numbers (likely street number)
    if (preg_match('/\d+/', $address)) {
        return false; // VALID
    }
    
    // ACCEPT: If it's 15+ characters (likely detailed enough)
    if (strlen($address) >= 15) {
        return false; // VALID
    }
    
    // Only reject if it's very short (< 15 chars) AND has none of the above
    return true;
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
} elseif (isIncompleteAddress($location)) {
    $errors[] = 'Please provide a complete address. Abbreviations like "SJ", "CD", "San Juan", "Candon" alone are not sufficient. Include street, barangay, city/municipality for better service.';
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

    // Fetch ALL barber/admin emails
    $barberStmt = $pdo->query("SELECT email, name FROM users WHERE role IN ('admin', 'barber')");
    $barbers = $barberStmt->fetchAll();
    
    if (empty($barbers)) {
        // Fallback to default admin email
        $barbers = [['email' => 'dhonmarck2004@gmail.com', 'name' => 'Admin']];
    }
    
    error_log("[Booking] Sending admin notifications to " . count($barbers) . " admin(s)/barber(s)");

    // Send emails via PHPMailer (with error handling)
    try {
        // Send to customer
        $customerSent = false;
        $adminSentCount = 0;
        
        $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
        
        if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
            // Use Brevo HTTP API
            $customerSent = sendBrevoEmail(
                $user['email'],
                $user['name'],
                'Your Appointment Confirmation - Barbershop',
                buildCustomerEmailBody($appointmentDetails)
            );
            
            // Send to ALL admins/barbers
            foreach ($barbers as $barber) {
                $sent = sendBrevoEmail(
                    $barber['email'],
                    $barber['name'],
                    'New Booking Received - Barbershop',
                    buildAdminEmailBody($appointmentDetails)
                );
                if ($sent) {
                    $adminSentCount++;
                    error_log("[Booking] Admin notification SENT to: " . $barber['email']);
                } else {
                    error_log("[Booking] Admin notification FAILED for: " . $barber['email']);
                }
            }
        } else {
            // Fallback to PHPMailer SMTP
            $mail = getMailer();
            $mail->addAddress($user['email'], $user['name']);
            $mail->isHTML(true);
            $mail->Subject = 'Your Appointment Confirmation - Barbershop';
            $mail->Body    = buildCustomerEmailBody($appointmentDetails);
            $mail->send();
            $customerSent = true;
            
            // Send to ALL admins/barbers
            foreach ($barbers as $barber) {
                try {
                    $mail = getMailer();
                    $mail->addAddress($barber['email'], $barber['name']);
                    $mail->isHTML(true);
                    $mail->Subject = 'New Booking Received - Barbershop';
                    $mail->Body    = buildAdminEmailBody($appointmentDetails);
                    $mail->send();
                    $adminSentCount++;
                    error_log("[Booking] Admin notification SENT to: " . $barber['email']);
                } catch (Exception $e) {
                    error_log("[Booking] Admin notification FAILED for " . $barber['email'] . ": " . $e->getMessage());
                }
            }
        }
        
        $emailSent = $customerSent && ($adminSentCount > 0);
        
        if ($emailSent) {
            error_log("[Booking] Customer email SENT. Admin emails sent: {$adminSentCount}/" . count($barbers));
        } else {
            error_log("[Booking] Email sending failed. Customer: " . ($customerSent ? 'YES' : 'NO') . ", Admins: {$adminSentCount}/" . count($barbers));
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
error_log("[Booking] Redirecting to payment upload page");

// Check if this is user's first booking (for biometric enrollment)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$bookingCount = $stmt->fetch()['count'];
$showBiometricPrompt = ($bookingCount <= 1) ? '&biometric_prompt=1' : '';

error_log("[Booking] User has $bookingCount bookings. Biometric prompt: " . ($showBiometricPrompt ? 'YES' : 'NO'));

// Redirect to payment upload page (after booking, customer needs to pay ₱50 downpayment)
$redirectUrl = 'payment_upload.php?appointment_id=' . $appointmentId;
error_log("[Booking] Final redirect URL: $redirectUrl");

header('Location: ' . $redirectUrl);
exit;
