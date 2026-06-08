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
    
    // VAPID Configuration
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:dhonmarck2004@gmail.com',
            'publicKey' => 'BDSd6CxnFxXK3O3tWG7Ik90SrPwFVB0NDXK6bc2tJx6THXcSbL7mprKAO8tzpr9DY8fUXZaoamTx6cniZT5QwIc',
            'privateKey' => 'zRYORC94sD71vHoUdfQlVXxa-b26i8k4c-PFYAO6Kt8'
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
            error_log("[PUSH-SEND] Attempting to send to endpoint: " . substr($sub['endpoint'], 0, 80));
            $report = $webPush->sendOneNotification($subscription, $payload);
            
            if ($report->isSuccess()) {
                $notificationsSent++;
                error_log("[PUSH-SEND] SUCCESS");
            } else {
                $notificationsFailed++;
                $reason = $report->getReason();
                error_log("[PUSH-SEND] FAILED: " . $reason);
                error_log("[PUSH-SEND] HTTP Code: " . $report->getResponse()->getStatusCode());
            }
        } catch (Exception $e) {
            $notificationsFailed++;
            error_log("[PUSH-SEND] EXCEPTION: " . $e->getMessage());
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
