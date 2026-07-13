<?php
/**
 * Fix payment_proof column to support base64 images
 * Changes from VARCHAR(255) to LONGTEXT
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Payment Proof Column</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #28a745; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border-left: 4px solid #dc3545; margin: 10px 0; }
        .info { color: #007bff; padding: 10px; background: #d1ecf1; border-left: 4px solid #007bff; margin: 10px 0; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Fix Payment Proof Column</h1>
";

try {
    // Check current column type
    echo "<p>Checking current column type...</p>";
    $stmt = $pdo->query("SHOW COLUMNS FROM appointments LIKE 'payment_proof'");
    $column = $stmt->fetch();
    
    if (!$column) {
        echo "<div class='error'>❌ Column 'payment_proof' does not exist!</div>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<div class='info'>Current column type: <strong>" . $column['Type'] . "</strong></div>";
    
    if ($column['Type'] === 'longtext') {
        echo "<div class='success'>✅ Column is already LONGTEXT - no changes needed!</div>";
    } else {
        echo "<p>Changing column type from VARCHAR(255) to LONGTEXT...</p>";
        
        // Change column type to LONGTEXT
        $pdo->exec("ALTER TABLE appointments MODIFY COLUMN payment_proof LONGTEXT");
        
        echo "<div class='success'>✅ Column type changed to LONGTEXT successfully!</div>";
        echo "<div class='info'>Base64 images can now be stored (up to 4GB per image)</div>";
    }
    
    // Check if there are any existing base64 records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE payment_proof LIKE 'data:%'");
    $base64_count = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE payment_proof IS NOT NULL AND payment_proof NOT LIKE 'data:%'");
    $old_count = $stmt->fetch()['count'];
    
    echo "<div class='info'>";
    echo "<strong>Current payment records:</strong><br>";
    echo "• Base64 images: {$base64_count}<br>";
    echo "• Old file-based: {$old_count}<br>";
    echo "</div>";
    
    if ($old_count > 0) {
        echo "<div class='info'>Note: {$old_count} old file-based records will be auto-cleaned when you visit the admin payments page.</div>";
    }
    
    echo "<div class='success'>";
    echo "<strong>✅ Fix complete!</strong><br>";
    echo "New payment uploads will now persist after refresh.<br>";
    echo "<a href='admin_payments.php'>← Back to Admin Payments</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ ERROR: " . $e->getMessage() . "</div>";
}

echo "</div></body></html>";
?>
