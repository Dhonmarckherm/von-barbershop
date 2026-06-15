<?php
/**
 * Quick Biometric Debug - Shows what the login page sees
 */
require_once __DIR__ . '/config/db.php';

// Get all credentials
$stmt = $pdo->query("
    SELECT up.id, up.user_id, u.name, u.email, u.role,
           up.credential_id, 
           CHAR_LENGTH(up.credential_id) as char_len,
           up.created_at,
           up.last_used_at
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.created_at DESC
");
$credentials = $stmt->fetchAll();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Quick Debug</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0a0a0a; color: #fff; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #c0c0c0; margin-bottom: 20px; font-size: 24px; }
        .summary { background: #1a1a1a; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .summary p { margin: 8px 0; font-size: 16px; }
        .good { color: #28a745; }
        .bad { color: #dc3545; }
        .warn { color: #ffc107; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #333; text-align: left; font-size: 13px; }
        th { background: #1a1a1a; color: #c0c0c0; }
        .btn { background: #c0c0c0; color: #000; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; margin: 5px; }
        .btn:hover { background: #d0d0d0; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-danger:hover { background: #c82333; }
        .credential-preview { font-family: monospace; font-size: 11px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        code { background: #2d2d2d; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Biometric Login Debug</h1>
        
        <div class="summary">
            <p><strong>Total Registered Credentials:</strong> <code><?= count($credentials) ?></code></p>
            <p><strong>Issue:</strong> Login page sends ALL credentials to browser, causing confusion</p>
            <div style="margin-top: 15px;">
                <button class="btn" onclick="testLoginAPI()">Test Login API</button>
                <button class="btn btn-danger" onclick="resetAll()">Reset ALL Biometrics</button>
            </div>
        </div>

        <?php if (count($credentials) > 0): ?>
            <h2 style="color: #c0c0c0; margin-bottom: 15px;">Registered Credentials</h2>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Credential Length</th>
                        <th>Created</th>
                        <th>Credential Preview</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($credentials as $cred): ?>
                        <tr>
                            <td><?= htmlspecialchars($cred['name']) ?></td>
                            <td><?= htmlspecialchars($cred['email']) ?></td>
                            <td><?= htmlspecialchars($cred['role']) ?></td>
                            <td class="<?= $cred['char_len'] >= 20 ? 'good' : 'bad' ?>">
                                <?= $cred['char_len'] ?> chars 
                                <?= $cred['char_len'] >= 20 ? '✅' : '❌ TOO SHORT' ?>
                            </td>
                            <td><?= date('M j, Y g:i A', strtotime($cred['created_at'])) ?></td>
                            <td class="credential-preview"><?= htmlspecialchars(substr($cred['credential_id'], 0, 50)) ?>...</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="summary" style="text-align: center; padding: 40px;">
                <p class="warn">⚠️ No biometric credentials registered!</p>
                <p style="color: #999; margin-top: 10px;">Users need to enable biometrics after logging in.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        async function testLoginAPI() {
            const result = await fetch('/api/biometric_login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_challenge' })
            });
            
            const data = await result.json();
            console.log('Login API Response:', data);
            
            alert(`Login API Test:\n\nChallenge: ${data.challenge ? '✅ YES' : '❌ NO'}\nCredentials Count: ${data.allowCredentials?.length || 0}\nError: ${data.error || 'NONE'}`);
        }

        async function resetAll() {
            if (!confirm('⚠️ This will DELETE ALL biometric credentials for ALL users.\n\nAre you sure?')) {
                return;
            }
            
            const result = await fetch('/api/biometric_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_all' })
            });
            
            const data = await result.json();
            alert(data.message || data.error);
            location.reload();
        }
    </script>
</body>
</html>
