<?php
/**
 * Test Welcome Email
 * Visit this page to test if welcome emails are working
 */

require_once 'config/mailer.php';

// Test email - CHANGE THIS to your email
$testEmail = 'fayesomera31@gmail.com'; // Your Gmail
$testName = 'Test User';

echo "<h1>🧪 Testing Welcome Email</h1>";
echo "<p>Sending test email to: <strong>$testEmail</strong></p>";
echo "<hr>";

try {
    echo "<h3>Step 1: Checking Brevo API Key...</h3>";
    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
    
    if (!$brevoKey) {
        echo "<p style='color: red;'>❌ ERROR: Brevo API key not found!</p>";
        echo "<p>Please check your Render environment variables.</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Brevo API key found</p>";
    echo "<p>Key starts with: " . substr($brevoKey, 0, 10) . "...</p>";
    
    echo "<h3>Step 2: Sending Welcome Email...</h3>";
    
    $subject = 'Welcome to V.O.N Barber Studio! ✂️';
    $htmlContent = "
        <div style='font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; background: #000000; color: #f5f5f5;'>
            <div style='background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); padding: 40px 20px; text-align: center;'>
                <h1 style='color: #c0c0c0; font-family: Playfair Display, serif; font-size: 32px; margin: 0;'>Welcome to V.O.N Barber Studio!</h1>
                <p style='color: #f5f5f5; font-size: 18px; margin-top: 10px;'>Thank you for registering, $testName! ✂️</p>
            </div>
            <div style='padding: 30px 20px; background: #000000;'>
                <p style='font-size: 16px; line-height: 1.6;'>We're excited to have you join our community! You can now:</p>
                <ul style='font-size: 16px; line-height: 1.8;'>
                    <li>✅ Book appointments online</li>
                    <li>✅ Choose your preferred haircut style</li>
                    <li>✅ Select your location (shop or home service)</li>
                    <li>✅ Receive email confirmations</li>
                    <li>✅ Manage your bookings</li>
                </ul>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://von-barbershop.onrender.com/login.php' style='background: #c0c0c0; color: #000000; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block;'>Login Now</a>
                </div>
            </div>
        </div>
    ";
    
    echo "<p>Sending via Brevo API...</p>";
    
    $result = sendBrevoEmail($testEmail, $testName, $subject, $htmlContent);
    
    if ($result) {
        echo "<hr>";
        echo "<h2 style='color: green;'>✅ SUCCESS!</h2>";
        echo "<p><strong>Welcome email sent successfully to: $testEmail</strong></p>";
        echo "<p>Please check your Gmail inbox (and spam folder) within 1-2 minutes.</p>";
        echo "<hr>";
        echo "<h3>Debug Info:</h3>";
        echo "<ul>";
        echo "<li>API: Brevo HTTP API</li>";
        echo "<li>Sender: dhonmarck2004@gmail.com</li>";
        echo "<li>Recipient: $testEmail</li>";
        echo "<li>Subject: $subject</li>";
        echo "</ul>";
    } else {
        echo "<hr>";
        echo "<h2 style='color: red;'>❌ FAILED!</h2>";
        echo "<p>Welcome email was NOT sent.</p>";
        echo "<p>Check Render logs for detailed error messages.</p>";
    }
    
} catch (Exception $e) {
    echo "<hr>";
    echo "<h2 style='color: red;'>❌ EXCEPTION!</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
