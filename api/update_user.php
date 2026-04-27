<?php
/**
 * API Endpoint: Update User (Admin Only)
 * 
 * Accepts POST parameters:
 *   - user_id (int)
 *   - name (string)
 *   - email (string)
 *   - role (string)
 *   - password (optional, string, min 6 chars)
 * 
 * Admin access only.
 */

error_reporting(0);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../config/session.php';
    initializeSession();

    header('Content-Type: application/json');

    // Admin check
    if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'barber')) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden. Admin access required.']);
        exit;
    }

    // Validate inputs
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$userId) {
        echo json_encode(['error' => 'Invalid user ID.']);
        exit;
    }

    if (empty($name) || strlen($name) < 2) {
        echo json_encode(['error' => 'Name must be at least 2 characters.']);
        exit;
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email address.']);
        exit;
    }

    if (empty($role) || strlen($role) < 2) {
        echo json_encode(['error' => 'Role must be at least 2 characters.']);
        exit;
    }

    // Check if email already belongs to another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Email address is already in use by another account.']);
        exit;
    }

    // Build update query
    $params = [$name, $email, $role];
    $setPassword = '';

    if (!empty($password)) {
        if (strlen($password) < 6) {
            echo json_encode(['error' => 'Password must be at least 6 characters.']);
            exit;
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $setPassword = ', password_hash = ?';
        $params[] = $hash;
    }

    $params[] = $userId;
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?" . $setPassword . " WHERE id = ?");
    $stmt->execute($params);

    echo json_encode(['success' => true]);
    exit;
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
