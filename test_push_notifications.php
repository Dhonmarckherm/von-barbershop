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

echo "<script>";
echo "const logDiv = document.getElementById('log');";
echo "const userId = " . (int)$userId . ";";

echo "function log(msg, type) {";
echo "  type = type || 'info';";
echo "  var colors = { info: '#fff', success: '#4caf50', error: '#f44336', warning: '#ff9800' };";
echo "  logDiv.innerHTML += '\\n<span style=\\\"color:' + colors[type] + '\\\">' + new Date().toLocaleTimeString() + ' - ' + msg + '</span>';";
echo "  logDiv.scrollTop = logDiv.scrollHeight;";
echo "}";

echo "async function subscribe() {";
echo "  log('Starting subscription...', 'warning');";
echo "  try {";
echo "    var reg = await navigator.serviceWorker.ready;";
echo "    log('Service Worker ready', 'success');";
echo "    var existing = await reg.pushManager.getSubscription();";
echo "    if (existing) {";
echo "      log('Deleting old subscription...', 'warning');";
echo "      await existing.unsubscribe();";
echo "    }";
echo "    log('Requesting push permission...', 'warning');";
echo "    var sub = await reg.pushManager.subscribe({";
echo "      userVisibleOnly: true,";
echo "      applicationServerKey: 'BDSd6CxnFxXK3O3tWG7Ik90SrPwFVB0NDXK6bc2tJx6THXcSbL7mprKAO8tzpr9DY8fUXZaoamTx6cniZT5QwIc'";
echo "    });";
echo "    log('Subscription created!', 'success');";
echo "    log('Endpoint: ' + sub.endpoint.substring(0, 80) + '...', 'info');";
echo "    var response = await fetch('/api/save_push_subscription.php', {";
echo "      method: 'POST',";
echo "      headers: { 'Content-Type': 'application/json' },";
echo "      body: JSON.stringify({ subscription: sub, user_id: userId })";
echo "    });";
echo "    var result = await response.json();";
echo "    log('Save response: ' + JSON.stringify(result), result.success ? 'success' : 'error');";
echo "    if (result.success) { location.reload(); }";
echo "  } catch (err) {";
echo "    log('Error: ' + err.message, 'error');";
echo "  }";
echo "}";

echo "async function deleteAndResubscribe() {";
echo "  log('Deleting subscription...', 'warning');";
echo "  var reg = await navigator.serviceWorker.ready;";
echo "  var existing = await reg.pushManager.getSubscription();";
echo "  if (existing) {";
echo "    await existing.unsubscribe();";
echo "    log('Subscription deleted', 'success');";
echo "  }";
echo "  log('Sending delete request to server...', 'warning');";
echo "  var response = await fetch('/api/delete_push_subscription.php?user_id=' + userId);";
echo "  var result = await response.json();";
echo "  log('Delete response: ' + JSON.stringify(result), 'info');";
echo "  setTimeout(function() { subscribe(); }, 1000);";
echo "}";

echo "async function testPush() {";
echo "  log('Sending test push notification...', 'warning');";
echo "  try {";
echo "    var response = await fetch('/api/send_push_notification.php', {";
echo "      method: 'POST',";
echo "      headers: { 'Content-Type': 'application/json' },";
echo "      body: JSON.stringify({";
echo "        user_id: userId,";
echo "        title: '🧪 Test Notification',";
echo "        body: 'This is a test notification. If you see this, push notifications are working!',";
echo "        url: '/my_appointments.php'";
echo "      })";
echo "    });";
echo "    var result = await response.json();";
echo "    log('Response: ' + JSON.stringify(result), result.success ? 'success' : 'error');";
echo "    if (result.success && result.sent > 0) {";
echo "      log('Push notification sent successfully!', 'success');";
echo "    } else {";
echo "      log('Failed to send push notification', 'error');";
echo "    }";
echo "  } catch (err) {";
echo "    log('Error: ' + err.message, 'error');";
echo "  }";
echo "}";

echo "</script>";

echo "</body></html>";
