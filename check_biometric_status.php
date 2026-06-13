<?php
/**
 * Check Biometric Enrollment Status
 * Shows if user has enrolled biometric credentials
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
initializeSession();

if (!isset($_SESSION['user_id'])) {
    die("Please login first to check biometric status.");
}

$userId = $_SESSION['user_id'];
$email = $_SESSION['email'];
$name = $_SESSION['name'];

// Check for enrolled biometric credentials
$stmt = $pdo->prepare("
    SELECT 
        up.id,
        up.credential_id,
        up.created_at,
        up.last_used_at,
        u.name,
        u.email
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    WHERE up.user_id = ?
    ORDER BY up.created_at DESC
");
$stmt->execute([$userId]);
$credentials = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Status Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #0a0a0a;
            color: #ffffff;
            padding: 40px 20px;
        }
        .status-card {
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 15px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }
        .enrolled {
            border-color: #28a745;
            background: rgba(40, 167, 69, 0.1);
        }
        .not-enrolled {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.1);
        }
        .credential-item {
            background: #2d2d2d;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="status-card">
        <h2 class="text-center mb-4">
            <i class="bi bi-fingerprint"></i> Biometric Enrollment Status
        </h2>
        
        <div class="mb-4">
            <p><strong>User:</strong> <?php echo htmlspecialchars($name); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>User ID:</strong> <?php echo $userId; ?></p>
        </div>

        <?php if (count($credentials) > 0): ?>
            <div class="enrolled rounded p-3 mb-4">
                <h4 style="color: #28a745;">
                    <i class="bi bi-check-circle-fill"></i> BIOMETRIC ENROLLED ✓
                </h4>
                <p>You have <strong><?php echo count($credentials); ?></strong> biometric credential(s) registered on this device.</p>
            </div>

            <h5 class="mt-4">Registered Credentials:</h5>
            <?php foreach ($credentials as $cred): ?>
                <div class="credential-item">
                    <p class="mb-1"><strong>Credential ID:</strong> <?php echo substr($cred['credential_id'], 0, 40); ?>...</p>
                    <p class="mb-1"><strong>Created:</strong> <?php echo date('M d, Y h:i A', strtotime($cred['created_at'])); ?></p>
                    <p class="mb-0"><strong>Last Used:</strong> <?php echo $cred['last_used_at'] ? date('M d, Y h:i A', strtotime($cred['last_used_at'])) : 'Never used'; ?></p>
                </div>
            <?php endforeach; ?>

            <div class="mt-4 p-3" style="background: rgba(192, 192, 192, 0.1); border-radius: 10px;">
                <h6 style="color: #c0c0c0;"><i class="bi bi-info-circle"></i> What this means:</h6>
                <ul class="mb-0" style="color: #b0b0b0;">
                    <li>✅ Biometric login is ENABLED on this device</li>
                    <li>✅ You can login with fingerprint/face on the login page</li>
                    <li>✅ Credentials are stored securely in the database</li>
                    <li>✅ Credentials are permanent until manually removed</li>
                </ul>
            </div>

        <?php else: ?>
            <div class="not-enrolled rounded p-3 mb-4">
                <h4 style="color: #dc3545;">
                    <i class="bi bi-x-circle-fill"></i> BIOMETRIC NOT ENROLLED ✗
                </h4>
                <p>No biometric credentials found for this account on this device.</p>
            </div>

            <div class="mt-4 p-3" style="background: rgba(192, 192, 192, 0.1); border-radius: 10px;">
                <h6 style="color: #c0c0c0;"><i class="bi bi-exclamation-triangle"></i> Why this happens:</h6>
                <ul style="color: #b0b0b0;">
                    <li>❌ You never clicked "Enable Now" on the enrollment popup</li>
                    <li>❌ You're using a different device than where you enrolled</li>
                    <li>❌ You cleared browser data (removes WebAuthn credentials)</li>
                    <li>❌ You're using incognito/private mode</li>
                </ul>
            </div>

            <div class="mt-4 p-3" style="background: rgba(40, 167, 69, 0.1); border-radius: 10px;">
                <h6 style="color: #28a745;"><i class="bi bi-lightbulb"></i> How to enable:</h6>
                <ol style="color: #b0b0b0;">
                    <li>Login with email & password</li>
                    <li>Look for "Enable Quick Login?" popup</li>
                    <li>Click "Enable Now"</li>
                    <li>Complete fingerprint/face scan</li>
                    <li>Or go to Profile page → "Re-enable Biometric Login"</li>
                </ol>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="login.php" class="btn btn-secondary me-2">
                <i class="bi bi-box-arrow-in-right"></i> Go to Login
            </a>
            <a href="profile.php" class="btn btn-outline-secondary">
                <i class="bi bi-person"></i> Profile Settings
            </a>
        </div>
    </div>
</body>
</html>
