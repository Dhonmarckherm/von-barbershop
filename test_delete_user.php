<?php
/**
 * Test script to diagnose user deletion issues
 */

require_once 'config/db.php';
require_once 'config/session.php';
initializeSession();

echo "=== User Deletion Diagnostic Test ===\n\n";

// Check if admin is logged in
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'barber')) {
    echo "❌ ERROR: Not logged in as admin/barber\n";
    echo "Current role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
    echo "Please login as admin first.\n";
    exit(1);
}

echo "✅ Admin logged in: " . $_SESSION['name'] . " (ID: " . $_SESSION['user_id'] . ")\n\n";

// List all users
echo "=== Current Users ===\n";
$stmt = $pdo->query("SELECT id, name, email, role FROM users ORDER BY id");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    $current = ($user['id'] == $_SESSION['user_id']) ? " [YOU]" : "";
    echo "ID: {$user['id']} | {$user['name']} | {$user['email']} | {$user['role']}{$current}\n";
}

echo "\n=== Testing Delete API ===\n";

// Find a test user to delete (not the current admin)
$testUser = null;
foreach ($users as $user) {
    if ($user['id'] != $_SESSION['user_id']) {
        $testUser = $user;
        break;
    }
}

if (!$testUser) {
    echo "❌ No test users available to delete (only you exist in database)\n";
    exit(1);
}

echo "\nAttempting to delete user: {$testUser['name']} (ID: {$testUser['id']})\n\n";

// Simulate the delete API call
$userId = $testUser['id'];

echo "Step 1: Checking if user exists...\n";
$stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    echo "❌ User not found\n";
    exit(1);
}
echo "✅ User found: {$user['name']}\n\n";

echo "Step 2: Checking appointments for this user...\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM appointments WHERE user_id = ?");
$stmt->execute([$userId]);
$appointmentCount = $stmt->fetch()['count'];
echo "📊 Found {$appointmentCount} appointment(s)\n\n";

echo "Step 3: Attempting to delete user...\n";
try {
    $pdo->beginTransaction();
    echo "  - Transaction started\n";
    
    // Delete appointments
    $stmt = $pdo->prepare("DELETE FROM appointments WHERE user_id = ?");
    $stmt->execute([$userId]);
    $deletedAppointments = $stmt->rowCount();
    echo "  - Deleted {$deletedAppointments} appointment(s)\n";
    
    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $deletedUsers = $stmt->rowCount();
    echo "  - Deleted {$deletedUsers} user(s)\n";
    
    $pdo->commit();
    echo "  - Transaction committed\n\n";
    
    echo "✅ SUCCESS! User deleted successfully\n";
    echo "   - {$deletedAppointments} appointments removed\n";
    echo "   - {$deletedUsers} user removed\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ FAILED to delete user\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "=== Verification ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE id = ?");
$stmt->execute([$userId]);
$count = $stmt->fetch()['count'];

if ($count == 0) {
    echo "✅ User confirmed deleted from database\n";
} else {
    echo "❌ User still exists in database!\n";
}

echo "\n=== Test Complete ===\n";
