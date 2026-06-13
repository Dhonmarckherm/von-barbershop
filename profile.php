<?php
/**
 * Customer Profile Page
 * Logged-in customers can edit their name and email address.
 */
$pageTitle = 'My Profile';
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$success = '';
$errors = [];

// Show success message after redirect
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $success = 'Profile updated successfully!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    // Check if email already belongs to another user
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email address is already in use by another account.';
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $_SESSION['user_id']]);
        
        // Update session
        $_SESSION['name'] = $name;
        $_SESSION['email'] = $email;
        
        // Update auth cookies to reflect new name immediately
        $isHttps = true; // Render always serves over HTTPS
        setcookie('auth_name', $name, time() + (86400 * 30), '/', '', $isHttps, true);
        setcookie('auth_email', $email, time() + (86400 * 30), '/', '', $isHttps, true);
        
        // Reload page to reflect changes in navbar immediately
        header('Location: profile.php?updated=1');
        exit;
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">My Profile</h2>
                <p class="text-center mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Update your personal information</p>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?php echo htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="profile.php">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo htmlspecialchars($user['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>

                <hr class="my-4">

                <!-- Biometric Login Section -->
                <div class="text-center">
                    <h5 style="color: var(--barber-gold); font-family: 'Playfair Display', serif; margin-bottom: 15px;">
                        <i class="bi bi-fingerprint"></i> Quick Login
                    </h5>
                    <p style="color: var(--barber-gray); font-size: 14px; margin-bottom: 15px;">
                        Enable fingerprint or face recognition for faster login
                    </p>
                    <button type="button" id="reenableBiometricBtn" class="btn btn-outline-warning" style="border-color: var(--barber-gold); color: var(--barber-gold);">
                        <i class="bi bi-fingerprint"></i> Re-enable Biometric Login
                    </button>
                    <p style="color: #888; font-size: 12px; margin-top: 10px;">
                        Use this if biometric login stopped working on this device
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Biometric Authentication Script -->
<script src="/www/js/biometric-auth.js"></script>
<script>
// Re-enable biometric login
document.getElementById('reenableBiometricBtn').addEventListener('click', async function() {
    const btn = this;
    
    // Check if WebAuthn is supported
    if (typeof BiometricAuth === 'undefined' || !BiometricAuth.isSupported()) {
        alert('Biometric login is not supported on this device/browser.\n\nTry using Safari on iPhone or Chrome on Android.');
        return;
    }
    
    // Check if biometric hardware is available
    const isAvailable = await BiometricAuth.isBiometricAvailable();
    if (!isAvailable) {
        alert('No biometric hardware detected on this device.\n\nMake sure Face ID or Touch ID is enabled in your iPhone settings.');
        return;
    }
    
    // Register new biometric credential
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Setting up...';
    
    const result = await BiometricAuth.register(
        '<?php echo htmlspecialchars($_SESSION["email"]); ?>',
        <?php echo (int)$_SESSION["user_id"]; ?>
    );
    
    if (result.success) {
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Enabled!';
        btn.style.background = '#28a745';
        btn.style.borderColor = '#28a745';
        btn.style.color = '#ffffff';
        
        setTimeout(function() {
            alert('✅ Biometric login enabled successfully!\n\nNext time you can login with fingerprint or face recognition.');
            btn.innerHTML = '<i class="bi bi-fingerprint"></i> Biometric Enabled';
        }, 1000);
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-fingerprint"></i> Re-enable Biometric Login';
        alert('❌ Failed to enable biometric login:\n\n' + result.error + '\n\nPlease try again or contact support.');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
