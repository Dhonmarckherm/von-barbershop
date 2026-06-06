<?php
/**
 * Test login against LIVE database
 * This connects to the same Aiven database that Render uses
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== LIVE DATABASE LOGIN TEST ===\n\n";

// Connect to LIVE database (same as Render)
$host = 'mysql-3f546165-dhonmarck2004-dc4f.c.aivencloud.com';
$port = '12138';
$dbname = 'defaultdb';
$user = 'avnadmin';
$password = ''; // Will need actual password

echo "Attempting to connect to live database...\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Database: $dbname\n";
echo "User: $user\n\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Connected to live database!\n\n";
    
    // Test email and password
    $testEmail = 'dhondump@gmail.com';
    $testPassword = 'lalala123';
    
    echo "Testing login for: $testEmail\n";
    echo "Password: $testPassword\n\n";
    
    // Fetch user
    $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->execute([$testEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ User NOT FOUND in database!\n";
        exit;
    }
    
    echo "✅ User found:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Name: {$user['name']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Role: {$user['role']}\n";
    echo "  Password hash: {$user['password_hash']}\n";
    echo "  Hash length: " . strlen($user['password_hash']) . "\n\n";
    
    // Verify password
    echo "Verifying password...\n";
    $result = password_verify($testPassword, $user['password_hash']);
    
    if ($result) {
        echo "✅ PASSWORD VERIFICATION SUCCESSFUL!\n\n";
        echo "This means login SHOULD work on the live site.\n";
        echo "If login is still failing, the issue is:\n";
        echo "  - Browser cookies being blocked\n";
        echo "  - Service worker caching\n";
        echo "  - Session configuration issue\n";
    } else {
        echo "❌ PASSWORD VERIFICATION FAILED!\n\n";
        echo "The password 'lalala123' does NOT match the hash in the database.\n";
        
        // Generate correct hash
        $correctHash = password_hash($testPassword, PASSWORD_DEFAULT);
        echo "Correct hash for 'lalala123':\n$correctHash\n\n";
        
        echo "SQL to fix this user:\n";
        echo "UPDATE users SET password_hash = '$correctHash' WHERE email = '$testEmail';\n\n";
        
        // Check other users
        echo "Checking all users in database...\n";
        $stmt = $pdo->query("SELECT id, name, email, role FROM users");
        $allUsers = $stmt->fetchAll();
        
        echo "\nAll users that need password verification:\n";
        foreach ($allUsers as $u) {
            echo "  - {$u['email']} ({$u['name']}, {$u['role']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nThis means:\n";
    echo "  1. Database password might be wrong\n";
    echo "  2. Network/firewall blocking connection\n";
    echo "  3. Database credentials in Render env vars might be incorrect\n";
}
