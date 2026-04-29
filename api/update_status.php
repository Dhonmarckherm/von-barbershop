<?php
/**
 * API Endpoint: Update Appointment Status
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 *   - status (pending|accepted|completed|cancelled)
 * 
 * Admin access only. Sends email notification to customer on accept/cancel.
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
initializeSession();

header('Content-Type: application/json');

// Debug: Log session data
error_log('Session data: ' . print_r($_SESSION, true));

// Admin check
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'barber')) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden. Admin access required. Please log in as admin. Session role: ' . ($_SESSION['role'] ?? 'not set')]);
    exit;
}

// Validate inputs
$appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';

if (!$appointmentId) {
    echo json_encode(['error' => 'Invalid appointment ID.']);
    exit;
}

$allowedStatuses = ['pending', 'accepted', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses, true)) {
    echo json_encode(['error' => 'Invalid status value.']);
    exit;
}

try {
    // Fetch current status and customer details
    $stmt = $pdo->prepare("SELECT a.status AS current_status, a.appointment_date, a.appointment_time, a.haircut_description, a.location, u.name AS customer_name, u.email AS customer_email FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->execute([$appointmentId]);
    $appt = $stmt->fetch();

    if (!$appt) {
        echo json_encode(['error' => 'Appointment not found.']);
        exit;
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $appointmentId]);

    if ($stmt->rowCount() > 0 || $appt['current_status'] === $status) {
        // Send email notification asynchronously (non-blocking)
        if (in_array($status, ['accepted', 'cancelled', 'completed'])) {
            $details = [
                'customer_name'  => $appt['customer_name'],
                'customer_email' => $appt['customer_email'],
                'service_name'   => $appt['haircut_description'],
                'location'       => $appt['location'],
                'date'           => $appt['appointment_date'],
                'time'           => $appt['appointment_time'],
            ];

            // Use fastcgi_finish_request() to send response before sending email
            if (function_exists('fastcgi_finish_request')) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                fastcgi_finish_request();
            } else {
                // For non-FastCGI, send response and continue
                ignore_user_abort(true);
                set_time_limit(30);
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            }

            // Send email after response is sent
            try {
                require_once __DIR__ . '/../config/mailer.php';
                
                // Fetch barber email for notifications
                $barberStmt = $pdo->query("SELECT email FROM users WHERE role IN ('admin', 'barber') ORDER BY id ASC LIMIT 1");
                $barberUser = $barberStmt->fetch();
                $barberEmail = $barberUser ? $barberUser['email'] : 'dhonmarck2004@gmail.com';
                
                if ($status === 'accepted' && $appt['current_status'] !== 'accepted') {
                    // Send acceptance email to customer
                    $emailResult = sendAcceptanceEmail($appt['customer_email'], $appt['customer_name'], $details);
                    error_log('Acceptance email sent to ' . $appt['customer_email'] . ': ' . ($emailResult ? 'SUCCESS' : 'FAILED'));
                    
                    // Notify barber about acceptance
                    try {
                        $mail = getMailer();
                        $mail->addAddress($barberEmail, 'Barber');
                        $mail->isHTML(true);
                        $mail->Subject = 'Appointment Accepted - ' . $appt['customer_name'];
                        $mail->Body = "
                            <h2>Appointment Accepted</h2>
                            <p><strong>Customer:</strong> {$appt['customer_name']} ({$appt['customer_email']})</p>
                            <p><strong>Haircut:</strong> {$appt['haircut_description']}</p>
                            <p><strong>Location:</strong> {$appt['location']}</p>
                            <p><strong>Date:</strong> {$appt['appointment_date']}</p>
                            <p><strong>Time:</strong> {$appt['appointment_time']}</p>
                            <p>This appointment has been accepted.</p>
                        ";
                        $mail->send();
                        error_log('Barber notification sent for acceptance');
                    } catch (Exception $e) {
                        error_log('Barber acceptance notification failed: ' . $e->getMessage());
                    }
                } elseif ($status === 'cancelled' && $appt['current_status'] !== 'cancelled') {
                    // Send cancellation email to customer
                    $emailResult = sendCancellationEmail($appt['customer_email'], $appt['customer_name'], $details);
                    error_log('Cancellation email sent to ' . $appt['customer_email'] . ': ' . ($emailResult ? 'SUCCESS' : 'FAILED'));
                    
                    // Notify barber about cancellation
                    try {
                        $mail = getMailer();
                        $mail->addAddress($barberEmail, 'Barber');
                        $mail->isHTML(true);
                        $mail->Subject = 'Appointment Cancelled - ' . $appt['customer_name'];
                        $mail->Body = "
                            <h2>Appointment Cancelled</h2>
                            <p><strong>Customer:</strong> {$appt['customer_name']} ({$appt['customer_email']})</p>
                            <p><strong>Haircut:</strong> {$appt['haircut_description']}</p>
                            <p><strong>Location:</strong> {$appt['location']}</p>
                            <p><strong>Date:</strong> {$appt['appointment_date']}</p>
                            <p><strong>Time:</strong> {$appt['appointment_time']}</p>
                            <p>This appointment has been cancelled.</p>
                        ";
                        $mail->send();
                        error_log('Barber notification sent for cancellation');
                    } catch (Exception $e) {
                        error_log('Barber cancellation notification failed: ' . $e->getMessage());
                    }
                } elseif ($status === 'completed' && $appt['current_status'] !== 'completed') {
                    // Send completion email to customer with review link
                    $details['appointment_id'] = $appointmentId; // Add appointment ID for review link
                    $emailResult = sendCompletionEmail($appt['customer_email'], $appt['customer_name'], $details);
                    error_log('Completion email sent to ' . $appt['customer_email'] . ': ' . ($emailResult ? 'SUCCESS' : 'FAILED'));
                    
                    // Notify barber about completion
                    try {
                        $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
                        
                        if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
                            // Use Brevo HTTP API (faster)
                            $htmlContent = "
                                <div style='font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #1a1a2e; color: #F5F0E8; border: 1px solid #C5A059;'>
                                    <h2 style='color: #28a745; font-family: Playfair Display, serif;'>✅ Appointment Completed</h2>
                                    <p style='margin-top: 20px;'>Customer: <strong>{$appt['customer_name']}</strong> ({$appt['customer_email']})</p>
                                    <p>Haircut: <strong>{$appt['haircut_description']}</strong></p>
                                    <p>Location: <strong>{$appt['location']}</strong></p>
                                    <p>Date: <strong>{$appt['appointment_date']}</strong> at <strong>{$appt['appointment_time']}</strong></p>
                                    <p style='margin-top: 20px; color: #28a745;'>This appointment has been marked as completed.</p>
                                    <hr style='border-color: rgba(197,160,89,0.3); margin: 30px 0;'>
                                    <p style='color: #8A8A9A; font-size: 0.85rem;'>V.O.N Barbershop Notification System</p>
                                </div>
                            ";
                            sendBrevoEmail($barberEmail, 'Barber', 'Appointment Completed - ' . $appt['customer_name'], $htmlContent);
                        } else {
                            // Fallback to PHPMailer SMTP
                            $mail = getMailer();
                            $mail->addAddress($barberEmail, 'Barber');
                            $mail->isHTML(true);
                            $mail->Subject = 'Appointment Completed - ' . $appt['customer_name'];
                            $mail->Body = "
                                <h2>Appointment Completed</h2>
                                <p><strong>Customer:</strong> {$appt['customer_name']} ({$appt['customer_email']})</p>
                                <p><strong>Haircut:</strong> {$appt['haircut_description']}</p>
                                <p><strong>Location:</strong> {$appt['location']}</p>
                                <p><strong>Date:</strong> {$appt['appointment_date']}</p>
                                <p><strong>Time:</strong> {$appt['appointment_time']}</p>
                                <p>This appointment has been marked as completed.</p>
                            ";
                            $mail->send();
                        }
                        error_log('Barber notification sent for completion');
                    } catch (Exception $e) {
                        error_log('Barber completion notification failed: ' . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                error_log('Email notification failed: ' . $e->getMessage());
            } catch (Error $e) {
                error_log('Email notification error: ' . $e->getMessage());
            }
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        echo json_encode(['error' => 'No changes made. Appointment may not exist.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit;
