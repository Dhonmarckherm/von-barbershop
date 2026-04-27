<?php
/**
 * Booking Page
 * Customers enter their desired haircut/style, location, select a date, and available time slot.
 * AJAX is used to fetch available slots dynamically when a date is selected.
 */
$pageTitle = 'Book Appointment';
require_once 'includes/auth_check.php';
require_once 'config/db.php';

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body p-4">
                <h2 class="text-center mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Book an Appointment</h2>
                <p class="text-center mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Tell us what you want and where to meet</p>

                <form method="POST" action="process_booking.php" id="bookingForm">
                    <div class="mb-3">
                        <label for="haircut_description" class="form-label">What haircut / style do you want?</label>
                        <input type="text" class="form-control" id="haircut_description" name="haircut_description" 
                               placeholder="e.g. Low Taper, Blow Out Taper, Mid Fade, etc." required>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location / Address</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               placeholder="Enter the address where the barber should go" required>
                    </div>

                    <div class="mb-3">
                        <label for="appointment_date" class="form-label">Select Date</label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" required
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Available Time Slots</label>
                        <div id="slotsContainer" class="d-flex flex-wrap gap-2">
                            <p style="color: var(--text-secondary);">Please select a date to view available slots.</p>
                        </div>
                        <input type="hidden" id="appointment_time" name="appointment_time" required>
                        <div id="slotError" class="text-danger small mt-1 d-none">Please select a time slot.</div>
                        <small class="mt-2 d-block" style="color: var(--text-secondary);">
                            <span class="badge bg-secondary">Gray slots</span> are already booked and unavailable
                        </small>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>Confirm Booking</button>
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

        fetch('api/get_slots.php?date=' + encodeURIComponent(date))
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
                        // Available slot
                        btn.className = 'btn btn-outline-primary time-slot';
                        btn.textContent = formatTime(slot.time);
                        btn.addEventListener('click', function() {
                            document.querySelectorAll('.time-slot:not(.booked)').forEach(b => b.classList.remove('selected'));
                            btn.classList.add('selected');
                            timeInput.value = slot.time;
                            submitBtn.disabled = false;
                            slotError.classList.add('d-none');
                        });
                    } else {
                        // Booked slot - disabled
                        btn.className = 'btn btn-outline-secondary time-slot booked';
                        btn.textContent = formatTime(slot.time);
                        btn.disabled = true;
                        btn.title = 'Already booked';
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

    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        if (!timeInput.value) {
            e.preventDefault();
            slotError.classList.remove('d-none');
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
