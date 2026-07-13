<?php
/**
 * Payment System Database Migration
 * 
 * Run this ONCE on your production server to add payment tables
 * Access via: https://von-barbershop.onrender.com/run_payment_migration.php
 */

// Load database configuration
require_once 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Payment System Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #000; color: #fff; }
        .success { background: rgba(40,167,69,0.2); border-left: 4px solid #28a745; padding: 15px; margin: 10px 0; }
        .error { background: rgba(220,53,69,0.2); border-left: 4px solid #dc3545; padding: 15px; margin: 10px 0; }
        .info { background: rgba(0,123,255,0.2); border-left: 4px solid #007bff; padding: 15px; margin: 10px 0; }
        h1 { color: #28a745; }
        h2 { color: #007bff; margin-top: 30px; }
        code { background: #1a1a1a; padding: 2px 8px; border-radius: 4px; }
    </style>
</head>
<body>
<h1>🚀 Payment System Database Migration</h1>
<p>Running migration to add payment system tables...</p>
<hr>
";

try {
    // Check if migration already run
    echo "<h2>Step 1: Checking if payment columns exist...</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM appointments LIKE 'payment_status'");
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        echo "<div class='info'>ℹ️ Payment columns already exist! Migration may have already been run.</div>";
    } else {
        echo "<div class='success'>✅ Payment columns not found. Proceeding with migration...</div>";
    }
    
    // Add payment columns to appointments table
    echo "<h2>Step 2: Adding payment columns to appointments table...</h2>";
    $sql1 = "
        ALTER TABLE appointments 
        ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','verified','rejected') DEFAULT 'pending',
        ADD COLUMN IF NOT EXISTS payment_proof VARCHAR(255) DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS downpayment_amount DECIMAL(10,2) DEFAULT 50.00,
        ADD COLUMN IF NOT EXISTS balance_amount DECIMAL(10,2) DEFAULT 0.00,
        ADD COLUMN IF NOT EXISTS payment_verified_at TIMESTAMP NULL
    ";
    
    $pdo->exec($sql1);
    echo "<div class='success'>✅ Successfully added payment columns to appointments table!</div>";
    
    // Create payment_logs table
    echo "<h2>Step 3: Creating payment_logs table...</h2>";
    $sql2 = "
        CREATE TABLE IF NOT EXISTS payment_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            appointment_id INT NOT NULL,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) DEFAULT 'GCash',
            status ENUM('pending','verified','rejected') DEFAULT 'pending',
            proof_filename VARCHAR(255) DEFAULT NULL,
            admin_notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            verified_at TIMESTAMP NULL,
            verified_by INT DEFAULT NULL,
            FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ";
    
    $pdo->exec($sql2);
    echo "<div class='success'>✅ Successfully created payment_logs table!</div>";
    
    // Verify migration
    echo "<h2>Step 4: Verifying migration...</h2>";
    $stmt = $pdo->query("SHOW COLUMNS FROM appointments LIKE 'payment_status'");
    $verify1 = $stmt->fetch();
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'payment_logs'");
    $verify2 = $stmt->fetch();
    
    if ($verify1 && $verify2) {
        echo "<div class='success'>
            <strong>✅ MIGRATION COMPLETE!</strong><br><br>
            Payment system is now installed!<br><br>
            You can now:<br>
            • Accept ₱50 downpayments via GCash (0969-055-8227)<br>
            • View payment uploads in Admin → Payments<br>
            • Approve/reject payments<br>
            • Send payment instructions via email
        </div>";
    } else {
        echo "<div class='error'>❌ Verification failed. Please check manually.</div>";
    }
    
    echo "<hr>
    <h2>🎉 Next Steps:</h2>
    <ol>
        <li>Delete this file (run_payment_migration.php) for security</li>
        <li>Test the payment system by booking an appointment</li>
        <li>Upload a payment proof</li>
        <li>Go to Admin → Payments → Approve</li>
    </ol>
    
    <p style='color: #dc3545; font-weight: bold;'>
        ⚠️ IMPORTANT: Delete this file after running migration!
    </p>";
    
} catch (PDOException $e) {
    echo "<div class='error'>
        <strong>❌ ERROR:</strong><br>
        " . htmlspecialchars($e->getMessage()) . "
    </div>
    <div class='info'>
        <strong>Common Solutions:</strong><br>
        • Check if you have database access<br>
        • Verify database credentials in Render Environment variables<br>
        • Contact support if issue persists
    </div>";
}

echo "</body></html>";
exit;
