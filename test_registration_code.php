<!DOCTYPE html>
<html>
<head>
    <title>Test Biometric Registration</title>
    <style>
        body { background: #0a0a0a; color: #fff; padding: 20px; font-family: monospace; }
        button { padding: 15px 30px; font-size: 18px; margin: 10px; }
        .output { background: #1a1a1a; padding: 15px; margin: 10px 0; border-radius: 8px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h2>🧪 Test Biometric Registration</h2>
    <p>This tests if the FIXED code is loaded</p>
    
    <button onclick="testRegistration()">Test Registration</button>
    <button onclick="clearCacheAndReload()">Clear Cache & Reload</button>
    
    <div id="output" class="output">Click "Test Registration" to begin...</div>
    
    <script src="/www/js/biometric-auth.js?v=<?= time() ?>"></script>
    <script>
        async function testRegistration() {
            const output = document.getElementById('output');
            output.textContent = 'Testing...\n\n';
            
            try {
                // Check if BiometricAuth is loaded
                if (typeof BiometricAuth === 'undefined') {
                    output.textContent += '❌ BiometricAuth NOT loaded!\n';
                    return;
                }
                
                output.textContent += '✅ BiometricAuth loaded\n\n';
                
                // Check the register function
                const registerFunc = BiometricAuth.register.toString();
                
                // Check if it uses rawId
                if (registerFunc.includes('credential.rawId')) {
                    output.textContent += '✅ Code uses credential.rawId (CORRECT)\n\n';
                } else if (registerFunc.includes('credential.id')) {
                    output.textContent += '❌ Code uses credential.id (WRONG - old code!)\n\n';
                }
                
                // Check for console logging
                if (registerFunc.includes('=== CREDENTIAL INFO ===')) {
                    output.textContent += '✅ Debug logging present (NEW code)\n\n';
                } else {
                    output.textContent += '❌ No debug logging (OLD code)\n\n';
                }
                
                // Now actually try to register
                output.textContent += 'Attempting registration...\n';
                output.textContent += 'Check browser console for [Biometric Register] messages\n\n';
                
                // This will trigger the actual WebAuthn flow
                const result = await BiometricAuth.register(
                    'test@example.com',
                    999
                );
                
                output.textContent += 'Result: ' + JSON.stringify(result, null, 2);
                
            } catch (e) {
                output.textContent += 'Error: ' + e.message;
            }
        }
        
        function clearCacheAndReload() {
            if (confirm('This will clear cache and reload. Continue?')) {
                // Force reload without cache
                window.location.reload(true);
            }
        }
    </script>
</body>
</html>
