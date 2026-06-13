<?php
/**
 * Reset Biometric Credentials
 * Deletes user's biometric passkeys so they can re-enroll
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
$error = '';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_biometric'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM user_passkeys WHERE user_id = ?");
        $stmt->execute([$userId]);
        $count = $stmt->rowCount();
        $message = "Successfully deleted $count biometric credential(s). You can now re-enroll by logging in again.";
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Check current credentials
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_passkeys WHERE user_id = ?");
$stmt->execute([$userId]);
$credentialCount = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Biometric</title>
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
            padding: 25px;
            margin: 20px auto;
            max-width: 500px;
        }
        .status-enrolled {
            color: #28a745;
            font-weight: bold;
        }
        .status-none {
            color: #ffc107;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2 class="text-center mb-4">
            <i class="bi bi-fingerprint"></i> Reset Biometric Login
        </h2>
        
        <div class="mb-4">
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
            <p><strong>Current Credentials:</strong> 
                <?php if ($credentialCount > 0): ?>
                    <span class="status-enrolled"><?php echo $credentialCount; ?> enrolled ✓</span>
                <?php else: ?>
                    <span class="status-none">None ✗</span>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($credentialCount > 0): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill"></i> 
                <strong>Warning:</strong> This will delete your biometric credentials. You'll need to re-enroll after your next login.
                <br><br>
                This does NOT delete your account or appointments.
            </div>
            
            <form method="POST" onsubmit="return confirm('Are you sure you want to delete your biometric credentials?');">
                <div class="d-grid gap-2">
                    <button type="submit" name="reset_biometric" class="btn btn-danger">
                        <i class="bi bi-trash"></i> Delete Biometric Credentials
                    </button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle-fill"></i> 
                You don't have any biometric credentials enrolled.
                <br><br>
                To enroll: Logout and login again with email/password.
            </div>
        <?php endif; ?>
        
        <div class="mt-4 text-center">
            <a href="my_appointments.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Appointments
            </a>
            <a href="logout.php" class="btn btn-secondary ms-2">
                <i class="bi bi-box-arrow-right"></i> Logout & Test Login
            </a>
        </div>
    </div>
</body>
</html>
