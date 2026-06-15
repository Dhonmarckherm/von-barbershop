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

// Check if this is a new user who just registered
$isNewUser = isset($_GET['welcome']) && $_GET['welcome'] == '1';

// Check if user just reset their password
$isPasswordReset = isset($_GET['password_reset']) && $_GET['password_reset'] == '1';
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

<?php if ($isNewUser): ?>
<!-- New User Welcome Message -->
<div class="alert alert-success mb-4" style="background: rgba(40, 167, 69, 0.15); border: 2px solid #28a745; color: #ffffff; border-radius: 15px; padding: 20px; animation: fadeSlideIn 0.5s ease;">
    <div class="d-flex align-items-center">
        <i class="bi bi-emoji-smile-fill" style="font-size: 32px; margin-right: 15px; color: #28a745;"></i>
        <div>
            <h5 style="color: #28a745; margin: 0 0 5px 0; font-weight: bold;">Welcome to VON BARBER STUDIO! 🎉</h5>
            <p style="margin: 0; color: #b0b0b0;">Your account has been created successfully. Enable Face ID/Touch ID for instant login next time!</p>
        </div>
    </div>
</div>
<?php endif; ?>

<h2 class="mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">My Appointments</h2>
<p class="mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Your grooming history</p>

<?php if (isset($_GET['booked'])): ?>
    <div class="alert alert-success booking-success-alert" id="bookingSuccessAlert" style="animation: fadeSlideIn 0.5s ease;">
        <i class="bi bi-check-circle-fill"></i> Your appointment has been booked successfully!
    </div>
    <?php if (isset($_GET['email']) && $_GET['email'] === 'sent'): ?>
        <div class="alert alert-info booking-email-alert" id="bookingEmailAlert" style="animation: fadeSlideIn 0.5s ease 0.2s both;">
            <i class="bi bi-envelope-fill"></i> A confirmation email has been sent to your Gmail.
        </div>
    <?php elseif (isset($_GET['email']) && $_GET['email'] === 'failed'): ?>
        <div class="alert alert-warning booking-email-alert" id="bookingEmailAlert" style="animation: fadeSlideIn 0.5s ease 0.2s both;">
            <i class="bi bi-exclamation-triangle-fill"></i> Booking saved, but the confirmation email failed to send. Please contact the barber directly.
        </div>
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
                               aria-label="Select new appointment date"
                               style="background: rgba(255,255,255,0.08); border: 1px solid rgba(192,192,192,0.4); color: #f5f5f5; padding: 12px 15px; border-radius: 10px; font-size: 15px;" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="rescheduleTime" class="form-label" style="color: #f5f5f5; font-weight: 600; font-size: 14px;">
                            <i class="bi bi-clock" style="color: #c0c0c0;"></i> New Time
                        </label>
                        <input type="text" class="form-control" id="rescheduleTime" placeholder="Select time..." 
                               aria-label="Select new appointment time"
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

