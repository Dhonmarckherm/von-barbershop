<?php
/**
 * API Endpoint: Customer Reschedule Appointment
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 *   - new_date (YYYY-MM-DD)
 *   - new_time (HH:MM:SS)
 * 
 * Customer can only reschedule their own appointments with status 'pending' or 'accepted'.
 */

error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/auth_helper.php';

// Customer check
requireCustomerAuth();

// Validate inputs - use $_POST directly for reliability
$appointmentId = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
$newDate = $_POST['new_date'] ?? '';
$newTime = $_POST['new_time'] ?? '';

if ($appointmentId <= 0) {
    error_log("Reschedule failed: Invalid appointment ID. Received: " . ($_POST['appointment_id'] ?? 'null'));
    echo json_encode(['error' => 'Invalid appointment ID.']);
    exit;
}

if (empty($newDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
    error_log("Reschedule failed: Invalid date format. Received: {$newDate}");
    echo json_encode(['error' => 'Invalid date format.']);
    exit;
}

if (empty($newTime) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $newTime)) {
    error_log("Reschedule failed: Invalid time format. Received: {$newTime}");
    echo json_encode(['error' => 'Invalid time format.']);
    exit;
}

// Normalize time to HH:MM:SS format
if (strlen($newTime) === 5) {
    $newTime .= ':00';
}

// Fetch appointment and verify ownership
$stmt = $pdo->prepare("SELECT a.appointment_date, a.appointment_time, a.status, a.haircut_description, a.location, u.name AS customer_name, u.email AS customer_email FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.user_id = ?");
$stmt->execute([$appointmentId, $_SESSION['user_id']]);
$appt = $stmt->fetch();

if (!$appt) {
    error_log("Reschedule failed: Appointment not found or access denied. ID: {$appointmentId}, User: " . $_SESSION['user_id']);
    echo json_encode(['error' => 'Appointment not found or access denied.']);
    exit;
}

// Only allow rescheduling pending or accepted appointments
if (!in_array($appt['status'], ['pending', 'accepted'])) {
    error_log("Reschedule failed: Invalid status '{$appt['status']}' for appointment {$appointmentId}");
    echo json_encode(['error' => 'Cannot reschedule this appointment. Current status: ' . ucfirst($appt['status'])]);
    exit;
}

// Check if new slot is already booked (excluding current appointment)
$stmt = $pdo->prepare("SELECT id FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled' AND id != ?");
$stmt->execute([$newDate, $newTime, $appointmentId]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'The selected time slot is already booked.']);
    exit;
}

// Update appointment
$stmt = $pdo->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ? WHERE id = ?");
$stmt->execute([$newDate, $newTime, $appointmentId]);

