<?php
/**
 * Debug Biometric Credentials
 * Shows all credentials in database and tests login flow
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';

initializeSession();

if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$userId = $_SESSION['user_id'];
$email = $_SESSION['email'];

// Get all credentials for this user
$stmt = $pdo->prepare("
    SELECT 
        up.id,
        up.credential_id,
        up.transports,
        up.created_at,
        up.last_used_at,
        u.name,
        u.email,
        u.role
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    WHERE up.user_id = ?
    ORDER BY up.created_at DESC
");
$stmt->execute([$userId]);
$myCredentials = $stmt->fetchAll();

// Get ALL credentials (for debugging)
$allCredentials = $pdo->query("
    SELECT 
        up.id,
        up.credential_id,
        up.user_id,
        u.email,
        LENGTH(up.credential_id) as id_length
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #0a0a0a;
            color: #ffffff;
            padding: 20px;
        }
        .debug-card {
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 15px;
            padding: 25px;
            margin: 20px auto;
            max-width: 900px;
        }
        .credential-id {
            font-family: monospace;
            font-size: 11px;
            word-break: break-all;
            background: #2d2d2d;
            padding: 8px;
            border-radius: 5px;
            display: block;
            margin-top: 5px;
        }
        .status-yes {
            color: #28a745;
            font-weight: bold;
        }
        .status-no {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="debug-card">
        <h2 class="text-center mb-4">
            <i class="bi bi-bug"></i> Biometric Credentials Debug
        </h2>
        
        <div class="mb-4">
            <p><strong>Your Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Your User ID:</strong> <?php echo $userId; ?></p>
            <p><strong>Your Credentials:</strong> 
                <?php if (count($myCredentials) > 0): ?>
                    <span class="status-yes"><?php echo count($myCredentials); ?> enrolled ✓</span>
                <?php else: ?>
                    <span class="status-no">None ✗</span>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (count($myCredentials) > 0): ?>
            <h5>Your Biometric Credentials</h5>
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Credential ID</th>
                        <th>Created</th>
                        <th>Last Used</th>
                        <th>Transports</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myCredentials as $cred): ?>
                    <tr>
                        <td><?php echo $cred['id']; ?></td>
                        <td>
                            <span class="credential-id"><?php echo htmlspecialchars($cred['credential_id']); ?></span>
                            <small class="text-muted">Length: <?php echo strlen($cred['credential_id']); ?> chars</small>
                        </td>
                        <td><?php echo $cred['created_at']; ?></td>
                        <td><?php echo $cred['last_used_at'] ?? 'Never'; ?></td>
                        <td><?php echo $cred['transports']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="alert alert-success mt-4">
                <i class="bi bi-check-circle-fill"></i> 
                <strong>Good!</strong> You have biometric credentials enrolled.
                <br><br>
                If "Login with Biometrics" doesn't work on login page, check:
                <ul class="mb-0 mt-2">
                    <li>Browser console for errors</li>
                    <li>Render logs for "Biometric Login" messages</li>
                    <li>Credential ID matches between device and database</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i> 
                <strong>No credentials found!</strong>
                <br><br>
                You need to enroll biometric first:
                <ol class="mb-0 mt-2">
                    <li>Logout</li>
                    <li>Login with email/password</li>
                    <li>Wait for "Enable Quick Login" popup</li>
                    <li>Click "Enable Now"</li>
                    <li>Complete Face ID/Touch ID</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <h5 class="mt-4">All Credentials in Database (Debug)</h5>
        <p class="text-muted small">Total: <?php echo count($allCredentials); ?> credentials</p>
        <?php if (count($allCredentials) > 0): ?>
            <table class="table table-dark table-sm">
                <thead>
                    <tr>
                        <th>Cred ID</th>
                        <th>User ID</th>
                        <th>Email</th>
                        <th>Length</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allCredentials as $cred): ?>
                    <tr>
                        <td><code class="small"><?php echo substr($cred['credential_id'], 0, 50); ?>...</code></td>
                        <td><?php echo $cred['user_id']; ?></td>
                        <td><?php echo htmlspecialchars($cred['email']); ?></td>
                        <td><?php echo $cred['id_length']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="mt-4 text-center">
            <a href="login.php" class="btn btn-primary me-2">
                <i class="bi bi-box-arrow-in-right"></i> Test Login
            </a>
            <a href="reset_biometric.php" class="btn btn-warning me-2">
                <i class="bi bi-arrow-repeat"></i> Reset Credentials
            </a>
            <a href="my_appointments.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</body>
</html>
