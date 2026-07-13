<?php
session_start();
require_once 'config/db.php';
require_once 'includes/auth_check.php';

// Get appointment ID from URL
$appointment_id = $_GET['appointment_id'] ?? null;

if (!$appointment_id) {
    header('Location: my_appointments.php');
    exit;
}

// Verify appointment belongs to current user
$stmt = $pdo->prepare("
    SELECT a.*, u.name, u.email, s.name as service_name, s.price 
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN services s ON a.service_id = s.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$appointment_id, $_SESSION['user_id']]);
$appointment = $stmt->fetch();

if (!$appointment) {
    $_SESSION['error'] = 'Appointment not found.';
    header('Location: my_appointments.php');
    exit;
}

// Check if payment already uploaded
if ($appointment['payment_status'] === 'verified') {
    $_SESSION['success'] = 'Payment already verified!';
    header('Location: my_appointments.php');
    exit;
}

// Handle payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $file = $_FILES['payment_proof'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed_types)) {
        $error = 'Invalid file type. Please upload a JPG or PNG image.';
    } elseif ($file['size'] > $max_size) {
        $error = 'File too large. Maximum size is 5MB.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error. Please try again.';
    } else {
        // Create uploads directory if it doesn't exist
        $upload_dir = 'uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'payment_' . $appointment_id . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Update database
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET payment_proof = ?, payment_status = 'pending'
                WHERE id = ?
            ");
            $stmt->execute([$new_filename, $appointment_id]);
            
            // Insert payment log
            $stmt = $pdo->prepare("
                INSERT INTO payment_logs (appointment_id, user_id, amount, proof_filename)
                VALUES (?, ?, 50.00, ?)
            ");
            $stmt->execute([$appointment_id, $_SESSION['user_id'], $new_filename]);
            
            $_SESSION['success'] = 'Payment uploaded successfully! Waiting for admin verification.';
            header('Location: my_appointments.php');
            exit;
        } else {
            $error = 'Failed to upload file. Please try again.';
        }
    }
}

$page_title = 'Upload Payment Proof';
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px;">
                <div class="card-body p-4">
                    <h2 class="text-center mb-4" style="color: var(--text-primary); font-family: 'Playfair Display', serif;">
                        Upload Payment Proof
                    </h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Appointment Details -->
                    <div class="mb-4 p-3" style="background: rgba(255,255,255,0.05); border-radius: 12px;">
                        <h5 style="color: var(--text-primary); margin-bottom: 15px;">
                            <i class="bi bi-calendar-check"></i> Appointment Details
                        </h5>
                        <div class="row">
                            <div class="col-6">
                                <small style="color: var(--text-secondary);">Service:</small>
                                <p style="color: var(--text-primary); font-weight: 600; margin: 0;">
                                    <?php echo htmlspecialchars($appointment['service_name'] ?? 'Custom Service'); ?>
                                </p>
                            </div>
                            <div class="col-6">
                                <small style="color: var(--text-secondary);">Date & Time:</small>
                                <p style="color: var(--text-primary); font-weight: 600; margin: 0;">
                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                    <br>
                                    <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Instructions -->
                    <div class="mb-4 p-3" style="background: rgba(40,167,69,0.1); border-left: 3px solid #28a745; border-radius: 8px;">
                        <h5 style="color: #28a745; margin-bottom: 10px;">
                            <i class="bi bi-info-circle-fill"></i> Payment Instructions
                        </h5>
                        <ol style="color: var(--text-primary); padding-left: 20px; margin: 0;">
                            <li>Send <strong>₱50.00</strong> to GCash number: <strong style="color: #28a745;">0969-055-8227</strong></li>
                            <li>Take a screenshot of the payment confirmation</li>
                            <li>Upload the screenshot below</li>
                            <li>Wait for admin verification (usually within 5 minutes)</li>
                        </ol>
                    </div>
                    
                    <!-- GCash QR Code Display -->
                    <div class="text-center mb-4">
                        <div style="background: white; padding: 20px; border-radius: 12px; display: inline-block;">
                            <i class="bi bi-qr-code" style="font-size: 150px; color: #007bff;"></i>
                            <p style="color: #000; margin-top: 10px; font-weight: 600;">GCash QR Code</p>
                            <p style="color: #666; font-size: 12px; margin: 0;">Scan to pay ₱50.00</p>
                        </div>
                        <p class="mt-2" style="color: var(--text-secondary); font-size: 14px;">
                            Or send manually to: <strong style="color: #28a745;">0969-055-8227</strong>
                        </p>
                    </div>
                    
                    <!-- Upload Form -->
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="payment_proof" class="form-label" style="color: var(--text-primary);">
                                <i class="bi bi-upload"></i> Upload Payment Screenshot
                            </label>
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof" 
                                   accept="image/jpeg,image/png,image/jpg" required
                                   style="background: var(--form-bg); color: var(--text-primary); border: 1px solid var(--border-color);">
                            <small style="color: var(--text-secondary);">Accepted formats: JPG, PNG (Max 5MB)</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; padding: 12px; font-weight: 600;">
                                <i class="bi bi-check-circle"></i> Submit Payment Proof
                            </button>
                            <a href="my_appointments.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to My Appointments
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
