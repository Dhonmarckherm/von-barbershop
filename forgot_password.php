<?php
$pageTitle = 'Forgot Password';
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/mailer.php';
initializeSession();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);
            
            // Create reset link with correct domain
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            $resetLink = "$protocol://$host/reset_password.php?token=$token";
            
            // Send email with reset link
            $subject = "V.O.N Barbershop - Password Reset Request";
            $message = "<html><body style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>";
            $message .= "<div style='background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); padding: 30px; text-align: center;'>";
            $message .= "<h1 style='color: #d4af37; margin: 0; font-size: 28px;'>V.O.N Barbershop</h1>";
            $message .= "<p style='color: #b0b0b0; margin: 5px 0 0; font-size: 14px;'>Professional Grooming Services</p>";
            $message .= "</div>";
            $message .= "<div style='background: white; padding: 30px;'>";
            $message .= "<h2 style='color: #333; margin-top: 0;'>Password Reset Request</h2>";
            $message .= "<p style='color: #666; font-size: 16px;'>Hello <strong>{$user['name']}</strong>,</p>";
            $message .= "<p style='color: #666; font-size: 16px;'>We received a request to reset your password. Click the button below to reset it:</p>";
            $message .= "<div style='text-align: center; margin: 30px 0;'>";
            $message .= "<a href='$resetLink' style='background: #d4af37; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-size: 18px; font-weight: bold; display: inline-block;'>Reset Password</a>";
            $message .= "</div>";
            $message .= "<p style='color: #666; font-size: 14px;'>This link will expire in <strong>1 hour</strong>.</p>";
            $message .= "<p style='color: #666; font-size: 14px;'>If you didn't request this, please ignore this email.</p>";
            $message .= "<hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>";
            $message .= "<p style='color: #999; font-size: 12px; text-align: center;'>If the button doesn't work, copy and paste this link:</p>";
            $message .= "<p style='color: #999; font-size: 12px; text-align: center; word-break: break-all;'>$resetLink</p>";
            $message .= "</div>";
            $message .= "<div style='background: #f5f5f5; padding: 20px; text-align: center;'>";
            $message .= "<p style='color: #999; font-size: 12px; margin: 0;'>© " . date('Y') . " V.O.N Barbershop. All rights reserved.</p>";
            $message .= "</div>";
            $message .= "</body></html>";
            
            // Send the email
            try {
                $mail = getMailer();
                $mail->addAddress($user['email'], $user['name']);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $message;
                $mail->send();
                $emailSent = true;
            } catch (Exception $e) {
                error_log("Password Reset Email Error: " . $e->getMessage());
                $emailSent = false;
            }
            
            if ($emailSent) {
                $success = "Password reset link has been sent to <strong>{$user['email']}</strong>. Please check your inbox (and spam folder).";
            } else {
                $error = "Failed to send email. Please try again or contact support.";
            }
        } else {
            // Don't reveal if email exists or not (security)
            $success = 'If an account exists with that email, a password reset link has been sent.';
        }
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Reset Password</h2>
                <p class="text-center mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Enter your email to receive a reset link</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="forgot_password.php" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required autofocus>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Send Reset Link</button>
                    </div>
                </form>

                <hr class="my-4">
                <p class="text-center mb-0">Remember your password? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
