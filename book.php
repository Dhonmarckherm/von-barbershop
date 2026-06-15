<?php
/**
 * Booking Page
 * Customers enter their desired haircut/style, location, select a date, and available time slot.
 * AJAX is used to fetch available slots dynamically when a date is selected.
 * Version: 2026-06-06-v2
 */
$pageTitle = 'Book Appointment';
require_once 'includes/auth_check.php';
require_once 'config/db.php';

require_once 'includes/header.php';

// Display booking errors if any
if (isset($_SESSION['booking_errors']) && !empty($_SESSION['booking_errors'])) {
    $bookingErrors = $_SESSION['booking_errors'];
    unset($_SESSION['booking_errors']);
    
    echo '<div class="alert alert-danger" style="background: rgba(220,53,69,0.15); border: 2px solid #dc3545; color: #ff6b6b; border-radius: 10px; padding: 15px; margin-bottom: 20px;">';
    echo '<i class="bi bi-exclamation-triangle-fill"></i> <strong>Booking Error:</strong><br>';
    foreach ($bookingErrors as $error) {
        echo '• ' . htmlspecialchars($error) . '<br>';
    }
    echo '</div>';
}

// Restore old form input if exists
$oldInput = $_SESSION['booking_old_input'] ?? [];
unset($_SESSION['booking_old_input']);
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-3" style="color: var(--barber-gold); font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: bold;">Book an Appointment</h2>
                <p class="text-center mb-2" style="color: #F5F0E8; font-size: 1rem; line-height: 1.6; opacity: 0.9;">
                    Choose a date and time that works for you and enjoy priority service—no waiting, no hassle.
                </p>
                <p class="text-center mb-4" style="color: var(--barber-gold); font-size: 0.95rem; line-height: 1.6; font-weight: 500; opacity: 0.95;">
                    <i class="bi bi-info-circle"></i> Reserve your preferred schedule and enjoy a seamless, wait-free haircut experience.
                </p>

                <form method="POST" action="process_booking.php" id="bookingForm">
                    <div class="mb-4">
                        <label for="haircut_description" class="form-label" style="color: #F5F0E8; font-weight: 600; font-size: 15px; margin-bottom: 8px;">
                            <i class="bi bi-scissors" style="color: var(--barber-gold);"></i> What haircut / style do you want?
                        </label>
                        <input type="text" class="form-control" id="haircut_description" name="haircut_description" 
                               placeholder="e.g. Low Taper, Blow Out Taper, Mid Fade, Consultation, etc." 
                               value="<?php echo htmlspecialchars($oldInput['haircut_description'] ?? ''); ?>"
                               style="background: rgba(255,255,255,0.08); border: 2px solid rgba(192,192,192,0.3); color: #FFFFFF; padding: 14px 16px; border-radius: 10px; font-size: 15px;" required>
                        <style>
                            #haircut_description::placeholder { color: rgba(255,255,255,0.5); }
                        </style>
                        <div class="mt-3">
                            <p style="color: #B8B8CC; font-size: 13px; margin-bottom: 10px; font-weight: 500;">Quick select:</p>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn quick-style-btn" onclick="setStyle('Consultation')" 
                                        style="background: rgba(197,160,89,0.15); border: 2px solid var(--barber-gold); color: var(--barber-gold); padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 13px; transition: all 0.3s ease;">
                                    <i class="bi bi-chat-dots"></i> Consultation
                                </button>
                                <button type="button" class="btn quick-style-btn" onclick="setStyle('Low Taper Fade')" 
                                        style="background: rgba(255,255,255,0.05); border: 2px solid rgba(192,192,192,0.4); color: #F5F0E8; padding: 8px 16px; border-radius: 20px; font-weight: 500; font-size: 13px; transition: all 0.3s ease;">
                                    Low Taper Fade
                                </button>
                                <button type="button" class="btn quick-style-btn" onclick="setStyle('Mid Fade')" 
                                        style="background: rgba(255,255,255,0.05); border: 2px solid rgba(192,192,192,0.4); color: #F5F0E8; padding: 8px 16px; border-radius: 20px; font-weight: 500; font-size: 13px; transition: all 0.3s ease;">
                                    Mid Fade
                                </button>
                                <button type="button" class="btn quick-style-btn" onclick="setStyle('Buzz Cut')" 
                                        style="background: rgba(255,255,255,0.05); border: 2px solid rgba(192,192,192,0.4); color: #F5F0E8; padding: 8px 16px; border-radius: 20px; font-weight: 500; font-size: 13px; transition: all 0.3s ease;">
                                    Buzz Cut
                                </button>
                            </div>
                        </div>
                        <small class="d-block mt-3" style="color: var(--barber-gold); font-size: 13px; background: rgba(197,160,89,0.1); padding: 10px 14px; border-radius: 8px; border-left: 3px solid var(--barber-gold);">
                            <i class="bi bi-lightbulb"></i> <strong>Not sure what style?</strong> Just click <strong>Consultation</strong> and we'll help you choose!
                        </small>
                    </div>

                    <div class="mb-4">
                        <label for="location" class="form-label" style="color: #F5F0E8; font-weight: 600; font-size: 15px; margin-bottom: 8px;">
                            <i class="bi bi-geo-alt" style="color: var(--barber-gold);"></i> Location / Address
                        </label>
                        <input type="text" class="form-control" id="location" name="location" 
                               placeholder="Enter the address where you're located" 
                               value="<?php echo htmlspecialchars($oldInput['location'] ?? ''); ?>"
                               style="background: rgba(255,255,255,0.08); border: 2px solid rgba(192,192,192,0.3); color: #FFFFFF; padding: 14px 16px; border-radius: 10px; font-size: 15px;" required>
                        <style>
                            #location::placeholder { color: rgba(255,255,255,0.5); }
                        </style>
                    </div>

                    <div class="mb-4">
                        <label for="appointment_date" class="form-label" style="color: #F5F0E8; font-weight: 600; font-size: 15px; margin-bottom: 8px;">
                            <i class="bi bi-calendar3" style="color: var(--barber-gold);"></i> Select Date
                        </label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" required
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo htmlspecialchars($oldInput['appointment_date'] ?? ''); ?>"
                               style="background: rgba(255,255,255,0.08); border: 2px solid rgba(192,192,192,0.3); color: #FFFFFF; padding: 14px 16px; border-radius: 10px; font-size: 15px;">
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="color: #F5F0E8; font-weight: 600; font-size: 15px; margin-bottom: 8px;">
                            <i class="bi bi-clock" style="color: var(--barber-gold);"></i> Available Time Slots
                        </label>
                        <div id="slotsContainer" class="d-flex flex-wrap gap-2" style="min-height: 60px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 10px; border: 1px solid rgba(192,192,192,0.2);">
                            <p style="color: #B8B8CC; margin: 0;">Please select a date to view available slots.</p>
                        </div>
                        <input type="hidden" id="appointment_time" name="appointment_time" required>
                        <div id="slotError" class="text-danger small mt-2 d-none" style="color: #ff6b6b !important;"><i class="bi bi-exclamation-circle"></i> Please select a time slot.</div>
                        <small class="mt-3 d-block" style="color: #B8B8CC; font-size: 13px;">
                            <span style="display: inline-block; background: rgba(115,115,115,0.3); color: #999; padding: 3px 10px; border-radius: 12px; font-size: 12px; text-decoration: line-through;">Booked</span> slots are already taken
                        </small>
                    </div>

                    <!-- Price Reminder -->
                    <div class="mb-3" style="background: rgba(192,192,192,0.1); border-left: 4px solid #c0c0c0; padding: 15px 18px; border-radius: 10px;">
                        <div class="d-flex align-items-start" style="gap: 12px;">
                            <i class="bi bi-info-circle-fill" style="font-size: 22px; color: #c0c0c0; flex-shrink: 0; margin-top: 2px;"></i>
                            <div>
                                <p style="color: #ffffff; font-size: 14px; font-weight: 600; margin: 0 0 5px 0;">Service Fee Reminder</p>
                                <p style="color: #c0c0c0; font-size: 13px; margin: 0; line-height: 1.5;">
                                    All haircuts and services are <strong style="color: #ffffff; font-size: 15px;">₱150</strong> each. Payment is collected at the shop after your appointment.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled 
                                style="background: linear-gradient(135deg, #FFFFFF 0%, #C0C0C0 100%); border: none; color: #1a1a2e; padding: 14px 24px; border-radius: 10px; font-weight: 700; font-size: 16px; letter-spacing: 1px; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(192,192,192,0.3);">
                            <i class="bi bi-check-circle"></i> Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * AJAX Flow for Real-Time Slot Checking:
 * 1. User selects a date.
 * 2. JavaScript fetch() calls api/get_slots.php with date as query param.
 * 3. Backend queries the database for booked times on that date.
 * 4. Backend generates all possible slots (9:00 AM - 5:00 PM, 30-min intervals).
 * 5. Booked slots are marked as unavailable, available slots are marked.
 * 6. Frontend dynamically renders clickable time slot buttons.
 * 7. Booked slots are shown as disabled/grayed out.
 * 8. When an available slot is clicked, it populates the hidden input and enables the submit button.
 */

