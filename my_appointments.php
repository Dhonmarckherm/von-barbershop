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

// Fetch reviewed appointment IDs (handle if table doesn't exist yet)
$reviewedAppointments = [];
try {
    $stmt = $pdo->prepare("SELECT appointment_id FROM reviews WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    while ($row = $stmt->fetch()) {
        $reviewedAppointments[] = $row['appointment_id'];
    }
} catch (PDOException $e) {
    // Table doesn't exist yet, no reviews
    error_log("Reviews table not found in my_appointments: " . $e->getMessage());
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
                            <?php elseif (in_array($appt['status'], ['pending', 'accepted'])): ?>
                                <button class="btn btn-sm btn-warning me-1" onclick="openRescheduleModal(<?php echo $appt['id']; ?>, '<?php echo $appt['appointment_date']; ?>', '<?php echo substr($appt['appointment_time'], 0, 5); ?>')">
                                    <i class="bi bi-calendar-check"></i> Reschedule
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="cancelAppointment(<?php echo $appt['id']; ?>)">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </button>
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

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #000000; border: 1px solid rgba(192,192,192,0.3); border-radius: 16px; overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%); border-bottom: 2px solid #c0c0c0; padding: 25px 30px 20px;">
                <div style="text-align: center; width: 100%;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#c0c0c0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px;">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                        <path d="M8 14h.01"></path>
                        <path d="M12 14h.01"></path>
                        <path d="M16 14h.01"></path>
                        <path d="M8 18h.01"></path>
                        <path d="M12 18h.01"></path>
                    </svg>
                    <h5 class="modal-title" style="color: #c0c0c0; font-family: 'Playfair Display', serif; font-weight: bold; margin: 0;">
                        Reschedule Appointment
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; right: 20px; top: 20px;"></button>
            </div>
            <div class="modal-body" style="padding: 25px 30px;">
                <form id="rescheduleForm">
                    <input type="hidden" id="rescheduleAppointmentId">
                    <div class="mb-3">
                        <label for="rescheduleDate" class="form-label" style="color: #f5f5f5; font-weight: 600; font-size: 14px;">
                            <i class="bi bi-calendar3" style="color: #c0c0c0;"></i> New Date
                        </label>
                        <input type="text" class="form-control" id="rescheduleDate" placeholder="Select date..." 
                               style="background: rgba(255,255,255,0.08); border: 1px solid rgba(192,192,192,0.4); color: #f5f5f5; padding: 12px 15px; border-radius: 10px; font-size: 15px;" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="rescheduleTime" class="form-label" style="color: #f5f5f5; font-weight: 600; font-size: 14px;">
                            <i class="bi bi-clock" style="color: #c0c0c0;"></i> New Time
                        </label>
                        <input type="text" class="form-control" id="rescheduleTime" placeholder="Select time..." 
                               style="background: rgba(255,255,255,0.08); border: 1px solid rgba(192,192,192,0.4); color: #f5f5f5; padding: 12px 15px; border-radius: 10px; font-size: 15px;" readonly>
                    </div>
                    <div class="alert alert-info" style="background: rgba(192,192,192,0.1); border: 1px solid rgba(192,192,192,0.3); color: #c0c0c0; border-radius: 10px; padding: 12px 15px; font-size: 13px; margin-bottom: 0;">
                        <i class="bi bi-info-circle"></i> The barber will be notified of this change via email.
                    </div>
                    <div id="rescheduleError" class="alert alert-danger d-none" style="background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #ff6b6b; border-radius: 10px; margin-top: 12px; margin-bottom: 0;"></div>
                </form>
            </div>
            <div class="modal-footer" style="background: rgba(192,192,192,0.05); border-top: 1px solid rgba(192,192,192,0.3); padding: 20px 30px; justify-content: center; gap: 12px;">
                <button type="button" class="btn" data-bs-dismiss="modal" 
                        style="flex: 1; background: transparent; border: 1px solid rgba(255,255,255,0.3); color: #f5f5f5; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 15px;">
                    <i class="bi bi-x-circle"></i> Cancel
                </button>
                <button type="button" class="btn" id="confirmRescheduleBtn" 
                        style="flex: 1; background: linear-gradient(135deg, #c0c0c0 0%, #d4d4d4 100%); border: none; color: #000000; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 15px;">
                    <i class="bi bi-check-circle"></i> Confirm
                </button>
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

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}
</style>

<!-- Flatpickr -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
let selectedRating = 0;
let rescheduleModal;

