<?php
/**
 * Server File Checker - Check if critical files exist
 * DELETE THIS FILE after use!
 */

$critical_files = [
    'book.php' => 'Booking page',
    'process_booking.php' => 'Process booking',
    'my_appointments.php' => 'My appointments',
    'api/send_push_notification.php' => 'Send push notification',
    'api/save_push_subscription.php' => 'Save subscription',
    'includes/push_helper.php' => 'Push helper',
    'config/db.php' => 'Database config',
    'config/session.php' => 'Session config',
    'sw.js' => 'Service worker',
    'www/js/web-push-notifications.js' => 'Web push JS',
    'composer.json' => 'Composer config',
    'vendor/autoload.php' => 'Composer autoload (WebPush library)',
];

echo "<!DOCTYPE html>
<html>
<head>
    <title>Server File Check</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #1a1a1a; color: #fff; }
        .file { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .exists { background: #1b5e20; }
        .missing { background: #b71c1c; }
        h1 { color: #C5A059; }
    </style>
</head>
<body>
    <h1>🔍 Server File Checker</h1>
    <p>Timestamp: " . date('Y-m-d H:i:s') . "</p>
    <hr>
";

$missing_count = 0;
foreach ($critical_files as $file => $description) {
    $full_path = __DIR__ . '/' . $file;
    $exists = file_exists($full_path);
    
    if (!$exists) {
        $missing_count++;
    }
    
    $class = $exists ? 'exists' : 'missing';
    $icon = $exists ? '✅' : '❌';
    
    echo "<div class='file {$class}'>";
    echo "{$icon} <strong>{$file}</strong> - {$description}";
    echo "</div>";
}

echo "<hr>";
echo "<h2>Summary: " . (count($critical_files) - $missing_count) . "/" . count($critical_files) . " files exist</h2>";

if ($missing_count > 0) {
    echo "<p style='color: #ff5252; font-size: 18px;'>⚠️ <strong>{$missing_count} file(s) missing!</strong> Redeploy needed.</p>";
} else {
    echo "<p style='color: #4caf50; font-size: 18px;'>✅ <strong>All critical files present!</strong></p>";
}

// Check if composer dependencies are installed
echo "<hr>";
echo "<h2>Composer Dependencies</h2>";

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<div class='file exists'>✅ vendor/autoload.php exists</div>";
    
    // Check if WebPush library is installed
    if (class_exists('Minishlink\\WebPush\\WebPush')) {
        echo "<div class='file exists'>✅ Minishlink/WebPush library loaded</div>";
    } else {
        echo "<div class='file missing'>❌ WebPush library NOT loaded - run: composer require minishlink/web-push</div>";
    }
} else {
    echo "<div class='file missing'>❌ vendor/autoload.php missing - run: composer install</div>";
}

echo "</body></html>";
