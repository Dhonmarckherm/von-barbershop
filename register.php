<?php
$pageTitle = 'Register';
require_once 'config/db.php';
require_once 'config/session.php';
require_once 'config/mailer.php';
initializeSession();

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
        
        // Send welcome email to new user
        try {
            $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
            
            error_log("Welcome Email: Starting for $email");
            error_log("Welcome Email: Brevo key exists: " . ($brevoKey ? 'YES' : 'NO'));
            if ($brevoKey) {
                error_log("Welcome Email: Key starts with: " . substr($brevoKey, 0, 10) . "...");
            }
            
            $subject = 'Welcome to V.O.N Barbershop! ✂️';
            $htmlContent = "
                <div style='font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; background: #1a1a2e; color: #F5F0E8;'>
                    <div style='background: linear-gradient(135deg, #1a1a2e 0%, #2d2d44 100%); padding: 40px 20px; text-align: center;'>
                        <h1 style='color: #C5A059; font-family: Playfair Display, serif; font-size: 32px; margin: 0;'>Welcome to V.O.N Barbershop!</h1>
                        <p style='color: #F5F0E8; font-size: 18px; margin-top: 10px;'>Thank you for registering, " . htmlspecialchars($name) . "! ✂️</p>
                    </div>
                    <div style='padding: 30px 20px; background: #1a1a2e;'>
                        <p style='font-size: 16px; line-height: 1.6;'>We're excited to have you join our community! You can now:</p>
                        <ul style='font-size: 16px; line-height: 1.8;'>
                            <li>✅ Book appointments online</li>
                            <li>✅ Choose your preferred haircut style</li>
                            <li>✅ Select your location (shop or home service)</li>
                            <li>✅ Receive email confirmations</li>
                            <li>✅ Manage your bookings</li>
                        </ul>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='https://von-barbershop.onrender.com/login.php' style='background: #C5A059; color: #1a1a2e; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block;'>Login Now</a>
                        </div>
                        <p style='color: #8A8A9A; font-size: 14px; line-height: 1.6;'>If you have any questions, feel free to contact us. We look forward to serving you!</p>
                    </div>
                    <hr style='border-color: rgba(197,160,89,0.3); margin: 0 20px;'>
                    <div style='padding: 20px; text-align: center; background: #1a1a2e;'>
                        <p style='color: #8A8A9A; font-size: 0.85rem; margin: 0;'>V.O.N Barbershop - Premium Grooming Experience</p>
                        <p style='color: #8A8A9A; font-size: 0.85rem; margin: 5px 0 0 0;'>Developed by Dhon Marck V. Hermosura, IT Specialist</p>
                    </div>
                </div>
            ";
            
            if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
                // Use Brevo HTTP API
                error_log("Welcome Email: Using Brevo HTTP API");
                $emailResult = sendBrevoEmail($email, $name, $subject, $htmlContent);
                error_log("Welcome Email: Brevo API result: " . ($emailResult ? 'SUCCESS' : 'FAILED'));
            } else {
                // Fallback to PHPMailer SMTP
                error_log("Welcome Email: Using PHPMailer SMTP fallback");
                $mail = getMailer();
                $mail->addAddress($email, $name);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlContent;
                $mail->send();
                error_log("Welcome Email: SMTP email sent successfully");
            }
        } catch (Exception $e) {
            error_log("Welcome email failed: " . $e->getMessage());
            error_log("Welcome email stack trace: " . $e->getTraceAsString());
        }
        
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
                <h2 class="text-center mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Join the Club</h2>
                <p class="text-center mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Create your account</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?php echo htmlspecialchars($e); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" novalidate>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Minimum 6 characters.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
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
