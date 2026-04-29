<?php
/**
 * PHPMailer Configuration
 * 
 * Install PHPMailer first: composer require phpmailer/phpmailer
 * Then configure your SMTP credentials below or load from environment variables.
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/settings.php';

/**
 * Returns a configured PHPMailer instance.
 *
 * @return PHPMailer
 */
function getMailer(): PHPMailer {
    $mail = new PHPMailer(true);

    // Try Brevo API first (much faster than SMTP - uses HTTP)
    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
    
    if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
        // Use Brevo HTTP API (faster)
        $mail->isSMTP();
        $mail->Host       = 'smtp-relay.brevo.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'a9a607001@smtp-brevo.com';
        $mail->Password   = $brevoKey;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 10; // Shorter timeout for API
        $mail->setFrom('noreply@vonbarbershop.com', 'V.O.N Barbershop');
    } else {
        // Fallback to Gmail
        $mailUsername = 'dhonmarck2004@gmail.com';
        $mailPassword = 'ffsygjederrhvpfu';
        
        $envUsername = getenv('MAIL_USERNAME') ?: ($_ENV['MAIL_USERNAME'] ?? null) ?: ($_SERVER['MAIL_USERNAME'] ?? null);
        $envPassword = getenv('MAIL_PASSWORD') ?: ($_ENV['MAIL_PASSWORD'] ?? null) ?: ($_SERVER['MAIL_PASSWORD'] ?? null);
        
        if ($envUsername) $mailUsername = $envUsername;
        if ($envPassword) $mailPassword = $envPassword;
        
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $mailUsername;
        $mail->Password   = $mailPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->Timeout    = 30;
        $mail->SMTPKeepAlive = false;
        $mail->setFrom($mailUsername, 'V.O.N Barbershop');
    }
    
    $mail->SMTPDebug  = 0;
    $mail->Debugoutput = function($str, $level) {
        error_log("PHPMailer Debug [$level]: $str");
    };
    $mail->SMTPKeepAlive = false;

    return $mail;
}

/**
 * Send email using Brevo HTTP API (faster than SMTP).
 *
 * @param string $toEmail
 * @param string $toName
 * @param string $subject
 * @param string $htmlBody
 * @return bool
 */
function sendBrevoEmail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
    
    if (!$brevoKey) {
        error_log('Brevo API key not found');
        return false;
    }
    
    if (strpos($brevoKey, 'xkeysib-') !== 0) {
        error_log('Brevo API key has wrong format: ' . substr($brevoKey, 0, 10) . '...');
        return false;
    }
    
    $data = [
        'sender' => [
            'name' => 'V.O.N Barbershop',
            'email' => 'dhonmarck2004@gmail.com'
        ],
        'to' => [
            [
                'email' => $toEmail,
                'name' => $toName
            ]
        ],
        'subject' => $subject,
        'htmlContent' => $htmlBody
    ];
    
    if (!function_exists('curl_init')) {
        error_log('cURL is not enabled on this server');
        return false;
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $brevoKey,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        error_log("Brevo email sent successfully to $toEmail");
        return true;
    } else {
        error_log("Brevo API Error: HTTP $httpCode - $error - Response: $response");
        return false;
    }
}

/**
 * Send booking notification emails to customer and admin.
 *
 * @param string $customerEmail
 * @param string $customerName
 * @param array  $appointmentDetails
 * @return bool
 */
