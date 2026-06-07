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
        $mail->setFrom('noreply@vonbarbershop.com', 'V.O.N Barber Studio');
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
        $mail->setFrom($mailUsername, 'V.O.N Barber Studio');
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
            'name' => 'V.O.N Barber Studio',
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
        <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #c0c0c0;'>
                <div style='font-size: 48px; margin-bottom: 10px;'>✂️</div>
                <h1 style='color: #c0c0c0; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>Appointment Confirmed!</h1>
                <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>Your booking is all set</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px;'>
                <p style='font-size: 18px; margin-bottom: 25px;'>Hello <strong style='color: #C5A059;'>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
                
                <p style='font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>Great news! Your appointment has been <strong style='color: #28a745; font-size: 18px;'>BOOKED SUCCESSFULLY</strong>. We're looking forward to seeing you!</p>
                
                <!-- Appointment Details Card -->
                <div style='background: rgba(192, 192, 192, 0.1); border-left: 4px solid #c0c0c0; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h3 style='color: #C5A059; margin: 0 0 15px 0; font-size: 18px;'>📋 Appointment Details</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 140px;'>💈 Style:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['service_name']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📍 Location:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . $location . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📅 Date:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['date']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>⏰ Time:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['time']) . "</td>
                        </tr>
                    </table>
                </div>
                
                <p style='font-size: 15px; line-height: 1.6; color: #B8B8CC;'>Please arrive 5-10 minutes early. If you need to reschedule or cancel, please contact us as soon as possible.</p>
            </div>
            
            <!-- Footer -->
            <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                <p style='color: #c0c0c0; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>Thank you for choosing V.O.N Barber Studio!</p>
                <p style='color: #8A8A9A; font-size: 13px; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
            </div>
        </div>
    ";
}

/**
 * Build HTML email body for admin notification.
 */
function buildAdminEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    return "
        <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #c0c0c0;'>
                <div style='font-size: 48px; margin-bottom: 10px;'>🔔</div>
                <h1 style='color: #C5A059; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>New Booking Alert!</h1>
                <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>A new appointment has been made</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px;'>
                <p style='font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>A customer has just booked an appointment. Here are the details:</p>
                
                <!-- Customer Info -->
                <div style='background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #28a745; margin: 0 0 10px 0; font-size: 18px;'>👤 Customer Information</h3>
                    <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Name:</strong> " . htmlspecialchars($details['customer_name']) . "</p>
                    <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Email:</strong> <a href='mailto:" . htmlspecialchars($details['customer_email']) . "' style='color: #C5A059; text-decoration: none;'>" . htmlspecialchars($details['customer_email']) . "</a></p>
                </div>
                
                <!-- Appointment Details Card -->
                <div style='background: rgba(192, 192, 192, 0.1); border-left: 4px solid #c0c0c0; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h3 style='color: #C5A059; margin: 0 0 15px 0; font-size: 18px;'>📋 Appointment Details</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 140px;'>💈 Style:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['service_name']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📍 Location:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . $location . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📅 Date:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['date']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>⏰ Time:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['time']) . "</td>
                        </tr>
                    </table>
                </div>
                
                <p style='font-size: 15px; line-height: 1.6; color: #B8B8CC;'>Log in to your admin dashboard to review and manage this appointment.</p>
            </div>
            
            <!-- Footer -->
            <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                <p style='color: #C5A059; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>V.O.N Barber Studio Admin Dashboard</p>
                <p style='color: #8A8A9A; font-size: 13px; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
            </div>
        </div>
    ";
}

/**
 * Build HTML email body for acceptance notification.
 */
function buildAcceptanceEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    return "
        <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #28a745;'>
                <div style='font-size: 48px; margin-bottom: 10px;'>✅</div>
                <h1 style='color: #28a745; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>Appointment Accepted!</h1>
                <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>Your booking has been confirmed</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px;'>
                <p style='font-size: 18px; margin-bottom: 25px;'>Hello <strong style='color: #C5A059;'>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
                
                <p style='font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>Great news! Your appointment has been <strong style='color: #28a745; font-size: 18px;'>ACCEPTED</strong> by our barber. We're excited to see you!</p>
                
                <!-- Appointment Details Card -->
                <div style='background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h3 style='color: #28a745; margin: 0 0 15px 0; font-size: 18px;'>📋 Appointment Details</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 140px;'>💈 Style:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['service_name']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📍 Location:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . $location . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📅 Date:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['date']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>⏰ Time:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['time']) . "</td>
                        </tr>
                    </table>
                </div>
                
                <p style='font-size: 15px; line-height: 1.6; color: #B8B8CC;'>We look forward to providing you with excellent service. Please arrive on time!</p>
            </div>
            
            <!-- Footer -->
            <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                <p style='color: #C5A059; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>See you soon at V.O.N Barber Studio!</p>
                <p style='color: #8A8A9A; font-size: 13px; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
            </div>
        </div>
    ";
}

/**
 * Build HTML email body for cancellation notification.
 */
