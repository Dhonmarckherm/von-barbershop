<?php
/**
 * Login Debug - Shows EXACTLY what happens during login
 * DELETE AFTER USE
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html><html><head><title>Login Debug</title>";
echo "<style>body{font-family:monospace;background:#1a1a1a;color:#0f0;padding:20px;}";
echo ".error{color:#f44;}.success{color:#4f4;}.warn{color:#ff0;}</style></head><body>";

echo "<h1>🐛 LOGIN DEBUG - REAL-TIME</h1>";
echo "<hr>";

// Test 1: Check if POST data is received
echo "<h2>1. POST Data Check</h2>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<p class='success'>✅ POST request received</p>";
    echo "<pre>";
    echo "Email: " . ($_POST['email'] ?? 'NOT SET') . "\n";
    echo "Password: " . (isset($_POST['password']) ? '[RECEIVED]' : 'NOT SET') . "\n";
    echo "</pre>";
} else {
    echo "<p class='warn'>⚠️ Not a POST request. Showing login form:</p>";
    echo '<form method="POST">';
    echo 'Email: <input type="email" name="email" value="dhondump@gmail.com"><br>';
    echo 'Password: <input type="password" name="password" value="lalala123"><br>';
    echo '<button type="submit" name="test_login">TEST LOGIN</button>';
    echo '</form>';
    echo "</body></html>";
    exit;
}

// Test 2: Database connection
echo "<h2>2. Database Connection</h2>";
try {
    require_once 'config/db.php';
    echo "<p class='success'>✅ Database connected</p>";
    echo "<pre>Host: " . DB_HOST . "\nDB: " . DB_NAME . "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 3: Query user
echo "<h2>3. User Query</h2>";
$email = trim($_POST['email']);
$password = $_POST['password'];

echo "<pre>Searching for: $email\n</pre>";

$stmt = $pdo->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo "<p class='error'>❌ User NOT FOUND!</p>";
    exit;
}

echo "<p class='success'>✅ User found</p>";
echo "<pre>ID: {$user['id']}\nName: {$user['name']}\nEmail: {$user['email']}\nRole: {$user['role']}\n";
echo "Password hash: {$user['password_hash']}\n";
echo "Hash length: " . strlen($user['password_hash']) . "</pre>";

// Test 4: Password verification
echo "<h2>4. Password Verification</h2>";
$verifyResult = password_verify($password, $user['password_hash']);

if ($verifyResult) {
    echo "<p class='success'>✅ Password VERIFIED!</p>";
} else {
    echo "<p class='error'>❌ Password WRONG!</p>";
    echo "<p>Testing password: '$password'</p>";
    echo "<p>Against hash: '{$user['password_hash']}'</p>";
    
    // Try with a known good hash
    echo "<hr><h3>Testing with fresh hash:</h3>";
    $testHash = password_hash($password, PASSWORD_DEFAULT);
    echo "<pre>Fresh hash for '$password': $testHash\n</pre>";
    echo "<p>These don't match - the database password is wrong!</p>";
    exit;
}

// Test 5: Session creation
echo "<h2>5. Session Creation</h2>";
require_once 'config/session.php';

echo "<pre>Before initializeSession():\n";
echo "Session status: " . session_status() . " (1=disabled, 2=none, 3=active)\n";

initializeSession();

echo "After initializeSession():\n";
echo "Session status: " . session_status() . "\n";
echo "Session ID: " . session_id() . "\n\n";

// Regenerate and set
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];

echo "After setting session vars:\n";
echo "Session ID: " . session_id() . "\n";
echo "user_id: " . $_SESSION['user_id'] . "\n";
echo "name: " . $_SESSION['name'] . "\n";
echo "role: " . $_SESSION['role'] . "\n";
echo "Session is WORKING: ✅</pre>";

// Test 6: Cookie setting
echo "<h2>6. Cookie Setting</h2>";
setAuthCookies($user['id'], $user['name'], $user['email'], $user['role']);
echo "<p class='success'>✅ setAuthCookies() called</p>";
echo "<pre>Cookies should be sent to browser (check DevTools Application tab)</pre>";

// Test 7: Verify redirect would work
echo "<h2>7. Redirect Test</h2>";
$redirectUrl = ($user['role'] === 'admin' || $user['role'] === 'barber') ? 'admin_dashboard.php' : 'index.php';
echo "<pre>Would redirect to: $redirectUrl\n</pre>";

echo "<hr><h2 class='success'>✅✅✅ LOGIN IS WORKING! ✅✅✅</h2>";
echo "<p>If actual login fails, the problem is:</p>";
echo "<ol>";
echo "<li>JavaScript preventing form submit</li>";
echo "<li>Service worker intercepting POST request</li>";
echo "<li>Browser blocking cookies</li>";
echo "<li>Output being sent before header() redirect</li>";
echo "</ol>";

echo "<hr><h3>Check for output buffering issues:</h3>";
echo "<pre>";
echo "Headers sent: " . (headers_sent() ? 'YES - THIS IS THE PROBLEM!' : 'NO - Good') . "\n";
if (headers_sent($file, $line)) {
    echo "Sent by: $file on line $line\n";
}
echo "Output buffer level: " . ob_get_level() . "\n";
echo "</pre>";

echo "<hr><p class='warn'>⚠️ DELETE THIS FILE after testing!</p>";
echo "<p><a href='index.php' style='color:#4f4;'>Go to homepage</a></p>";