if ($stmt->rowCount() > 0) {
    // Get customer user_id
    $customerId = $_SESSION['user_id'];
    
    // Prepare email details
    $details = [
        'customer_name'  => $appt['customer_name'],
        'customer_email' => $appt['customer_email'],
        'service_name'   => $appt['haircut_description'],
        'location'       => $appt['location'],
        'date'           => $newDate,
        'time'           => $newTime,
        'old_date'       => $appt['appointment_date'],
        'old_time'       => $appt['appointment_time'],
    ];
    
    // Helper function to convert time to 12-hour format (define BEFORE use)
    if (!function_exists('formatTime12HourResched')) {
        function formatTime12HourResched(string $time24): string {
            if (empty($time24)) return $time24;
            $time24 = preg_replace('/:\d{2}$/', '', $time24);
            list($hours, $minutes) = explode(':', $time24);
            $hours = (int)$hours;
            $period = $hours >= 12 ? 'PM' : 'AM';
            if ($hours > 12) $hours -= 12;
            else if ($hours == 0) $hours = 12;
            return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' ' . $period;
        }
    }
    
    // Format times for display
    $oldTime12 = formatTime12HourResched($appt['appointment_time']);
    $newTime12 = formatTime12HourResched($newTime);
    
    // Send push notification to customer
    require_once __DIR__ . '/../includes/push_helper.php';
    sendPushNotification($pdo, $customerId, '📅 Appointment Rescheduled', "Your appointment has been rescheduled to {$newDate} at {$newTime12}", '/my_appointments.php');

    // Send response immediately for fast UX
    // NOTE: On Render free tier, we must send emails BEFORE response to ensure delivery
    
    // Send emails FIRST (synchronous to guarantee delivery)
    try {
        require_once __DIR__ . '/../config/mailer.php';
        
        // Send reschedule email to customer
        sendRescheduleEmail($appt['customer_email'], $appt['customer_name'], $details);
        error_log('Customer reschedule email sent to ' . $appt['customer_email']);
        
        // Notify barber about reschedule
        try {
            $barberStmt = $pdo->query("SELECT email, name FROM users WHERE role IN ('admin', 'barber') ORDER BY id ASC LIMIT 1");
            $barberUser = $barberStmt->fetch();
            $barberEmail = $barberUser ? $barberUser['email'] : 'dhonmarck2004@gmail.com';
            $barberName = $barberUser ? $barberUser['name'] : 'Barber';
            
            error_log("=== BARBER RESCHEDULE NOTIFICATION DEBUG ===");
            error_log("Barber email: {$barberEmail}");
            error_log("Barber name: {$barberName}");
            error_log("Customer: {$appt['customer_name']}");
            error_log("Old time: {$oldTime12}, New time: {$newTime12}");
            
            // Use sendBrevoEmail for reliable delivery
            $barberEmailBody = "
                <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
                    <!-- Header -->
                    <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #C5A059;'>
                        <div style='font-size: 48px; margin-bottom: 10px;'>🔄</div>
                        <h1 style='color: #C5A059; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>Customer Rescheduled Appointment</h1>
                        <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>Appointment schedule has been changed</p>
                    </div>
                    
                    <!-- Content -->
                    <div style='padding: 30px;'>
                        <!-- Customer Info -->
                        <div style='background: rgba(197, 160, 89, 0.1); border-left: 4px solid #C5A059; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                            <h3 style='color: #C5A059; margin: 0 0 10px 0; font-size: 18px;'>👤 Customer Information</h3>
                            <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Name:</strong> {$appt['customer_name']}</p>
                            <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Email:</strong> <a href='mailto:{$appt['customer_email']}' style='color: #C5A059; text-decoration: none;'>{$appt['customer_email']}</a></p>
                            <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Haircut:</strong> {$appt['haircut_description']}</p>
                            <p style='margin: 5px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Location:</strong> {$appt['location']}</p>
                        </div>
                        
                        <!-- Old Schedule -->
                        <div style='background: rgba(108, 117, 125, 0.15); border-left: 4px solid #6c757d; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                            <h3 style='color: #6c757d; margin: 0 0 15px 0; font-size: 18px;'>📅 Previous Schedule</h3>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 100px;'>Date:</td>
                                    <td style='padding: 8px 0; color: #F5F0E8;'>{$appt['appointment_date']}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>Time:</td>
                                    <td style='padding: 8px 0; color: #F5F0E8;'>{$oldTime12}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- New Schedule -->
                        <div style='background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; padding: 20px; border-radius: 8px; margin-bottom: 25px;'>
                            <h3 style='color: #28a745; margin: 0 0 15px 0; font-size: 18px;'>✨ New Schedule</h3>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0; width: 100px;'>Date:</td>
                                    <td style='padding: 8px 0; color: #F5F0E8;'>{$newDate}</td>
                                </tr>
                                <tr>
                                    <td style='padding: 8px 0; font-weight: bold; color: #c0c0c0;'>Time:</td>
                                    <td style='padding: 8px 0; color: #F5F0E8; font-size: 20px; font-weight: bold;'>{$newTime12}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <p style='font-size: 15px; line-height: 1.6; color: #B8B8CC;'>Please update your calendar accordingly. If you have any conflicts with the new time, please contact the customer directly.</p>
                    </div>
                    
                    <!-- Footer -->
                    <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                        <p style='color: #C5A059; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>V.O.N Barber Studio Admin Dashboard</p>
                        <p style='color: #8A8A9A; font-size: 13px; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
                    </div>
                </div>
            ";
            
            error_log("Calling sendBrevoEmail for barber notification...");
            
            $barberSent = sendBrevoEmail(
                $barberEmail,
                $barberName,
                '🔄 Customer Rescheduled Appointment - ' . $appt['customer_name'],
                $barberEmailBody
            );
            
            error_log("sendBrevoEmail returned: " . ($barberSent ? 'TRUE' : 'FALSE'));
            
            if ($barberSent) {
                error_log('✓ Barber reschedule notification sent successfully to ' . $barberEmail);
            } else {
                error_log('✗ Barber reschedule notification failed (Brevo API)');
            }
        } catch (Exception $e) {
            error_log('✗ Barber reschedule notification failed: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log('Reschedule email failed: ' . $e->getMessage());
    } catch (Error $e) {
        error_log('Reschedule email error: ' . $e->getMessage());
    }
    
    // NOW send response after emails are sent
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'message' => 'Appointment rescheduled successfully',
        'notification' => [
            'title' => 'Appointment Rescheduled',
            'body' => 'Your appointment has been rescheduled to ' . date('M d, Y', strtotime($newDate)) . ' at ' . $newTime12,
            'id' => 'reschedule_' . $appointmentId
        ]
    ]);
    exit;
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Appointment rescheduled successfully',
        'notification' => [
            'title' => '📅 Appointment Rescheduled',
            'body' => "Your appointment has been rescheduled to {$newDate} at " . substr($newTime, 0, 5) . ".",
            'id' => $appointmentId + 4000
        ]
    ]);
}
exit;
