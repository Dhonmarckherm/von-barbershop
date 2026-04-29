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

// Get greeting
$greeting = 'Good Day'; // Updated greeting

// Count appointments by status
$pendingCount = 0;
$acceptedCount = 0;
$completedCount = 0;
$cancelledCount = 0;
$reviewedAppointments = [];

foreach ($appointments as $appt) {
    if ($appt['status'] === 'pending') $pendingCount++;
    elseif ($appt['status'] === 'accepted') $acceptedCount++;
    elseif ($appt['status'] === 'completed') $completedCount++;
    elseif ($appt['status'] === 'cancelled') $cancelledCount++;
}

// Fetch reviewed appointment IDs
$stmt = $pdo->prepare("SELECT appointment_id FROM reviews WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
while ($row = $stmt->fetch()) {
    $reviewedAppointments[] = $row['appointment_id'];
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
                    <th>Actions</th>
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
                        <td>
                            <?php if ($appt['status'] === 'completed' && !in_array($appt['id'], $reviewedAppointments)): ?>
                                <button class="btn btn-sm btn-success" onclick="openReviewModal(<?php echo $appt['id']; ?>, '<?php echo htmlspecialchars($appt['haircut_description'], ENT_QUOTES); ?>')">
                                    <i class="bi bi-star-fill"></i> Leave Review
                                </button>
                            <?php elseif ($appt['status'] === 'completed' && in_array($appt['id'], $reviewedAppointments)): ?>
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Reviewed</span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--barber-dark); color: var(--barber-gold);">
                <h5 class="modal-title">Leave a Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reviewForm">
                    <input type="hidden" id="reviewAppointmentId" name="appointment_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Haircut: <strong id="reviewHaircut"></strong></label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Rating *</label>
                        <div class="star-rating" id="starRating">
                            <i class="bi bi-star" data-rating="1"></i>
                            <i class="bi bi-star" data-rating="2"></i>
                            <i class="bi bi-star" data-rating="3"></i>
                            <i class="bi bi-star" data-rating="4"></i>
                            <i class="bi bi-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" id="ratingValue" name="rating" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reviewComment" class="form-label">Comment (Optional)</label>
                        <textarea class="form-control" id="reviewComment" name="comment" rows="3" placeholder="Share your experience..."></textarea>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.star-rating {
    display: flex;
    gap: 5px;
    font-size: 2.5rem;
    cursor: pointer;
}

.star-rating i {
    color: #ddd;
    transition: color 0.2s ease;
}

.star-rating i.active,
.star-rating i:hover,
.star-rating i:hover ~ i {
    color: #ffc107;
}

.star-rating i.active {
    color: #ffc107;
}
</style>

<script>
let selectedRating = 0;

function openReviewModal(appointmentId, haircut) {
    document.getElementById('reviewAppointmentId').value = appointmentId;
    document.getElementById('reviewHaircut').textContent = haircut;
    selectedRating = 0;
    updateStars(0);
    
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();
}

function updateStars(rating) {
    const stars = document.querySelectorAll('#starRating i');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('bi-star');
            star.classList.add('bi-star-fill');
            star.classList.add('active');
        } else {
            star.classList.remove('bi-star-fill');
            star.classList.add('bi-star');
            star.classList.remove('active');
        }
    });
    document.getElementById('ratingValue').value = rating;
}

document.querySelectorAll('#starRating i').forEach(star => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.getAttribute('data-rating'));
        updateStars(selectedRating);
    });
    
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.getAttribute('data-rating'));
        updateStars(rating);
    });
});

document.getElementById('starRating').addEventListener('mouseleave', function() {
    updateStars(selectedRating);
});

document.getElementById('reviewForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (selectedRating === 0) {
        alert('Please select a rating.');
        return;
    }
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('api/submit_review.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert(result.error || 'Failed to submit review.');
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
        console.error(error);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
