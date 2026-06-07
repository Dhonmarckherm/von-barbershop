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
    // Send reschedule email
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
            
            error_log("Sending barber reschedule notification to: {$barberEmail}");
            
            // Helper function to convert time to 12-hour format
            function formatTime12Hour($time24) {
                if (empty($time24)) return $time24;
                $time24 = preg_replace('/:\d{2}$/', '', $time24);
                list($hours, $minutes) = explode(':', $time24);
                $hours = (int)$hours;
                $period = $hours >= 12 ? 'PM' : 'AM';
                if ($hours > 12) $hours -= 12;
                else if ($hours == 0) $hours = 12;
                return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . ' ' . $period;
            }
            
            $oldTime12 = formatTime12Hour($appt['appointment_time']);
            $newTime12 = formatTime12Hour($newTime);
            
            $mail = getMailer();
            $mail->addAddress($barberEmail, $barberName);
            $mail->isHTML(true);
            $mail->Subject = '🔄 Customer Rescheduled Appointment - ' . $appt['customer_name'];
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #C5A059;'>🔄 Customer Rescheduled Appointment</h2>
                    <p><strong>Customer:</strong> {$appt['customer_name']} ({$appt['customer_email']})</p>
                    <p><strong>Haircut:</strong> {$appt['haircut_description']}</p>
                    <p><strong>Location:</strong> {$appt['location']}</p>
                    <hr>
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                        <p style='color: #6c757d; margin: 5px 0;'><strong>Old Schedule:</strong></p>
                        <p style='margin: 5px 0;'>{$appt['appointment_date']} at {$oldTime12}</p>
                    </div>
                    <div style='background: #e8f5e9; padding: 15px; border-radius: 8px;'>
                        <p style='color: #28a745; margin: 5px 0;'><strong>New Schedule:</strong></p>
                        <p style='margin: 5px 0;'>{$newDate} at {$newTime12}</p>
                    </div>
                </div>
            ";
            $mail->send();
            error_log('✓ Barber reschedule notification sent successfully to ' . $barberEmail);
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
