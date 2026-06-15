<?php
/**
 * Customer Profile Page
 * Logged-in customers can edit their name and email address.
 * Password change requires email verification token for enhanced security.
 */
$pageTitle = 'My Profile';
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once 'config/mailer.php';

$success = '';
$errors = [];

// Show success message after redirect
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $success = 'Profile updated successfully!';
}

if (isset($_GET['password_changed']) && $_GET['password_changed'] == '1') {
    $success = 'Password changed successfully!';
}

// Handle password change verification token request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_password_token'])) {
    // Generate verification token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+15 minutes')); // Token expires in 15 minutes
    
    // Store token in database
    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?");
    $stmt->execute([$token, $expires, $_SESSION['user_id']]);
    
    // Send verification email
    $verificationLink = "https://von-barbershop.onrender.com/profile.php?verify_token=$token";
    $subject = "Password Change Verification - V.O.N Barber Studio";
    $htmlBody = "
        <div style='font-family: Inter, Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #F5F0E8; border-radius: 12px; overflow: hidden;'>
            <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 30px; text-align: center; border-bottom: 3px solid #c0c0c0;'>
                <div style='font-size: 48px; margin-bottom: 10px;'>🔐</div>
                <h1 style='color: #c0c0c0; font-family: Georgia, serif; font-size: 28px; margin: 0 0 10px 0; font-weight: bold;'>Verify Password Change</h1>
                <p style='color: #F5F0E8; font-size: 16px; margin: 0;'>Security verification required</p>
            </div>
            
            <div style='padding: 30px;'>
                <p style='font-size: 18px; margin-bottom: 25px;'>Hello <strong style='color: #C5A059;'>" . htmlspecialchars($_SESSION['name']) . "</strong>,</p>
                
                <p style='font-size: 16px; line-height: 1.6; margin-bottom: 25px;'>We received a request to change your password. For security, please verify this request by clicking the button below:</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verificationLink' style='display: inline-block; background: #c0c0c0; color: #000000; padding: 15px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 18px;'>
                        ✅ Verify Password Change
                    </a>
                </div>
                
                <p style='font-size: 14px; line-height: 1.6; color: #B8B8CC; background: rgba(220,53,69,0.1); padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>
                    <strong>⚠️ Security Notice:</strong> This link will expire in <strong>15 minutes</strong>. If you did not request this change, please ignore this email or contact support immediately.
                </p>
            </div>
            
            <div style='background: rgba(192, 192, 192, 0.05); padding: 25px 30px; text-align: center; border-top: 1px solid rgba(192, 192, 192, 0.3);'>
                <p style='color: #c0c0c0; font-size: 16px; font-weight: bold; margin: 0 0 8px 0;'>V.O.N Barber Studio - Security Team</p>
            </div>
        </div>
    ";
    
    try {
        $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null);
        
        if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
            $emailSent = sendBrevoEmail($_SESSION['email'], $_SESSION['name'], $subject, $htmlBody);
        } else {
            $mail = getMailer();
            $mail->addAddress($_SESSION['email'], $_SESSION['name']);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->send();
            $emailSent = true;
        }
        
        if ($emailSent) {
            $success = "Verification email sent to " . htmlspecialchars($_SESSION['email']) . ". Please check your inbox and click the verification link to change your password.";
        } else {
            $errors[] = "Failed to send verification email. Please try again.";
        }
    } catch (Exception $e) {
        error_log("Profile password token error: " . $e->getMessage());
        $errors[] = "Failed to send verification email. Please try again.";
    }
}

