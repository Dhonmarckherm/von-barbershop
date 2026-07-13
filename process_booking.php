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
        'appointment_id' => $appointmentId, // Add appointment ID for payment link
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

// Redirect to payment upload page with beautiful loading animation
$redirectUrl = 'payment_upload.php?appointment_id=' . $appointmentId;

// Format time for display
$timeDisplay = $time;
if (preg_match('/^(\d{1,2}):(\d{2})$/', $time, $m)) {
    $hours = (int)$m[1];
    $mins = $m[2];
    $period = $hours >= 12 ? 'PM' : 'AM';
    if ($hours > 12) $hours -= 12;
    else if ($hours == 0) $hours = 12;
    $timeDisplay = $hours . ':' . $mins . ' ' . $period;
}
$dateDisplay = date('M d, Y', strtotime($date));

// Render beautiful loading animation page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Your Booking...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #000000;
            color: #F5F0E8;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .processing-container {
            text-align: center;
            padding: 40px 24px;
            max-width: 420px;
            width: 100%;
        }
        /* Animated checkmark circle */
        .success-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto 30px;
            position: relative;
            opacity: 0;
            animation: fadeInScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s forwards;
        }
        .success-icon .circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 40px rgba(40, 167, 69, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }
        .success-icon .circle i {
            font-size: 48px;
            color: white;
            opacity: 0;
            animation: checkDraw 0.4s ease 0.8s forwards;
        }
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.3); }
            to { opacity: 1; transform: scale(1); }
        }
        @keyframes checkDraw {
            from { opacity: 0; transform: scale(0.5) rotate(-10deg); }
            to { opacity: 1; transform: scale(1) rotate(0deg); }
        }
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 40px rgba(40, 167, 69, 0.4); }
            50% { box-shadow: 0 0 60px rgba(40, 167, 69, 0.6); }
        }
        .title {
            font-family: Georgia, 'Times New Roman', serif;
            font-size: 28px;
            font-weight: 700;
            color: #C5A059;
            margin-bottom: 8px;
            opacity: 0;
            animation: fadeUp 0.5s ease 0.6s forwards;
        }
        .subtitle {
            color: #8A8A9A;
            font-size: 15px;
            margin-bottom: 35px;
            opacity: 0;
            animation: fadeUp 0.5s ease 0.8s forwards;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        /* Appointment card */
        .apt-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(197, 160, 89, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
            opacity: 0;
            animation: fadeUp 0.5s ease 1s forwards;
        }
        .apt-card .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #8A8A9A;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .apt-card .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .apt-card .row:last-child { border-bottom: none; }
        .apt-card .row .key { color: #8A8A9A; font-size: 14px; }
        .apt-card .row .val { color: #F5F0E8; font-weight: 600; font-size: 14px; }
        .apt-card .row .val.gold { color: #C5A059; }
        .apt-card .row .val.green { color: #28a745; }
        /* Progress steps */
        .steps {
            text-align: left;
            margin-bottom: 30px;
            opacity: 0;
            animation: fadeUp 0.5s ease 1.2s forwards;
        }
        .step {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            font-size: 14px;
            color: #8A8A9A;
            transition: all 0.3s ease;
        }
        .step .icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.4s ease;
        }
        .step.active { color: #F5F0E8; }
        .step.active .icon {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-color: transparent;
            color: white;
            box-shadow: 0 0 15px rgba(40, 167, 69, 0.4);
        }
        .step.done .icon {
            background: rgba(40, 167, 69, 0.15);
            border-color: #28a745;
            color: #28a745;
        }
        .step.done { color: rgba(245, 240, 232, 0.5); }
        /* Spinner for active step */
        .step.active .icon::after {
            content: '';
            position: absolute;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid transparent;
            border-top-color: rgba(40, 167, 69, 0.6);
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Redirect bar */
        .redirect-bar {
            height: 3px;
            background: rgba(255,255,255,0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 15px;
            opacity: 0;
            animation: fadeUp 0.5s ease 1.4s forwards;
        }
        .redirect-bar .fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #28a745, #C5A059);
            border-radius: 3px;
            animation: fillBar 3s ease 2s forwards;
        }
        @keyframes fillBar {
            to { width: 100%; }
        }
        .redirect-text {
            font-size: 13px;
            color: #8A8A9A;
            opacity: 0;
            animation: fadeUp 0.5s ease 1.6s forwards;
        }
        .redirect-text a {
            color: #C5A059;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="processing-container">
        <!-- Success Icon -->
        <div class="success-icon">
            <div class="circle">
                <i class="bi bi-check-lg"></i>
            </div>
        </div>
        
        <h1 class="title">Booking Confirmed!</h1>
        <p class="subtitle">Your appointment has been successfully created</p>
        
        <!-- Appointment Details Card -->
        <div class="apt-card">
            <div class="label">Appointment Details</div>
            <div class="row">
                <span class="key">Service</span>
                <span class="val gold"><?php echo htmlspecialchars($haircutDescription); ?></span>
            </div>
            <div class="row">
                <span class="key">Date</span>
                <span class="val"><?php echo $dateDisplay; ?></span>
            </div>
            <div class="row">
                <span class="key">Time</span>
                <span class="val"><?php echo $timeDisplay; ?></span>
            </div>
            <div class="row">
                <span class="key">Location</span>
                <span class="val"><?php echo htmlspecialchars($location); ?></span>
            </div>
            <div class="row">
                <span class="key">Downpayment</span>
                <span class="val green">₱50.00</span>
            </div>
            <div class="row" style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed rgba(197,160,89,0.2);">
                <span class="key" style="color: #C5A059; font-size: 12px;">Balance at Studio</span>
                <span class="val" style="color: #F5F0E8; font-size: 14px;">₱100.00 <span style="font-size: 11px; color: #8A8A9A;">(pay after haircut)</span></span>
            </div>
        </div>
        
        <!-- Progress Steps -->
        <div class="steps">
            <div class="step done" id="step1">
                <div class="icon"><i class="bi bi-check"></i></div>
                <span>Appointment created</span>
            </div>
            <div class="step done" id="step2">
                <div class="icon"><i class="bi bi-check"></i></div>
                <span>Confirmation email sent</span>
            </div>
            <div class="step active" id="step3">
                <div class="icon"><i class="bi bi-credit-card"></i></div>
                <span>Redirecting to payment...</span>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="redirect-bar">
            <div class="fill"></div>
        </div>
        <p class="redirect-text">
            Redirecting to payment page... <a href="<?php echo $redirectUrl; ?>">Skip</a>
        </p>
    </div>
    
    <script>
        // Animate steps sequentially
        setTimeout(() => {
            document.getElementById('step3').classList.remove('active');
            document.getElementById('step3').classList.add('done');
            document.getElementById('step3').querySelector('.icon').innerHTML = '<i class="bi bi-check"></i>';
        }, 2000);
        
        // Redirect after animation
        setTimeout(() => {
            window.location.href = '<?php echo $redirectUrl; ?>';
        }, 5000);
    </script>
</body>
</html>
