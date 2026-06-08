<?php
/**
 * Create push_subscriptions table on LIVE database
 * Run this ONCE: php create_push_table_live.php
 */

// Load live database config
require_once 'config/db.production.php';

echo "Creating push_subscriptions table on LIVE database...\n\n";

try {
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
    
    echo "✅ Table 'push_subscriptions' created successfully on LIVE database!\n";
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'push_subscriptions'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verified - ready for push notifications!\n";
    } else {
        echo "❌ Table creation failed!\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Migration complete! Push notifications will now work on production.\n";