// Handle password change after token verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password']) && (isset($_GET['verified']) || isset($_SESSION['verified_for_password_change']))) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (strlen($new_password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    } else {
        // Update password
        $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
        $stmt->execute([$passwordHash, $_SESSION['user_id']]);
        
        // Clear verification session
        unset($_SESSION['verified_for_password_change']);
        
        header('Location: profile.php?password_changed=1');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['request_password_token']) && !isset($_POST['change_password'])) {
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
        // Update name and email
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

// Check if user clicked verification link from email
$verified = false;
if (isset($_GET['verify_token'])) {
    $token = $_GET['verify_token'];
    
    // Verify token
    $stmt = $pdo->prepare("SELECT id, reset_token_expires FROM users WHERE id = ? AND reset_token = ?");
    $stmt->execute([$_SESSION['user_id'], $token]);
    $tokenData = $stmt->fetch();
    
    if (!$tokenData) {
        $errors[] = 'Invalid verification token.';
    } elseif (strtotime($tokenData['reset_token_expires']) < time()) {
        $errors[] = 'Verification token has expired. Please request a new one.';
    } else {
        $verified = true;
        // Token is valid, set session flag for password change
        $_SESSION['verified_for_password_change'] = true;
    }
}

require_once 'includes/header.php';
?>

<style>
/* Profile Page Styles - Dark Theme Only */
body, .profile-container {
    background: #000000 !important;
    color: #ffffff !important;
}

.card {
    background: #2d2d2d !important;
    border-color: #404040 !important;
    box-shadow: 0 10px 40px rgba(0,0,0,0.5) !important;
}

.card-body h2 {
    color: #ffffff !important;
}

.card-body p.text-center {
    color: #b0b0b0 !important;
}

.form-label {
    color: #ffffff !important;
    font-weight: 600;
}

.form-control {
    background: #1a1a1a !important;
    border-color: #505050 !important;
    color: #ffffff !important;
}

.form-control:focus {
    background: #1a1a1a !important;
    border-color: #c0c0c0 !important;
    color: #ffffff !important;
    box-shadow: 0 0 0 0.25rem rgba(192, 192, 192, 0.25) !important;
}

.form-control::placeholder {
    color: #808080 !important;
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
</style>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4" style="color: var(--text-primary); font-family: 'Playfair Display', serif;">My Profile</h2>
                <p class="text-center mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Update your personal information</p>

                <?php if ($success && !isset($_GET['password_changed'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['password_changed']) && $_GET['password_changed'] == '1'): ?>
                    <!-- DEBUG: Password changed modal should appear -->
                    <!-- Password Changed Success Modal -->
                    <div id="passwordSuccessModal" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 9999; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease;">
                        <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border-radius: 24px; padding: 40px; max-width: 420px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 2px solid rgba(40,167,69,0.3); animation: slideUp 0.5s ease;">
                            <!-- Success Icon with Animation -->
                            <div style="text-align: center; margin-bottom: 30px;">
                                <div style="width: 100px; height: 100px; margin: 0 auto; background: linear-gradient(135deg, rgba(40,167,69,0.2) 0%, rgba(40,167,69,0.1) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #28a745; animation: scaleIn 0.6s ease;">
                                    <i class="bi bi-check-circle" style="font-size: 56px; color: #28a745; animation: checkPop 0.8s ease 0.3s both;"></i>
                                </div>
                            </div>
                            
                            <!-- Success Message -->
                            <div style="text-align: center; margin-bottom: 30px;">
                                <h2 style="color: #ffffff; margin: 0 0 12px 0; font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px;">Password Changed!</h2>
                                <p style="color: #b0b0b0; margin: 0; font-size: 15px; line-height: 1.6;">Your password has been successfully updated. You can now login with your new password.</p>
                            </div>
                            
                            <!-- Info Box -->
                            <div style="background: rgba(40,167,69,0.1); border-left: 3px solid #28a745; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                                <p style="color: #F5F0E8; margin: 0; font-size: 13px; line-height: 1.6;">
                                    <i class="bi bi-shield-check" style="color: #28a745; margin-right: 8px;"></i>
                                    <strong>Security Tip:</strong> Keep your password safe and never share it with anyone.
                                </p>
                            </div>
                            
                            <!-- Done Button -->
                            <button onclick="closePasswordModal()" style="width: 100%; padding: 16px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; font-family: 'Inter', sans-serif; box-shadow: 0 4px 15px rgba(40,167,69,0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(40,167,69,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(40,167,69,0.3)'">
                                <i class="bi bi-check-lg" style="margin-right: 8px;"></i> Done
                            </button>
                        </div>
                    </div>
                    
                    <style>
                        @keyframes fadeIn {
                            from { opacity: 0; }
                            to { opacity: 1; }
                        }
                        @keyframes slideUp {
                            from { transform: translateY(50px); opacity: 0; }
                            to { transform: translateY(0); opacity: 1; }
                        }
                        @keyframes scaleIn {
                            from { transform: scale(0); }
                            to { transform: scale(1); }
                        }
                        @keyframes checkPop {
                            0% { transform: scale(0); opacity: 0; }
                            50% { transform: scale(1.2); }
                            100% { transform: scale(1); opacity: 1; }
                        }
                    </style>
                    
                    <script>
                        function closePasswordModal() {
                            const modal = document.getElementById('passwordSuccessModal');
                            modal.style.animation = 'fadeOut 0.3s ease';
                            setTimeout(() => {
                                window.location.href = 'profile.php';
                            }, 300);
                        }
                        
                        // Add fadeOut animation
                        const style = document.createElement('style');
                        style.textContent = '@keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }';
                        document.head.appendChild(style);
                    </script>
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
                    
                    <hr class="my-4">
                    
                    <!-- Password Change Section -->
                    <h5 style="color: var(--text-primary); font-family: 'Playfair Display', serif; margin-bottom: 20px;">
                        <i class="bi bi-key"></i> Change Password
                    </h5>
                    
                    <?php if ($verified): ?>
                        <!-- Show password change form after email verification -->
                        <div class="alert alert-success" style="background: rgba(40,167,69,0.15); border: 2px solid #28a745; color: #28a745; border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                            <i class="bi bi-check-circle-fill"></i> Email verified! You can now change your password.
                        </div>
                        
                        <form method="POST" action="profile.php?verified=1">
                            <p style="color: var(--barber-gray); font-size: 14px; margin-bottom: 15px;">
                                Enter your new password below:
                            </p>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required
                                       placeholder="Enter new password (min 6 characters)">
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required
                                       placeholder="Re-enter new password">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                <a href="profile.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Show request verification button -->
                        <p style="color: var(--barber-gray); font-size: 14px; margin-bottom: 15px;">
                            <i class="bi bi-shield-lock"></i> For security, we'll send a verification link to your email to change your password.
                        </p>
                        
                        <form method="POST" id="requestTokenForm">
                            <div class="alert alert-info" style="background: rgba(0,123,255,0.1); border-left: 4px solid #007bff; color: #F5F0E8; padding: 12px; border-radius: 8px; margin-bottom: 15px;">
                                <i class="bi bi-info-circle"></i> A verification link will be sent to <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>. The link expires in 15 minutes.
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="request_password_token" class="btn btn-outline-primary" id="requestTokenBtn">
                                    <i class="bi bi-envelope"></i> Send Verification Email
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <hr class="my-4">
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>

                <hr class="my-4">

                <!-- Biometric Login Section -->
                <div class="text-center">
                    <h5 style="color: var(--text-primary); font-family: 'Playfair Display', serif; margin-bottom: 15px;">
                        <i class="bi bi-fingerprint"></i> Quick Login
                    </h5>
                    <p style="color: var(--barber-gray); font-size: 14px; margin-bottom: 15px;">
                        Enable fingerprint or face recognition for faster login
                    </p>
                    <button type="button" id="reenableBiometricBtn" class="btn btn-outline-secondary" style="border-color: var(--accent-color); color: var(--text-primary);">
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
