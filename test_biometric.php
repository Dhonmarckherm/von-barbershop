<?php
require_once __DIR__ . '/config/session.php';
initializeSession();

// Check if logged in
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
    <title>Test Biometric</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card bg-secondary">
                    <div class="card-body">
                        <h2 class="card-title">🔐 Biometric Test Page</h2>
                        <p class="card-text">Use this page to test biometric enrollment on your device.</p>
                        
                        <div id="status" class="alert alert-info">
                            Click the button below to start
                        </div>

                        <button id="testBiometricBtn" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-fingerprint"></i> Test Biometric Enrollment
                        </button>

                        <a href="my_appointments.php" class="btn btn-outline-light w-100">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>

                <div class="card bg-secondary mt-3">
                    <div class="card-body">
                        <h5>Debug Information:</h5>
                        <pre id="debugInfo" class="bg-dark text-success p-3" style="font-size: 12px;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="/www/js/biometric-auth.js"></script>
    <script>
        const status = document.getElementById('status');
        const debugInfo = document.getElementById('debugInfo');
        const testBtn = document.getElementById('testBiometricBtn');

        function log(message) {
            debugInfo.textContent += message + '\n';
            console.log(message);
        }

        testBtn.addEventListener('click', async function() {
            debugInfo.textContent = '';
            log('=== BIOMETRIC TEST STARTED ===');
            log('User ID: <?php echo $_SESSION["user_id"]; ?>');
            log('Email: <?php echo $_SESSION["email"]; ?>');
            
            // Step 1: Check if WebAuthn is supported
            log('\n1. Checking WebAuthn support...');
            if (!BiometricAuth.isSupported()) {
                status.className = 'alert alert-danger';
                status.innerHTML = '❌ WebAuthn is NOT supported in this browser!';
                log('❌ WebAuthn NOT supported');
                return;
            }
            log('✅ WebAuthn supported');

            // Step 2: Check if biometric hardware is available
            log('\n2. Checking biometric hardware...');
            const isAvailable = await BiometricAuth.isBiometricAvailable();
            if (!isAvailable) {
                status.className = 'alert alert-warning';
                status.innerHTML = '⚠️ No biometric hardware detected on this device';
                log('❌ No biometric hardware');
                return;
            }
            log('✅ Biometric hardware available');

            // Step 3: Register biometric
            log('\n3. Starting biometric registration...');
            status.className = 'alert alert-primary';
            status.innerHTML = '⏳ Please complete the biometric scan...';
            
            const result = await BiometricAuth.register(
                '<?php echo $_SESSION["email"]; ?>',
                <?php echo (int)$_SESSION["user_id"]; ?>
            );

            if (result.success) {
                status.className = 'alert alert-success';
                status.innerHTML = '✅ SUCCESS! Biometric login is now enabled!<br><br>You can now logout and test "Login with Biometrics"';
                log('\n✅ Registration successful!');
                log(result.message);
            } else {
                status.className = 'alert alert-danger';
                status.innerHTML = '❌ Failed: ' + result.error;
                log('\n❌ Registration failed: ' + result.error);
            }
        });

        log('Page loaded. Click button to test.');
    </script>
</body>
</html>