function sendBookingEmails(string $customerEmail, string $customerName, array $appointmentDetails, string $adminEmail): bool {
    try {
        // Check if using Brevo API key
        $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
        
        if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
            // Use Brevo HTTP API (faster)
            $customerSent = sendBrevoEmail(
                $customerEmail,
                $customerName,
                'Your Appointment Confirmation - Barbershop',
                buildCustomerEmailBody($appointmentDetails)
            );
            
            $adminSent = sendBrevoEmail(
                $adminEmail,
                'Barber',
                'New Booking Received - Barbershop',
                buildAdminEmailBody($appointmentDetails)
            );
            
            return $customerSent && $adminSent;
        }
        
        // Fallback to PHPMailer SMTP
        $mail = getMailer();
        $mail->addAddress($customerEmail, $customerName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Appointment Confirmation - Barbershop';
        $mail->Body    = buildCustomerEmailBody($appointmentDetails);
        $mail->send();

        $mail = getMailer();
        $mail->addAddress($adminEmail, 'Barber');
        $mail->isHTML(true);
        $mail->Subject = 'New Booking Received - Barbershop';
        $mail->Body    = buildAdminEmailBody($appointmentDetails);
        $mail->send();

        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send acceptance email to customer when admin accepts the appointment.
 *
 * @param string $customerEmail
 * @param string $customerName
 * @param array  $details
 * @return bool
 */
function sendAcceptanceEmail(string $customerEmail, string $customerName, array $details): bool {
    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
    
    if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
        return sendBrevoEmail(
            $customerEmail,
            $customerName,
            'Your Appointment Has Been Accepted - Barbershop',
            buildAcceptanceEmailBody($details)
        );
    }
    
    try {
        $mail = getMailer();
        $mail->addAddress($customerEmail, $customerName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Appointment Has Been Accepted - Barbershop';
        $mail->Body    = buildAcceptanceEmailBody($details);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send cancellation email to customer when admin cancels the appointment.
 *
 * @param string $customerEmail
 * @param string $customerName
 * @param array  $details
 * @return bool
 */
function sendCancellationEmail(string $customerEmail, string $customerName, array $details): bool {
    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
    
    if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
        return sendBrevoEmail(
            $customerEmail,
            $customerName,
            'Your Appointment Has Been Cancelled - Barbershop',
            buildCancellationEmailBody($details)
        );
    }
    
    try {
        $mail = getMailer();
        $mail->addAddress($customerEmail, $customerName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Appointment Has Been Cancelled - Barbershop';
        $mail->Body    = buildCancellationEmailBody($details);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send reschedule email to customer when admin changes date/time.
 *
 * @param string $customerEmail
 * @param string $customerName
 * @param array  $details
 * @return bool
 */
function sendRescheduleEmail(string $customerEmail, string $customerName, array $details): bool {
    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
    
    if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
        return sendBrevoEmail(
            $customerEmail,
            $customerName,
            'Your Appointment Has Been Rescheduled - Barbershop',
            buildRescheduleEmailBody($details)
        );
    }
    
    try {
        $mail = getMailer();
        $mail->addAddress($customerEmail, $customerName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Appointment Has Been Rescheduled - Barbershop';
        $mail->Body    = buildRescheduleEmailBody($details);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Build HTML email body for customer notification.
 */
function buildCustomerEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    return "
        <h2>Appointment Confirmed</h2>
        <p>Hello <strong>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
        <p>Your appointment has been booked successfully.</p>
        <ul>
            <li><strong>Haircut / Style:</strong> " . htmlspecialchars($details['service_name']) . "</li>
            <li><strong>Location:</strong> " . $location . "</li>
            <li><strong>Date:</strong> " . htmlspecialchars($details['date']) . "</li>
            <li><strong>Time:</strong> " . htmlspecialchars($details['time']) . "</li>
        </ul>
        <p>Thank you for choosing us!</p>
    ";
}

/**
 * Build HTML email body for admin notification.
 */
function buildAdminEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    return "
        <h2>New Booking Alert</h2>
        <p>A new appointment has been made.</p>
        <ul>
            <li><strong>Customer:</strong> " . htmlspecialchars($details['customer_name']) . " (" . htmlspecialchars($details['customer_email']) . ")</li>
            <li><strong>Haircut / Style:</strong> " . htmlspecialchars($details['service_name']) . "</li>
            <li><strong>Location:</strong> " . $location . "</li>
            <li><strong>Date:</strong> " . htmlspecialchars($details['date']) . "</li>
            <li><strong>Time:</strong> " . htmlspecialchars($details['time']) . "</li>
        </ul>
    ";
}

/**
 * Build HTML email body for acceptance notification.
 */
function buildAcceptanceEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    return "
        <h2>Appointment Accepted</h2>
        <p>Hello <strong>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
        <p>Great news! Your appointment has been <strong style='color: #2d6a4f;'>ACCEPTED</strong> by our barber.</p>
        <ul>
            <li><strong>Haircut / Style:</strong> " . htmlspecialchars($details['service_name']) . "</li>
            <li><strong>Location:</strong> " . $location . "</li>
            <li><strong>Date:</strong> " . htmlspecialchars($details['date']) . "</li>
            <li><strong>Time:</strong> " . htmlspecialchars($details['time']) . "</li>
        </ul>
        <p>We look forward to seeing you!</p>
    ";
}

/**
 * Build HTML email body for cancellation notification.
 */
function buildCancellationEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    return "
        <h2>Appointment Cancelled</h2>
        <p>Hello <strong>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
        <p>We regret to inform you that your appointment has been <strong style='color: #dc3545;'>CANCELLED</strong>.</p>
        <ul>
            <li><strong>Haircut / Style:</strong> " . htmlspecialchars($details['service_name']) . "</li>
            <li><strong>Location:</strong> " . $location . "</li>
            <li><strong>Date:</strong> " . htmlspecialchars($details['date']) . "</li>
            <li><strong>Time:</strong> " . htmlspecialchars($details['time']) . "</li>
        </ul>
        <p>Please book a new appointment at your convenience.</p>
    ";
}

/**
 * Build HTML email body for reschedule notification.
 */
function buildRescheduleEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    $oldDate = isset($details['old_date']) ? htmlspecialchars($details['old_date']) : 'Previous date';
    $oldTime = isset($details['old_time']) ? htmlspecialchars($details['old_time']) : 'Previous time';
    return "
        <h2>Appointment Rescheduled</h2>
        <p>Hello <strong>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
        <p>Your appointment has been <strong style='color: #b8860b;'>RESCHEDULED</strong> by our barber.</p>
        <ul>
            <li><strong>Haircut / Style:</strong> " . htmlspecialchars($details['service_name']) . "</li>
            <li><strong>Location:</strong> " . $location . "</li>
            <li><strong>Previous Date:</strong> " . $oldDate . " at " . $oldTime . "</li>
            <li><strong>New Date:</strong> " . htmlspecialchars($details['date']) . " at " . htmlspecialchars($details['time']) . "</li>
        </ul>
        <p>Please confirm that the new time works for you.</p>
    ";
}

/**
 * Send completion email to customer when admin marks appointment as completed.
 *
 * @param string $customerEmail
 * @param string $customerName
 * @param array  $details
 * @return bool
 */
function sendCompletionEmail(string $customerEmail, string $customerName, array $details): bool {
    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
    
    if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
        return sendBrevoEmail(
            $customerEmail,
            $customerName,
            'Your Appointment Has Been Completed - Barbershop',
            buildCompletionEmailBody($details)
        );
    }
    
    try {
        $mail = getMailer();
        $mail->addAddress($customerEmail, $customerName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Appointment Has Been Completed - Barbershop';
        $mail->Body    = buildCompletionEmailBody($details);
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Build HTML email body for completion notification.
 */
function buildCompletionEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    $appointmentId = isset($details['appointment_id']) ? intval($details['appointment_id']) : 0;
    
    // Get the site URL dynamically
    $siteUrl = 'https://von-barbershop.onrender.com';
    
    $emailBody = "
        <h2 style='color: #28a745;'>✅ Appointment Completed</h2>
        <p>Hello <strong>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
        <p>Great news! Your appointment has been <strong style='color: #28a745;'>COMPLETED</strong>.</p>
        <ul>
            <li><strong>Haircut / Style:</strong> " . htmlspecialchars($details['service_name']) . "</li>
            <li><strong>Location:</strong> " . $location . "</li>
            <li><strong>Date:</strong> " . htmlspecialchars($details['date']) . "</li>
            <li><strong>Time:</strong> " . htmlspecialchars($details['time']) . "</li>
        </ul>
        <hr style='border-color: #eee; margin: 30px 0;'>
        <div style='text-align: center; padding: 30px 20px; background: #f8f9fa; border-radius: 10px;'>
            <h3 style='color: var(--barber-gold); margin-top: 0;'>Thank you for choosing RUBICUTS V.O.N! 💈</h3>
            <p style='font-size: 16px; color: #555;'>We hope you love your new look! Your feedback helps us improve.</p>
            <p style='font-size: 18px; font-weight: bold; color: #333;'>Please rate your experience with us:</p>
    ";
    
    if ($appointmentId > 0) {
        $reviewLink = $siteUrl . '/my_appointments.php?review=' . $appointmentId;
        $emailBody .= "
            <a href='{$reviewLink}' style='display: inline-block; background: #C5A059; color: #1a1a2e; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 18px; margin: 20px 0;'>
                ⭐ Rate Us Now
            </a>
        ";
    }
    
    $emailBody .= "
        </div>
        <p style='margin-top: 30px; color: #666; font-size: 14px;'>Click the button above to leave a review and share your experience!</p>
    ";
    
    return $emailBody;
}

