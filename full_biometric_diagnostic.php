<?php
/**
 * Complete Biometric Diagnostic
 * Shows exactly what's stored vs what's expected
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';

initializeSession();

$userId = $_SESSION['user_id'] ?? 0;
$email = $_SESSION['email'] ?? 'Not logged in';

// Get ALL credentials
$stmt = $pdo->query("
    SELECT 
        up.id,
        up.credential_id,
        up.user_id,
        up.transports,
        up.created_at,
        u.email,
        u.name,
        CHAR_LENGTH(up.credential_id) as char_length,
        OCTET_LENGTH(up.credential_id) as byte_length,
        HEX(up.credential_id) as hex_value
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.created_at DESC
");
$allCreds = $stmt->fetchAll();

// Get YOUR credentials
$myCreds = array_filter($allCreds, fn($c) => $c['user_id'] == $userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Diagnostic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0a0a0a; color: #fff; padding: 20px; }
        .card { background: #1a1a1a; border: 1px solid #404040; border-radius: 10px; padding: 20px; margin: 20px auto; max-width: 1000px; }
        code { background: #2d2d2d; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        .cred-id { word-break: break-all; font-family: monospace; font-size: 10px; background: #2d2d2d; padding: 10px; border-radius: 5px; display: block; }
    </style>
</head>
<body>
    <div class="card">
        <h2>🔍 Complete Biometric Diagnostic</h2>
        
        <div class="alert alert-info">
            <strong>Your User ID:</strong> <?= $userId ?><br>
            <strong>Your Email:</strong> <?= htmlspecialchars($email) ?><br>
            <strong>Total Credentials in DB:</strong> <?= count($allCreds) ?><br>
            <strong>Your Credentials:</strong> <?= count($myCreds) ?>
        </div>
        
        <?php if (count($myCreds) > 0): ?>
            <div class="alert alert-success">✅ You HAVE biometric credentials enrolled!</div>
            
            <h4>Your Credential Details:</h4>
            <?php foreach ($myCreds as $i => $cred): ?>
                <div class="mb-4 p-3" style="background: #2d2d2d; border-radius: 8px;">
                    <p><strong>Credential #<?= $i + 1 ?></strong></p>
                    <table class="table table-dark table-sm">
                        <tr><td width="200">Credential ID</td><td><span class="cred-id"><?= htmlspecialchars($cred['credential_id']) ?></span></td></tr>
                        <tr><td>Character Length</td><td><?= $cred['char_length'] ?></td></tr>
                        <tr><td>Byte Length</td><td><?= $cred['byte_length'] ?></td></tr>
                        <tr><td>Hex Preview</td><td><code><?= substr($cred['hex_value'], 0, 100) ?>...</code></td></tr>
                        <tr><td>Transports</td><td><?= $cred['transports'] ?></td></tr>
                        <tr><td>Created</td><td><?= $cred['created_at'] ?></td></tr>
                    </table>
                </div>
            <?php endforeach; ?>
            
            <div class="alert alert-warning mt-4">
                <strong>⚠️ If biometric login still fails:</strong><br>
                The credential ID stored in the database might not match what your device sends.<br><br>
                <strong>Next steps:</strong>
                <ol>
                    <li>Open browser console (F12)</li>
                    <li>Go to login page</li>
                    <li>Click "Login with Biometrics"</li>
                    <li>Check console for "[Biometric Login]" messages</li>
                    <li>Look for the credential ID being sent</li>
                    <li>Compare with the ID shown above</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">❌ You have NO biometric credentials!</div>
            <p>You need to enroll first:</p>
            <ol>
                <li>Logout</li>
                <li>Login with email/password</li>
                <li>Wait for "Enable Quick Login" popup</li>
                <li>Click "Enable Now"</li>
                <li>Complete Face ID/Touch ID</li>
            </ol>
        <?php endif; ?>
        
        <h4 class="mt-4">All Credentials in System:</h4>
        <table class="table table-dark">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Credential ID (first 50 chars)</th>
                    <th>Length</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allCreds as $cred): ?>
                    <tr>
                        <td><?= $cred['id'] ?></td>
                        <td><?= $cred['user_id'] ?></td>
                        <td><?= htmlspecialchars($cred['email']) ?></td>
                        <td><code><?= htmlspecialchars(substr($cred['credential_id'], 0, 50)) ?>...</code></td>
                        <td><?= $cred['char_length'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="mt-4">
            <a href="force_reset_biometric.php" class="btn btn-danger me-2">
                <i class="bi bi-trash"></i> Force Reset
            </a>
            <a href="login.php" class="btn btn-primary me-2">
                <i class="bi bi-box-arrow-in-right"></i> Test Login
            </a>
            <a href="my_appointments.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</body>
</html>
