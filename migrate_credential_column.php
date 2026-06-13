<?php
/**
 * Migrate credential_id column from VARCHAR(255) to TEXT
 * Run this ONCE to fix the column size
 */
require_once __DIR__ . '/config/db.php';

try {
    // Alter the column to TEXT
    $pdo->exec("ALTER TABLE user_passkeys MODIFY COLUMN credential_id TEXT NOT NULL");
    
    echo "✅ Successfully migrated credential_id column to TEXT\n";
    
    // Verify the change
    $stmt = $pdo->query("SHOW COLUMNS FROM user_passkeys LIKE 'credential_id'");
    $column = $stmt->fetch();
    
    echo "Column type is now: " . $column['Type'] . "\n";
    
    // Show existing credentials
    $stmt = $pdo->query("SELECT id, user_id, LENGTH(credential_id) as cred_length, SUBSTRING(credential_id, 1, 50) as preview FROM user_passkeys");
    $creds = $stmt->fetchAll();
    
    echo "\nExisting credentials:\n";
    foreach ($creds as $cred) {
        echo "ID: {$cred['id']}, User: {$cred['user_id']}, Length: {$cred['cred_length']}, Preview: {$cred['preview']}...\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
