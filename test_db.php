<?php
/**
 * Database Connection Test
 * Visit: https://von-barbershop.onrender.com/test_db.php
 */

echo "<h1>Database Connection Test</h1>";

$host = getenv('DB_HOST');
$port = getenv('DB_PORT');
$name = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

echo "<h2>Environment Variables:</h2>";
echo "<ul>";
echo "<li><strong>DB_HOST:</strong> " . ($host ?: 'NOT SET') . "</li>";
echo "<li><strong>DB_PORT:</strong> " . ($port ?: 'NOT SET') . "</li>";
echo "<li><strong>DB_NAME:</strong> " . ($name ?: 'NOT SET') . "</li>";
echo "<li><strong>DB_USER:</strong> " . ($user ?: 'NOT SET') . "</li>";
echo "<li><strong>DB_PASS:</strong> " . ($pass ? '***SET***' : 'NOT SET') . "</li>";
echo "</ul>";

if (!$host || !$user || !$pass) {
    echo "<p style='color:red'><strong>ERROR:</strong> Environment variables not set!</p>";
    exit;
}

echo "<h2>Attempting Connection...</h2>";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p style='color:green;font-size:20px'><strong>✅ SUCCESS!</strong> Database connected!</p>";
    
    // Test query
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables Found:</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>{$table}</li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;font-size:20px'><strong>❌ FAILED!</strong></p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Possible Issues:</h3>";
    echo "<ul>";
    echo "<li>Wrong password or username</li>";
    echo "<li>IP not allowed in Aiven allowlist</li>";
    echo "<li>Database doesn't exist</li>";
    echo "<li>Wrong port number</li>";
    echo "</ul>";
}
