<?php
session_start();

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body style="background: #1a1a1a; color: #F5F0E8;">
    <div class="container mt-5">
        <h1 class="text-center mb-4">🔐 Biometric Login Test</h1>
        
        <div class="card" style="background: #2d2d2d; border: 1px solid #c0c0c0;">
            <div class="card-body">
                <h5>Debug Information:</h5>
                <div id="debugInfo" class="mb-4" style="background: #000; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 0.85rem;"></div>
                
                <div class="d-grid gap-3">
                    <button id="testWebAuthn" class="btn btn-primary" style="background: linear-gradient(135deg, #c0c0c0, #ffffff); color: #000; font-weight: 600;">
                        <i class="bi bi-shield-check"></i> Test 1: Check WebAuthn Support
                    </button>
                    
                    <button id="testBiometric" class="btn btn-success" disabled>
                        <i class="bi bi-fingerprint"></i> Test 2: Check Biometric Hardware
                    </button>
                    
                    <button id="testEnrollment" class="btn btn-warning" disabled>
                        <i class="bi bi-plus-circle"></i> Test 3: Try Enroll Biometric
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="/www/js/biometric-auth.js"></script>
    <script>
        const debugInfo = document.getElementById('debugInfo');
        const logs = [];

        function log(message, type = 'info') {
            logs.push(`[${type.toUpperCase()}] ${message}`);
            debugInfo.innerHTML = logs.join('<br>');
            console.log(`[Biometric Test] ${message}`);
        }

        // Initial checks
        log('Page loaded');
        log(`User: <?= $_SESSION['user_name'] ?? 'Unknown' ?>`);
        log(`Role: <?= $_SESSION['user_role'] ?? 'Unknown' ?>`);
        log(`WebAuthn API available: ${window.PublicKeyCredential ? 'YES' : 'NO'}`);
        log(`navigator.credentials available: ${navigator.credentials ? 'YES' : 'NO'}`);
        log(`Protocol: ${window.location.protocol}`);
        log(`Hostname: ${window.location.hostname}`);

        // Test 1: WebAuthn Support
        document.getElementById('testWebAuthn').addEventListener('click', async function() {
            log('Testing WebAuthn support...', 'test');
            
            if (typeof BiometricAuth === 'undefined') {
                log('ERROR: BiometricAuth library not loaded!', 'error');
                return;
            }
            
            const isSupported = BiometricAuth.isSupported();
            log(`WebAuthn supported: ${isSupported ? 'YES ✅' : 'NO ❌'}`, isSupported ? 'success' : 'error');
            
            if (isSupported) {
                document.getElementById('testBiometric').disabled = false;
            }
        });

        // Test 2: Biometric Hardware
        document.getElementById('testBiometric').addEventListener('click', async function() {
            log('Checking biometric hardware...', 'test');
            
            try {
                const isAvailable = await BiometricAuth.isBiometricAvailable();
                log(`Biometric hardware available: ${isAvailable ? 'YES ✅' : 'NO ❌'}`, isAvailable ? 'success' : 'error');
                
                if (isAvailable) {
                    document.getElementById('testEnrollment').disabled = false;
                }
            } catch (err) {
                log(`Error checking biometric: ${err.message}`, 'error');
            }
        });

        // Test 3: Enrollment
        document.getElementById('testEnrollment').addEventListener('click', async function() {
            log('Starting biometric enrollment...', 'test');
            
            try {
                // Check if already registered
                const checkResponse = await fetch('/api/biometric_login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_challenge' })
                });
                
                const checkResult = await checkResponse.json();
                log(`API response: ${JSON.stringify(checkResult).substring(0, 100)}`, 'api');
                
                if (checkResult.allowCredentials && checkResult.allowCredentials.length > 0) {
                    log('⚠️ You already have biometric registered!', 'warning');
                    alert('You already have biometric login enabled!');
                    return;
                }
                
                // Try to register
                const result = await BiometricAuth.register(
                    '<?= $_SESSION['user_email'] ?>',
                    <?= $_SESSION['user_id'] ?>
                );
                
                if (result.success) {
                    log('✅ Biometric enrollment successful!', 'success');
                    alert('Biometric login enabled!');
                } else {
                    log(`❌ Enrollment failed: ${result.error}`, 'error');
                    alert('Failed: ' + result.error);
                }
            } catch (err) {
                log(`❌ Error: ${err.message}`, 'error');
                alert('Error: ' + err.message);
            }
        });

        // Auto-run initial check
        setTimeout(() => {
            document.getElementById('testWebAuthn').click();
        }, 500);
    </script>
</body>
</html>
