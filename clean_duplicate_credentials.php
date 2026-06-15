<?php
/**
 * Clean Duplicate Biometric Credentials
 * Keep only the LATEST credential per user
 */
require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');

// Get all credentials grouped by user
$stmt = $pdo->query("
    SELECT up.id, up.user_id, u.name, u.email, 
           up.credential_id,
           CHAR_LENGTH(up.credential_id) as char_len,
           up.created_at
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.user_id, up.created_at DESC
");
$allCreds = $stmt->fetchAll();

// Group by user_id
$grouped = [];
foreach ($allCreds as $cred) {
    $userId = $cred['user_id'];
    if (!isset($grouped[$userId])) {
        $grouped[$userId] = [];
    }
    $grouped[$userId][] = $cred;
}

// Find duplicates
$duplicates = [];
$toKeep = [];
foreach ($grouped as $userId => $creds) {
    if (count($creds) > 1) {
        // Keep the latest (first one, since we ordered DESC)
        $toKeep[] = $creds[0]['id'];
        for ($i = 1; $i < count($creds); $i++) {
            $duplicates[] = $creds[$i];
        }
    } else {
        $toKeep[] = $creds[0]['id'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clean_duplicates'])) {
    $deletedCount = 0;
    foreach ($duplicates as $dup) {
        $stmt = $pdo->prepare("DELETE FROM user_passkeys WHERE id = ?");
        $stmt->execute([$dup['id']]);
        $deletedCount++;
    }
    $success = "✅ Deleted $deletedCount duplicate credential(s)!";
    
    // Refresh page after 2 seconds
    echo "<script>setTimeout(() => location.reload(), 2000);</script>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clean Duplicate Credentials</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #0a0a0a; color: #fff; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #c0c0c0; margin-bottom: 20px; }
        .section { background: #1a1a1a; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .success { background: rgba(40,167,69,0.15); border: 2px solid #28a745; border-radius: 10px; padding: 15px; margin-bottom: 20px; color: #28a745; }
        .warning { background: rgba(255,193,7,0.1); border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
        .duplicate { border-left: 4px solid #dc3545; }
        .keep { border-left: 4px solid #28a745; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; }
        th, td { padding: 10px; border: 1px solid #444; text-align: left; }
        th { background: #2d2d2d; color: #c0c0c0; }
        .btn { display: inline-block; padding: 14px 28px; background: #dc3545; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; }
        .btn:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        code { background: #2d2d2d; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Clean Duplicate Biometric Credentials</h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>

        <div class="section">
            <p><strong>Total Credentials:</strong> <?= count($allCreds) ?></p>
            <p><strong>Users with Duplicates:</strong> <?= count(array_filter($grouped, fn($c) => count($c) > 1)) ?></p>
            <p><strong>Duplicates to Delete:</strong> <?= count($duplicates) ?></p>
        </div>

        <?php if (count($duplicates) > 0): ?>
            <div class="section warning">
                <h3 style="color: #ffc107; margin-bottom: 10px;">⚠️ Found Duplicates!</h3>
                <p>These credentials will be deleted (keeping only the latest for each user):</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Length</th>
                        <th>Created</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allCreds as $cred): 
                        $isDuplicate = in_array($cred['id'], array_column($duplicates, 'id'));
                        $isKeep = in_array($cred['id'], $toKeep);
                    ?>
                        <tr class="<?= $isDuplicate ? 'duplicate' : ($isKeep ? 'keep' : '') ?>">
                            <td><?= htmlspecialchars($cred['name']) ?></td>
                            <td><?= htmlspecialchars($cred['email']) ?></td>
                            <td><code><?= $cred['char_len'] ?> chars</code></td>
                            <td><?= date('M j, g:i A', strtotime($cred['created_at'])) ?></td>
                            <td>
                                <?php if ($isDuplicate): ?>
                                    <span style="color: #dc3545">❌ DELETE</span>
                                <?php elseif ($isKeep): ?>
                                    <span style="color: #28a745">✅ KEEP (Latest)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="POST" onsubmit="return confirm('Delete <?= count($duplicates) ?> duplicate credential(s)?');">
                <button type="submit" name="clean_duplicates" class="btn">
                    🗑️ Delete <?= count($duplicates) ?> Duplicates
                </button>
            </form>
        <?php else: ?>
            <div class="section" style="text-align: center; padding: 40px;">
                <p style="color: #28a745; font-size: 18px;">✅ No duplicates found! All users have only 1 credential.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="test_biometric_flow.php" style="color: #c0c0c0; margin-right: 20px;">← Back to Flow Test</a>
            <a href="login.php" style="color: #c0c0c0;">Go to Login →</a>
        </div>
    </div>
</body>
</html>
