<?php
/**
 * Add password reset columns to users table
 * Run this ONCE to fix the password reset functionality
 */
require_once __DIR__ . '/config/db.php';

try {
    echo "Adding password reset columns to users table...\n\n";
    
    // Check if columns already exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token'");
    $tokenExists = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token_expires'");
    $expiresExists = $stmt->fetch();
    
    if ($tokenExists && $expiresExists) {
        echo "✅ Columns already exist! No migration needed.\n";
        exit;
    }
    
    // Add reset_token column if it doesn't exist
    if (!$tokenExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL DEFAULT NULL");
        echo "✅ Added reset_token column\n";
    }
    
    // Add reset_token_expires column if it doesn't exist
    if (!$expiresExists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL DEFAULT NULL");
        echo "✅ Added reset_token_expires column\n";
    }
    
    // Add index for faster lookups
    try {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_reset_token (reset_token)");
        echo "✅ Added index on reset_token\n";
    } catch (PDOException $e) {
        // Index might already exist
        echo "ℹ️  Index already exists (skipped)\n";
    }
    
    echo "\n✅ Migration complete! Password reset should now work.\n";
    
    // Verify columns exist
    echo "\nVerifying columns:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token%'");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
