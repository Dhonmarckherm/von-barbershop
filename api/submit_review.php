<?php
/**
 * API Endpoint: Submit Review
 * 
 * Accepts POST parameters:
 *   - appointment_id (int)
 *   - rating (int 1-5)
 *   - comment (string, optional)
 * 
 * Customer access only. Can only review completed appointments.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
initializeSession();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login to submit a review.']);
    exit;
}

// Validate inputs
$appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
$rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment = trim($_POST['comment'] ?? '');

if (!$appointmentId) {
    echo json_encode(['error' => 'Invalid appointment ID.']);
    exit;
}

if (!$rating || $rating < 1 || $rating > 5) {
    echo json_encode(['error' => 'Rating must be between 1 and 5 stars.']);
    exit;
}

try {
    // Check if appointment belongs to user and is completed
    $stmt = $pdo->prepare("SELECT id, status FROM appointments WHERE id = ? AND user_id = ?");
    $stmt->execute([$appointmentId, $_SESSION['user_id']]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        echo json_encode(['error' => 'Appointment not found.']);
        exit;
    }

    if ($appointment['status'] !== 'completed') {
        echo json_encode(['error' => 'You can only review completed appointments.']);
        exit;
    }

    // Check if already reviewed
    $stmt = $pdo->prepare("SELECT id FROM reviews WHERE appointment_id = ? AND user_id = ?");
    $stmt->execute([$appointmentId, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'You have already reviewed this appointment.']);
        exit;
    }

    // Insert review
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, appointment_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $appointmentId, $rating, $comment]);

    echo json_encode(['success' => true, 'message' => 'Thank you for your review!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
exit;
