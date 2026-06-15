<?php
/**
 * COMPREHENSIVE BIOMETRIC DIAGNOSTIC
 * Traces the entire flow from registration to login
 */
require_once __DIR__ . '/config/db.php';

// Get current logged-in user if any
session_start();
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['name'] ?? 'Not logged in';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Biometric Flow Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #0a0a0a; color: #fff; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #c0c0c0; margin-bottom: 20px; font-size: 24px; }
        h2 { color: #c0c0c0; margin: 30px 0 15px; font-size: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section { background: #1a1a1a; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .step { background: #2d2d2d; border-radius: 8px; padding: 15px; margin: 10px 0; }
        .step-num { display: inline-block; background: #c0c0c0; color: #000; width: 28px; height: 28px; border-radius: 50%; text-align: center; line-height: 28px; font-weight: bold; margin-right: 10px; }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .warning { border-left: 4px solid #ffc107; }
        .info { border-left: 4px solid #17a2b8; }
        code { background: #1a1a1a; padding: 2px 8px; border-radius: 4px; font-size: 13px; color: #ffc107; }
        pre { background: #0a0a0a; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px; margin: 10px 0; }
        .btn { display: inline-block; padding: 12px 24px; background: #c0c0c0; color: #000; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 14px; margin: 5px; text-decoration: none; }
        .btn:hover { background: #d0d0d0; }
        .btn-danger { background: #dc3545; color: #fff; }
        .btn-success { background: #28a745; color: #fff; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; }
        th, td { padding: 10px; border: 1px solid #444; text-align: left; }
        th { background: #2d2d2d; color: #c0c0c0; }
        .cred-id { font-family: monospace; font-size: 11px; word-break: break-all; max-width: 300px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Complete Biometric Flow Test</h1>
        
        <?php if ($currentUserId): ?>
            <div class="section">
                <p><strong>👤 Current User:</strong> <?= htmlspecialchars($currentUserName) ?> (ID: <?= $currentUserId ?>)</p>
            </div>
        <?php else: ?>
            <div class="section warning">
                <p>⚠️ <strong>Not logged in!</strong> You need to login first to test biometric enrollment.</p>
                <a href="login.php" class="btn">Login Now</a>
            </div>
        <?php endif; ?>

        <h2>Step 1: Check Database Credentials</h2>
        <?php
        $stmt = $pdo->query("
            SELECT up.id, up.user_id, u.name, u.email,
                   up.credential_id,
                   CHAR_LENGTH(up.credential_id) as char_len,
                   up.transports,
                   up.created_at
            FROM user_passkeys up
            JOIN users u ON up.user_id = u.id
            ORDER BY up.created_at DESC
        ");
        $allCredentials = $stmt->fetchAll();
        ?>

        <div class="section">
            <p><strong>Total Registered Credentials:</strong> <?= count($allCredentials) ?></p>
            
            <?php if (count($allCredentials) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Length</th>
                            <th>Valid?</th>
                            <th>Credential ID</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allCredentials as $cred): ?>
                            <tr>
                                <td><?= htmlspecialchars($cred['name']) ?></td>
                                <td><?= htmlspecialchars($cred['email']) ?></td>
                                <td><code><?= $cred['char_len'] ?> chars</code></td>
                                <td><?= $cred['char_len'] >= 20 ? '<span style="color:#28a745">✅ YES</span>' : '<span style="color:#dc3545">❌ NO</span>' ?></td>
                                <td class="cred-id"><?= htmlspecialchars(substr($cred['credential_id'], 0, 40)) ?>...</td>
                                <td><?= date('M j, g:i A', strtotime($cred['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #999; margin-top: 15px;">⚠️ No credentials registered yet.</p>
            <?php endif; ?>
        </div>

        <h2>Step 2: Test Login API (What login.php sees)</h2>
        <div class="section">
            <button class="btn" onclick="testLoginAPI()">Test /api/biometric_login.php</button>
            <div id="login-api-result"></div>
        </div>

        <?php if ($currentUserId): ?>
            <h2>Step 3: Test Registration (Re-enroll if needed)</h2>
            <div class="section">
                <button class="btn btn-success" onclick="testRegistration()">Test Biometric Registration</button>
                <div id="register-result"></div>
            </div>

            <h2>Step 4: Delete Current User's Credential</h2>
            <div class="section">
                <button class="btn btn-danger" onclick="deleteCurrentUserCredential()">Delete My Credential Only</button>
                <div id="delete-result"></div>
            </div>
        <?php endif; ?>

        <h2>Step 5: Reset ALL Credentials</h2>
        <div class="section warning">
            <p>⚠️ This will delete ALL biometric credentials for ALL users.</p>
            <button class="btn btn-danger" onclick="deleteAllCredentials()">Reset Everything</button>
            <div id="delete-all-result"></div>
        </div>
    </div>

    <script>
        async function testLoginAPI() {
            const resultDiv = document.getElementById('login-api-result');
            resultDiv.innerHTML = '<p style="color:#999">Testing...</p>';
            
            try {
                const response = await fetch('/api/biometric_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_challenge' })
                });
                
                const data = await response.json();
                console.log('Login API Response:', data);
                
                let html = '<div class="section info" style="margin-top:15px">';
                html += '<h4>API Response:</h4>';
                html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                
                if (data.error) {
                    html += '<p style="color:#dc3545">❌ ERROR: ' + data.error + '</p>';
                } else {
                    html += '<p style="color:#28a745">✅ API returned ' + (data.allowCredentials?.length || 0) + ' credentials</p>';
                    html += '<p>Challenge: ' + (data.challenge ? 'YES (' + data.challenge.length + ' chars)' : 'NO') + '</p>';
                }
                html += '</div>';
                
                resultDiv.innerHTML = html;
            } catch (err) {
                resultDiv.innerHTML = '<div class="section error"><p>❌ Request failed: ' + err.message + '</p></div>';
            }
        }

        async function testRegistration() {
            const resultDiv = document.getElementById('register-result');
            resultDiv.innerHTML = '<p style="color:#999">Starting registration...</p>';
            
            try {
                // Step 1: Get registration options
                const regResponse = await fetch('/api/biometric_register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        email: '<?= $_SESSION['email'] ?? '' ?>',
                        user_id: <?= $currentUserId ?? 0 ?> 
                    })
                });
                
                const options = await regResponse.json();
                console.log('Registration options:', options);
                
                if (options.error) {
                    resultDiv.innerHTML = '<div class="section error"><p>❌ Registration failed: ' + options.error + '</p></div>';
                    return;
                }
                
                // Step 2: Create credential
                resultDiv.innerHTML += '<p style="color:#999">Creating credential...</p>';
                
                const credential = await navigator.credentials.create({
                    publicKey: {
                        challenge: base64urlToBuffer(options.challenge),
                        rp: {
                            name: 'VON BARBER STUDIO',
                            id: window.location.hostname
                        },
                        user: {
                            id: stringToBuffer(String(options.user_id)),
                            name: options.email,
                            displayName: options.display_name
                        },
                        pubKeyCredParams: [
                            { type: 'public-key', alg: -7 },
                            { type: 'public-key', alg: -257 }
                        ],
                        authenticatorSelection: {
                            authenticatorAttachment: 'platform',
                            userVerification: 'required',
                            requireResidentKey: false
                        },
                        timeout: 60000,
                        attestation: 'none'
                    }
                });
                
                console.log('Credential created:', credential);
                console.log('credential.id:', credential.id);
                console.log('credential.rawId byteLength:', credential.rawId.byteLength);
                
                // Convert rawId to base64url
                const rawIdBytes = new Uint8Array(credential.rawId);
                const credentialIdBase64url = bufferToBase64url(rawIdBytes);
                
                resultDiv.innerHTML += '<p style="color:#ffc107">Credential created! Length: ' + credentialIdBase64url.length + ' chars</p>';
                
                // Step 3: Verify with server
                resultDiv.innerHTML += '<p style="color:#999">Verifying with server...</p>';
                
                const verifyResponse = await fetch('/api/biometric_verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'register',
                        credential: {
                            id: credentialIdBase64url,
                            rawId: credentialIdBase64url,
                            response: {
                                attestationObject: bufferToBase64url(new Uint8Array(credential.response.attestationObject)),
                                clientDataJSON: bufferToBase64url(new Uint8Array(credential.response.clientDataJSON))
                            },
                            type: credential.type
                        }
                    })
                });
                
                const result = await verifyResponse.json();
                console.log('Verify response:', result);
                
                if (result.success) {
                    resultDiv.innerHTML = '<div class="section success"><p>✅ SUCCESS! Biometric registered!</p><p>Credential length: ' + credentialIdBase64url.length + ' chars</p><pre>' + JSON.stringify(result, null, 2) + '</pre></div>';
                } else {
                    resultDiv.innerHTML = '<div class="section error"><p>❌ Verification failed: ' + (result.error || 'Unknown error') + '</p></div>';
                }
                
            } catch (err) {
                resultDiv.innerHTML = '<div class="section error"><p>❌ Error: ' + err.message + '</p><pre>' + err.stack + '</pre></div>';
            }
        }

        async function deleteCurrentUserCredential() {
            if (!confirm('Delete your biometric credential?')) return;
            
            const resultDiv = document.getElementById('delete-result');
            resultDiv.innerHTML = '<p style="color:#999">Deleting...</p>';
            
            const response = await fetch('/api/biometric_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_current_user' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                resultDiv.innerHTML = '<div class="section success"><p>✅ ' + data.message + '</p></div>';
            } else {
                resultDiv.innerHTML = '<div class="section error"><p>❌ ' + (data.error || 'Failed') + '</p></div>';
            }
        }

        async function deleteAllCredentials() {
            if (!confirm('⚠️ Delete ALL biometric credentials for ALL users?')) return;
            
            const resultDiv = document.getElementById('delete-all-result');
            resultDiv.innerHTML = '<p style="color:#999">Deleting...</p>';
            
            const response = await fetch('/api/biometric_verify.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_all' })
            });
            
            const data = await response.json();
            
            if (data.success) {
                resultDiv.innerHTML = '<div class="section success"><p>✅ ' + data.message + '</p></div>';
            } else {
                resultDiv.innerHTML = '<div class="section error"><p>❌ ' + (data.error || 'Failed') + '</p></div>';
            }
        }

        // Helper functions
        function base64urlToBuffer(base64url) {
            const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
            const pad = base64.length % 4;
            const padded = pad ? base64 + '='.repeat(4 - pad) : base64;
            const binary = atob(padded);
            const buffer = new Uint8Array(binary.length);
            for (let i = 0; i < binary.length; i++) {
                buffer[i] = binary.charCodeAt(i);
            }
            return buffer;
        }

        function stringToBuffer(str) {
            const buffer = new Uint8Array(str.length);
            for (let i = 0; i < str.length; i++) {
                buffer[i] = str.charCodeAt(i);
            }
            return buffer;
        }

        function bufferToBase64url(buffer) {
            let binary = '';
            for (let i = 0; i < buffer.length; i++) {
                binary += String.fromCharCode(buffer[i]);
            }
            const base64 = btoa(binary);
            return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
        }
    </script>
</body>
</html>
