<?php
/**
 * Admin User Management
 * Admin can view all users, edit emails, and reset passwords.
 */
$pageTitle = 'Manage Users';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';
require_once 'config/db.php';

$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE role != 'customer' ORDER BY role DESC, name ASC");
$users = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<h2 class="mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Profile</h2>
<p class="mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Edit admin account details</p>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] === 'admin' ? 'bg-danger' : 'bg-primary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning edit-user-btn"
                                    data-id="<?php echo $user['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                    data-role="<?php echo $user['role']; ?>">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editUserId">

                <div class="mb-3">
                    <label for="editUserName" class="form-label">Name</label>
                    <input type="text" class="form-control" id="editUserName" required>
                </div>

                <div class="mb-3">
                    <label for="editUserRole" class="form-label">Role</label>
                    <input type="text" class="form-control" id="editUserRole" placeholder="e.g. customer, admin, barber">
                </div>

                <div class="mb-3">
                    <label for="editUserEmail" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="editUserEmail" required>
                </div>

                <hr>
                <h6 class="mb-3" style="color: var(--barber-gold);">Reset Password</h6>

                <div class="mb-3">
                    <label for="editUserPassword" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="editUserPassword" minlength="6">
                    <div class="form-text">Leave blank to keep current password. Minimum 6 characters.</div>
                </div>

                <div class="mb-3">
                    <label for="editUserPasswordConfirm" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="editUserPasswordConfirm">
                </div>

                <div id="editUserError" class="alert alert-danger d-none"></div>
                <div id="editUserSuccess" class="alert alert-success d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    const editUserId = document.getElementById('editUserId');
    const editUserName = document.getElementById('editUserName');
    const editUserRole = document.getElementById('editUserRole');
    const editUserEmail = document.getElementById('editUserEmail');
    const editUserPassword = document.getElementById('editUserPassword');
    const editUserPasswordConfirm = document.getElementById('editUserPasswordConfirm');
    const editUserError = document.getElementById('editUserError');
    const editUserSuccess = document.getElementById('editUserSuccess');

    // Open edit modal
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            editUserId.value = this.dataset.id;
            document.getElementById('editUserName').value = this.dataset.name;
            document.getElementById('editUserRole').value = this.dataset.role;
            document.getElementById('editUserRole').placeholder = 'Current: ' + this.dataset.role;
            editUserEmail.value = this.dataset.email;
            editUserPassword.value = '';
            editUserPasswordConfirm.value = '';
            editUserError.classList.add('d-none');
            editUserSuccess.classList.add('d-none');
            editModal.show();
        });
    });

    // Save changes
    document.getElementById('saveUserBtn').addEventListener('click', function() {
        const id = editUserId.value;
        const name = document.getElementById('editUserName').value.trim();
        const email = editUserEmail.value.trim();
        const password = editUserPassword.value;
        const confirm = editUserPasswordConfirm.value;

        editUserError.classList.add('d-none');
        editUserSuccess.classList.add('d-none');

        // Validate name
        if (!name || name.length < 2) {
            editUserError.textContent = 'Name must be at least 2 characters.';
            editUserError.classList.remove('d-none');
            return;
        }

        // Validate email
        if (!email || !email.includes('@')) {
            editUserError.textContent = 'Please enter a valid email address.';
            editUserError.classList.remove('d-none');
            return;
        }

        // Validate password if provided
        if (password) {
            if (password.length < 6) {
                editUserError.textContent = 'Password must be at least 6 characters.';
                editUserError.classList.remove('d-none');
                return;
            }
            if (password !== confirm) {
                editUserError.textContent = 'Passwords do not match.';
                editUserError.classList.remove('d-none');
                return;
            }
        }

        const role = document.getElementById('editUserRole').value;
        let body = 'user_id=' + encodeURIComponent(id) +
                     '&name=' + encodeURIComponent(name) +
                     '&email=' + encodeURIComponent(email) +
                     '&role=' + encodeURIComponent(role);
        if (password) {
            body += '&password=' + encodeURIComponent(password);
        }

        fetch('api/update_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                editUserSuccess.textContent = 'User updated successfully!';
                editUserSuccess.classList.remove('d-none');
                setTimeout(() => {
                    editModal.hide();
                    location.reload();
                }, 1000);
            } else {
                editUserError.textContent = data.error || 'Failed to update user.';
                editUserError.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            editUserError.textContent = 'An error occurred while updating.';
            editUserError.classList.remove('d-none');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
