<?php
/**
 * API Endpoint: Customer Cancel Appointment
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 * 
 * Customer can only cancel their own appointments with status 'pending' or 'accepted'.
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/auth_helper.php';

// Customer check
requireCustomerAuth();

// Validate input - use $_POST directly for reliability
$appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;

if ($appointmentId <= 0) {
    error_log("Cancel failed: Invalid appointment ID. Received: " . ($_POST['appointment_id'] ?? 'null'));
    echo json_encode(['error' => 'Invalid appointment ID.']);
    exit;
}

// Fetch appointment and verify ownership
$stmt = $pdo->prepare("SELECT a.appointment_date, a.appointment_time, a.status, a.haircut_description, a.location, u.name AS customer_name, u.email AS customer_email FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.user_id = ?");
$stmt->execute([$appointmentId, $_SESSION['user_id']]);
$appt = $stmt->fetch();

if (!$appt) {
    error_log("Cancel failed: Appointment not found or access denied. ID: {$appointmentId}, User: " . $_SESSION['user_id']);
    echo json_encode(['error' => 'Appointment not found or access denied.']);
    exit;
}

// Only allow cancelling pending or accepted appointments
if (!in_array($appt['status'], ['pending', 'accepted'])) {
    error_log("Cancel failed: Invalid status '{$appt['status']}' for appointment {$appointmentId}");
    echo json_encode(['error' => 'Cannot cancel this appointment. Current status: ' . ucfirst($appt['status'])]);
    exit;
}

// Update appointment status to cancelled
$stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
$stmt->execute([$appointmentId]);

if ($stmt->rowCount() > 0) {
    // Send cancellation email
    $details = [
        'customer_name'  => $appt['customer_name'],
        'customer_email' => $appt['customer_email'],
        'service_name'   => $appt['haircut_description'],
        'location'       => $appt['location'],
        'date'           => $appt['appointment_date'],
        'time'           => $appt['appointment_time'],
    ];

    // NOTE: On Render free tier, we must send emails BEFORE response to ensure delivery
    
    // Send emails FIRST (synchronous to guarantee delivery)
    try {
        require_once __DIR__ . '/../config/mailer.php';
        
        // Send cancellation email to customer
        sendCancellationEmail($appt['customer_email'], $appt['customer_name'], $details);
        error_log('Customer cancellation email sent to ' . $appt['customer_email']);
        
        // Notify barber about cancellation
        try {
            $barberStmt = $pdo->query("SELECT email, name FROM users WHERE role IN ('admin', 'barber') ORDER BY id ASC LIMIT 1");
            $barberUser = $barberStmt->fetch();
            $barberEmail = $barberUser ? $barberUser['email'] : 'dhonmarck2004@gmail.com';
            $barberName = $barberUser ? $barberUser['name'] : 'Barber';
            
            error_log("Sending barber cancellation notification to: {$barberEmail}");
            
            // Convert time to 12-hour format
            $time12 = $appt['appointment_time'];
            if (preg_match('/^(\d{1,2}):(\d{2})$/', $time12, $matches)) {
                $hours = (int)$matches[1];
                $minutes = $matches[2];
                $period = $hours >= 12 ? 'PM' : 'AM';
                if ($hours > 12) $hours -= 12;
                else if ($hours == 0) $hours = 12;
                $time12 = $hours . ':' . $minutes . ' ' . $period;
            }
            
            $mail = getMailer();
            $mail->addAddress($barberEmail, $barberName);
            $mail->isHTML(true);
            $mail->Subject = '❌ Appointment Cancelled - ' . $appt['customer_name'];
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #dc3545;'>❌ Appointment Cancelled</h2>
                    <p><strong>Customer:</strong> {$appt['customer_name']} ({$appt['customer_email']})</p>
                    <p><strong>Haircut:</strong> {$appt['haircut_description']}</p>
                    <p><strong>Location:</strong> {$appt['location']}</p>
                    <hr>
                    <p><strong>Date:</strong> {$appt['appointment_date']}</p>
                    <p><strong>Time:</strong> {$time12}</p>
                    <p style='color: #dc3545; font-weight: bold;'>Status: CANCELLED</p>
                </div>
            ";
            $mail->send();
            error_log('✓ Barber cancellation notification sent successfully to ' . $barberEmail);
        } catch (Exception $e) {
            error_log('✗ Barber cancellation notification failed: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log('Cancellation email failed: ' . $e->getMessage());
    } catch (Error $e) {
        error_log('Cancellation email error: ' . $e->getMessage());
    }
    
    // NOW send response after emails are sent
    exit;
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Appointment cancelled successfully',
        'notification' => [
            'title' => '❌ Appointment Cancelled',
            'body' => "Your appointment on {$appt['appointment_date']} at " . substr($appt['appointment_time'], 0, 5) . " has been cancelled.",
            'id' => $appointmentId + 3000
        ]
    ]);
}
exit;