// Helper function to format time from 24h to 12h format
function formatTime(time24) {
    const [hours, minutes] = time24.split(':');
    const h = parseInt(hours);
    const ampm = h >= 12 ? 'PM' : 'AM';
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
}

document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('appointment_date');
    const slotsContainer = document.getElementById('slotsContainer');
    const timeInput = document.getElementById('appointment_time');
    const submitBtn = document.getElementById('submitBtn');
    const slotError = document.getElementById('slotError');

    function fetchSlots() {
        const date = dateInput.value;

        if (!date) {
            slotsContainer.innerHTML = '<p style="color: var(--text-secondary);">Please select a date to view available slots.</p>';
            submitBtn.disabled = true;
            return;
        }

        slotsContainer.innerHTML = '<p style="color: var(--text-secondary);">Loading slots...</p>';

        fetch('api/get_slots.php?date=' + encodeURIComponent(date), {
            credentials: 'include'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                slotsContainer.innerHTML = '';
                
                if (!data.slots || data.slots.length === 0) {
                    slotsContainer.innerHTML = '<p class="text-muted">No slots available for this date. Please choose another date.</p>';
                    submitBtn.disabled = true;
                    return;
                }

                // Check if there are any available slots
                const availableSlots = data.slots.filter(slot => slot.available);
                if (availableSlots.length === 0) {
                    slotsContainer.innerHTML = '<p class="text-muted">All slots are booked for this date. Please choose another date.</p>';
                    submitBtn.disabled = true;
                    return;
                }

                data.slots.forEach(slot => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    
                    if (slot.available) {
                        // Available slot - modern styling with better visibility
                        btn.className = 'btn time-slot';
                        
                        // Check if this is the 6 PM slot with additional fee
                        if (slot.has_additional_fee) {
                            btn.innerHTML = formatTime(slot.time) + '<br><small style="font-size:10px;color:#C5A059;">+Additional Fee</small>';
                            btn.style.cssText = 'background: rgba(197,160,89,0.15); border: 2px solid rgba(197,160,89,0.6); color: #1a1a2e; padding: 10px 18px; border-radius: 20px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;';
                        } else {
                            btn.textContent = formatTime(slot.time);
                            btn.style.cssText = 'background: rgba(255,255,255,0.95); border: 2px solid rgba(192,192,192,0.6); color: #1a1a2e; padding: 10px 18px; border-radius: 20px; font-weight: 600; font-size: 14px; transition: all 0.3s ease;';
                        }
                        
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('.time-slot:not(.booked)').forEach(b => {
                                b.classList.remove('selected');
                                b.style.background = slot.has_additional_fee ? 'rgba(197,160,89,0.15)' : 'rgba(255,255,255,0.95)';
                                b.style.borderColor = slot.has_additional_fee ? 'rgba(197,160,89,0.6)' : 'rgba(192,192,192,0.6)';
                                b.style.color = '#1a1a2e';
                                b.style.fontWeight = '600';
                            });
                            btn.classList.add('selected');
                            btn.style.background = slot.has_additional_fee ? 'linear-gradient(135deg, #C5A059 0%, #D4AF37 100%)' : 'linear-gradient(135deg, #2c2c2c 0%, #1a1a1a 100%)';
                            btn.style.borderColor = slot.has_additional_fee ? '#D4AF37' : '#FFFFFF';
                            btn.style.color = '#FFFFFF';
                            btn.style.fontWeight = '700';
                            btn.style.boxShadow = slot.has_additional_fee ? '0 4px 15px rgba(197,160,89,0.4)' : '0 4px 15px rgba(255,255,255,0.3)';
                            timeInput.value = slot.time;
                            submitBtn.disabled = false;
                            slotError.classList.add('d-none');
                        });
                    } else {
                        // Booked slot - disabled with better styling
                        btn.className = 'btn time-slot booked';
                        btn.textContent = formatTime(slot.time);
                        btn.style.cssText = 'background: rgba(115,115,115,0.15); border: 2px solid rgba(115,115,115,0.3); color: #888; padding: 10px 18px; border-radius: 20px; font-weight: 400; font-size: 14px; text-decoration: line-through; cursor: not-allowed; opacity: 0.6; position: relative;';
                        btn.disabled = true;
                        btn.title = 'Already booked';
                        
                        // Add "Booked" badge
                        const badge = document.createElement('span');
                        badge.style.cssText = 'position: absolute; top: -8px; right: -5px; background: #6c757d; color: #fff; font-size: 10px; padding: 2px 6px; border-radius: 8px; font-weight: 600;';
                        badge.textContent = 'Booked';
                        btn.style.position = 'relative';
                        btn.appendChild(badge);
                    }
                    
                    slotsContainer.appendChild(btn);
                });
            })
            .catch(error => {
                console.error('Error fetching slots:', error);
                slotsContainer.innerHTML = '<p class="text-danger">Failed to load slots. Please try again.</p>';
            });
    }

    dateInput.addEventListener('change', fetchSlots);
    
    // If there's an old date value, fetch slots automatically
    if (dateInput.value) {
        fetchSlots();
    }

    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        console.log('[Booking] Form submitted');
        console.log('[Booking] Time value:', timeInput.value);
        
        if (!timeInput.value) {
            e.preventDefault();
            slotError.classList.remove('d-none');
            console.log('[Booking] Prevented submit - no time selected');
        } else {
            console.log('[Booking] Allowing form submission to process_booking.php');
            // Disable submit button to prevent double-submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
        }
    });
});