function buildCancellationEmailBody(array $details): string {
    $location = isset($details['location']) && $details['location'] ? htmlspecialchars($details['location']) : 'Barbershop';
    return "
        <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #dc3545;'>
                <div style='font-size: 48px; margin-bottom: 10px;'>❌</div>
                <h1 style='color: #dc3545; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>Appointment Cancelled</h1>
                <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>Your booking has been cancelled</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px;'>
                <p style='font-size: 18px; margin-bottom: 25px;'>Hello <strong style='color: #C5A059;'>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
                
                <p style='font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>We regret to inform you that your appointment has been <strong style='color: #dc3545; font-size: 18px;'>CANCELLED</strong>.</p>
                
                <!-- Appointment Details Card -->
                <div style='background: rgba(220, 53, 69, 0.1); border-left: 4px solid #dc3545; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h3 style='color: #dc3545; margin: 0 0 15px 0; font-size: 18px;'>📋 Cancelled Appointment Details</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 140px;'>💈 Style:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['service_name']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📍 Location:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . $location . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📅 Date:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['date']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>⏰ Time:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['time']) . "</td>
                        </tr>
                    </table>
                </div>
                
                <p style='font-size: 15px; line-height: 1.6; color: #B8B8CC;'>Don't worry! You can easily book a new appointment at your convenience. We're here when you're ready.</p>
            </div>
            
            <!-- Footer -->
            <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                <p style='color: #C5A059; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>Book your next appointment anytime!</p>
                <p style='color: #8A8A9A; font-size: 13px; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
            </div>
        </div>
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
        <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #c0c0c0;'>
                <div style='font-size: 48px; margin-bottom: 10px;'>🔄</div>
                <h1 style='color: #C5A059; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>Appointment Rescheduled</h1>
                <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>Your appointment time has been changed</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px;'>
                <p style='font-size: 18px; margin-bottom: 25px;'>Hello <strong style='color: #C5A059;'>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
                
                <p style='font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>Your appointment has been <strong style='color: #C5A059; font-size: 18px;'>RESCHEDULED</strong> by our barber. Please review the new details:</p>
                
                <!-- Old Appointment Details -->
                <div style='background: rgba(108, 117, 125, 0.1); border-left: 4px solid #6c757d; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                    <h3 style='color: #6c757d; margin: 0 0 10px 0; font-size: 18px;'>📅 Previous Schedule</h3>
                    <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Date:</strong> " . $oldDate . "</p>
                    <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Time:</strong> " . $oldTime . "</p>
                </div>
                
                <!-- New Appointment Details Card -->
                <div style='background: rgba(192, 192, 192, 0.1); border-left: 4px solid #c0c0c0; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h3 style='color: #C5A059; margin: 0 0 15px 0; font-size: 18px;'>✨ New Schedule</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 140px;'>💈 Style:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['service_name']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📍 Location:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . $location . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📅 Date:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['date']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>⏰ Time:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['time']) . "</td>
                        </tr>
                    </table>
                </div>
                
                <p style='font-size: 15px; line-height: 1.6; color: #B8B8CC;'>Please confirm that the new time works for you. If you need to make further changes, don't hesitate to contact us.</p>
            </div>
            
            <!-- Footer -->
            <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                <p style='color: #C5A059; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>Thank you for your flexibility!</p>
                <p style='color: #8A8A9A; font-size: 13px; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
            </div>
        </div>
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
        <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #28a745;'>
                <div style='font-size: 48px; margin-bottom: 10px;'>✅</div>
                <h1 style='color: #28a745; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>Appointment Completed!</h1>
                <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>Your service has been completed</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 30px;'>
                <p style='font-size: 18px; margin-bottom: 25px;'>Hello <strong style='color: #C5A059;'>" . htmlspecialchars($details['customer_name']) . "</strong>,</p>
                
                <p style='font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>Great news! Your appointment has been <strong style='color: #28a745; font-size: 18px;'>COMPLETED</strong>. We hope you love your new look!</p>
                
                <!-- Appointment Details Card -->
                <div style='background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                    <h3 style='color: #28a745; margin: 0 0 15px 0; font-size: 18px;'>📋 Appointment Details</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 140px;'>💈 Style:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['service_name']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📍 Location:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . $location . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>📅 Date:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['date']) . "</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>⏰ Time:</td>
                            <td style='padding: 8px 0; color: #F5F0E8;'>" . htmlspecialchars($details['time']) . "</td>
                        </tr>
                    </table>
                </div>
    ";
    
    if ($appointmentId > 0) {
        $reviewLink = $siteUrl . '/my_appointments.php?review=' . $appointmentId;
        $emailBody .= "
                <!-- Review CTA -->
                <div style='background: rgba(192, 192, 192, 0.1); padding: 30px 20px; border-radius: 8px; text-align: center; margin-bottom: 25px; border: 2px solid #C5A059;'>
                    <h3 style='color: #c0c0c0; margin: 0 0 10px 0; font-size: 20px;'>⭐ Thank you for choosing VON BARBER STUDIO! 💈</h3>
                    <p style='font-size: 16px; color: #F5F0E8; margin-bottom: 20px;'>We hope you love your new look! Your feedback helps us improve.</p>
                    <p style='font-size: 18px; font-weight: bold; color: #c0c0c0; margin-bottom: 20px;'>Please rate your experience with us:</p>
                    <a href='{$reviewLink}' style='display: inline-block; background: #C5A059; color: #1a1a2e; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px; transition: all 0.3s;'>
                        ⭐ Rate Us Now
                    </a>
                </div>
        ";
    }
    
    $emailBody .= "
                <p style='font-size: 15px; line-height: 1.6; color: #B8B8CC;'>We appreciate your business and look forward to serving you again!</p>
            </div>
            
            <!-- Footer -->
            <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                <p style='color: #C5A059; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>Book your next appointment anytime!</p>
                <p style='color: #8A8A9A; font-size: 13px; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
            </div>
        </div>
    ";
    
    return $emailBody;
}

