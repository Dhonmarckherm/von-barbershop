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
            
            // Use sendBrevoEmail for reliable delivery
            $barberEmailBody = "
                <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
                    <!-- Header -->
                    <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #dc3545;'>
                        <div style='font-size: 48px; margin-bottom: 10px;'>❌</div>
                        <h1 style='color: #dc3545; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>Appointment Cancelled</h1>
                        <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>Customer has cancelled their appointment</p>
                    </div>
                    
                    <!-- Content -->
                    <div style='padding: 30px;'>
                        <!-- Customer Info -->
                        <div style='background: rgba(220, 53, 69, 0.1); border-left: 4px solid #dc3545; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                            <h3 style='color: #dc3545; margin: 0 0 10px 0; font-size: 18px;'>👤 Customer Information</h3>
                            <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Name:</strong> {$appt['customer_name']}</p>
                            <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Email:</strong> <a href='mailto:{$appt['customer_email']}' style='color: #C5A059; text-decoration: none;'>{$appt['customer_email']}</a></p>
                            <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Haircut:</strong> {$appt['haircut_description']}</p>
                            <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Location:</strong> {$appt['location']}</p>
                        </div>
                        
                        <!-- Cancelled Appointment Details -->
                        <div style='background: rgba(108, 117, 125, 0.15); border-left: 4px solid #6c757d; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                            <h3 style='color: #6c757d; margin: 0 0 15px 0; font-size: 18px;'>📋 Cancelled Appointment Details</h3>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 100px;'>Date:</td>
                                    <td style='padding: 8px 0; color: #F5F0E8;'>{$appt['appointment_date']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>Time:</td>
                                    <td style='padding: 8px 0; color: #F5F0E8; font-size: 20px; font-weight: bold;'>{$time12}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <p style='font-size: 15px; line-height: 1.6; color: #B8B8CC;'>The time slot is now available for other customers. Please update your calendar accordingly.</p>
                    </div>
                    
                    <!-- Footer -->
                    <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                        <p style='color: #C5A059; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>V.O.N Barber Studio Admin Dashboard</p>
                        <p style='color: #8A8A9A; font-size: 13px; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
                    </div>
                </div>
            ";
            
            $barberSent = sendBrevoEmail(
                $barberEmail,
                $barberName,
                '❌ Appointment Cancelled - ' . $appt['customer_name'],
                $barberEmailBody
            );
            
            if ($barberSent) {
                error_log('✓ Barber cancellation notification sent successfully to ' . $barberEmail);
            } else {
                error_log('✗ Barber cancellation notification failed (Brevo API)');
            }
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
