<?php
session_start();
require_once 'config/db.php';
require_once 'includes/admin_check.php';

// Handle payment verification actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if ($appointment_id && $action) {
        if ($action === 'approve') {
            // Approve payment
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET payment_status = 'verified', payment_verified_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$appointment_id]);
            
            // Update payment log
            $stmt = $pdo->prepare("
                UPDATE payment_logs 
                SET status = 'verified', verified_at = NOW(), verified_by = ?
                WHERE appointment_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $appointment_id]);
            
            $message = 'Payment approved successfully!';
            $message_type = 'success';
            
        } elseif ($action === 'reject') {
            // Reject payment
            $stmt = $pdo->prepare("
                UPDATE appointments 
                SET payment_status = 'rejected'
                WHERE id = ?
            ");
            $stmt->execute([$appointment_id]);
            
            // Update payment log
            $stmt = $pdo->prepare("
                UPDATE payment_logs 
                SET status = 'rejected', verified_at = NOW(), verified_by = ?
                WHERE appointment_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $appointment_id]);
            
            $message = 'Payment rejected.';
            $message_type = 'warning';
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Get appointments with payments
$query = "
    SELECT a.*, u.name as customer_name, u.email as customer_email,
           s.name as service_name, s.price,
           p.amount as payment_amount, p.status as payment_log_status,
           p.proof_filename, p.created_at as payment_created_at
    FROM appointments a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN services s ON a.service_id = s.id
    LEFT JOIN payment_logs p ON a.id = p.appointment_id
    WHERE a.payment_proof IS NOT NULL
";

if ($filter === 'pending') {
    $query .= " AND a.payment_status = 'pending'";
} elseif ($filter === 'verified') {
    $query .= " AND a.payment_status = 'verified'";
} elseif ($filter === 'rejected') {
    $query .= " AND a.payment_status = 'rejected'";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->query($query);
$appointments = $stmt->fetchAll();

// Get counts
$count_stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE payment_status = 'pending' AND payment_proof IS NOT NULL");
$pending_count = $count_stmt->fetch()['count'];

$count_stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE payment_status = 'verified'");
$verified_count = $count_stmt->fetch()['count'];

$count_stmt = $pdo->query("SELECT COUNT(*) as count FROM appointments WHERE payment_status = 'rejected'");
$rejected_count = $count_stmt->fetch()['count'];

$page_title = 'Payment Verification';
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: var(--text-primary); font-family: 'Playfair Display', serif;">
                    <i class="bi bi-credit-card"></i> Payment Verification Dashboard
                </h2>
                <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Pending Verification</h6>
                                    <h3 class="mb-0" style="color: #ffc107;"><?php echo $pending_count; ?></h3>
                                </div>
                                <i class="bi bi-hourglass-split" style="font-size: 48px; color: #ffc107; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Verified</h6>
                                    <h3 class="mb-0" style="color: #28a745;"><?php echo $verified_count; ?></h3>
                                </div>
                                <i class="bi bi-check-circle" style="font-size: 48px; color: #28a745; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Rejected</h6>
                                    <h3 class="mb-0" style="color: #dc3545;"><?php echo $rejected_count; ?></h3>
                                </div>
                                <i class="bi bi-x-circle" style="font-size: 48px; color: #dc3545; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <ul class="nav nav-tabs mb-3" style="border-bottom: 1px solid var(--border-color);">
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
                       href="?filter=pending"
                       style="color: var(--text-primary);">
                        <i class="bi bi-hourglass-split"></i> Pending (<?php echo $pending_count; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'verified' ? 'active' : ''; ?>" 
                       href="?filter=verified"
                       style="color: var(--text-primary);">
                        <i class="bi bi-check-circle"></i> Verified (<?php echo $verified_count; ?>)
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>" 
                       href="?filter=rejected"
                       style="color: var(--text-primary);">
                        <i class="bi bi-x-circle"></i> Rejected (<?php echo $rejected_count; ?>)
                    </a>
                </li>
            </ul>
            
            <!-- Payment List -->
            <?php if (empty($appointments)): ?>
                <div class="card" style="background: var(--card-bg); border: 1px solid var(--border-color);">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 64px; color: var(--text-secondary);"></i>
                        <h5 class="mt-3" style="color: var(--text-primary);">No payments found</h5>
                        <p style="color: var(--text-secondary);">
                            <?php if ($filter === 'pending'): ?>
                                All payments have been verified! Or no payments uploaded yet.
                            <?php else: ?>
                                No <?php echo $filter; ?> payments yet.
                            <?php endif; ?>
                        </p>
                        <hr style="border-color: var(--border-color);">
                        <small style="color: var(--text-secondary);">
                            <strong>Debug Info:</strong><br>
                            Total appointments with payment_proof: <?php echo $pdo->query("SELECT COUNT(*) FROM appointments WHERE payment_proof IS NOT NULL")->fetchColumn(); ?><br>
                            Total payment_logs: <?php echo $pdo->query("SELECT COUNT(*) FROM payment_logs")->fetchColumn(); ?>
                        </small>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($appointments as $apt): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px;">
                                <div class="card-body">
                                    <!-- Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-1" style="color: var(--text-primary);">
                                                <?php echo htmlspecialchars($apt['customer_name']); ?>
                                            </h5>
                                            <small style="color: var(--text-secondary);">
                                                <?php echo htmlspecialchars($apt['customer_email']); ?>
                                            </small>
                                        </div>
                                        <?php
                                        $status_colors = [
                                            'pending' => 'warning',
                                            'verified' => 'success',
                                            'rejected' => 'danger'
                                        ];
                                        $status_color = $status_colors[$apt['payment_status']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $status_color; ?>">
                                            <?php echo ucfirst($apt['payment_status']); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Appointment Details -->
                                    <div class="mb-3 p-2" style="background: rgba(255,255,255,0.05); border-radius: 8px;">
                                        <div class="row">
                                            <div class="col-6">
                                                <small style="color: var(--text-secondary);">Service:</small>
                                                <p style="color: var(--text-primary); font-weight: 600; margin: 0; font-size: 14px;">
                                                    <?php echo htmlspecialchars($apt['service_name'] ?? 'Custom'); ?>
                                                </p>
                                            </div>
                                            <div class="col-6">
                                                <small style="color: var(--text-secondary);">Amount:</small>
                                                <p style="color: #28a745; font-weight: 600; margin: 0; font-size: 14px;">
                                                    ₱<?php echo number_format($apt['payment_amount'] ?? 50.00, 2); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <small style="color: var(--text-secondary);">Appointment:</small>
                                            <p style="color: var(--text-primary); margin: 0; font-size: 14px;">
                                                <i class="bi bi-calendar"></i> 
                                                <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?>
                                                at <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Payment Proof -->
                                    <?php if ($apt['proof_filename']): ?>
                                        <div class="mb-3">
                                            <small style="color: var(--text-secondary);">Payment Proof:</small>
                                            <div class="mt-2">
                                                <img src="uploads/payments/<?php echo htmlspecialchars($apt['proof_filename']); ?>" 
                                                     alt="Payment Proof" 
                                                     class="img-fluid rounded"
                                                     style="max-height: 300px; width: 100%; object-fit: contain; border: 1px solid var(--border-color);"
                                                     onclick="window.open(this.src, '_blank')">
                                                <small class="d-block mt-1 text-center" style="color: var(--text-secondary);">
                                                    Click image to view full size
                                                </small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Actions -->
                                    <?php if ($apt['payment_status'] === 'pending'): ?>
                                        <div class="d-grid gap-2">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to APPROVE this payment? Make sure you received ₱50 via GCash.');">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success w-100">
                                                    <i class="bi bi-check-circle"></i> Approve Payment
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to REJECT this payment?');">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-outline-danger w-100">
                                                    <i class="bi bi-x-circle"></i> Reject Payment
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($apt['payment_status'] === 'verified'): ?>
                                        <div class="alert alert-success mb-0" style="background: rgba(40,167,69,0.1); border: 1px solid #28a745;">
                                            <i class="bi bi-check-circle-fill"></i> Payment verified on 
                                            <?php echo date('M d, Y g:i A', strtotime($apt['payment_verified_at'])); ?>
                                        </div>
                                    <?php elseif ($apt['payment_status'] === 'rejected'): ?>
                                        <div class="alert alert-danger mb-0" style="background: rgba(220,53,69,0.1); border: 1px solid #dc3545;">
                                            <i class="bi bi-x-circle-fill"></i> Payment rejected
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
