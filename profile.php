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

<style>
/* Theme Variables */
:root {
    --bg-primary: #1a1a1a;
    --bg-secondary: #2d2d2d;
    --bg-card: #242424;
    --text-primary: #ffffff;
    --text-secondary: #b0b0b0;
    --text-muted: #808080;
    --border-color: #404040;
    --input-bg: #1a1a1a;
    --input-border: #505050;
    --shadow: 0 10px 40px rgba(0,0,0,0.5);
}

[data-theme="light"] {
    --bg-primary: #f5f5f5;
    --bg-secondary: #ffffff;
    --bg-card: #ffffff;
    --text-primary: #1a1a1a;
    --text-secondary: #4a4a4a;
    --text-muted: #6a6a6a;
    --border-color: #e0e0e0;
    --input-bg: #ffffff;
    --input-border: #c0c0c0;
    --shadow: 0 10px 40px rgba(0,0,0,0.1);
}

/* Apply theme */
body, .profile-container {
    background: var(--bg-primary) !important;
    color: var(--text-primary) !important;
    transition: all 0.3s ease;
}

.card {
    background: var(--bg-card) !important;
    border-color: var(--border-color) !important;
    box-shadow: var(--shadow) !important;
    transition: all 0.3s ease;
}

.card-body h2 {
    color: var(--text-primary) !important;
}

.card-body p.text-center {
    color: var(--text-secondary) !important;
}

.form-label {
    color: var(--text-primary) !important;
    font-weight: 600;
}

.form-control {
    background: var(--input-bg) !important;
    border-color: var(--input-border) !important;
    color: var(--text-primary) !important;
    transition: all 0.3s ease;
}

.form-control:focus {
    background: var(--input-bg) !important;
    border-color: var(--barber-gold) !important;
    color: var(--text-primary) !important;
    box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.25) !important;
}

.form-control::placeholder {
    color: var(--text-muted) !important;
}

.alert-success {
    background: rgba(40, 167, 69, 0.15) !important;
    border-color: rgba(40, 167, 69, 0.4) !important;
    color: #28a745 !important;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.15) !important;
    border-color: rgba(220, 53, 69, 0.4) !important;
    color: #dc3545 !important;
}

.alert-danger ul li {
    color: #dc3545 !important;
}

/* Theme Toggle Button */
.theme-toggle-btn {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid var(--border-color);
    background: var(--bg-secondary);
    color: var(--text-primary);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.theme-toggle-btn:hover {
    transform: scale(1.1) rotate(180deg);
    box-shadow: 0 8px 30px rgba(0,0,0,0.4);
    border-color: var(--barber-gold);
}

.theme-toggle-btn:active {
    transform: scale(0.95);
}

.theme-icon {
    transition: all 0.3s ease;
}

/* Theme switch animation */
.theme-changing {
    animation: themePulse 0.5s ease;
}

@keyframes themePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}
</style>

<!-- Theme Toggle Button -->
<button class="theme-toggle-btn" id="themeToggle" title="Toggle Dark/Light Mode">
    <span class="theme-icon" id="themeIcon">☀️</span>
</button>

<script>
// Theme Management
(function() {
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    
    // Load saved theme or default to dark
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
    
    // Toggle theme on click
    themeToggle.addEventListener('click', function() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        // Add animation
        themeToggle.classList.add('theme-changing');
        
        // Update theme
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeIcon(newTheme);
        
        // Remove animation class
        setTimeout(() => {
            themeToggle.classList.remove('theme-changing');
        }, 500);
    });
    
    function updateThemeIcon(theme) {
        themeIcon.textContent = theme === 'dark' ? '☀️' : '🌙';
        themeToggle.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    }
})();
</script>

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
        
        // Show beautiful success modal
        setTimeout(function() {
            showBiometricSuccessModal();
            btn.innerHTML = '<i class="bi bi-fingerprint"></i> Biometric Enabled';
        }, 1000);
    } else {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-fingerprint"></i> Re-enable Biometric Login';
        alert('❌ Failed to enable biometric login:\n\n' + result.error + '\n\nPlease try again or contact support.');
    }
});
</script>

