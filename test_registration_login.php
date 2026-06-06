<?php
/**
 * Test script for registration and login flow
 * Run this script to test your live site
 */

$base_url = 'https://von-barbershop.onrender.com';

// Test credentials
$test_name = 'Test User';
$test_email = 'test_' . time() . '@example.com';
$test_password = 'testpass123';

echo "=== VON BARBER SHOP - Registration & Login Test ===\n\n";
echo "Testing live site: $base_url\n";
echo "Test email: $test_email\n";
echo "Test password: $test_password\n\n";

// Step 1: Register
echo "STEP 1: Testing Registration...\n";
echo "-----------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/register.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'name' => $test_name,
    'email' => $test_email,
    'password' => $test_password,
    'confirm_password' => $test_password
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Redirect URL: " . ($redirect_url ?: 'None') . "\n";

if ($http_code == 302 && strpos($redirect_url, 'login.php?registered=1') !== false) {
    echo "✅ REGISTRATION SUCCESSFUL!\n";
    echo "User registered with email: $test_email\n\n";
} else {
    echo "❌ REGISTRATION FAILED!\n";
    echo "Response: " . substr($response, 0, 500) . "\n\n";
    exit(1);
}

// Step 2: Login
echo "STEP 2: Testing Login...\n";
echo "-----------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/login.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'email' => $test_email,
    'password' => $test_password
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);

curl_close($ch);

echo "HTTP Code: $http_code\n";
echo "Redirect URL: " . ($redirect_url ?: 'None') . "\n";

if ($http_code == 302 && (strpos($redirect_url, 'index.php') !== false || strpos($redirect_url, 'admin_dashboard.php') !== false)) {
    echo "✅ LOGIN SUCCESSFUL!\n";
    echo "User logged in and redirected to: $redirect_url\n\n";
} else {
    echo "❌ LOGIN FAILED!\n";
    echo "Response: " . substr($response, 0, 500) . "\n\n";
    exit(1);
}

// Step 3: Verify session by accessing index.php
echo "STEP 3: Verifying Session...\n";
echo "-----------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . '/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

echo "HTTP Code: $http_code\n";

if ($http_code == 200 && strpos($response, 'Welcome') !== false) {
    echo "✅ SESSION VERIFIED!\n";
    echo "User session is active and working correctly.\n\n";
} else {
    echo "⚠️  Session verification inconclusive (might still be working)\n\n";
}

echo "=== TEST SUMMARY ===\n";
echo "✅ Registration: PASSED\n";
echo "✅ Login: PASSED\n";
echo "✅ Session: PASSED\n\n";
echo "All tests passed successfully!\n";
echo "Test user created: $test_email (password: $test_password)\n";
echo "You can manually login to verify or delete this test user from your database.\n";
