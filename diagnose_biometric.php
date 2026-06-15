<?php
/**
 * Comprehensive Biometric Diagnostic Tool
 * Shows exactly what's stored and what the browser sees
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';

session_start();

// Get current user's credentials
$stmt = $pdo->prepare("
    SELECT up.*, 
           CHAR_LENGTH(up.credential_id) as char_len,
           OCTET_LENGTH(up.credential_id) as byte_len,
           HEX(up.credential_id) as hex_value
    FROM user_passkeys up 
    WHERE up.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$credentials = $stmt->fetchAll();

// Start building HTML response
$html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Biometric Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; 
            background: #0a0a0a; 
            color: #fff; 
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #C5A059; margin-bottom: 10px; font-size: 28px; }
        h2 { color: #c0c0c0; margin: 30px 0 15px 0; font-size: 22px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .info-box { 
            background: #1a1a1a; 
            border: 2px solid #333; 
            border-radius: 12px; 
            padding: 20px; 
            margin: 15px 0;
        }
        .success { border-color: #28a745; background: rgba(40,167,69,0.1); }
        .error { border-color: #dc3545; background: rgba(220,53,69,0.1); }
        .warning { border-color: #ffc107; background: rgba(255,193,7,0.1); }
        .good { color: #28a745; font-weight: bold; }
        .bad { color: #dc3545; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { 
            padding: 12px; 
            border: 1px solid #444; 
            text-align: left; 
            font-size: 14px;
        }
        th { background: #2d2d2d; color: #C5A059; font-weight: 600; }
        code { 
            background: #2d2d2d; 
            padding: 2px 6px; 
            border-radius: 4px; 
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #C5A059, #D4AF37);
            color: #000;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px 5px 10px 0;
            border: none;
            cursor: pointer;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(197,160,89,0.4); }
        .btn-danger { background: linear-gradient(135deg, #dc3545, #c82333); color: #fff; }
        pre { 
            background: #1a1a1a; 
            border: 1px solid #444; 
            padding: 15px; 
            border-radius: 8px; 
            overflow-x: auto;
            font-size: 12px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Biometric Authentication Diagnostic</h1>
        <p style='color: #888; margin-bottom: 20px;'>User: <strong>" . htmlspecialchars($_SESSION['name']) . "</strong> (" . htmlspecialchars($_SESSION['email']) . ")</p>";

// Section 1: Database Credentials
$html .= "<h2>📊 Stored Credentials in Database</h2>";

if (empty($credentials)) {
    $html .= "<div class='info-box error'>
        <p><strong>❌ NO CREDENTIALS FOUND!</strong></p>
        <p>Your biometric has NOT been enrolled yet, or it was deleted.</p>
        <p><strong>Next Step:</strong> Login with password → Enable biometrics → This page should show your credential.</p>
    </div>";
} else {
    $html .= "<p>Found <strong>" . count($credentials) . "</strong> credential(s)</p>";
    
    foreach ($credentials as $i => $cred) {
        $isGood = $cred['char_len'] >= 60;
        $statusClass = $isGood ? 'success' : 'error';
        $statusText = $isGood ? '✅ VALID (Will work)' : '❌ TOO SHORT (Will NOT work)';
        
        $html .= "<div class='info-box {$statusClass}'>
            <h3 style='margin-bottom: 15px;'>Credential #{$cred['id']} {$statusText}</h3>
            <table>
                <tr><th>Property</th><th>Value</th></tr>
                <tr><td>Character Length</td><td><strong>{$cred['char_len']}</strong> " . ($isGood ? '<span class="good">✓</span>' : '<span class="bad">✗</span>') . "</td></tr>
                <tr><td>Byte Length</td><td>{$cred['byte_len']}</td></tr>
                <tr><td>Transports</td><td><code>{$cred['transports']}</code></td></tr>
                <tr><td>Created</td><td>{$cred['created_at']}</td></tr>
                <tr><td>Last Used</td><td>" . ($cred['last_used_at'] ?: 'Never') . "</td></tr>
                <tr><td>Hex Value (first 60)</td><td style='font-family: monospace; font-size: 11px; word-break: break-all;'>" . substr($cred['hex_value'], 0, 60) . "...</td></tr>
            </table>
        </div>";
    }
}

// Section 2: Browser Test
$html .= "<h2>🌐 Browser Biometric Test</h2>";
$html .= "<div class='info-box warning'>
    <p><strong>Click the button below to test if your browser can see your biometric credential:</strong></p>
    <button class='btn' onclick='testBrowserCredential()'>🔐 Test Browser Credential</button>
    <div id='browserTestResult' style='margin-top: 15px;'></div>
</div>";

// Section 3: Actions
$html .= "<h2>🛠️ Actions</h2>
<div class='info-box'>
    <button class='btn btn-danger' onclick='forceReset()'>🗑️ Force Reset & Re-enroll</button>
    <a href='profile.php' class='btn'>← Back to Profile</a>
    <a href='logout.php' class='btn'>🚪 Logout</a>
</div>";

$html .= "</div>";

// JavaScript for browser test
$html .= "<script>
async function testBrowserCredential() {
    const resultDiv = document.getElementById('browserTestResult');
    resultDiv.innerHTML = '<p style=\"color: #ffc107;\">⏳ Testing...</p>';
    
    try {
        // Get challenge from server
        const response = await fetch('/api/biometric_login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_challenge' })
        });
        
        const options = await response.json();
        console.log('Server options:', options);
        
        if (options.error) {
            resultDiv.innerHTML = '<div class=\"info-box error\"><p><strong>❌ Server Error:</strong> ' + options.error + '</p></div>';
            return;
        }
        
        if (!options.allowCredentials || options.allowCredentials.length === 0) {
            resultDiv.innerHTML = '<div class=\"info-box error\"><p><strong>❌ NO CREDENTIALS from server!</strong><br>Your biometric is not enabled in the database.</p></div>';
            return;
        }
        
        resultDiv.innerHTML += '<p style=\"color: #28a745;\">✅ Server returned ' + options.allowCredentials.length + ' credential(s)</p>';
        resultDiv.innerHTML += '<pre>' + JSON.stringify(options, null, 2) + '</pre>';
        
        // Try to get the credential
        const credential = await navigator.credentials.get({
            publicKey: {
                challenge: base64urlToBuffer(options.challenge),
                allowCredentials: options.allowCredentials.map(cred => ({
                    id: base64urlToBuffer(cred.id),
                    type: 'public-key',
                    transports: cred.transports || ['internal', 'hybrid']
                })),
                timeout: 60000,
                userVerification: 'required',
                rpId: window.location.hostname
            }
        });
        
        if (credential) {
            resultDiv.innerHTML += '<div class=\"info-box success\"><p><strong>✅ SUCCESS!</strong><br>Browser found your biometric credential!</p><p>Credential ID: <code>' + credential.id + '</code></p><p>Length: <strong>' + credential.id.length + ' characters</strong></p></div>';
        }
        
    } catch (err) {
        console.error('Browser test error:', err);
        resultDiv.innerHTML += '<div class=\"info-box error\"><p><strong>❌ Browser Test Failed:</strong> ' + err.message + '</p><p>This means the browser cannot find your credential.</p></div>';
    }
}

function base64urlToBuffer(base64url) {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const binary = atob(base64);
    const buffer = new ArrayBuffer(binary.length);
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return buffer;
}

function forceReset() {
    if (confirm('This will delete ALL your biometric credentials. You will need to re-enroll. Continue?')) {
        window.location.href = '/force_reset_biometric.php';
    }
}
</script>";

$html .= "</body></html>";

echo $html;