@keyframes fadeSlideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes fadeSlideOut {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(-20px);
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
    dateFormat: 'h:i K',  // 12-hour format with AM/PM (e.g., 3:00 PM)
    time_24hr: false,      // Use 12-hour format
    minTime: '10:00',
    maxTime: '18:00',      // 6:00 PM last slot
    minuteIncrement: 60,   // 1-hour intervals
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
            body: formData,
            credentials: 'include'
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
    
    // Convert 12-hour format (3:00 PM) to 24-hour format (15:00) for backend
    let time = timeValue;
    
    // Check if time contains AM/PM
    const hasAMPM = /AM|PM/i.test(time);
    if (hasAMPM) {
        // Parse 12-hour format and convert to 24-hour
        const match = time.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
        if (match) {
            let hours = parseInt(match[1]);
            const minutes = match[2];
            const period = match[3].toUpperCase();
            
            // Convert to 24-hour format
            if (period === 'PM' && hours !== 12) {
                hours += 12;
            } else if (period === 'AM' && hours === 12) {
                hours = 0;
            }
            
            time = String(hours).padStart(2, '0') + ':' + minutes;
        }
    }
    
    // Ensure seconds are included
    if (time && !time.includes(':00')) {
        time = time + ':00';
    }
    
    document.getElementById('rescheduleError').classList.add('d-none');
    
    try {
        const response = await fetch('api/customer_reschedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'appointment_id=' + encodeURIComponent(id) + '&new_date=' + encodeURIComponent(date) + '&new_time=' + encodeURIComponent(time),
            credentials: 'include'
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Show push notification if in Capacitor app
            if (result.notification && typeof showNotification === 'function' && isCapacitorNative()) {
                showNotification(result.notification.title, result.notification.body, result.notification.id);
            }
            
            // Show beautiful toast notification instead of ugly alert
            showSuccessMessage('Appointment rescheduled successfully! Barber has been notified.');
            rescheduleModal.hide();
            
            // Reload after toast is visible
            setTimeout(() => location.reload(), 1500);
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
            body: 'appointment_id=' + encodeURIComponent(appointmentId),
            credentials: 'include'
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                // Show push notification if in Capacitor app
                if (result.notification && typeof showNotification === 'function' && isCapacitorNative()) {
                    showNotification(result.notification.title, result.notification.body, result.notification.id);
                }
                
                // Show success message
                showSuccessMessage('Appointment cancelled successfully! Barber has been notified.');
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
    
    // Auto-dismiss booking success alerts after 5 seconds
    const successAlert = document.getElementById('bookingSuccessAlert');
    const emailAlert = document.getElementById('bookingEmailAlert');
    
    if (successAlert || emailAlert) {
        setTimeout(() => {
            // Fade out email alert first
            if (emailAlert) {
                emailAlert.style.animation = 'fadeSlideOut 0.5s ease forwards';
                setTimeout(() => emailAlert.remove(), 500);
            }
            
            // Then fade out success alert
            if (successAlert) {
                setTimeout(() => {
                    successAlert.style.animation = 'fadeSlideOut 0.5s ease forwards';
                    setTimeout(() => {
                        successAlert.remove();
                        // Clean URL parameters to prevent showing message on refresh
                        const url = new URL(window.location);
                        url.searchParams.delete('booked');
                        url.searchParams.delete('email');
                        window.history.replaceState({}, document.title, url.toString());
                    }, 500);
                }, 300);
            }
        }, 5000); // 5 seconds
    }
});
</script>

<!-- Biometric Enrollment Modal -->
<div class="modal fade" id="biometricPromptModal" tabindex="-1" aria-labelledby="biometricPromptLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: rgba(30, 30, 30, 0.95); backdrop-filter: blur(10px); border: 1px solid rgba(197, 160, 89, 0.3); border-radius: 15px;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(197, 160, 89, 0.2); padding: 20px 25px 15px;">
                <h5 class="modal-title" id="biometricPromptLabel" style="color: var(--barber-gold); font-family: 'Playfair Display', serif; font-size: 1.5rem;">
                    <i class="bi bi-fingerprint"></i> <span id="biometricModalTitle">Enable Quick Login?</span>
                </h5>
            </div>
            <div class="modal-body" style="padding: 20px 25px;">
                <p style="color: #F5F0E8; font-size: 1rem; line-height: 1.6; margin-bottom: 15px;" id="biometricModalMessage">
                    Would you like to enable <strong style="color: var(--barber-gold);">biometric login</strong> for faster access?
                </p>
                <div style="background: rgba(197, 160, 89, 0.1); border-left: 3px solid var(--barber-gold); padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                    <p style="color: #F5F0E8; margin: 0; font-size: 0.9rem;">
                        <i class="bi bi-check-circle-fill" style="color: var(--barber-gold);"></i> Login with fingerprint or face recognition<br>
                        <i class="bi bi-check-circle-fill" style="color: var(--barber-gold);"></i> No need to type email & password<br>
                        <i class="bi bi-check-circle-fill" style="color: var(--barber-gold);"></i> Secure & encrypted on your device
                    </p>
                </div>
                <p style="color: #8A8A9A; font-size: 0.85rem; margin: 0;">
                    <i class="bi bi-info-circle"></i> You can always enable this later from your profile settings.
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(197, 160, 89, 0.2); padding: 15px 25px 20px;">
                <button type="button" class="btn btn-secondary" id="biometric-skip-btn" style="border-radius: 8px;">
                    Not Now
                </button>
                <button type="button" class="btn btn-primary" id="biometric-enable-btn" style="border-radius: 8px; background: linear-gradient(135deg, #C5A059 0%, #D4AF37 100%); border: none; color: #1a1a1a; font-weight: 700; padding: 12px 24px; box-shadow: 0 4px 15px rgba(197,160,89,0.4);">
                    <i class="bi bi-fingerprint"></i> Enable Now
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Biometric Auth Script -->
<script src="/www/js/biometric-auth.js"></script>
<script>
// Show biometric prompt after login if requested
document.addEventListener('DOMContentLoaded', async function() {
    console.log('[Biometric] ===== START =====');
    console.log('[Biometric] Page loaded');
    
    // Check if we should show biometric prompt
    const urlParams = new URLSearchParams(window.location.search);
    const showBiometricPrompt = urlParams.get('biometric_prompt') === '1';
    const isPasswordReset = urlParams.get('password_reset') === '1';
    
    console.log('[Biometric] showBiometricPrompt:', showBiometricPrompt);
    console.log('[Biometric] isPasswordReset:', isPasswordReset);
    console.log('[Biometric] URL:', window.location.href);
    
    if (!showBiometricPrompt) {
        console.log('[Biometric] No biometric_prompt parameter - skipping');
        return;
    }
    
    // Update modal content for password reset users
    if (isPasswordReset) {
        const titleEl = document.getElementById('biometricModalTitle');
        const messageEl = document.getElementById('biometricModalMessage');
        
        if (titleEl) {
            titleEl.textContent = 'Re-enable Biometric Login';
        }
        
        if (messageEl) {
            messageEl.innerHTML = 'Your password has been reset. For security, you need to <strong style="color: var(--barber-gold);">re-enable biometric login</strong> on this device.';
        }
        
        console.log('[Biometric] Updated modal for password reset user');
    }
    
    if (typeof BiometricAuth === 'undefined') {
        console.error('[Biometric] BiometricAuth library NOT loaded!');
        return;
    }
    
    console.log('[Biometric] BiometricAuth library loaded: YES');
    
    // Check if biometrics are supported
    const isSupported = BiometricAuth.isSupported();
    console.log('[Biometric] WebAuthn supported:', isSupported);
    
    if (!isSupported) {
        console.log('[Biometric] WebAuthn not supported - skipping');
        return;
    }
    
    const isAvailable = await BiometricAuth.isBiometricAvailable();
    console.log('[Biometric] Biometric hardware available:', isAvailable);
    
    if (!isAvailable) {
        console.log('[Biometric] No biometric hardware - skipping');
        return;
    }
    
    // IMPORTANT: Check if THIS SPECIFIC USER has credentials registered
    // Use a dedicated API endpoint that checks for current user only
    try {
        const checkResponse = await fetch('/api/check_biometric_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });
        
        const checkResult = await checkResponse.json();
        console.log('[Biometric] User credential status:', checkResult);
        
        // If user ALREADY has credentials on THIS device, DON'T show modal
        if (checkResult.hasCredentials && checkResult.hasCredentials === true) {
            console.log('[Biometric] User already enrolled - skipping modal (PERMANENT)');
            // Clean URL
            window.history.replaceState({}, document.title, window.location.pathname);
            return; // Don't show modal - user already enrolled!
        }
        
        console.log('[Biometric] User NOT enrolled - showing modal');
    } catch (err) {
        console.error('[Biometric] Error checking credentials:', err);
        // If check fails, show modal anyway to be safe
    }
    
    console.log('[Biometric] ===== SHOWING MODAL =====');
    // Clean URL (remove query parameter)
    window.history.replaceState({}, document.title, window.location.pathname);
    
    // Show a brief notification before modal
    const bioNotification = document.createElement('div');
    bioNotification.id = 'biometric-notification';
    bioNotification.style.cssText = 'position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #C5A059 0%, #D4AF37 100%); color: #1a1a1a; padding: 12px 24px; border-radius: 10px; font-weight: bold; z-index: 9998; box-shadow: 0 4px 15px rgba(197,160,89,0.4); animation: fadeSlideIn 0.5s ease;';
    bioNotification.innerHTML = '<i class="bi bi-fingerprint"></i> Setting up quick login...';
    document.body.appendChild(bioNotification);
    
    // Show modal after a short delay
    setTimeout(function() {
        // Remove notification
        if (bioNotification) {
            bioNotification.style.animation = 'fadeSlideOut 0.3s ease';
            setTimeout(() => bioNotification.remove(), 300);
        }
        
        try {
            const modalElement = document.getElementById('biometricPromptModal');
            if (!modalElement) {
                console.error('[Biometric] Modal element not found!');
                alert('Biometric enrollment modal not found. Please contact support.');
                return;
            }
            
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
            console.log('[Biometric] Modal shown successfully');
        } catch (error) {
            console.error('[Biometric] Error showing modal:', error);
            alert('Error showing biometric modal: ' + error.message);
        }
    }, 1500); // Increased delay to 1.5s for better reliability
    
    // Handle Enable button
    document.getElementById('biometric-enable-btn').addEventListener('click', async function() {
        const enableBtn = this;
        const skipBtn = document.getElementById('biometric-skip-btn');
        
        enableBtn.disabled = true;
        skipBtn.disabled = true;
        enableBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Setting up...';
        
        const result = await BiometricAuth.register(
            '<?php echo htmlspecialchars($_SESSION["email"] ?? ""); ?>',
            <?php echo (int)($_SESSION["user_id"] ?? 0); ?>
        );
        
        if (result.success) {
            enableBtn.innerHTML = '<i class="bi bi-check-circle"></i> Enabled!';
            enableBtn.style.background = '#28a745';
            enableBtn.style.borderColor = '#28a745';
            
            console.log('[Biometric Enrollment] Success:', result);
            
            // Close modal
            setTimeout(function() {
                const modal = bootstrap.Modal.getInstance(document.getElementById('biometricPromptModal'));
                modal.hide();
                
                // Show beautiful success modal
                showBiometricSuccessModal();
            }, 500);
            
            setTimeout(function() {
                const modal = bootstrap.Modal.getInstance(document.getElementById('biometricPromptModal'));
                modal.hide();
                
                // Show success toast
                const toast = document.createElement('div');
                toast.className = 'alert alert-success';
                toast.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; background: rgba(40, 167, 69, 0.95); color: white; border-radius: 10px; padding: 15px 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
                toast.innerHTML = '<i class="bi bi-check-circle-fill"></i> Biometric login enabled! Next time you can login with fingerprint/face.';
                document.body.appendChild(toast);
                
                setTimeout(() => toast.remove(), 5000);
            }, 1000);
        } else {
            enableBtn.disabled = false;
            skipBtn.disabled = false;
            enableBtn.innerHTML = '<i class="bi bi-fingerprint"></i> Enable Now';
            alert('Failed to enable biometric login: ' + result.error);
        }
    });
    
    // Handle Skip button
    document.getElementById('biometric-skip-btn').addEventListener('click', function() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('biometricPromptModal'));
        modal.hide();
    });
});

