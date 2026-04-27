<?php
$pageTitle = 'Reset Password';
require_once 'config/db.php';
require_once 'config/session.php';
initializeSession();

$success = '';
$error = '';
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid reset link.';
} else {
    // Verify token
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = 'Invalid or expired reset link. Please request a new one.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Update password
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$password_hash, $user['id']]);
        
        $success = 'Password has been reset successfully! You can now login with your new password.';
        unset($user);
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Set New Password</h2>
                <p class="text-center mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Enter your new password</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">Go to Login</a>
                    </div>
                <?php elseif (isset($user)): ?>
                    <form method="POST" action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" novalidate>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required autofocus>
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                    </form>
                <?php endif; ?>

                <hr class="my-4">
                <p class="text-center mb-0">Remember your password? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
