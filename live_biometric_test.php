<?php
/**
 * LIVE Biometric Login Test
 * Shows exact errors in real-time
 */
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Biometric Login Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #0a0a0a; color: #fff; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; }
        h1 { color: #c0c0c0; margin-bottom: 20px; }
        .btn { display: inline-block; padding: 16px 32px; background: linear-gradient(135deg, #C5A059 0%, #D4AF37 100%); color: #1a1a1a; border: none; border-radius: 12px; cursor: pointer; font-weight: bold; font-size: 16px; width: 100%; margin: 10px 0; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .log { background: #1a1a1a; border-radius: 8px; padding: 15px; margin: 10px 0; font-family: monospace; font-size: 13px; max-height: 500px; overflow-y: auto; }
        .log-entry { padding: 5px 0; border-bottom: 1px solid #333; }
        .error { color: #ff6b6b; }
        .success { color: #28a745; }
        .info { color: #74c0fc; }
        .warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔴 Live Biometric Login Test</h1>
        
        <p style="color: #999; margin-bottom: 20px;">Click the button below and watch the console log in real-time:</p>
        
        <button class="btn" id="loginBtn" onclick="testBiometricLogin()">
            🔐 Test Biometric Login NOW
        </button>
        
        <div id="logContainer" class="log"></div>
    </div>

    <script>
        function log(message, type = 'info') {
            const logContainer = document.getElementById('logContainer');
            const entry = document.createElement('div');
            entry.className = `log-entry ${type}`;
            const timestamp = new Date().toLocaleTimeString();
            entry.textContent = `[${timestamp}] ${message}`;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
            console.log(message);
        }

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

        function bufferToBase64url(buffer) {
            let binary = '';
            for (let i = 0; i < buffer.length; i++) {
                binary += String.fromCharCode(buffer[i]);
            }
            const base64 = btoa(binary);
            return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
        }

        async function testBiometricLogin() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.textContent = '⏳ Testing...';
            
            log('=== STARTING BIOMETRIC LOGIN TEST ===', 'info');
            
            try {
                // Step 1: Call login API
                log('Step 1: Calling /api/biometric_login.php...', 'info');
                
                const response = await fetch('/api/biometric_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_challenge' })
                });
                
                const options = await response.json();
                log(`API Response: ${JSON.stringify(options, null, 2)}`, 'info');
                
                if (options.error) {
                    throw new Error(`API Error: ${options.error}`);
                }
                
                if (!options.allowCredentials || options.allowCredentials.length === 0) {
                    throw new Error('No credentials returned from server!');
                }
                
                log(`✅ Got ${options.allowCredentials.length} credentials from server`, 'success');
                
                // Step 2: Prepare credential options
                log('Step 2: Preparing credential options...', 'info');
                
                const allowCredentials = options.allowCredentials.map(cred => {
                    log(`  - Credential ID: ${cred.id} (${cred.id.length} chars)`, 'info');
                    
                    return {
                        id: base64urlToBuffer(cred.id),
                        type: 'public-key',
                        transports: cred.transports || ['internal', 'hybrid']
                    };
                });
                
                const getOptions = {
                    publicKey: {
                        challenge: base64urlToBuffer(options.challenge),
                        allowCredentials: allowCredentials,
                        timeout: 60000,
                        userVerification: 'required',
                        rpId: window.location.hostname
                    }
                };
                
                log(`rpId: ${window.location.hostname}`, 'info');
                log(`Challenge length: ${options.challenge.length} chars`, 'info');
                
                // Step 3: Request biometric
                log('Step 3: Requesting biometric authentication...', 'warning');
                log('👆 Look at your device - it should ask for Face ID/Touch ID/Fingerprint', 'warning');
                
                try {
                    const assertion = await navigator.credentials.get(getOptions);
                    
                    log('✅ BIOMETRIC VERIFIED!', 'success');
                    log(`Assertion ID: ${assertion.id}`, 'success');
                    log(`Assertion rawId length: ${assertion.rawId.byteLength} bytes`, 'success');
                    
                    // Step 4: Verify with server
                    log('Step 4: Verifying with server...', 'info');
                    
                    const verifyResponse = await fetch('/api/biometric_verify.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'login',
                            assertion: {
                                id: assertion.id,
                                rawId: bufferToBase64url(new Uint8Array(assertion.rawId)),
                                response: {
                                    authenticatorData: bufferToBase64url(new Uint8Array(assertion.response.authenticatorData)),
                                    clientDataJSON: bufferToBase64url(new Uint8Array(assertion.response.clientDataJSON)),
                                    signature: bufferToBase64url(new Uint8Array(assertion.response.signature)),
                                    userHandle: assertion.response.userHandle ? 
                                        bufferToBase64url(new Uint8Array(assertion.response.userHandle)) : null
                                },
                                type: assertion.type
                            }
                        })
                    });
                    
                    const result = await verifyResponse.json();
                    log(`Server response: ${JSON.stringify(result, null, 2)}`, result.success ? 'success' : 'error');
                    
                    if (result.success) {
                        log('🎉 SUCCESS! You are now logged in!', 'success');
                        log(`User: ${result.name} (${result.email || 'N/A'})`, 'success');
                        log(`Role: ${result.role}`, 'success');
                        btn.textContent = '✅ LOGIN SUCCESS!';
                        btn.style.background = '#28a745';
                        
                        // Redirect after 2 seconds
                        setTimeout(() => {
                            if (result.role === 'admin' || result.role === 'barber') {
                                window.location.href = '/admin_dashboard.php';
                            } else {
                                window.location.href = '/my_appointments.php';
                            }
                        }, 2000);
                    } else {
                        throw new Error(`Server rejected: ${result.error}`);
                    }
                    
                } catch (biometricError) {
                    log(`❌ BIOMETRIC AUTH FAILED: ${biometricError.message}`, 'error');
                    log(`Error name: ${biometricError.name}`, 'error');
                    
                    if (biometricError.name === 'NotAllowedError') {
                        log('⚠️ User cancelled or biometric not recognized', 'warning');
                    } else if (biometricError.name === 'NotSupportedError') {
                        log('⚠️ Biometric not supported on this device', 'warning');
                    } else if (biometricError.name === 'SecurityError') {
                        log('⚠️ Security error - check HTTPS and rpId', 'warning');
                    }
                    
                    throw biometricError;
                }
                
            } catch (error) {
                log(`❌ TEST FAILED: ${error.message}`, 'error');
                log(`Full error: ${error.stack}`, 'error');
                btn.textContent = '❌ FAILED - See log above';
                btn.style.background = '#dc3545';
            } finally {
                setTimeout(() => {
                    btn.disabled = false;
                    btn.textContent = '🔐 Test Biometric Login NOW';
                    btn.style.background = '';
                }, 5000);
            }
        }
    </script>
</body>
</html>
