<?php
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';

// Debug: Check if we're connected
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle payment verification actions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $action = $_POST['action'] ?? null;
    
    if ($appointment_id && $action) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE appointments SET payment_status = 'verified', payment_verified_at = NOW() WHERE id = ?");
                $stmt->execute([$appointment_id]);
                
                $stmt = $pdo->prepare("UPDATE payment_logs SET status = 'verified', verified_at = NOW(), verified_by = ? WHERE appointment_id = ?");
                $stmt->execute([$_SESSION['user_id'], $appointment_id]);
                
                $message = 'Payment approved successfully!';
                $message_type = 'success';
                
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE appointments SET payment_status = 'rejected' WHERE id = ?");
                $stmt->execute([$appointment_id]);
                
                $stmt = $pdo->prepare("UPDATE payment_logs SET status = 'rejected', verified_at = NOW(), verified_by = ? WHERE appointment_id = ?");
                $stmt->execute([$_SESSION['user_id'], $appointment_id]);
                
                $message = 'Payment rejected.';
                $message_type = 'warning';
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = 'Invalid request. Missing appointment ID or action.';
        $message_type = 'danger';
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Get appointments with payments
$query = "
    SELECT a.*, u.name as customer_name, u.email as customer_email,
           COALESCE(p.amount, a.downpayment_amount, 50.00) as payment_amount, 
           p.status as payment_log_status,
           p.proof_filename, p.created_at as payment_created_at
    FROM appointments a
    JOIN users u ON a.user_id = u.id
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <!-- Header -->
            <div class="text-center mb-5">
                <h1 style="color: var(--text-primary); font-family: 'Playfair Display', serif; font-size: 36px; font-weight: 700;">
                    <i class="bi bi-credit-card" style="color: #28a745;"></i> Payment Verification Dashboard
                </h1>
                <p style="color: var(--text-secondary); font-size: 16px; margin-top: 10px;">
                    Review and verify customer payment proofs
                </p>
            </div>
            
            <!-- Success/Error Message -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert" style="border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(255, 193, 7, 0.3);">
                        <div class="card-body text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-1" style="opacity: 0.9; font-size: 14px;">Pending Verification</h6>
                                    <h2 class="mb-0" style="font-weight: 700; font-size: 42px;"><?php echo $pending_count; ?></h2>
                                </div>
                                <i class="bi bi-hourglass-split" style="font-size: 56px; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(40, 167, 69, 0.3);">
                        <div class="card-body text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-1" style="opacity: 0.9; font-size: 14px;">Verified</h6>
                                    <h2 class="mb-0" style="font-weight: 700; font-size: 42px;"><?php echo $verified_count; ?></h2>
                                </div>
                                <i class="bi bi-check-circle" style="font-size: 56px; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="card" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: none; border-radius: 16px; box-shadow: 0 8px 24px rgba(220, 53, 69, 0.3);">
                        <div class="card-body text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-white mb-1" style="opacity: 0.9; font-size: 14px;">Rejected</h6>
                                    <h2 class="mb-0" style="font-weight: 700; font-size: 42px;"><?php echo $rejected_count; ?></h2>
                                </div>
                                <i class="bi bi-x-circle" style="font-size: 56px; opacity: 0.3;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <ul class="nav nav-pills mb-4" style="border: none; background: var(--card-bg); padding: 8px; border-radius: 12px;">
                <li class="nav-item flex-fill text-center">
                    <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" 
                       href="?filter=pending"
                       style="border-radius: 8px; color: var(--text-primary); font-weight: 600; border: none;">
                        <i class="bi bi-hourglass-split"></i> Pending (<?php echo $pending_count; ?>)
                    </a>
                </li>
                <li class="nav-item flex-fill text-center">
                    <a class="nav-link <?php echo $filter === 'verified' ? 'active' : ''; ?>" 
                       href="?filter=verified"
                       style="border-radius: 8px; color: var(--text-primary); font-weight: 600; border: none;">
                        <i class="bi bi-check-circle"></i> Verified (<?php echo $verified_count; ?>)
                    </a>
                </li>
                <li class="nav-item flex-fill text-center">
                    <a class="nav-link <?php echo $filter === 'rejected' ? 'active' : ''; ?>" 
                       href="?filter=rejected"
                       style="border-radius: 8px; color: var(--text-primary); font-weight: 600; border: none;">
                        <i class="bi bi-x-circle"></i> Rejected (<?php echo $rejected_count; ?>)
                    </a>
                </li>
            </ul>
            
            <!-- Payment List -->
            <?php if (empty($appointments)): ?>
                <div class="card" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 16px;">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-inbox" style="font-size: 80px; color: var(--text-secondary); opacity: 0.5;"></i>
                        <h4 class="mt-4" style="color: var(--text-primary); font-weight: 600;">No payments found</h4>
                        <p style="color: var(--text-secondary); font-size: 16px;">
                            <?php if ($filter === 'pending'): ?>
                                All payments have been verified! Or no payments uploaded yet.
                            <?php else: ?>
                                No <?php echo $filter; ?> payments yet.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($appointments as $apt): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card" style="background: var(--card-bg); border: 2px solid var(--border-color); border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.1); transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.1)'">
                                <div class="card-body p-4">
                                    <!-- Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-3 pb-3" style="border-bottom: 2px solid var(--border-color);">
                                        <div>
                                            <h5 class="mb-1" style="color: var(--text-primary); font-weight: 700; font-size: 20px;">
                                                <i class="bi bi-person-circle" style="color: #28a745;"></i> <?php echo htmlspecialchars($apt['customer_name']); ?>
                                            </h5>
                                            <p class="mb-0" style="color: var(--text-secondary); font-size: 14px;">
                                                <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($apt['customer_email']); ?>
                                            </p>
                                        </div>
                                        <?php
                                        $status_colors = [
                                            'pending' => '#ffc107',
                                            'verified' => '#28a745',
                                            'rejected' => '#dc3545'
                                        ];
                                        $status_color = $status_colors[$apt['payment_status']] ?? '#6c757d';
                                        ?>
                                        <span class="badge" style="background: <?php echo $status_color; ?>; color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 13px;">
                                            <?php echo ucfirst($apt['payment_status']); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Details Grid -->
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <div style="background: rgba(255,255,255,0.05); padding: 12px; border-radius: 10px;">
                                                <small style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; font-weight: 600;">Service</small>
                                                <p style="color: var(--text-primary); font-weight: 600; margin: 4px 0 0 0; font-size: 15px;">
                                                    <?php echo htmlspecialchars($apt['service_name'] ?? 'Custom'); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div style="background: rgba(40,167,69,0.1); padding: 12px; border-radius: 10px; border-left: 3px solid #28a745;">
                                                <small style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; font-weight: 600;">Amount</small>
                                                <p style="color: #28a745; font-weight: 700; margin: 4px 0 0 0; font-size: 20px;">
                                                    ₱<?php echo number_format($apt['payment_amount'] ?? 50.00, 2); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div style="background: rgba(0,123,255,0.1); padding: 12px; border-radius: 10px; border-left: 3px solid #007bff;">
                                            <small style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; font-weight: 600;">Appointment</small>
                                            <p style="color: var(--text-primary); margin: 4px 0 0 0; font-size: 15px;">
                                                <i class="bi bi-calendar-event" style="color: #007bff;"></i>
                                                <?php echo date('M d, Y', strtotime($apt['appointment_date'])); ?> at <?php echo date('g:i A', strtotime($apt['appointment_time'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Payment Proof -->
                                    <?php if ($apt['proof_filename']): ?>
                                        <div class="mb-3">
                                            <small style="color: var(--text-secondary); font-size: 12px; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 8px;">
                                                Payment Proof
                                            </small>
                                            <div style="background: rgba(255,255,255,0.05); padding: 12px; border-radius: 10px; text-align: center;">
                                                <img src="uploads/payments/<?php echo htmlspecialchars($apt['proof_filename']); ?>" 
                                                     alt="Payment Proof" 
                                                     class="img-fluid rounded"
                                                     style="max-height: 250px; width: 100%; object-fit: contain; border-radius: 8px; cursor: pointer; border: 2px solid var(--border-color);"
                                                     onclick="window.open(this.src, '_blank')">
                                                <small class="d-block mt-2" style="color: var(--text-secondary); font-size: 12px;">
                                                    <i class="bi bi-zoom-in"></i> Click image to view full size
                                                </small>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Actions -->
                                    <?php if ($apt['payment_status'] === 'pending'): ?>
                                        <div class="d-grid gap-2 mt-4">
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to APPROVE this payment? Make sure you received ₱50 via GCash.');">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn w-100" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; border-radius: 10px; padding: 12px; font-weight: 600; font-size: 15px; box-shadow: 0 4px 12px rgba(40,167,69,0.3);">
                                                    <i class="bi bi-check-circle-fill"></i> Approve Payment
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to REJECT this payment?');">
                                                <input type="hidden" name="appointment_id" value="<?php echo $apt['id']; ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn w-100" style="background: white; color: #dc3545; border: 2px solid #dc3545; border-radius: 10px; padding: 12px; font-weight: 600; font-size: 15px;">
                                                    <i class="bi bi-x-circle-fill"></i> Reject Payment
                                                </button>
                                            </form>
                                        </div>
                                    <?php elseif ($apt['payment_status'] === 'verified'): ?>
                                        <div class="alert alert-success mb-0" style="background: rgba(40,167,69,0.1); border: 2px solid #28a745; border-radius: 10px; padding: 12px;">
                                            <i class="bi bi-check-circle-fill" style="color: #28a745;"></i>
                                            <strong style="color: #28a745;">Payment verified</strong> on 
                                            <?php echo date('M d, Y g:i A', strtotime($apt['payment_verified_at'])); ?>
                                        </div>
                                    <?php elseif ($apt['payment_status'] === 'rejected'): ?>
                                        <div class="alert alert-danger mb-0" style="background: rgba(220,53,69,0.1); border: 2px solid #dc3545; border-radius: 10px; padding: 12px;">
                                            <i class="bi bi-x-circle-fill" style="color: #dc3545;"></i>
                                            <strong style="color: #dc3545;">Payment rejected</strong>
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