<!-- Biometric Success Modal -->
<div id="biometricSuccessModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 10000; backdrop-filter: blur(15px); align-items: center; justify-content: center;">
    <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border-radius: 28px; padding: 50px 40px; max-width: 450px; width: 90%; box-shadow: 0 25px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(40, 167, 69, 0.3); text-align: center; animation: successModalSlideIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);">
        <!-- Animated Checkmark Icon -->
        <div style="margin-bottom: 30px; position: relative;">
            <div style="width: 100px; height: 100px; margin: 0 auto; background: linear-gradient(135deg, rgba(40, 167, 69, 0.2) 0%, rgba(40, 167, 69, 0.05) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #28a745; animation: successPulse 2s ease-in-out infinite;">
                <i class="bi bi-check-circle-fill" style="font-size: 64px; color: #28a745; animation: checkmarkPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) 0.3s both;"></i>
            </div>
            <!-- Sparkle effects -->
            <div style="position: absolute; top: -10px; right: 20px; animation: sparkle 1.5s ease-in-out infinite;">
                <i class="bi bi-stars" style="font-size: 24px; color: #ffd700;"></i>
            </div>
            <div style="position: absolute; bottom: 0; left: 10px; animation: sparkle 1.5s ease-in-out 0.5s infinite;">
                <i class="bi bi-stars" style="font-size: 18px; color: #ffd700;"></i>
            </div>
        </div>
        
        <!-- Success Message -->
        <h2 style="color: #ffffff; margin: 0 0 15px 0; font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; animation: fadeInUp 0.6s ease 0.4s both;">
            Biometric Login Enabled!
        </h2>
        
        <p style="color: #b0b0b0; margin: 0 0 30px 0; font-size: 16px; font-family: 'Inter', sans-serif; line-height: 1.7; animation: fadeInUp 0.6s ease 0.5s both;">
            Next time you can login with <strong style="color: #28a745;">fingerprint</strong> or <strong style="color: #28a745;">face recognition</strong> for instant access.
        </p>
        
        <!-- Info Box -->
        <div style="background: rgba(40, 167, 69, 0.1); border-left: 4px solid #28a745; padding: 18px; border-radius: 10px; margin-bottom: 30px; text-align: left; animation: fadeInUp 0.6s ease 0.6s both;">
            <p style="color: #F5F0E8; margin: 0 0 10px 0; font-size: 14px; font-weight: 600;">
                <i class="bi bi-shield-check" style="color: #28a745;"></i> What's Next?
            </p>
            <ul style="color: #b0b0b0; margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.9;">
                <li>Logout and try logging in again</li>
                <li>Look for "Login with Biometrics" button</li>
                <li>Use your fingerprint or face to login instantly</li>
            </ul>
        </div>
        
        <!-- Close Button -->
        <button onclick="closeBiometricSuccessModal()" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; border: none; border-radius: 14px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; font-family: 'Inter', sans-serif; box-shadow: 0 6px 20px rgba(40,167,69,0.4); animation: fadeInUp 0.6s ease 0.7s both;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(40,167,69,0.5)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(40,167,69,0.4)'">
            <i class="bi bi-check-lg" style="margin-right: 8px;"></i> Got it, Thanks!
        </button>
        
        <p style="color: #666; font-size: 12px; margin: 15px 0 0 0; animation: fadeInUp 0.6s ease 0.8s both;">
            You can disable this anytime in settings
        </p>
    </div>
</div>

<style>
@keyframes successModalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.8) translateY(50px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes successPulse {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
    }
    50% {
        box-shadow: 0 0 0 20px rgba(40, 167, 69, 0);
    }
}

@keyframes checkmarkPop {
    from {
        opacity: 0;
        transform: scale(0) rotate(-180deg);
    }
    to {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

@keyframes sparkle {
    0%, 100% {
        opacity: 0;
        transform: scale(0) rotate(0deg);
    }
    50% {
        opacity: 1;
        transform: scale(1) rotate(180deg);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<script>
function showBiometricSuccessModal() {
    const modal = document.getElementById('biometricSuccessModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeBiometricSuccessModal() {
    const modal = document.getElementById('biometricSuccessModal');
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('biometricSuccessModal');
    if (e.target === modal) {
        closeBiometricSuccessModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
