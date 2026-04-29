<?php
/**
 * Live Email Test - Run this on Render to test email configuration
 */

require_once 'config/db.php';
require_once 'config/mailer.php';

echo "<h1>Email Configuration Test</h1>";

echo "<h2>Current Configuration:</h2>";
echo "<ul>";
echo "<li><strong>MAIL_USERNAME from env:</strong> " . getenv('MAIL_USERNAME') . "</li>";
echo "<li><strong>MAIL_PASSWORD from env:</strong> " . (getenv('MAIL_PASSWORD') ? 'SET (length: ' . strlen(getenv('MAIL_PASSWORD')) . ')' : 'NOT SET') . "</li>";
echo "<li><strong>Fallback Username:</strong> dhonmarck2004@gmail.com</li>";
echo "<li><strong>Fallback Password:</strong> glqypadiqiqidsgb</li>";
echo "</ul>";

echo "<h2>Testing Email Send:</h2>";

try {
    $mail = getMailer();
    echo "<p style='color:green'>✓ Mailer created successfully</p>";
    echo "<p>Username: " . htmlspecialchars($mail->Username) . "</p>";
    
    // Try sending a test email
    $mail->addAddress('dhonmarck2004@gmail.com', 'Test');
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from V.O.N Barbershop';
    $mail->Body = '<h1>Test</h1><p>If you see this, email is working!</p>';
    
    echo "<p>Sending test email...</p>";
    $result = $mail->send();
    
    if ($result) {
        echo "<p style='color:green;font-size:20px'><strong>✓ SUCCESS! Email sent!</strong></p>";
        echo "<p>Check your inbox at dhonmarck2004@gmail.com</p>";
    } else {
        echo "<p style='color:red'><strong>✗ FAILED: Email not sent</strong></p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'><strong>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
}

echo "<hr>";
echo "<h2>What to do if it fails:</h2>";
echo "<ol>";
echo "<li>Check Render Environment Variables at: <a href='https://dashboard.render.com' target='_blank'>Render Dashboard</a></li>";
echo "<li>Ensure MAIL_USERNAME = dhonmarck2004@gmail.com</li>";
echo "<li>Ensure MAIL_PASSWORD = glqypadiqiqidsgb (NO spaces)</li>";
echo "<li>Save changes and wait for redeploy (~3 minutes)</li>";
echo "<li>Refresh this page to test again</li>";
echo "</ol>";
