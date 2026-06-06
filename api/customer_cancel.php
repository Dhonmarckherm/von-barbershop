<?php
/**
 * API Endpoint: Customer Cancel Appointment
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 * 
 * Customer can only cancel their own appointments with status 'pending' or 'accepted'.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
initializeSession();

header('Content-Type: application/json');

// Customer check
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized.']);
    exit;
}

// Validate input
$appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);

if (!$appointmentId) {
    echo json_encode(['error' => 'Invalid appointment ID.']);
    exit;
}

// Fetch appointment and verify ownership
$stmt = $pdo->prepare("SELECT a.appointment_date, a.appointment_time, a.status, a.haircut_description, a.location, u.name AS customer_name, u.email AS customer_email FROM appointments a JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.user_id = ?");
$stmt->execute([$appointmentId, $_SESSION['user_id']]);
$appt = $stmt->fetch();

if (!$appt) {
    echo json_encode(['error' => 'Appointment not found or access denied.']);
    exit;
}

// Only allow cancelling pending or accepted appointments
if (!in_array($appt['status'], ['pending', 'accepted'])) {
    echo json_encode(['error' => 'Cannot cancel this appointment.']);
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

    // Send response first, then email
    if (function_exists('fastcgi_finish_request')) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
        fastcgi_finish_request();
    } else {
        ignore_user_abort(true);
        set_time_limit(30);
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
        if (ob_get_level() > 0) { ob_end_flush(); }
        flush();
    }

    // Send email after response
    try {
        require_once __DIR__ . '/../config/mailer.php';
        
        // Send cancellation email to customer
        sendCancellationEmail($appt['customer_email'], $appt['customer_name'], $details);
        error_log('Customer cancellation email sent to ' . $appt['customer_email']);
        
        // Notify barber about cancellation
        try {
            $barberStmt = $pdo->query("SELECT email FROM users WHERE role IN ('admin', 'barber') ORDER BY id ASC LIMIT 1");
            $barberUser = $barberStmt->fetch();
            $barberEmail = $barberUser ? $barberUser['email'] : 'dhonmarck2004@gmail.com';
            
            $mail = getMailer();
            $mail->addAddress($barberEmail, 'Barber');
            $mail->isHTML(true);
            $mail->Subject = 'Appointment Cancelled - ' . $appt['customer_name'];
            $mail->Body = "
                <h2>Appointment Cancelled</h2>
                <p><strong>Customer:</strong> {$appt['customer_name']} ({$appt['customer_email']})</p>
                <p><strong>Haircut:</strong> {$appt['haircut_description']}</p>
                <p><strong>Location:</strong> {$appt['location']}</p>
                <hr>
                <p><strong>Date/Time:</strong> {$appt['appointment_date']} at {$appt['appointment_time']}</p>
                <p style='color: #dc3545;'><strong>Status:</strong> CANCELLED</p>
            ";
            $mail->send();
            error_log('Barber notification sent for customer cancellation');
        } catch (Exception $e) {
            error_log('Barber cancellation notification failed: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log('Cancellation email failed: ' . $e->getMessage());
    } catch (Error $e) {
        error_log('Cancellation email error: ' . $e->getMessage());
    }
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
