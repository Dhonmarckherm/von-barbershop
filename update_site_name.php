<?php
/**
 * Update site name in LIVE Render database
 * Run this ONCE to change "V.O.N Barbershop" to "V.O.N Barber Studio"
 */

// Use the same DB connection as your app
require 'config/db.php';

echo "Updating site name on LIVE database\n";
echo "===============================================\n\n";

try {
    // Update the site_name setting
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = 'V.O.N Barber Studio' WHERE setting_key = 'site_name'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "✓ SUCCESS! Site name updated to: V.O.N Barber Studio\n";
        echo "  All email templates will now use the correct name.\n\n";
        
        // Verify it was updated
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
        $stmt->execute();
        $setting = $stmt->fetch();
        
        echo "✓ VERIFIED: Current site name = " . $setting['setting_value'] . "\n";
    } else {
        echo "✗ No rows updated. Setting may not exist.\n";
        echo "  Trying to insert...\n";
        
        // Try inserting if it doesn't exist
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_name', 'V.O.N Barber Studio')");
        $stmt->execute();
        echo "✓ INSERTED: Site name created.\n";
    }
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}
