<?php
require_once 'config/db.php';

$stmt = $pdo->query('SELECT COUNT(*) as count FROM push_subscriptions');
$result = $stmt->fetch();

echo "Total push subscriptions in database: " . $result['count'] . "\n\n";

if ($result['count'] > 0) {
    $stmt = $pdo->query('SELECT ps.id, ps.user_id, u.name, u.email, u.role, ps.created_at FROM push_subscriptions ps JOIN users u ON ps.user_id = u.id ORDER BY ps.created_at DESC');
    $subs = $stmt->fetchAll();
    
    echo "Active subscriptions:\n";
    foreach ($subs as $sub) {
        echo "- User: {$sub['name']} ({$sub['email']}) - Role: {$sub['role']}\n";
        echo "  Subscribed: {$sub['created_at']}\n\n";
    }
} else {
    echo "⚠️ No push subscriptions found!\n";
    echo "Users need to visit the website and allow notifications first.\n";
}
