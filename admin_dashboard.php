<?php
/**
 * Admin Dashboard
 * Displays all appointments in a FullCalendar.js calendar view
 * and a detailed table with inline action buttons.
 */
$pageTitle = 'Admin Dashboard';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';
require_once 'config/db.php';

// Summary stats
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
$stats = $stmt->fetchAll();
$statMap = ['pending' => 0, 'accepted' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($stats as $s) {
    $statMap[$s['status']] = $s['count'];
}

// Fetch all appointments for the table
$stmt = $pdo->query("SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.haircut_description, a.location, a.created_at, u.name AS customer_name, u.email AS customer_email FROM appointments a JOIN users u ON a.user_id = u.id ORDER BY a.appointment_date DESC, a.appointment_time DESC");
$appointments = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<h2 class="mb-4">Admin Dashboard</h2>
<p class="mb-4" style="color: var(--text-muted); font-family: 'Poppins', sans-serif; letter-spacing: 1px; font-size: 0.9rem;">Manage appointments and schedules</p>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-6 col-md-3 mb-3">
        <div class="stats-card stats-pending">
            <div class="stats-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 3.5a.5.5 0 0 0-1 0V8a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 7.71V3.5z"/>
                    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/>
                </svg>
            </div>
            <div class="stats-number"><?php echo $statMap['pending']; ?></div>
            <div class="stats-label">Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="stats-card stats-accepted">
            <div class="stats-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                </svg>
            </div>
            <div class="stats-number"><?php echo $statMap['accepted']; ?></div>
            <div class="stats-label">Accepted</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="stats-card stats-completed">
            <div class="stats-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    <path d="M10.5 3.5a.5.5 0 0 0-1 0v5a.5.5 0 0 0 1 0v-5z"/>
                </svg>
            </div>
            <div class="stats-number"><?php echo $statMap['completed']; ?></div>
            <div class="stats-label">Completed</div>
        </div>
    </div>
    <div class="col-6 col-md-3 mb-3">
        <div class="stats-card stats-cancelled">
            <div class="stats-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-8 0a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 1 0v-3A.5.5 0 0 0 8 8z"/>
                </svg>
            </div>
            <div class="stats-number"><?php echo $statMap['cancelled']; ?></div>
            <div class="stats-label">Cancelled</div>
        </div>
    </div>
</div>

<!-- FullCalendar -->
<div class="card mb-4">
    <div class="card-body">
        <div id="calendar" style="min-height: 600px;"></div>
    </div>
</div>

<!-- Appointments Table -->
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">All Appointments</h4>
            <div>
                <button id="selectAllBtn" class="btn btn-sm btn-outline-secondary me-2">Select All</button>
                <button id="deleteSelectedBtn" class="btn btn-sm btn-danger" disabled>
                    <span id="selectedCount">0</span> Selected - Delete
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th width="50">
                            <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                        </th>
                        <th>Client</th>
                        <th>Haircut / Style</th>
                        <th>Location</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                        <tr data-id="<?php echo $appt['id']; ?>">
                            <td>
                                <input type="checkbox" class="form-check-input appointment-checkbox" value="<?php echo $appt['id']; ?>">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($appt['customer_name']); ?></strong><br>
                                <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($appt['customer_email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($appt['haircut_description']); ?></td>
                            <td><?php echo htmlspecialchars($appt['location']); ?></td>
                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($appt['appointment_date']))); ?></td>
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
                                <span class="badge <?php echo $badgeClass; ?> status-badge">
                                    <?php echo ucfirst(htmlspecialchars($appt['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <?php if ($appt['status'] !== 'accepted' && $appt['status'] !== 'completed' && $appt['status'] !== 'cancelled'): ?>
                                        <button class="btn btn-sm action-btn action-accept" data-id="<?php echo $appt['id']; ?>">Accept</button>
                                    <?php endif; ?>
                                    <?php if ($appt['status'] !== 'cancelled' && $appt['status'] !== 'completed'): ?>
                                        <button class="btn btn-sm action-btn action-cancel" data-id="<?php echo $appt['id']; ?>">Cancel</button>
                                    <?php endif; ?>
                                    <?php if ($appt['status'] !== 'cancelled' && $appt['status'] !== 'completed'): ?>
                                        <button class="btn btn-sm action-btn action-reschedule" data-id="<?php echo $appt['id']; ?>" data-date="<?php echo $appt['appointment_date']; ?>" data-time="<?php echo substr($appt['appointment_time'], 0, 5); ?>">Reschedule</button>
                                    <?php endif; ?>
                                    <?php if ($appt['status'] === 'accepted'): ?>
                                        <button class="btn btn-sm action-btn action-complete" data-id="<?php echo $appt['id']; ?>">Complete</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong id="statusActionText"></strong> this appointment?</p>
                <input type="hidden" id="statusAppointmentId">
                <input type="hidden" id="statusNewValue">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No</button>
                <button type="button" class="btn btn-primary btn-sm" id="confirmStatusBtn">Yes</button>
            </div>
        </div>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal fade" id="rescheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reschedule Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rescheduleAppointmentId">
                <div class="mb-3">
                    <label for="rescheduleDate" class="form-label">New Date</label>
                    <input type="date" class="form-control" id="rescheduleDate" min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="mb-3">
                    <label for="rescheduleTime" class="form-label">New Time</label>
                    <input type="time" class="form-control" id="rescheduleTime" step="1800" min="09:00" max="17:00">
                </div>
                <div id="rescheduleError" class="alert alert-danger d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-warning btn-sm" id="confirmRescheduleBtn">Reschedule</button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS & JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 600,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: 'api/get_events.php',
        eventClassNames: function(arg) {
            return ['fc-event-' + arg.event.extendedProps.status];
        }
    });

    calendar.render();

    // Status modal
    const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    const statusAppointmentId = document.getElementById('statusAppointmentId');
    const statusNewValue = document.getElementById('statusNewValue');
    const statusActionText = document.getElementById('statusActionText');

    // Accept buttons
    document.querySelectorAll('.action-accept').forEach(btn => {
        btn.addEventListener('click', function() {
            statusAppointmentId.value = this.dataset.id;
            statusNewValue.value = 'accepted';
            statusActionText.textContent = 'ACCEPT';
            statusModal.show();
        });
    });

    // Cancel buttons
    document.querySelectorAll('.action-cancel').forEach(btn => {
        btn.addEventListener('click', function() {
            statusAppointmentId.value = this.dataset.id;
            statusNewValue.value = 'cancelled';
            statusActionText.textContent = 'CANCEL';
            statusModal.show();
        });
    });

    // Complete buttons
    document.querySelectorAll('.action-complete').forEach(btn => {
        btn.addEventListener('click', function() {
            statusAppointmentId.value = this.dataset.id;
            statusNewValue.value = 'completed';
            statusActionText.textContent = 'COMPLETE';
            statusModal.show();
        });
    });

    // Confirm status change
    document.getElementById('confirmStatusBtn').addEventListener('click', function() {
        const id = statusAppointmentId.value;
        const status = statusNewValue.value;

        fetch('api/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'appointment_id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(status)
        })
        .then(r => r.json())
        .then(data => {
            statusModal.hide();
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to update status');
            }
        })
        .catch(err => {
            console.error(err);
            alert('An error occurred');
        });
    });

    // Reschedule modal
    const rescheduleModal = new bootstrap.Modal(document.getElementById('rescheduleModal'));
    const rescheduleAppointmentId = document.getElementById('rescheduleAppointmentId');
    const rescheduleDate = document.getElementById('rescheduleDate');
    const rescheduleTime = document.getElementById('rescheduleTime');
    const rescheduleError = document.getElementById('rescheduleError');

    // Reschedule buttons
    document.querySelectorAll('.action-reschedule').forEach(btn => {
        btn.addEventListener('click', function() {
            rescheduleAppointmentId.value = this.dataset.id;
            rescheduleDate.value = this.dataset.date;
            rescheduleTime.value = this.dataset.time;
            rescheduleError.classList.add('d-none');
            rescheduleModal.show();
        });
    });

    // Confirm reschedule
    document.getElementById('confirmRescheduleBtn').addEventListener('click', function() {
        const id = rescheduleAppointmentId.value;
        const date = rescheduleDate.value;
        const time = rescheduleTime.value;

        if (!date || !time) {
            rescheduleError.textContent = 'Please select both date and time.';
            rescheduleError.classList.remove('d-none');
            return;
        }

        rescheduleError.classList.add('d-none');

        fetch('api/reschedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'appointment_id=' + encodeURIComponent(id) + '&new_date=' + encodeURIComponent(date) + '&new_time=' + encodeURIComponent(time)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                rescheduleModal.hide();
                location.reload();
            } else {
                rescheduleError.textContent = data.error || 'Failed to reschedule.';
                rescheduleError.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            rescheduleError.textContent = 'An error occurred.';
            rescheduleError.classList.remove('d-none');
        });
    });

    // ==========================================
    // SELECT AND DELETE MULTIPLE APPOINTMENTS
    // ==========================================
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const appointmentCheckboxes = document.querySelectorAll('.appointment-checkbox');

    function updateSelectedCount() {
        const checked = document.querySelectorAll('.appointment-checkbox:checked').length;
        selectedCountSpan.textContent = checked;
        deleteSelectedBtn.disabled = checked === 0;
    }

    // Select All checkbox in header
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            appointmentCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateSelectedCount();
        });
    }

    // Select All button
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            const allChecked = Array.from(appointmentCheckboxes).every(cb => cb.checked);
            appointmentCheckboxes.forEach(cb => {
                cb.checked = !allChecked;
            });
            selectAllCheckbox.checked = !allChecked;
            updateSelectedCount();
        });
    }

    // Individual checkbox changes
    appointmentCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    // Delete Selected button
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', function() {
            const selectedIds = Array.from(document.querySelectorAll('.appointment-checkbox:checked'))
                .map(cb => cb.value);
            
            if (selectedIds.length === 0) {
                alert('No appointments selected.');
                return;
            }

            const confirmMsg = `Are you sure you want to delete ${selectedIds.length} appointment(s)?\n\nThis action cannot be undone.`;
            if (confirm(confirmMsg)) {
                // Send delete request
                fetch('api/delete_appointments.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'ids[]=' + selectedIds.join('&ids[]=')
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message || 'Appointments deleted successfully.');
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to delete appointments.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('An error occurred while deleting.');
                });
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
