<?php
/**
 * Quick Check - What credentials exist RIGHT NOW
 */
require_once __DIR__ . '/config/db.php';

$stmt = $pdo->query("
    SELECT 
        up.id,
        up.user_id,
        u.email,
        up.credential_id,
        LENGTH(up.credential_id) as length,
        up.created_at,
        CASE 
            WHEN LENGTH(up.credential_id) < 50 THEN '❌ TOO SHORT (corrupted)'
            WHEN LENGTH(up.credential_id) >= 60 THEN '✅ GOOD LENGTH'
            ELSE '⚠️ MEDIUM'
        END as status
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.created_at DESC
");
$creds = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Quick Credential Check</title>
    <style>
        body { background: #0a0a0a; color: #fff; padding: 20px; font-family: monospace; }
        .cred { background: #1a1a1a; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .good { border-left: 4px solid #28a745; }
        .bad { border-left: 4px solid #dc3545; }
    </style>
</head>
<body>
    <h2>Current Credentials in Database</h2>
    
    <?php foreach ($creds as $c): ?>
        <div class="cred <?= $c['status'] === '✅ GOOD LENGTH' ? 'good' : 'bad' ?>">
            <strong><?= $c['status'] ?></strong><br>
            User: <?= $c['user_id'] ?> (<?= htmlspecialchars($c['email']) ?>)<br>
            Credential ID: <code><?= htmlspecialchars(substr($c['credential_id'], 0, 100)) ?><?= strlen($c['credential_id']) > 100 ? '...' : '' ?></code><br>
            Length: <?= $c['length'] ?> characters<br>
            Created: <?= $c['created_at'] ?>
        </div>
    <?php endforeach; ?>
    
    <hr>
    <p><strong>Expected:</strong> 60-100+ characters (✅ GOOD LENGTH)</p>
    <p><strong>Problem:</strong> 27 characters = corrupted credential (❌ TOO SHORT)</p>
    
    <a href="force_reset_biometric.php" style="color: #ff6b6b;">→ Force Reset & Delete All</a><br>
    <a href="full_biometric_diagnostic.php" style="color: #74c0fc;">→ Full Diagnostic</a>
</body>
</html>
