<?php
/**
 * LIVE Database Migration - Create push_subscriptions table
 * Access: https://von-barbershop.onrender.com/migrate_push_table.php
 * DELETE THIS FILE after running once!
 */

// WARNING: Remove this check after confirming it works, or restrict to admin only
// For now, anyone with the URL can run this (just once)

require_once 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Migration</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #1a1a1a; color: #fff; text-align: center; }
        .result { background: #2d2d2d; padding: 30px; margin: 20px auto; max-width: 600px; border-radius: 10px; }
        .success { color: #4CAF50; font-size: 24px; }
        .error { color: #f44336; }
        code { background: #000; padding: 10px; display: block; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔧 Push Subscriptions Table Migration</h1>
";

try {
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'push_subscriptions'");
    
    if ($stmt->rowCount() > 0) {
        echo "<div class='result'>";
        echo "<h2 class='success'>✅ Table Already Exists!</h2>";
        echo "<p>The push_subscriptions table is already created on the live database.</p>";
        echo "<p><strong>Push notifications should now work!</strong></p>";
        echo "</div>";
    } else {
        // Create the table
        $sql = "CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            endpoint TEXT NOT NULL,
            p256dh VARCHAR(255) NOT NULL,
            auth VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_endpoint (endpoint(100)),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        
        // Verify it was created
        $stmt = $pdo->query("SHOW TABLES LIKE 'push_subscriptions'");
        
        if ($stmt->rowCount() > 0) {
            echo "<div class='result'>";
            echo "<h2 class='success'>✅ SUCCESS! Table Created!</h2>";
            echo "<p>The push_subscriptions table has been created on the live database.</p>";
            echo "<p><strong>Push notifications are now ready!</strong></p>";
            echo "<hr style='border-color: #4CAF50; margin: 20px 0;'>";
            echo "<h3>Next Steps:</h3>";
            echo "<ol style='text-align: left;'>";
            echo "<li>Go back to <a href='/test_push.php' style='color: #C5A059;'>Test Push Page</a></li>";
            echo "<li>Click 'Test Push Notification' button</li>";
            echo "<li>You should see a notification popup!</li>";
            echo "</ol>";
            echo "<p style='color: #ff9800; margin-top: 20px;'>⚠️ IMPORTANT: Delete this file (migrate_push_table.php) after use for security!</p>";
            echo "</div>";
        } else {
            echo "<div class='result'>";
            echo "<h2 class='error'>❌ Failed to create table!</h2>";
            echo "<p>Please check the error logs.</p>";
            echo "</div>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div class='result'>";
    echo "<h2 class='error'>❌ Database Error</h2>";
    echo "<code>" . htmlspecialchars($e->getMessage()) . "</code>";
    echo "</div>";
}

echo "</body></html>";
