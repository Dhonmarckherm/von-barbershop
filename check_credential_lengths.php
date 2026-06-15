<?php
/**
 * Check biometric credential lengths in database
 */
require_once __DIR__ . '/config/db.php';

echo "<html><head><style>
body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin-top: 20px; }
th, td { padding: 12px; border: 1px solid #444; text-align: left; }
th { background: #2d2d2d; }
.good { color: #28a745; }
.bad { color: #dc3545; }
</style></head><body>";

echo "<h1>🔍 Biometric Credential Length Check</h1>";

// Check all credentials
$stmt = $pdo->query("
    SELECT up.id, up.user_id, u.name, u.email, 
           CHAR_LENGTH(up.credential_id) as char_len,
           OCTET_LENGTH(up.credential_id) as byte_len,
           HEX(up.credential_id) as hex_preview,
           up.transports,
           up.created_at
    FROM user_passkeys up
    JOIN users u ON up.user_id = u.id
    ORDER BY up.created_at DESC
");

$credentials = $stmt->fetchAll();

if (empty($credentials)) {
    echo "<p style='color: #ffc107;'>⚠️ No biometric credentials found in database.</p>";
} else {
    echo "<p>Found <strong>" . count($credentials) . "</strong> credential(s)</p>";
    
    echo "<table>";
    echo "<tr>
        <th>ID</th>
        <th>User</th>
        <th>Email</th>
        <th>Char Length</th>
        <th>Byte Length</th>
        <th>Status</th>
        <th>Hex Preview (first 40)</th>
        <th>Transports</th>
        <th>Created</th>
    </tr>";
    
    foreach ($credentials as $cred) {
        $status = $cred['char_len'] >= 60 ? '<span class="good">✅ GOOD</span>' : '<span class="bad">❌ TOO SHORT</span>';
        
        echo "<tr>
            <td>{$cred['id']}</td>
            <td>{$cred['name']}</td>
            <td>{$cred['email']}</td>
            <td><strong>{$cred['char_len']}</strong></td>
            <td>{$cred['byte_len']}</td>
            <td>{$status}</td>
            <td style='font-family: monospace; font-size: 11px;'>" . substr($cred['hex_preview'], 0, 40) . "...</td>
            <td>{$cred['transports']}</td>
            <td>{$cred['created_at']}</td>
        </tr>";
    }
    
    echo "</table>";
}

echo "<hr style='margin: 40px 0; border-color: #444;'>";
echo "<h2>📊 Summary</h2>";

$good = 0;
$bad = 0;
foreach ($credentials as $cred) {
    if ($cred['char_len'] >= 60) {
        $good++;
    } else {
        $bad++;
    }
}

echo "<p>✅ Good credentials (60+ chars): <strong class='good'>$good</strong></p>";
echo "<p>❌ Short credentials (<60 chars): <strong class='bad'>$bad</strong></p>";

if ($bad > 0) {
    echo "<div style='background: rgba(220,53,69,0.2); border: 2px solid #dc3545; padding: 20px; border-radius: 10px; margin-top: 20px;'>";
    echo "<h3 style='color: #dc3545;'>⚠️ Action Required</h3>";
    echo "<p>$bad credential(s) are too short and will NOT work for biometric login.</p>";
    echo "<p><strong>Solution:</strong> Users need to re-enroll their biometrics:</p>";
    echo "<ol>
        <li>Visit: <a href='https://von-barbershop.onrender.com/force_reset_biometric.php' style='color: #ffc107;'>Force Reset Biometrics</a></li>
        <li>Logout completely</li>
        <li>Login with password</li>
        <li>Re-enable biometrics</li>
    </ol>";
    echo "</div>";
}

echo "</body></html>";