// Beautiful biometric success modal
function showBiometricSuccessModal() {
    const modalHTML = `
        <div id="biometricSuccessOverlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); z-index: 9999; display: flex; align-items: center; justify-content: center; animation: fadeIn 0.3s ease;">
            <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border-radius: 20px; padding: 40px; max-width: 400px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 2px solid rgba(40,167,69,0.3); animation: slideUp 0.5s ease;">
                <!-- Success Icon with Animation -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; animation: scaleIn 0.5s ease 0.2s both; box-shadow: 0 10px 30px rgba(40,167,69,0.4);">
                        <i class="bi bi-check-lg" style="font-size: 48px; color: white;"></i>
                    </div>
                </div>
                
                <!-- Title -->
                <h2 style="color: #28a745; text-align: center; font-family: 'Playfair Display', serif; font-size: 28px; margin: 0 0 15px 0; animation: fadeIn 0.5s ease 0.4s both;">Welcome to V.O.N Barber Studio!</h2>
                
                <!-- Message -->
                <p style="color: #F5F0E8; text-align: center; font-size: 16px; line-height: 1.6; margin: 0 0 25px 0; animation: fadeIn 0.5s ease 0.6s both;">
                    Biometric login has been <strong style="color: #28a745;">successfully enabled</strong>!<br>
                    You can now login with Face ID or Touch ID
                </p>
                
                <!-- Features List -->
                <div style="background: rgba(40,167,69,0.1); border-left: 4px solid #28a745; padding: 15px; border-radius: 10px; margin-bottom: 25px; animation: fadeIn 0.5s ease 0.8s both;">
                    <p style="color: #F5F0E8; font-size: 14px; margin: 0 0 10px 0; font-weight: 600;">
                        <i class="bi bi-lightning-charge-fill" style="color: #28a745;"></i> What's Next?
                    </p>
                    <ul style="color: #B8B8CC; font-size: 13px; margin: 0; padding-left: 20px; line-height: 1.8;">
                        <li>Next time, just click <strong style="color: #28a745;">"Login with Biometrics"</strong></li>
                        <li>No need to type your password anymore</li>
                        <li>Works with Face ID, Touch ID, or Windows Hello</li>
                    </ul>
                </div>
                
                <!-- Done Button -->
                <button onclick="closeBiometricSuccess()" style="width: 100%; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 15px; border-radius: 10px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s; animation: fadeIn 0.5s ease 1s both;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 10px 25px rgba(40,167,69,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                    <i class="bi bi-check-circle"></i> Done
                </button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function closeBiometricSuccess() {
    const overlay = document.getElementById('biometricSuccessOverlay');
    if (overlay) {
        overlay.style.animation = 'fadeOut 0.3s ease';
        setTimeout(() => overlay.remove(), 300);
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { transform: translateY(50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    @keyframes scaleIn {
        from { transform: scale(0); }
        to { transform: scale(1); }
    }
    @keyframes fadeOut {
        from { opacity: 1; }
        to { opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>

<?php require_once 'includes/footer.php'; ?>
