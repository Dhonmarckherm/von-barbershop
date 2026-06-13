<?php
/**
 * Debug Booking Flow
 * Shows session info and recent bookings
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
initializeSession();

if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

$userId = $_SESSION['user_id'];
$email = $_SESSION['email'];
$name = $_SESSION['name'];

// Get user's appointments
$stmt = $pdo->prepare("SELECT id, appointment_date, appointment_time, status, created_at FROM appointments WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$appointments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: #0a0a0a;
            color: #ffffff;
            padding: 20px;
        }
        .debug-card {
            background: #1a1a1a;
            border: 1px solid #404040;
            border-radius: 15px;
            padding: 25px;
            margin: 20px auto;
            max-width: 800px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #404040;
        }
        .info-label {
            color: #c0c0c0;
            font-weight: 600;
        }
        .info-value {
            color: #ffffff;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="debug-card">
        <h2 class="text-center mb-4">
            <i class="bi bi-bug"></i> Booking Flow Debug
        </h2>
        
        <h5 class="mt-4">Session Information</h5>
        <div class="info-row">
            <span class="info-label">User ID:</span>
            <span class="info-value"><?php echo $userId; ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value"><?php echo htmlspecialchars($email); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value"><?php echo htmlspecialchars($name); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Session ID:</span>
            <span class="info-value"><?php echo session_id(); ?></span>
        </div>
        
        <h5 class="mt-4">Your Appointments (<?php echo count($appointments); ?> total)</h5>
        <?php if (empty($appointments)): ?>
            <p class="text-muted">No appointments found.</p>
        <?php else: ?>
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?php echo $appt['id']; ?></td>
                        <td><?php echo $appt['appointment_date']; ?></td>
                        <td><?php echo $appt['appointment_time']; ?></td>
                        <td>
                            <span class="badge bg-<?php 
                                echo $appt['status'] === 'pending' ? 'warning' : 
                                    ($appt['status'] === 'accepted' ? 'success' : 
                                    ($appt['status'] === 'completed' ? 'info' : 'danger')); 
                            ?>">
                                <?php echo ucfirst($appt['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $appt['created_at']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <h5 class="mt-4">Test Booking Redirect</h5>
        <div class="alert alert-info">
            <p class="mb-2"><strong>Steps to test:</strong></p>
            <ol class="mb-0">
                <li>Open browser console (F12 on desktop)</li>
                <li>Go to <a href="book.php" class="alert-link">Book Appointment</a></li>
                <li>Fill out the form and submit</li>
                <li>Watch the console for [Booking] messages</li>
                <li>Check what URL you land on after submission</li>
            </ol>
        </div>
        
        <div class="mt-4 text-center">
            <a href="book.php" class="btn btn-primary me-2">
                <i class="bi bi-calendar-plus"></i> Go to Book
            </a>
            <a href="my_appointments.php" class="btn btn-secondary me-2">
                <i class="bi bi-calendar-check"></i> My Appointments
            </a>
            <a href="logout.php" class="btn btn-outline-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
    
    <script>
        console.log('%c[Debug] Page loaded', 'color: #00ff00; font-weight: bold;');
        console.log('%c[Debug] User ID: <?php echo $userId; ?>', 'color: #00ff00;');
        console.log('%c[Debug] Email: <?php echo $email; ?>', 'color: #00ff00;');
    </script>
</body>
</html>