// Show success message
function showSuccessMessage(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(16,185,129,0.3);
        z-index: 9999;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Show error message
function showErrorMessage(message) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(239,68,68,0.3);
        z-index: 9999;
        font-weight: 600;
        animation: slideIn 0.3s ease;
    `;
    toast.innerHTML = `<i class="bi bi-x-circle-fill"></i> ${message}`;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Initialize Flatpickers
const datepicker = flatpickr('#rescheduleDate', {
    minDate: 'today',
    dateFormat: 'Y-m-d',
    theme: 'dark',
    animate: true
});

const timepicker = flatpickr('#rescheduleTime', {
    enableTime: true,
    noCalendar: true,
    dateFormat: 'H:i',
    time_24hr: true,
    minTime: '09:00',
    maxTime: '17:00',
    minuteIncrement: 30,
    theme: 'dark',
    animate: true,
    static: true,
    defaultHour: 9,
    defaultMinute: 0
});

function openReviewModal(appointmentId, haircut) {
    document.getElementById('reviewAppointmentId').value = appointmentId;
    document.getElementById('reviewHaircut').textContent = haircut;
    selectedRating = 0;
    updateStars(0);
    
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();
}

function openRescheduleModal(appointmentId, date, time) {
    document.getElementById('rescheduleAppointmentId').value = appointmentId;
    
    // Set Flatpickr values
    datepicker.setDate(date);
    timepicker.setDate(time);
    
    document.getElementById('rescheduleError').classList.add('d-none');
    
    if (!rescheduleModal) {
        rescheduleModal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
    }
    rescheduleModal.show();
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

// Reschedule button
document.getElementById('confirmRescheduleBtn').addEventListener('click', async function() {
    const id = document.getElementById('rescheduleAppointmentId').value;
    const date = document.getElementById('rescheduleDate').value;
    const timeValue = document.getElementById('rescheduleTime').value;
    
    if (!date || !timeValue) {
        document.getElementById('rescheduleError').textContent = 'Please select both date and time.';
        document.getElementById('rescheduleError').classList.remove('d-none');
        return;
    }
    
    // Time is already in HH:MM format (24-hour)
    let time = timeValue;
    if (time && !time.includes(':00')) {
        time = time + ':00';
    }
    
    document.getElementById('rescheduleError').classList.add('d-none');
    
    try {
        const response = await fetch('api/customer_reschedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'appointment_id=' + encodeURIComponent(id) + '&new_date=' + encodeURIComponent(date) + '&new_time=' + encodeURIComponent(time)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show push notification if in Capacitor app
            if (result.notification && typeof showNotification === 'function' && isCapacitorNative()) {
                showNotification(result.notification.title, result.notification.body, result.notification.id);
            }
            
            alert(result.message);
            rescheduleModal.hide();
            location.reload();
        } else {
            document.getElementById('rescheduleError').textContent = result.error || 'Failed to reschedule.';
            document.getElementById('rescheduleError').classList.remove('d-none');
        }
    } catch (error) {
        document.getElementById('rescheduleError').textContent = 'An error occurred. Please try again.';
        document.getElementById('rescheduleError').classList.remove('d-none');
        console.error(error);
    }
});

// Cancel appointment
function cancelAppointment(appointmentId) {
    // Create custom modal
    const modalHtml = `
        <div class="modal fade" id="confirmCancelModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="background: #000000; border: 1px solid rgba(192,192,192,0.3); border-radius: 16px; overflow: hidden;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: none; padding: 30px 30px 20px; text-align: center;">
                        <div style="margin: 0 auto;">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                    </div>
                    <div class="modal-body" style="padding: 30px; text-align: center;">
                        <h4 style="color: #f5f5f5; font-family: 'Playfair Display', serif; margin-bottom: 15px;">Cancel Appointment?</h4>
                        <p style="color: #b0b0b0; font-size: 15px; margin-bottom: 0;">This action cannot be undone. The barber will be notified of this cancellation.</p>
                    </div>
                    <div class="modal-footer" style="background: rgba(192,192,192,0.05); border-top: 1px solid rgba(192,192,192,0.3); padding: 20px 30px; justify-content: center; gap: 12px;">
                        <button type="button" class="btn" data-bs-dismiss="modal" 
                                style="flex: 1; background: transparent; border: 1px solid rgba(255,255,255,0.3); color: #f5f5f5; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 15px;">
                            <i class="bi bi-x-circle"></i> Keep It
                        </button>
                        <button type="button" class="btn" id="confirmCancelBtn" 
                                style="flex: 1; background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: none; color: white; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 15px;">
                            <i class="bi bi-check-circle"></i> Yes, Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    const modal = new bootstrap.Modal(document.getElementById('confirmCancelModal'));
    modal.show();
    
    // Handle confirm
    document.getElementById('confirmCancelBtn').addEventListener('click', function() {
        modal.hide();
        
        fetch('api/customer_cancel.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'appointment_id=' + encodeURIComponent(appointmentId)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Show push notification if in Capacitor app
                if (result.notification && typeof showNotification === 'function' && isCapacitorNative()) {
                    showNotification(result.notification.title, result.notification.body, result.notification.id);
                }
                
                // Show success message
                showSuccessMessage('Appointment cancelled successfully!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showErrorMessage(result.error || 'Failed to cancel appointment.');
            }
        })
        .catch(error => {
            showErrorMessage('An error occurred. Please try again.');
            console.error(error);
        });
    });
    
    // Remove modal after hidden
    document.getElementById('confirmCancelModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// Auto-open review modal if URL has review parameter
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const reviewAppointmentId = urlParams.get('review');
    
    if (reviewAppointmentId) {
        // Find the appointment and open the review modal
        const reviewButton = document.querySelector(`button[onclick*="${reviewAppointmentId}"]`);
        if (reviewButton) {
            reviewButton.click();
        }
    }
    
    // Show push notification if exists in session (passed via PHP)
    <?php if (isset($_SESSION['push_notification'])): ?>
    const notification = <?php echo json_encode($_SESSION['push_notification']); ?>;
    if (typeof showNotification === 'function' && isCapacitorNative()) {
        setTimeout(() => {
            showNotification(notification.title, notification.body, notification.id);
        }, 1000); // Delay to ensure page is loaded
    }
    <?php unset($_SESSION['push_notification']); ?>
    <?php endif; ?>
});
</script>

<?php require_once 'includes/footer.php'; ?>
