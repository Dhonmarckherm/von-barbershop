<?php
/**
 * Test Biometric Support on Device
 * Shows detailed info about WebAuthn support
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometric Support Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #0a0a0a;
            color: #ffffff;
            padding: 20px;
        }
        .test-card {
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 15px;
            padding: 25px;
            margin: 20px auto;
            max-width: 600px;
        }
        .result-pass {
            color: #28a745;
            font-weight: bold;
        }
        .result-fail {
            color: #dc3545;
            font-weight: bold;
        }
        .result-warn {
            color: #ffc107;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="test-card">
        <h2 class="text-center mb-4">
            <i class="bi bi-phone"></i> Biometric Support Test
        </h2>
        
        <div id="results"></div>
        
        <div class="text-center mt-4">
            <button onclick="runTests()" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise"></i> Run Tests Again
            </button>
            <a href="login.php" class="btn btn-secondary ms-2">
                <i class="bi bi-box-arrow-in-right"></i> Go to Login
            </a>
        </div>
    </div>

    <script src="/www/js/biometric-auth.js"></script>
    <script>
        async function runTests() {
            const results = document.getElementById('results');
            results.innerHTML = '<p class="text-center">Running tests...</p>';
            
            const tests = [];
            
            // Test 1: User Agent
            const userAgent = navigator.userAgent;
            const isIOS = /iPhone|iPad|iPod/.test(userAgent);
            const isAndroid = /Android/.test(userAgent);
            const isSafari = /Safari/.test(userAgent) && !/Chrome/.test(userAgent);
            const isChrome = /Chrome/.test(userAgent);
            
            tests.push({
                name: 'Device Detection',
                result: isIOS ? 'iOS Device' : (isAndroid ? 'Android Device' : 'Other'),
                status: isIOS || isAndroid ? 'pass' : 'warn'
            });
            
            tests.push({
                name: 'Browser',
                result: isIOS && isSafari ? 'Safari on iOS' : (isAndroid && isChrome ? 'Chrome on Android' : 'Other Browser'),
                status: 'pass'
            });
            
            // Test 2: WebAuthn Support
            const webAuthnSupported = typeof window.PublicKeyCredential !== 'undefined';
            tests.push({
                name: 'WebAuthn API',
                result: webAuthnSupported ? 'SUPPORTED ✓' : 'NOT SUPPORTED ✗',
                status: webAuthnSupported ? 'pass' : 'fail'
            });
            
            // Test 3: Biometric Availability
            let biometricAvailable = false;
            if (webAuthnSupported) {
                try {
                    biometricAvailable = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                    tests.push({
                        name: 'Biometric Hardware',
                        result: biometricAvailable ? 'AVAILABLE ✓' : 'NOT AVAILABLE ✗',
                        status: biometricAvailable ? 'pass' : 'fail'
                    });
                } catch (err) {
                    tests.push({
                        name: 'Biometric Hardware',
                        result: 'ERROR: ' + err.message,
                        status: 'fail'
                    });
                }
            }
            
            // Test 4: BiometricAuth Library
            const bioLibAvailable = typeof BiometricAuth !== 'undefined';
            tests.push({
                name: 'BiometricAuth Library',
                result: bioLibAvailable ? 'LOADED ✓' : 'NOT LOADED ✗',
                status: bioLibAvailable ? 'pass' : 'fail'
            });
            
            // Test 5: HTTPS Check
            const isHTTPS = window.location.protocol === 'https:';
            tests.push({
                name: 'HTTPS Connection',
                result: isHTTPS ? 'YES ✓ (Required for WebAuthn)' : 'NO ✗ (WebAuthn requires HTTPS)',
                status: isHTTPS ? 'pass' : 'fail'
            });
            
            // Test 6: iOS Version Check
            if (isIOS) {
                const iOSVersion = userAgent.match(/OS (\d+)_(\d+)/);
                if (iOSVersion) {
                    const majorVersion = parseInt(iOSVersion[1]);
                    const iosSupportsWebAuthn = majorVersion >= 13;
                    tests.push({
                        name: 'iOS Version',
                        result: `iOS ${iOSVersion[1]}.${iOSVersion[2]} - WebAuthn ${iosSupportsWebAuthn ? 'SUPPORTED ✓' : 'TOO OLD ✗'}`,
                        status: iosSupportsWebAuthn ? 'pass' : 'fail'
                    });
                }
            }
            
            // Display results
            let html = '<div class="list-group">';
            tests.forEach(test => {
                const statusClass = test.status === 'pass' ? 'result-pass' : (test.status === 'fail' ? 'result-fail' : 'result-warn');
                html += `
                    <div class="list-group-item" style="background: #2d2d2d; border-color: #404040; margin: 5px 0; border-radius: 8px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong>${test.name}</strong>
                            <span class="${statusClass}">${test.result}</span>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            // Summary
            const allPassed = tests.every(t => t.status === 'pass');
            const hasFailures = tests.some(t => t.status === 'fail');
            
            if (allPassed) {
                html += `
                    <div class="mt-4 p-3" style="background: rgba(40, 167, 69, 0.1); border: 1px solid #28a745; border-radius: 10px;">
                        <h5 style="color: #28a745;"><i class="bi bi-check-circle-fill"></i> All Tests Passed!</h5>
                        <p style="color: #b0b0b0; margin: 0;">Your device supports biometric authentication. The enrollment popup should appear after login.</p>
                        <p style="color: #b0b0b0; margin: 10px 0 0 0;"><strong>If popup doesn't show:</strong></p>
                        <ul style="color: #b0b0b0; margin: 5px 0 0 0;">
                            <li>Check browser console for errors (F12 on desktop)</li>
                            <li>Try logging in with email/password first</li>
                            <li>Make sure you haven't already enrolled biometrics</li>
                        </ul>
                    </div>
                `;
            } else if (hasFailures) {
                html += `
                    <div class="mt-4 p-3" style="background: rgba(220, 53, 69, 0.1); border: 1px solid #dc3545; border-radius: 10px;">
                        <h5 style="color: #dc3545;"><i class="bi bi-x-circle-fill"></i> Biometric Not Supported</h5>
                        <p style="color: #b0b0b0; margin: 0;">Your device or browser doesn't support WebAuthn biometric authentication.</p>
                    </div>
                `;
            }
            
            results.innerHTML = html;
        }
        
        // Run tests on page load
        runTests();
    </script>
</body>
</html>
