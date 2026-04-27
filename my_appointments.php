<?php
/**
 * Customer Appointments Page
 * Displays the logged-in customer's appointment history.
 */
$pageTitle = 'My Appointments';
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$stmt = $pdo->prepare("SELECT id, appointment_date, appointment_time, status, created_at, haircut_description, location FROM appointments WHERE user_id = ? ORDER BY appointment_date DESC, appointment_time DESC");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll();

// Get time-based greeting
$hour = date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour >= 12 && $hour < 17) {
    $greeting = 'Good Afternoon';
} elseif ($hour >= 17 && $hour < 21) {
    $greeting = 'Good Evening';
} else {
    $greeting = 'Good Night';
}

// Count appointments by status
$pendingCount = 0;
$acceptedCount = 0;
$completedCount = 0;
foreach ($appointments as $appt) {
    if ($appt['status'] === 'pending') $pendingCount++;
    if ($appt['status'] === 'accepted') $acceptedCount++;
    if ($appt['status'] === 'completed') $completedCount++;
}

require_once 'includes/header.php';
?>

<!-- Welcome Greeting Section -->
<div class="customer-welcome">
    <div class="welcome-card">
        <div class="welcome-icon">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
        </div>
        <h2 class="welcome-title">
            <?php echo $greeting; ?>, <span class="welcome-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>!
        </h2>
        <p class="welcome-message">Welcome back to your grooming dashboard</p>
        
        <div class="welcome-stats">
            <div class="welcome-stat">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div class="stat-number"><?php echo $pendingCount; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="welcome-stat">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </div>
                <div class="stat-number"><?php echo $acceptedCount; ?></div>
                <div class="stat-label">Accepted</div>
            </div>
            <div class="welcome-stat">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
                <div class="stat-number"><?php echo $completedCount; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="welcome-stat">
                <div class="stat-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div class="stat-number"><?php echo count($appointments); ?></div>
                <div class="stat-label">Total</div>
            </div>
        </div>
        
        <a href="book.php" class="btn btn-primary welcome-btn">
            <span>Book New Appointment</span>
            <span class="btn-shimmer"></span>
        </a>
    </div>
</div>

<h2 class="mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">My Appointments</h2>
<p class="mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Your grooming history</p>

<?php if (isset($_GET['booked'])): ?>
    <div class="alert alert-success">Your appointment has been booked successfully!</div>
    <?php if (isset($_GET['email']) && $_GET['email'] === 'sent'): ?>
        <div class="alert alert-info">A confirmation email has been sent to your Gmail.</div>
    <?php elseif (isset($_GET['email']) && $_GET['email'] === 'failed'): ?>
        <div class="alert alert-warning">Booking saved, but the confirmation email failed to send. Please contact the barber directly.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if (empty($appointments)): ?>
    <div class="alert alert-info">You have no appointments yet. <a href="book.php">Book one now</a>.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Haircut / Style</th>
                    <th>Location</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Booked On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appt['haircut_description']); ?></td>
                        <td><?php echo htmlspecialchars($appt['location']); ?></td>
                        <td><?php echo htmlspecialchars(date('F j, Y', strtotime($appt['appointment_date']))); ?></td>
                        <td><?php echo htmlspecialchars(date('g:i A', strtotime($appt['appointment_time']))); ?></td>
                        <td>
                            <?php
                            $badgeClass = match($appt['status']) {
                                'pending' => 'bg-warning text-dark',
                                'accepted' => 'bg-success',
                                'completed' => 'bg-primary',
                                'cancelled' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <?php echo ucfirst(htmlspecialchars($appt['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($appt['created_at']))); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
