<?php
/**
 * Send Push Notification to User
 * Uses Web Push API to send notifications to browser/PWA
 */

require_once '../config/db.php';
require_once '../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['user_id'], $data['title'], $data['body'])) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    $userId = $data['user_id'];
    $title = $data['title'];
    $body = $data['body'];
    $url = $data['url'] ?? '/my_appointments.php';
    
    // Get user's push subscriptions
    $stmt = $pdo->prepare("SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?");
    $stmt->execute([$userId]);
    $subscriptions = $stmt->fetchAll();
    
    if (empty($subscriptions)) {
        echo json_encode(['success' => false, 'error' => 'No push subscriptions found for user']);
        exit;
    }
    
    // VAPID Configuration (replace with your own keys)
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:dhonmarck2004@gmail.com',
            'publicKey' => 'YOUR_VAPID_PUBLIC_KEY', // Replace with your VAPID public key
            'privateKey' => 'YOUR_VAPID_PRIVATE_KEY' // Replace with your VAPID private key
        ]
    ];
    
    $webPush = new WebPush($auth);
    
    $payload = json_encode([
        'title' => $title,
        'body' => $body,
        'url' => $url,
        'icon' => '/assets/images/rubiks.jpg',
        'tag' => 'von-barbershop-' . time()
    ]);
    
    $notificationsSent = 0;
    $notificationsFailed = 0;
    
    foreach ($subscriptions as $sub) {
        $subscription = Subscription::create([
            'endpoint' => $sub['endpoint'],
            'publicKey' => $sub['p256dh'],
            'authToken' => $sub['auth']
        ]);
        
        try {
            $report = $webPush->sendNotification($subscription, $payload);
            
            if ($report->isSuccess()) {
                $notificationsSent++;
            } else {
                $notificationsFailed++;
                error_log("Push notification failed: " . $report->getReason());
            }
        } catch (Exception $e) {
            $notificationsFailed++;
            error_log("Push notification error: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Notifications sent',
        'sent' => $notificationsSent,
        'failed' => $notificationsFailed
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
