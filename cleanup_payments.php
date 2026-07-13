<?php
/**
 * Clean up old payment records that have file-based images
 * Run this ONCE to remove old payments that won't work with base64 system
 */

require_once 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Clean Up Old Payment Records</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #000; color: #fff; }
        .success { background: rgba(40,167,69,0.2); border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: rgba(220,53,69,0.2); border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .info { background: rgba(0,123,255,0.2); border-left: 4px solid #007bff; padding: 15px; margin: 10px 0; }
        h1 { color: #28a745; }
        h2 { color: #007bff; margin-top: 30px; }
    </style>
</head>
<body>
<h1>🧹 Clean Up Old Payment Records</h1>
<p>Removing old payment records that have file-based images (not base64)...</p>
<hr>
";

try {
    // Count old payments
    echo "<h2>Step 1: Checking for old payment records...</h2>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE payment_proof IS NOT NULL 
        AND payment_proof NOT LIKE 'data:%'
    ");
    $old_count = $stmt->fetch()['count'];
    
    if ($old_count == 0) {
        echo "<div class='success'>✅ No old payment records found! All payments are using base64.</div>";
    } else {
        echo "<div class='info'>ℹ️ Found $old_count old payment record(s) with file-based images.</div>";
        
        // Delete old payment logs
        echo "<h2>Step 2: Deleting old payment logs...</h2>";
        $stmt = $pdo->prepare("
            DELETE FROM payment_logs 
            WHERE appointment_id IN (
                SELECT id FROM appointments 
                WHERE payment_proof IS NOT NULL 
                AND payment_proof NOT LIKE 'data:%'
            )
        ");
        $stmt->execute();
        echo "<div class='success'>✅ Deleted old payment logs.</div>";
        
        // Clear old payment_proof from appointments
        echo "<h2>Step 3: Clearing old payment_proof from appointments...</h2>";
        $stmt = $pdo->prepare("
            UPDATE appointments 
            SET payment_proof = NULL, payment_status = 'pending'
            WHERE payment_proof IS NOT NULL 
            AND payment_proof NOT LIKE 'data:%'
        ");
        $stmt->execute();
        echo "<div class='success'>✅ Cleared old payment_proof data.</div>";
        
        echo "<div class='success'>
            <strong>✅ CLEANUP COMPLETE!</strong><br><br>
            Removed $old_count old payment record(s).<br><br>
            Customers will need to re-upload their payment proofs using the new base64 system.
        </div>";
    }
    
    // Show current status
    echo "<h2>📊 Current Status:</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE payment_proof IS NOT NULL AND payment_proof LIKE 'data:%'");
    $base64_count = $stmt->fetch()['count'];
    
    echo "<div class='info'>
        <strong>Current Payment Records:</strong><br>
        • Base64 payments (working): $base64_count<br>
        • File-based payments (old): 0 (cleaned up)
    </div>";
    
    echo "<hr>
    <h2>🎉 Next Steps:</h2>
    <ol>
        <li>Delete this file (cleanup_payments.php) for security</li>
        <li>Test by uploading a new payment proof</li>
        <li>View it in Admin → Payments</li>
    </ol>";
    
} catch (PDOException $e) {
    echo "<div class='error'>
        <strong>❌ ERROR:</strong><br>
        " . htmlspecialchars($e->getMessage()) . "
    </div>";
}

echo "</body></html>";
exit;
