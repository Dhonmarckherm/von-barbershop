<?php
/**
 * Force Reset Biometric - Delete ALL credentials and start fresh
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';

initializeSession();

if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$userId = $_SESSION['user_id'];
$email = $_SESSION['email'];
$message = '';

// Get count before deletion
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_passkeys WHERE user_id = ?");
$stmt->execute([$userId]);
$beforeCount = $stmt->fetch()['count'];

// Delete all credentials for this user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['force_reset'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM user_passkeys WHERE user_id = ?");
        $stmt->execute([$userId]);
        $deleted = $stmt->rowCount();
        $message = "Successfully deleted $deleted credential(s). Now logout and login again to re-enroll with the FIXED system.";
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get count after deletion
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_passkeys WHERE user_id = ?");
$stmt->execute([$userId]);
$afterCount = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Force Reset Biometric</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #0a0a0a;
            color: #ffffff;
            padding: 20px;
        }
        .card {
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 15px;
            padding: 30px;
            margin: 20px auto;
            max-width: 600px;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2 class="text-center mb-4">
            <i class="bi bi-arrow-repeat"></i> Force Reset Biometric
        </h2>
        
        <div class="mb-4">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Credentials Before:</strong> <?php echo $beforeCount; ?></p>
            <p><strong>Credentials After:</strong> <?php echo $afterCount; ?></p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($afterCount > 0): ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>You still have <?php echo $afterCount; ?> credential(s)!</strong>
                <br><br>
                Click the button below to delete them all and start fresh with the fixed system.
            </div>
            
            <form method="POST">
                <div class="d-grid">
                    <button type="submit" name="force_reset" class="btn btn-danger btn-lg">
                        <i class="bi bi-trash"></i> Delete ALL Credentials & Start Fresh
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <strong>All credentials deleted!</strong>
                <br><br>
                Now follow these steps:
                <ol class="mt-2">
                    <li><strong>Logout</strong> completely</li>
                    <li><strong>Login</strong> with email/password</li>
                    <li>Wait for <strong>"Enable Quick Login"</strong> popup</li>
                    <li>Click <strong>"Enable Now"</strong></li>
                    <li>Complete Face ID/Touch ID</li>
                    <li><strong>Test:</strong> Logout and try "Login with Biometrics"</li>
                </ol>
            </div>
            
            <div class="d-grid gap-2 mt-4">
                <a href="logout.php" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-right"></i> Logout Now
                </a>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 text-center">
            <a href="my_appointments.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Appointments
            </a>
        </div>
    </div>
</body>
</html>
