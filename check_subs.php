<?php
/**
 * Check Push Subscriptions for a Specific User
 */
require_once 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Check Push Subscriptions</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #1a1a1a; color: #fff; }
        .card { background: #2d2d2d; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #444; }
        th { background: #333; }
    </style>
</head>
<body>
    <h1>🔍 Push Subscription Checker</h1>
";

try {
    // Show all subscriptions
    $stmt = $pdo->query("
        SELECT ps.id, ps.user_id, u.name, u.email, u.role, ps.endpoint, ps.created_at 
        FROM push_subscriptions ps 
        JOIN users u ON ps.user_id = u.id 
        ORDER BY ps.created_at DESC
    ");
    $subscriptions = $stmt->fetchAll();
    
    echo "<div class='card'>";
    echo "<h2>Total Subscriptions: " . count($subscriptions) . "</h2>";
    
    if (count($subscriptions) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>";
        foreach ($subscriptions as $sub) {
            echo "<tr>";
            echo "<td>{$sub['id']}</td>";
            echo "<td>{$sub['user_id']}</td>";
            echo "<td>{$sub['name']}</td>";
            echo "<td>{$sub['email']}</td>";
            echo "<td>{$sub['role']}</td>";
            echo "<td>{$sub['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ No subscriptions found!</p>";
    }
    echo "</div>";
    
    // Test sending a notification to user 35
    echo "<div class='card'>";
    echo "<h2>Test: Send Notification to User 35</h2>";
    
    $testUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/api/send_push_notification.php';
    $testData = json_encode([
        'user_id' => 35,
        'title' => '🧪 Test from Checker',
        'body' => 'This is a test notification from the subscription checker page',
        'url' => '/index.php'
    ]);
    
    echo "<p>Sending to: {$testUrl}</p>";
    echo "<p>Payload: " . htmlspecialchars($testData) . "</p>";
    
    $ch = curl_init($testUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: {$httpCode}</p>";
    echo "<p>Response: <pre>" . htmlspecialchars($response) . "</pre></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='card'>";
    echo "<h2 class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</h2>";
    echo "</div>";
}

echo "</body></html>";
