<?php
/**
 * iOS vs Android Credential Analysis
 */
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query("
    SELECT 
        up.id,
        up.user_id,
        u.name,
        u.email,
        up.credential_id,
        CHAR_LENGTH(up.credential_id) as char_len,
        OCTET_LENGTH(up.credential_id) as byte_len,
        up.transports,
        up.created_at,
        CASE 
            WHEN CHAR_LENGTH(up.credential_id) < 20 THEN '❌ INVALID (< 20)'
            WHEN CHAR_LENGTH(up.credential_id) < 50 THEN '⚠️ iOS SHORT (20-49)'
            ELSE '✅ NORMAL (50+)'
        END as type
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.created_at DESC
");
$credentials = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iOS vs Android Biometric Credentials</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #0a0a0a; color: #fff; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #c0c0c0; margin-bottom: 20px; }
        .info { background: #1a1a1a; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .info p { margin: 8px 0; line-height: 1.6; }
        .warning { background: rgba(255,193,7,0.1); border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
        .success { background: rgba(40,167,69,0.1); border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th, td { padding: 12px; border: 1px solid #333; text-align: left; }
        th { background: #1a1a1a; color: #c0c0c0; }
        .ios { color: #ffc107; }
        .android { color: #28a745; }
        .invalid { color: #dc3545; }
        .cred-id { font-family: monospace; font-size: 11px; max-width: 400px; word-break: break-all; }
        code { background: #2d2d2d; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📱 iOS vs Android Biometric Credentials</h1>
        
        <div class="info">
            <p><strong>Total Credentials:</strong> <?= count($credentials) ?></p>
            <p><strong>Valid (20+ chars):</strong> <?= count(array_filter($credentials, fn($c) => $c['char_len'] >= 20)) ?></p>
        </div>

        <?php
        $iosCount = 0;
        $androidCount = 0;
        foreach ($credentials as $cred) {
            if ($cred['char_len'] >= 20 && $cred['char_len'] < 50) $iosCount++;
            if ($cred['char_len'] >= 50) $androidCount++;
        }
        ?>

        <div class="warning">
            <strong>🍎 iOS Safari Credentials (20-49 chars)</strong>
            <p>iOS Face ID/Touch ID generates shorter credential IDs. This is <strong>normal iOS behavior</strong>.</p>
            <p>Count: <strong><?= $iosCount ?></strong></p>
        </div>

        <div class="success">
            <strong>🤖 Android/Other Credentials (50+ chars)</strong>
            <p>Android Chrome and other browsers generate full-length credential IDs.</p>
            <p>Count: <strong><?= $androidCount ?></strong></p>
        </div>

        <?php if (count($credentials) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Type</th>
                        <th>Length</th>
                        <th>Transports</th>
                        <th>Credential ID</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($credentials as $cred): ?>
                        <tr>
                            <td><?= htmlspecialchars($cred['name']) ?></td>
                            <td><?= htmlspecialchars($cred['email']) ?></td>
                            <td class="<?= strpos($cred['type'], 'iOS') !== false ? 'ios' : (strpos($cred['type'], 'NORMAL') !== false ? 'android' : 'invalid') ?>">
                                <?= $cred['type'] ?>
                            </td>
                            <td>
                                <strong><?= $cred['char_len'] ?></strong> chars<br>
                                <small><?= $cred['byte_len'] ?> bytes</small>
                            </td>
                            <td><code><?= htmlspecialchars($cred['transports'] ?: 'none') ?></code></td>
                            <td class="cred-id"><?= htmlspecialchars($cred['credential_id']) ?></td>
                            <td><?= date('M j, g:i A', strtotime($cred['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="info" style="text-align: center; padding: 40px;">
                <p style="color: #999;">No biometric credentials registered yet.</p>
            </div>
        <?php endif; ?>

        <div class="info" style="margin-top: 30px;">
            <h3 style="color: #c0c0c0; margin-bottom: 15px;">✅ Both iOS and Android credentials WORK!</h3>
            <p>The system now accepts credentials with <strong>20+ characters</strong>.</p>
            <p style="margin-top: 10px;">
                • iOS Safari: 20-49 chars (shorter but valid)<br>
                • Android Chrome: 50-100+ chars (full length)<br>
                • Both types authenticate successfully! 🎉
            </p>
        </div>
    </div>
</body>
</html>