// Quick style selection function
function setStyle(styleName) {
    document.getElementById('haircut_description').value = styleName;
    
    // Visual feedback - highlight the selected button
    document.querySelectorAll('.quick-style-btn').forEach(btn => {
        btn.style.background = 'rgba(255,255,255,0.05)';
        btn.style.border = '2px solid rgba(192,192,192,0.4)';
        btn.style.color = '#F5F0E8';
        btn.style.fontWeight = '500';
    });
    
    const selectedBtn = event.target.closest('.quick-style-btn');
    selectedBtn.style.background = 'rgba(197,160,89,0.2)';
    selectedBtn.style.border = '2px solid var(--barber-gold)';
    selectedBtn.style.color = 'var(--barber-gold)';
    selectedBtn.style.fontWeight = '700';
}
</script>

<style>
/* Confirm Booking button hover effect */
#submitBtn:not(:disabled):hover {
    background: linear-gradient(135deg, #F5F5F5 0%, #A8A8A8 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(192,192,192,0.5) !important;
}

#submitBtn:not(:disabled):active {
    transform: translateY(0);
    box-shadow: 0 2px 10px rgba(192,192,192,0.3) !important;
}

#submitBtn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: linear-gradient(135deg, #E0E0E0 0%, #B0B0B0 100%) !important;
}
</style>

<?php require_once 'includes/footer.php'; ?>
