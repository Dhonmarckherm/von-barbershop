<?php
// Prevent browser caching - always get fresh content
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start output buffering to prevent early header sends
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
$pageTitle = 'Login';
require_once 'config/db.php';
require_once 'config/session.php';
initializeSession();

// Check for logout parameter - clear session if logging out first
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    $_SESSION = array();
    if (session_id()) {
        session_destroy();
    }
    
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
        unset($_COOKIE[$cookieName]);
    }
    
    session_start();
}

// Redirect if already logged in (ONLY if NOT logging out and NOT just cleared session)
if (!isset($_GET['logout']) && isset($_SESSION['user_id']) && isset($_COOKIE['auth_user_id'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'barber') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: my_appointments.php');
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Always use secure cookies for production
            $isHttps = true; // Render always serves over HTTPS

            // Clear old auth cookies safely
            $clearParams = [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            foreach (['auth_user_id', 'auth_name', 'auth_email', 'auth_role'] as $cookieName) {
                setcookie($cookieName, '', $clearParams);
            }
            
            // Clear current session data without destroying the session entirely
            // This is more reliable for keeping cookie parameters intact
            $_SESSION = array();
            
            // Regenerate ID to prevent session fixation and start fresh
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = strtolower($user['role']); // Normalize to lowercase
            $_SESSION['login_time'] = time();
            
            // Set fresh auth cookies
            setAuthCookies($user['id'], $user['name'], $user['email'], strtolower($user['role']));

            // Redirect admin to dashboard, customers to my_appointments
            $role = strtolower($user['role']); // Normalize to lowercase
            if ($role === 'admin' || $role === 'barber') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: my_appointments.php');
            }
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

require_once 'includes/header.php';

// Show registration success message
if (isset($_GET['registered']) && $_GET['registered'] == '1'):
?>
<div class="alert alert-success alert-dismissible fade show" role="alert" style="background: rgba(40, 167, 69, 0.2); border: 1px solid #28a745; color: #f5f5f5; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
    <div class="d-flex align-items-center">
        <i class="bi bi-check-circle-fill" style="font-size: 24px; margin-right: 12px;"></i>
        <div>
            <strong style="font-size: 18px;">Registration Successful!</strong><br>
            <span style="font-size: 14px;">Your account has been created. Please log in with your email and password.</span>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(1);"></button>
</div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Welcome Back</h2>
                <p class="text-center mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Sign in to your account</p>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">Registration successful! Please login.</div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <strong><?php echo htmlspecialchars($error); ?></strong>
                        <div class="mt-2">
                            <a href="forgot_password.php" style="color: var(--barber-gold); text-decoration: none; font-size: 0.9rem;">
                                <i class="bi bi-key"></i> Forgot Password?
                            </a>
                            <span style="color: #6c757d;">|</span>
                            <a href="register.php" style="color: var(--barber-gold); text-decoration: none; font-size: 0.9rem;">
                                <i class="bi bi-person-plus"></i> Register New Account
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate autocomplete="off">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required autofocus autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>

                <div class="text-center mt-3">
                    <a href="forgot_password.php" style="color: var(--barber-gold); text-decoration: none; font-size: 0.9rem;">Forgot Password?</a>
                </div>

                <hr class="my-4">
                <p class="text-center mb-0">Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
