<?php
/**
 * Push Notification Helper
 * Sends push notifications to users
 */

function sendPushNotification($pdo, $userId, $title, $body, $url = '/index.php') {
    try {
        // Determine correct protocol
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
                   || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                   ? 'https' : 'http';
        
        $pushUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/api/send_push_notification.php';
        
        error_log("[PUSH] Sending to user {$userId}: {$title}");
        error_log("[PUSH] URL: {$pushUrl}");
        
        $pushData = json_encode([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'url' => $url
        ]);
        
        $ch = curl_init($pushUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $pushData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        error_log("[PUSH] Response (HTTP {$httpCode}): {$response}");
        
        return true;
    } catch (Exception $e) {
        error_log('[PUSH] ERROR: ' . $e->getMessage());
        return false;
    }
}
