<?php
require_once 'config/db.php';
require_once 'config/mailer.php';

echo "=== TESTING EMAIL NOTIFICATIONS ===\n\n";

// Test 1: Send acceptance email to customer
echo "1. Sending acceptance email to fayesomera31@gmail.com...\n";
try {
    $result = sendAcceptanceEmail(
        'fayesomera31@gmail.com',
        'Faye',
        [
            'service_name' => 'adasdsd',
            'location' => 'asfafggagf',
            'date' => '2026-04-30',
            'time' => '9:00 AM',
            'customer_name' => 'Faye',
            'customer_email' => 'fayesomera31@gmail.com'
        ]
    );
    echo $result ? "✅ SUCCESS - Customer email sent!\n\n" : "❌ FAILED\n\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

// Test 2: Send notification to barber
echo "2. Sending notification to barber (dhonmarck2004@gmail.com)...\n";
try {
    $mail = getMailer();
    $mail->addAddress('dhonmarck2004@gmail.com', 'Barber');
    $mail->isHTML(true);
    $mail->Subject = 'Test - Appointment Accepted';
    $mail->Body = '<h2>Test Email</h2><p>This is a test notification for the barber.</p>';
    $result = $mail->send();
    echo $result ? "✅ SUCCESS - Barber email sent!\n\n" : "❌ FAILED\n\n";
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
}

echo "=== TEST COMPLETE ===\n";
echo "Check both Gmail inboxes now!\n";
