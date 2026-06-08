<?php
require_once 'config/session.php';
initializeSession();

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Push Notification Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
        .card { background: #2d2d2d; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        .warning { color: #ff9800; }
        button { background: #C5A059; color: #000; padding: 12px 24px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        pre { background: #000; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>🔔 Push Notification Diagnostic</h1>
    
    <div class="card">
        <h2>Step 1: Check Login Status</h2>
        <?php if ($isLoggedIn): ?>
            <p class="success">✅ Logged in as User ID: <?php echo $userId; ?></p>
            <p>Name: <?php echo htmlspecialchars($_SESSION['name']); ?></p>
            <p>Email: <?php echo htmlspecialchars($_SESSION['email']); ?></p>
        <?php else: ?>
            <p class="error">❌ NOT LOGGED IN - Please login first!</p>
            <a href="login.php" style="color: #C5A059;">Go to Login</a>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>Step 2: Check Browser Support</h2>
        <p id="support-check">Checking...</p>
    </div>

    <div class="card">
        <h2>Step 3: Check Notification Permission</h2>
        <p id="permission-check">Checking...</p>
    </div>

    <div class="card">
        <h2>Step 4: Check Service Worker</h2>
        <p id="sw-check">Checking...</p>
    </div>

    <div class="card">
        <h2>Step 5: Test Push Subscription</h2>
        <button id="test-btn" onclick="testPush()" style="display:none;">Test Push Notification</button>
        <p id="subscription-check">Checking...</p>
    </div>

    <div class="card">
        <h2>Step 6: Manual Test</h2>
        <button onclick="showLocalNotification()">Show Test Notification</button>
        <p class="warning">This shows a local notification to test if notifications work at all</p>
    </div>

    <div class="card">
        <h2>Debug Log</h2>
        <pre id="log">Waiting...</pre>
    </div>

    <script>
        const log = [];
        function addLog(msg) {
            log.push(msg);
            document.getElementById('log').textContent = log.join('\n');
        }

        // Check browser support
        if (!('serviceWorker' in navigator)) {
            document.getElementById('support-check').innerHTML = '<span class="error">❌ Service Worker NOT supported</span>';
            addLog('Service Worker: NOT SUPPORTED');
        } else {
            document.getElementById('support-check').innerHTML = '<span class="success">✅ Service Worker supported</span>';
            addLog('Service Worker: Supported');
        }

        if (!('PushManager' in window)) {
            document.getElementById('support-check').innerHTML += '<br><span class="error">❌ Push API NOT supported</span>';
            addLog('Push API: NOT SUPPORTED');
        } else {
            document.getElementById('support-check').innerHTML += '<br><span class="success">✅ Push API supported</span>';
            addLog('Push API: Supported');
        }

        // Check notification permission
        if (!('Notification' in window)) {
            document.getElementById('permission-check').innerHTML = '<span class="error">❌ Notifications NOT supported</span>';
            addLog('Notification: NOT SUPPORTED');
        } else {
            const perm = Notification.permission;
            addLog('Notification permission: ' + perm);
            
            if (perm === 'granted') {
                document.getElementById('permission-check').innerHTML = '<span class="success">✅ Permission GRANTED</span>';
            } else if (perm === 'denied') {
                document.getElementById('permission-check').innerHTML = '<span class="error">❌ Permission DENIED - You blocked notifications!</span><br><button onclick="resetPermission()">Reset Permission</button>';
            } else {
                document.getElementById('permission-check').innerHTML = '<span class="warning">⚠️ Permission not asked yet</span><br><button onclick="requestPerm()">Request Permission</button>';
            }
        }

        // Check service worker
        navigator.serviceWorker.getRegistration().then(reg => {
            if (reg) {
                document.getElementById('sw-check').innerHTML = '<span class="success">✅ Service Worker registered</span>';
                addLog('Service Worker: Registered');
                
                // Check subscription
                reg.pushManager.getSubscription().then(sub => {
                    if (sub) {
                        document.getElementById('subscription-check').innerHTML = '<span class="success">✅ Push subscription exists</span>';
                        addLog('Subscription: EXISTS');
                        document.getElementById('test-btn').style.display = 'inline-block';
                    } else {
                        document.getElementById('subscription-check').innerHTML = '<span class="warning">⚠️ No subscription</span><br><button onclick="subscribe()">Create Subscription</button>';
                        addLog('Subscription: NOT EXISTS');
                    }
                });
            } else {
                document.getElementById('sw-check').innerHTML = '<span class="error">❌ Service Worker NOT registered</span><br><button onclick="registerSW()">Register Service Worker</button>';
                addLog('Service Worker: NOT REGISTERED');
            }
        });

        async function requestPerm() {
            addLog('Requesting permission...');
            const perm = await Notification.requestPermission();
            addLog('Permission result: ' + perm);
            location.reload();
        }

        async function registerSW() {
            addLog('Registering service worker...');
            try {
                const reg = await navigator.serviceWorker.register('/sw.js');
                addLog('Service worker registered!');
                location.reload();
            } catch (e) {
                addLog('Error: ' + e.message);
            }
        }

        async function subscribe() {
            addLog('Creating subscription...');
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array('BDSd6CxnFxXK3O3tWG7Ik90SrPwFVB0NDXK6bc2tJx6THXcSbL7mprKAO8tzpr9DY8fUXZaoamTx6cniZT5QwIc')
                });
                addLog('Subscription created!');
                addLog('Endpoint: ' + sub.endpoint.substring(0, 50) + '...');
                
                // Save to server
                const response = await fetch('/api/save_push_subscription.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        subscription: sub,
                        user_id: <?php echo $userId ? (int)$userId : 'null'; ?>
                    })
                });
                
                const result = await response.json();
                addLog('Server response: ' + JSON.stringify(result));
                
                if (result.success) {
                    document.getElementById('subscription-check').innerHTML = '<span class="success">✅ Subscription created and saved!</span>';
                    document.getElementById('test-btn').style.display = 'inline-block';
                } else {
                    document.getElementById('subscription-check').innerHTML = '<span class="error">❌ Failed to save: ' + result.error + '</span>';
                }
            } catch (e) {
                addLog('Error: ' + e.message);
                document.getElementById('subscription-check').innerHTML = '<span class="error">❌ Error: ' + e.message + '</span>';
            }
        }

        async function testPush() {
            addLog('Sending test notification...');
            try {
                const response = await fetch('/api/send_push_notification.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        user_id: <?php echo (int)$userId; ?>,
                        title: '🧪 Test Notification',
                        body: 'This is a test! If you see this, push notifications work!',
                        url: '/index.php'
                    })
                });
                const result = await response.json();
                addLog('Test result: ' + JSON.stringify(result));
            } catch (e) {
                addLog('Error: ' + e.message);
            }
        }

        async function showLocalNotification() {
            if ('serviceWorker' in navigator) {
                const reg = await navigator.serviceWorker.ready;
                reg.showNotification('🧪 Local Test', {
                    body: 'This is a local notification test',
                    icon: '/assets/images/rubiks.jpg'
                });
                addLog('Local notification shown');
            }
        }

        function resetPermission() {
            alert('Go to Chrome Settings → Site Settings → Notifications → Find von-barbershop.onrender.com → Change to "Ask"');
        }

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }
    </script>
</body>
</html>
