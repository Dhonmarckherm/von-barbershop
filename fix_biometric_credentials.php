<?php
/**
 * Fix ALL Biometric Credentials to Proper Base64URL Format
 * Converts + to -, / to _, and removes = padding
 */
require_once __DIR__ . '/config/db.php';

header('Content-Type: text/html; charset=utf-8');

// Get all credentials
$stmt = $pdo->query("
    SELECT id, credential_id, user_id 
    FROM user_passkeys
    ORDER BY id DESC
");
$credentials = $stmt->fetchAll();

$fixed = 0;
$alreadyGood = 0;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_credentials'])) {
    foreach ($credentials as $cred) {
        $originalId = $cred['credential_id'];
        
        // Normalize to proper base64url
        $normalized = $originalId;
        $normalized = rtrim($normalized, '='); // Remove padding
        $normalized = str_replace('+', '-', $normalized); // Convert + to -
        $normalized = str_replace('/', '_', $normalized); // Convert / to _
        
        // Only update if different
        if ($normalized !== $originalId) {
            $updateStmt = $pdo->prepare("UPDATE user_passkeys SET credential_id = ? WHERE id = ?");
            $updateStmt->execute([$normalized, $cred['id']]);
            $fixed++;
            
            echo "<div style='background: rgba(40,167,69,0.1); border-left: 4px solid #28a745; padding: 10px; margin: 5px 0;'>";
            echo "✅ <strong>Fixed:</strong> User {$cred['user_id']}<br>";
            echo "Old: <code>$originalId</code><br>";
            echo "New: <code>$normalized</code>";
            echo "</div>";
        } else {
            $alreadyGood++;
        }
    }
    
    echo "<div style='background: rgba(40,167,69,0.15); border: 2px solid #28a745; border-radius: 10px; padding: 20px; margin: 20px 0; text-align: center;'>";
    echo "<h2 style='color: #28a745; margin: 0;'>✅ All Credentials Fixed!</h2>";
    echo "<p style='margin: 10px 0;'>Fixed: <strong>$fixed</strong> | Already good: <strong>$alreadyGood</strong></p>";
    echo "<p style='margin: 10px 0;'>All users can now login with biometrics!</p>";
    echo "</div>";
    
    echo "<script>setTimeout(() => location.reload(), 3000);</script>";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Biometric Credentials</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #0a0a0a; color: #fff; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #c0c0c0; margin-bottom: 20px; }
        .section { background: #1a1a1a; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .warning { background: rgba(255,193,7,0.1); border-left: 4px solid #ffc107; padding: 15px; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; }
        th, td { padding: 10px; border: 1px solid #444; text-align: left; }
        th { background: #2d2d2d; color: #c0c0c0; }
        .btn { display: inline-block; padding: 14px 28px; background: #28a745; color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 15px; }
        .btn:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        code { background: #2d2d2d; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .bad { color: #ffc107; }
        .good { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Biometric Credentials to Base64URL Format</h1>
        
        <div class="section">
            <p><strong>Total Credentials:</strong> <?= count($credentials) ?></p>
            
            <?php
            $needsFix = 0;
            foreach ($credentials as $cred) {
                if (strpos($cred['credential_id'], '+') !== false || strpos($cred['credential_id'], '=') !== false) {
                    $needsFix++;
                }
            }
            ?>
            
            <p><strong>Need Fixing:</strong> <span class="bad"><?= $needsFix ?></span></p>
            <p><strong>Already Good:</strong> <span class="good"><?= count($credentials) - $needsFix ?></span></p>
        </div>

        <?php if ($needsFix > 0): ?>
            <div class="section warning">
                <h3 style="color: #ffc107; margin-bottom: 10px;">⚠️ Found Credentials with Invalid Characters</h3>
                <p>These credentials have <code>+</code> or <code>=</code> characters that cause iOS login failures.</p>
                <p style="margin-top: 10px;">This will convert them to proper base64url format:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><code>+</code> → <code>-</code></li>
                    <li><code>/</code> → <code>_</code></li>
                    <li>Remove <code>=</code> padding</li>
                </ul>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Current Format</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($credentials as $cred): 
                        $hasPlus = strpos($cred['credential_id'], '+') !== false;
                        $hasEquals = strpos($cred['credential_id'], '=') !== false;
                        $needsFix = $hasPlus || $hasEquals;
                    ?>
                        <tr>
                            <td><?= $cred['id'] ?></td>
                            <td><?= $cred['user_id'] ?></td>
                            <td>
                                <code><?= htmlspecialchars(substr($cred['credential_id'], 0, 40)) ?>...</code>
                                <?php if ($hasPlus): ?><span style="color: #ffc107">Has +</span><?php endif; ?>
                                <?php if ($hasEquals): ?><span style="color: #ffc107">Has =</span><?php endif; ?>
                            </td>
                            <td class="<?= $needsFix ? 'bad' : 'good' ?>">
                                <?= $needsFix ? '❌ NEEDS FIX' : '✅ GOOD' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="POST" onsubmit="return confirm('Fix <?= $needsFix ?> credential(s) to proper base64url format?');">
                <button type="submit" name="fix_credentials" class="btn">
                    🔧 Fix All <?= $needsFix ?> Credentials
                </button>
            </form>
        <?php else: ?>
            <div class="section" style="text-align: center; padding: 40px;">
                <p style="color: #28a745; font-size: 18px;">✅ All credentials are in proper base64url format!</p>
                <p style="color: #999; margin-top: 10px;">All users can login with biometrics.</p>
            </div>
        <?php endif; ?>

        <div style="margin-top: 30px; text-align: center;">
            <a href="login.php" style="color: #c0c0c0;">Go to Login →</a>
        </div>
    </div>
</body>
</html>
