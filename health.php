<?php
/**
 * Health Check Endpoint
 * Used to keep Render.com service awake and monitor status
 */

header('Content-Type: application/json');

$response = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'service' => 'V.O.N Barbershop Booking System'
];

// Quick database connectivity check
try {
    require_once __DIR__ . '/config/db.php';
    $pdo->query('SELECT 1');
    $response['database'] = 'connected';
} catch (Exception $e) {
    $response['database'] = 'error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
