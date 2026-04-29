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

    // Server settings - use environment variables from Render
    $mailUsername = getenv('MAIL_USERNAME') ?: 'dhondump@gmail.com';
    $mailPassword = getenv('MAIL_PASSWORD') ?: 'nnbdakyukluhihpb';

    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $mailUsername;
    $mail->Password   = $mailPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->SMTPDebug  = 0; // Disable debug output
    $mail->Timeout    = 10; // 10 second timeout to prevent hanging

    // Sender
    $siteName = getSetting('barbershop_name', 'V.O.N Barbershop');
    $mail->setFrom($mailUsername, $siteName);

    return $mail;
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
        // Email to Customer
        $mail = getMailer();
        $mail->addAddress($customerEmail, $customerName);
        $mail->isHTML(true);
        $mail->Subject = 'Your Appointment Confirmation - Barbershop';
        $mail->Body    = buildCustomerEmailBody($appointmentDetails);
        $mail->send();

        // Email to Admin/Barber
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

