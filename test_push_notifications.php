<?php
/**
 * Push Notification Test & Diagnostics
 */
require_once 'config/db.php';
require_once 'includes/auth_check.php';

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Push Notification Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; max-width: 800px; margin: 0 auto; }
        .card { background: #2a2a2a; padding: 20px; border-radius: 10px; margin: 15px 0; }
        .success { border-left: 4px solid #4caf50; }
        .error { border-left: 4px solid #f44336; }
        .warning { border-left: 4px solid #ff9800; }
        button { padding: 12px 24px; background: #C5A059; color: #000; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; margin: 5px; }
        button:hover { background: #D4AF61; }
        .log { background: #000; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>🔔 Push Notification Test</h1>
    
    <div class='card'>
        <h2>User Info</h2>
        <p><strong>User ID:</strong> $userId</p>
        <p><strong>Role:</strong> $userRole</p>
    </div>";

// Check subscriptions
$stmt = $pdo->prepare("SELECT id, endpoint, p256dh, auth, created_at FROM push_subscriptions WHERE user_id = ?");
$stmt->execute([$userId]);
$subscriptions = $stmt->fetchAll();

echo "<div class='card'>";
echo "<h2>Push Subscriptions (" . count($subscriptions) . ")</h2>";

if (empty($subscriptions)) {
    echo "<p class='error'>❌ No subscriptions found for user $userId</p>";
    echo "<button onclick='subscribe()'>🔄 Force Resubscribe</button>";
} else {
    foreach ($subscriptions as $i => $sub) {
        echo "<div class='card success'>";
        echo "<p><strong>Subscription #" . ($i+1) . "</strong></p>";
        echo "<p>Created: " . $sub['created_at'] . "</p>";
        echo "<p>Endpoint: " . substr($sub['endpoint'], 0, 80) . "...</p>";
        echo "<p>p256dh: " . substr($sub['p256dh'], 0, 40) . "...</p>";
        echo "<p>auth: " . substr($sub['auth'], 0, 40) . "...</p>";
        echo "</div>";
    }
    echo "<button onclick='testPush()'>🔔 Test Push Notification</button>";
    echo "<button onclick='deleteAndResubscribe()'>🗑️ Delete & Resubscribe</button>";
}

echo "</div>";

echo "<div class='card'>
    <h2>Test Log</h2>
    <div id='log' class='log'>Ready...</div>
</div>";

echo "<script>
const logDiv = document.getElementById('log');
const userId = $userId;

function log(msg, type = 'info') {
    const colors = { info: '#fff', success: '#4caf50', error: '#f44336', warning: '#ff9800' };
    logDiv.innerHTML += '\n<span style=\"color:' + colors[type] + '\">' + new Date().toLocaleTimeString() + ' - ' + msg + '</span>';
    logDiv.scrollTop = logDiv.scrollHeight;
}

async function subscribe() {
    log('Starting subscription...', 'warning');
    
    try {
        const reg = await navigator.serviceWorker.ready;
        log('Service Worker ready', 'success');
        
        // Delete existing
        const existing = await reg.pushManager.getSubscription();
        if (existing) {
            log('Deleting old subscription...', 'warning');
            await existing.unsubscribe();
        }
        
        log('Requesting push permission...', 'warning');
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: 'BDSd6CxnFxXK3O3tWG7Ik90SrPwFVB0NDXK6bc2tJx6THXcSbL7mprKAO8tzpr9DY8fUXZaoamTx6cniZT5QwIc'
        });
        
        log('Subscription created!', 'success');
        log('Endpoint: ' + sub.endpoint.substring(0, 80) + '...', 'info');
        
        const response = await fetch('/api/save_push_subscription.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subscription: sub, user_id: userId })
        });
        
        const result = await response.json();
        log('Save response: ' + JSON.stringify(result), result.success ? 'success' : 'error');
        
        if (result.success) {
            location.reload();
        }
    } catch (err) {
        log('Error: ' + err.message, 'error');
    }
}

async function deleteAndResubscribe() {
    log('Deleting subscription...', 'warning');
    const reg = await navigator.serviceWorker.ready;
    const existing = await reg.pushManager.getSubscription();
    if (existing) {
        await existing.unsubscribe();
        log('Subscription deleted', 'success');
    }
    
    log('Sending delete request to server...', 'warning');
    const response = await fetch('/api/delete_push_subscription.php?user_id=' + userId);
    const result = await response.json();
    log('Delete response: ' + JSON.stringify(result), 'info');
    
    setTimeout(() => subscribe(), 1000);
}

async function testPush() {
    log('Sending test push notification...', 'warning');
    
    try {
        const response = await fetch('/api/send_push_notification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: userId,
                title: '🧪 Test Notification',
                body: 'This is a test notification. If you see this, push notifications are working!',
                url: '/my_appointments.php'
            })
        });
        
        const result = await response.json();
        log('Response: ' + JSON.stringify(result), result.success ? 'success' : 'error');
        
        if (result.success && result.sent > 0) {
            log('✅ Push notification sent successfully!', 'success');
        } else {
            log('❌ Failed to send push notification', 'error');
        }
    } catch (err) {
        log('Error: ' + err.message, 'error');
    }
}
</script>";

echo "</body></html>";
