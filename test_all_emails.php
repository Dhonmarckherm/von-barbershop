<?php
/**
 * Email Notification Test Script
 * Tests all email notification types: booking, acceptance, cancellation, reschedule
 */

require_once 'config/db.php';
require_once 'config/mailer.php';

echo "=== EMAIL NOTIFICATION TEST ===\n\n";

// Test 1: Check mailer configuration
echo "Test 1: Checking mailer configuration...\n";
try {
    $mail = getMailer();
    echo "✓ Mailer created successfully\n";
    echo "  Username: " . $mail->Username . "\n";
    echo "  Host: " . $mail->Host . "\n";
    echo "  Port: " . $mail->Port . "\n\n";
} catch (Exception $e) {
    echo "✗ Mailer failed: " . $e->getMessage() . "\n";
    exit;
}

// Fetch test customer
$stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'customer' LIMIT 1");
$customer = $stmt->fetch();

if (!$customer) {
    echo "✗ No customer found in database to test with\n";
    exit;
}

echo "Test Customer: {$customer['name']} <{$customer['email']}>\n\n";

// Test 2: Send acceptance email
echo "Test 2: Sending acceptance email...\n";
$acceptDetails = [
    'customer_name'  => $customer['name'],
    'customer_email' => $customer['email'],
    'service_name'   => 'Haircut Test',
    'location'       => 'Test Location',
    'date'           => date('Y-m-d', strtotime('+1 day')),
    'time'           => '10:00 AM',
];

try {
    $result = sendAcceptanceEmail($customer['email'], $customer['name'], $acceptDetails);
    echo $result ? "✓ Acceptance email SENT\n\n" : "✗ Acceptance email FAILED\n\n";
} catch (Exception $e) {
    echo "✗ Acceptance email ERROR: " . $e->getMessage() . "\n\n";
}

// Test 3: Send cancellation email
echo "Test 3: Sending cancellation email...\n";
try {
    $result = sendCancellationEmail($customer['email'], $customer['name'], $acceptDetails);
    echo $result ? "✓ Cancellation email SENT\n\n" : "✗ Cancellation email FAILED\n\n";
} catch (Exception $e) {
    echo "✗ Cancellation email ERROR: " . $e->getMessage() . "\n\n";
}

// Test 4: Send reschedule email
echo "Test 4: Sending reschedule email...\n";
$rescheduleDetails = [
    'customer_name'  => $customer['name'],
    'customer_email' => $customer['email'],
    'service_name'   => 'Haircut Test',
    'location'       => 'Test Location',
    'date'           => date('Y-m-d', strtotime('+2 days')),
    'time'           => '2:00 PM',
    'old_date'       => date('Y-m-d', strtotime('+1 day')),
    'old_time'       => '10:00 AM',
];

try {
    $result = sendRescheduleEmail($customer['email'], $customer['name'], $rescheduleDetails);
    echo $result ? "✓ Reschedule email SENT\n\n" : "✗ Reschedule email FAILED\n\n";
} catch (Exception $e) {
    echo "✗ Reschedule email ERROR: " . $e->getMessage() . "\n\n";
}

// Test 5: Send booking emails (customer + admin)
echo "Test 5: Sending booking notification emails...\n";
$bookingDetails = [
    'service_name'   => 'Fade Haircut',
    'date'           => date('Y-m-d', strtotime('+3 days')),
    'time'           => '3:00 PM',
    'location'       => 'San Juan, Candon City',
];

// Get admin/barber email
$stmt = $pdo->query("SELECT email FROM users WHERE role IN ('admin', 'barber') ORDER BY id ASC LIMIT 1");
$admin = $stmt->fetch();
$adminEmail = $admin ? $admin['email'] : 'dhondump@gmail.com';

try {
    $result = sendBookingEmails($customer['email'], $customer['name'], $bookingDetails, $adminEmail);
    echo $result ? "✓ Booking emails SENT (to customer AND barber)\n\n" : "✗ Booking emails FAILED\n\n";
} catch (Exception $e) {
    echo "✗ Booking emails ERROR: " . $e->getMessage() . "\n\n";
}

echo "=== TEST COMPLETE ===\n";
echo "Check these email inboxes:\n";
echo "1. Customer: {$customer['email']}\n";
echo "2. Barber/Admin: {$adminEmail}\n\n";
echo "You should have received:\n";
echo "- 1 Acceptance email\n";
echo "- 1 Cancellation email\n";
echo "- 1 Reschedule email\n";
echo "- 1 Booking confirmation (customer)\n";
echo "- 1 Booking notification (barber)\n";
