<?php
$pageTitle = 'Forgot Password';
require_once 'config/db.php';
require_once 'config/session.php';
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
            
            // In production, send email here. For now, show the link
            $success = "Password reset link has been generated. In production, this would be sent to your email.<br><br>Reset Link: <a href='$resetLink' target='_blank'>Click here to reset password</a>";
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
