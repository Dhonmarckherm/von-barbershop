<?php
/**
 * Save Push Subscription to Database
 * Called when user subscribes to push notifications
 */

require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['subscription'])) {
        echo json_encode(['success' => false, 'error' => 'No subscription data']);
        exit;
    }
    
    $subscription = $data['subscription'];
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? null;
    
    if (!$userId) {
        echo json_encode(['success' => false, 'error' => 'User not authenticated']);
        exit;
    }
    
    // Check if subscription already exists
    $stmt = $pdo->prepare("SELECT id FROM push_subscriptions WHERE endpoint = ?");
    $stmt->execute([$subscription['endpoint']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing subscription
        $stmt = $pdo->prepare("UPDATE push_subscriptions SET user_id = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$userId, $existing['id']]);
    } else {
        // Insert new subscription
        $stmt = $pdo->prepare("
            INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $userId,
            $subscription['endpoint'],
            $subscription['keys']['p256dh'] ?? '',
            $subscription['keys']['auth'] ?? ''
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Subscription saved']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
