<?php
// Prevent browser caching - always get fresh content
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start output buffering to prevent headers already sent errors
ob_start();

$pageTitle = 'Register';
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/mailer.php';
initializeSession();

// ALWAYS clear session and cookies on register page to allow new registration
// This prevents logged-in users from being stuck seeing their old account
if (isset($_SESSION['user_id'])) {
    // Clear session
    $_SESSION = array();
    if (session_id()) {
        session_destroy();
    }
    
    // Clear auth cookies
    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
               || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    $clearParams = [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    foreach (['auth_user_id', 'auth_name', 'auth_email', 'auth_role'] as $cookieName) {
        setcookie($cookieName, '', $clearParams);
        // Also remove from $_COOKIE array so header.php cookie fallback won't find them
        unset($_COOKIE[$cookieName]);
    }
    
    // Start fresh session
    session_start();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($name) || strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered. Please login.';
        }
    }

    // Insert user
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'customer')");
        $stmt->execute([$name, $email, $hash]);
        
        // Get the new user ID
        $newUserId = $pdo->lastInsertId();
        
        // Send welcome email to new user
        try {
            error_log("========== WELCOME EMAIL START ==========");
            error_log("Welcome Email: Sending to $email for user $name (ID: $newUserId)");
            
            $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
            
            error_log("Welcome Email: Brevo key exists: " . ($brevoKey ? 'YES' : 'NO'));
            if ($brevoKey) {
                error_log("Welcome Email: Key starts with: " . substr($brevoKey, 0, 10) . "...");
            } else {
                error_log("Welcome Email: ERROR - No Brevo API key found!");
            }
            
            $subject = 'Welcome to V.O.N Barber Studio! ✂️';
            $htmlContent = "
                <div style='font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #f5f5f5;'>
                    <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 20px; text-align: center;'>
                        <h1 style='color: #c0c0c0; font-family: Playfair Display, serif; font-size: 32px; margin: 0;'>Welcome to V.O.N Barber Studio!</h1>
                        <p style='color: #f5f5f5; font-size: 18px; margin-top: 10px;'>Thank you for registering, " . htmlspecialchars($name) . "! ✂️</p>
                    </div>
                    <div style='padding: 30px 20px; background: #000000;'>
                        <p style='font-size: 16px; line-height: 1.6;'>We're excited to have you join our community! You can now:</p>
                        <ul style='font-size: 16px; line-height: 1.8;'>
                            <li>✅ Book appointments online</li>
                            <li>✅ Choose your preferred haircut style</li>
                            <li>✅ Select your location (shop or home service)</li>
                            <li>✅ Receive email confirmations</li>
                            <li>✅ Manage your bookings</li>
                        </ul>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='https://von-barbershop.onrender.com/login.php' style='background: #c0c0c0; color: #000000; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block;'>Login Now</a>
                        </div>
                        <p style='color: #8A8A9A; font-size: 14px; line-height: 1.6;'>If you have any questions, feel free to contact us. We look forward to serving you!</p>
                    </div>
                    <hr style='border-color: rgba(192,192,192,0.3); margin: 0 20px;'>
                    <div style='padding: 20px; text-align: center; background: #000000;'>
                        <p style='color: #8A8A9A; font-size: 0.85rem; margin: 0;'>V.O.N Barber Studio - Barber Studio</p>
                        <p style='color: #8A8A9A; font-size: 0.85rem; margin: 5px 0 0 0;'>Developed by Dhon Marck V. Hermosura, IT Specialist</p>
                    </div>
                </div>
            ";
            
            if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
                // Use Brevo HTTP API
                error_log("Welcome Email: Using Brevo HTTP API");
                $emailResult = sendBrevoEmail($email, $name, $subject, $htmlContent);
                error_log("Welcome Email: Brevo API result: " . ($emailResult ? 'SUCCESS ✅' : 'FAILED ❌'));
            } else {
                // Fallback to PHPMailer SMTP
                error_log("Welcome Email: Using PHPMailer SMTP fallback");
                $mail = getMailer();
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlContent;
                $mail->send();
                error_log("Welcome Email: SMTP email sent successfully ✅");
                $emailResult = true;
            }
            
            error_log("========== WELCOME EMAIL END ==========");
        } catch (Exception $e) {
            error_log("========== WELCOME EMAIL EXCEPTION ==========");
            error_log("Welcome email failed: " . $e->getMessage());
            error_log("Welcome email stack trace: " . $e->getTraceAsString());
            error_log("================================================");
        }
        
        // Registration successful - DO NOT auto-login
        // Redirect to login page with success message
        header('Location: login.php?registered=1');
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Your Best Look Starts Here</h2>
                <p class="text-center mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Create your account and book your first appointment</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?php echo htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate autocomplete="off">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required autocomplete="off"
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="off"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="6" autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </div>
                </form>

                <hr class="my-4">
                <p class="text-center mb-0">Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
