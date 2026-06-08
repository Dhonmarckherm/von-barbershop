<?php
/**
 * Delete Push Subscription
 * Removes a user's push subscription from the database
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
        exit;
    }
    
    // Delete all subscriptions for this user
    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    $deleted = $stmt->rowCount();
    
    error_log("Deleted {$deleted} push subscription(s) for user {$userId}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Subscription deleted',
        'deleted' => $deleted
    ]);
    
} catch (Exception $e) {
    error_log('Delete subscription error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
