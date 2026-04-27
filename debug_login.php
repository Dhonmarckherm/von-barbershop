<?php
/**
 * Login Debug Tool
 * Visit: https://von-barbershop.onrender.com/debug_login.php
 */

require_once 'config/db.php';

echo "<h1>Login Debug Tool</h1>";

// Check if users exist
echo "<h2>1. Users in Database:</h2>";
$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users");
$users = $stmt->fetchAll();

if (empty($users)) {
    echo "<p style='color:red'><strong>NO USERS FOUND!</strong> This is why login fails.</p>";
    echo "<p>Creating default admin user...</p>";
    
    // Create admin user
    $password_hash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
    $stmt->execute(['Admin', 'admin@barbershop.com', $password_hash, 'admin']);
    
    echo "<p style='color:green'>✅ Admin user created!</p>";
    echo "<p><strong>Email:</strong> admin@barbershop.com</p>";
    echo "<p><strong>Password:</strong> admin123</p>";
} else {
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test password verification
echo "<h2>2. Password Hash Test:</h2>";
$stmt = $pdo->query("SELECT id, name, email, password_hash FROM users LIMIT 1");
$test_user = $stmt->fetch();

if ($test_user) {
    echo "<p>Testing user: <strong>{$test_user['name']}</strong> ({$test_user['email']})</p>";
    echo "<p>Password hash: " . substr($test_user['password_hash'], 0, 30) . "...</p>";
    
    // Test with 'admin123'
    $test_password = 'admin123';
    $verified = password_verify($test_password, $test_user['password_hash']);
    
    if ($verified) {
        echo "<p style='color:green'>✅ Password 'admin123' works for this user!</p>";
    } else {
        echo "<p style='color:red'>❌ Password 'admin123' does NOT work!</p>";
        echo "<p>Creating new user with known password...</p>";
        
        $new_hash = password_hash('test123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Test User', 'test@barbershop.com', $new_hash, 'customer']);
        
        echo "<p style='color:green'>✅ Test user created!</p>";
        echo "<p><strong>Email:</strong> test@barbershop.com</p>";
        echo "<p><strong>Password:</strong> test123</p>";
    }
}

// Check database connection
echo "<h2>3. Database Status:</h2>";
echo "<p style='color:green'>✅ Database is connected</p>";

echo "<hr>";
echo "<h2>4. Try Login Now:</h2>";
echo "<p>Visit: <a href='login.php'>Login Page</a></p>";
echo "<p><strong>Admin Login:</strong></p>";
echo "<ul>";
echo "<li>Email: admin@barbershop.com</li>";
echo "<li>Password: admin123</li>";
echo "</ul>";
echo "<p><strong>Customer Login:</strong></p>";
echo "<ul>";
echo "<li>Email: test@barbershop.com</li>";
echo "<li>Password: test123</li>";
echo "</ul>";
?>
