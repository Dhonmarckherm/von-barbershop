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

// Check if payment already verified
if ($appointment['payment_status'] === 'verified' && !isset($_GET['success'])) {
    $_SESSION['success'] = 'Payment already verified!';
    header('Location: my_appointments.php');
    exit;
}

// If payment proof already uploaded (and not showing success page), redirect to appointments
if ($appointment['payment_proof'] !== null && !isset($_GET['success'])) {
    $_SESSION['info'] = 'Payment already uploaded! Waiting for admin verification.';
    header('Location: my_appointments.php');
    exit;
}

// Handle payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $file = $_FILES['payment_proof'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif', 'image/heic', 'image/heif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // iOS Safari fix: detect MIME type from file if browser sends empty type
    if (empty($file['type'])) {
        // Try to detect MIME type from file content
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if ($detected_type) {
            $file['type'] = $detected_type;
            error_log('[Payment] Detected MIME type: ' . $detected_type);
        } else {
            // Fallback: detect from file extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $ext_map = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 
                'png' => 'image/png', 'webp' => 'image/webp', 
                'gif' => 'image/gif', 'heic' => 'image/heic', 'heif' => 'image/heif'
            ];
            if (isset($ext_map[$ext])) {
                $file['type'] = $ext_map[$ext];
                error_log('[Payment] Detected MIME type from extension: ' . $file['type']);
            }
        }
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        $error = 'Invalid file type (' . $file['type'] . '). Please upload a JPG, PNG, or WebP image.';
    } elseif ($file['size'] > $max_size) {
        $error = 'File too large. Maximum size is 5MB.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error. Please try again.';
    } else {
        try {
            // Read file content and convert to base64
            $file_content = file_get_contents($file['tmp_name']);
            $base64_image = 'data:' . $file['type'] . ';base64,' . base64_encode($file_content);
            
            // Update database with base64 image
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET payment_proof = ?, payment_status = 'pending'
                WHERE id = ?
            ");
            $stmt->execute([$base64_image, $appointment_id]);
            
            // Insert payment log (store reference, not full base64 to avoid VARCHAR limit)
            $stmt = $pdo->prepare("
                INSERT INTO payment_logs (appointment_id, user_id, amount, proof_filename)
                VALUES (?, ?, 50.00, 'base64_stored_in_appointments')
            ");
            $stmt->execute([$appointment_id, $_SESSION['user_id']]);
            
            // Send email notification to admin/barber
            try {
                require_once 'config/mailer.php';
                require_once __DIR__ . '/includes/push_helper.php';
                
                // Get all admins/barbers
                $stmt = $pdo->query("SELECT id, email, name FROM users WHERE role IN ('admin', 'barber')");
                $admins = $stmt->fetchAll();
                
                // Get customer and appointment details
                $stmt = $pdo->prepare("
                    SELECT u.name as customer_name, u.email as customer_email, a.appointment_date, a.appointment_time
                    FROM users u
                    JOIN appointments a ON u.id = a.user_id
                    WHERE a.id = ?
                ");
                $stmt->execute([$appointment_id]);
                $details = $stmt->fetch();
                
                if ($details && !empty($admins)) {
                    // Convert time to 12-hour format
                    $time12 = $details['appointment_time'];
                    if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time12, $matches)) {
                        $hours = (int)$matches[1];
                        $minutes = $matches[2];
                        $period = $hours >= 12 ? 'PM' : 'AM';
                        if ($hours > 12) $hours -= 12;
                        else if ($hours == 0) $hours = 12;
                        $time12 = $hours . ':' . $minutes . ' ' . $period;
                    }
                    
                    foreach ($admins as $admin) {
                        // Send PUSH notification to each admin/barber
                        sendPushNotification($pdo, $admin['id'], '💰 Payment Received', 
                            "{$details['customer_name']} uploaded payment proof for {$details['appointment_date']} at {$time12}", 
                            '/admin_payments.php');
                        
                        // Send email notification
                        $emailBody = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
                                <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 30px; text-align: center; border-bottom: 3px solid #28a745;'>
                                    <h1 style='color: #28a745; font-family: Georgia, serif; font-size: 28px; margin: 0;'>💰 New Payment Received!</h1>
                                </div>
                                
                                <div style='padding: 30px;'>
                                    <p style='font-size: 18px; margin-bottom: 20px;'>Hello <strong style='color: #C5A059;'>" . htmlspecialchars($admin['name']) . "</strong>,</p>
                                    
                                    <p style='font-size: 16px; line-height: 1.6; margin-bottom: 20px;'>A customer has uploaded a payment proof. Please verify and approve it.</p>
                                    
                                    <div style='background: rgba(40,167,69,0.1); border-left: 4px solid #28a745; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>
                                        <h3 style='color: #28a745; margin: 0 0 15px 0; font-size: 18px;'>📋 Payment Details</h3>
                                        <p style='margin: 8px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Customer:</strong> " . htmlspecialchars($details['customer_name']) . "</p>
                                        <p style='margin: 8px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Email:</strong> " . htmlspecialchars($details['customer_email']) . "</p>
                                        <p style='margin: 8px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Amount:</strong> <span style='color: #28a745; font-size: 18px; font-weight: bold;'>₱50.00</span></p>
                                        <p style='margin: 8px 0; color: #F5F0E8;'><strong style='color: #C5A059;'>Appointment:</strong> " . date('M d, Y', strtotime($details['appointment_date'])) . " at " . $time12 . "</p>
                                    </div>
                                    
                                    <p style='font-size: 14px; line-height: 1.6; color: #B8B8CC; margin-bottom: 20px;'>Please check your GCash app to verify the payment, then approve it in the admin dashboard.</p>
                                    
                                    <a href='https://von-barbershop.onrender.com/admin_payments.php' style='display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; padding: 14px 35px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>
                                        🔍 View Payment in Dashboard
                                    </a>
                                </div>
                            </div>
                        ";
                        
                        sendBrevoEmail($admin['email'], $admin['name'], '💰 New Payment Received - Please Verify', $emailBody);
                    }
                }
                
                // Send push notification to customer confirming upload
                sendPushNotification($pdo, $_SESSION['user_id'], '✅ Payment Uploaded', 
                    "Your payment proof has been uploaded successfully. Waiting for admin verification.", 
                    '/my_appointments.php');
                    
            } catch (Exception $e) {
                error_log('Payment notification email failed: ' . $e->getMessage());
            }
            
            $_SESSION['payment_success'] = true;
            header('Location: payment_upload.php?appointment_id=' . $appointment_id . '&success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            error_log('Payment upload error: ' . $e->getMessage());
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
                            <li>Send <strong>₱50.00</strong> to GCash number: <strong style="color: #28a745;">0992-249-1190</strong></li>
                            <li>Take a screenshot of the payment confirmation</li>
                            <li>Upload the screenshot below</li>
                            <li>Wait for admin verification (usually within 5 minutes)</li>
                        </ol>
                    </div>
                    
                    <!-- GCash QR Code Display -->
                    <div class="text-center mb-4">
                        <p style="color: #28a745; font-size: 14px; font-weight: 600; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1.5px;">
                            <i class="bi bi-qr-code-scan"></i> Scan to Pay
                        </p>
                        <div style="background: white; padding: 25px; border-radius: 20px; display: inline-block; box-shadow: 0 6px 30px rgba(40,167,69,0.25); border: 3px solid rgba(40,167,69,0.2);">
                            <img src="qr-code.jpg" alt="GCash QR Code" style="width: 320px; height: 320px; object-fit: contain; border-radius: 8px; display: block;">
                        </div>
                        <div class="mt-3" style="background: rgba(40,167,69,0.08); border: 2px solid rgba(40,167,69,0.3); border-radius: 14px; padding: 18px 25px; display: inline-block;">
                            <p style="color: var(--text-secondary); font-size: 12px; margin: 0 0 6px 0; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">GCash Account Number</p>
                            <p style="color: #28a745; font-size: 28px; font-weight: 700; margin: 0; letter-spacing: 2px;">0992-249-1190</p>
                        </div>
                        <p class="mt-2" style="color: var(--text-secondary); font-size: 13px;">
                            Scan QR code or send manually to the number above
                        </p>
                    </div>
                    
                    <!-- Upload Form -->
                    <form method="POST" enctype="multipart/form-data" id="paymentForm">
                        <div class="mb-3">
                            <label for="payment_proof" class="form-label" style="color: var(--text-primary);">
                                <i class="bi bi-upload"></i> Upload Payment Screenshot
                            </label>
                            <input type="file" class="form-control" id="payment_proof" name="payment_proof" 
                                   accept="image/jpeg,image/png,image/jpg,image/webp,image/gif" required
                                   style="background: var(--form-bg); color: var(--text-primary); border: 1px solid var(--border-color);">
                            <small style="color: var(--text-secondary);">Accepted formats: JPG, PNG, WebP, GIF (Max 5MB)</small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success" id="submitBtn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; padding: 12px; font-weight: 600;">
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

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 9999; align-items: center; justify-content: center; flex-direction: column;">
    <div style="text-align: center; padding: 40px;">
        <!-- Spinner -->
        <div style="width: 80px; height: 80px; margin: 0 auto 30px; position: relative;">
            <div style="width: 80px; height: 80px; border-radius: 50%; border: 4px solid rgba(40,167,69,0.2); border-top-color: #28a745; animation: spin 1s linear infinite;"></div>
        </div>
        <h3 style="color: #C5A059; font-family: Georgia, serif; margin-bottom: 10px;">Uploading Payment Proof...</h3>
        <p style="color: #8A8A9A; font-size: 14px;">Please wait while we process your payment</p>
    </div>
</div>

<!-- Success Overlay -->
<div id="successOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 9999; align-items: center; justify-content: center; flex-direction: column;">
    <div style="text-align: center; padding: 40px; max-width: 400px;">
        <!-- Success Icon -->
        <div style="width: 100px; height: 100px; margin: 0 auto 30px; position: relative; animation: fadeInScale 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); display: flex; align-items: center; justify-content: center; box-shadow: 0 0 40px rgba(40, 167, 69, 0.4);">
                <i class="bi bi-check-lg" style="font-size: 48px; color: white;"></i>
            </div>
        </div>
        <h2 style="color: #C5A059; font-family: Georgia, serif; margin-bottom: 12px;">Payment Submitted!</h2>
        <p style="color: #b0b0b0; font-size: 16px; line-height: 1.6; margin-bottom: 20px;">
            Your payment proof has been uploaded successfully.
        </p>
        <p style="color: #8A8A9A; font-size: 14px; margin-bottom: 30px;">
            Please wait for admin verification. You will receive a notification once approved.
        </p>
        <!-- Progress Steps -->
        <div style="text-align: left; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; color: #28a745;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(40,167,69,0.15); border: 1px solid #28a745; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                    <i class="bi bi-check"></i>
                </div>
                <span>Payment proof uploaded</span>
            </div>
            <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; color: #8A8A9A;">
                <div style="width: 28px; height: 28px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 14px;">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <span>Waiting for admin verification</span>
            </div>
        </div>
        <a href="my_appointments.php" style="display: inline-block; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 14px 35px; border-radius: 12px; text-decoration: none; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(40,167,69,0.3);">
            <i class="bi bi-arrow-right"></i> View My Appointments
        </a>
    </div>
</div>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
@keyframes fadeInScale {
    from { opacity: 0; transform: scale(0.3); }
    to { opacity: 1; transform: scale(1); }
}
</style>

<script>
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const fileInput = document.getElementById('payment_proof');
    if (!fileInput.files.length) {
        e.preventDefault();
        alert('Please select a file to upload.');
        return;
    }
    
    // Show loading overlay
    document.getElementById('loadingOverlay').style.display = 'flex';
    document.getElementById('submitBtn').disabled = true;
    
    // Don't prevent default - let the form submit normally
    // The loading overlay will show while the page processes
});
</script>

<script>
// Show success overlay if coming from successful upload
<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
document.getElementById('successOverlay').style.display = 'flex';
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
