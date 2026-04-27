<?php
/**
 * Session Diagnostic Page
 * Use this to debug session issues
 */
require_once 'config/session.php';
initializeSession();

$pageTitle = 'Session Debug';
require_once 'includes/header.php';
?>

<h2 class="mb-4">Session Diagnostic Tool</h2>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Current Session Data</h5>
                <table class="table table-dark table-striped">
                    <tr>
                        <td><strong>Session ID:</strong></td>
                        <td><?php echo session_id(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>User ID:</strong></td>
                        <td><?php echo $_SESSION['user_id'] ?? '<span class="text-danger">Not set</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Name:</strong></td>
                        <td><?php echo $_SESSION['name'] ?? '<span class="text-danger">Not set</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email:</strong></td>
                        <td><?php echo $_SESSION['email'] ?? '<span class="text-danger">Not set</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Role:</strong></td>
                        <td>
                            <?php 
                            if (isset($_SESSION['role'])) {
                                $role = $_SESSION['role'];
                                $badge = ($role === 'admin' || $role === 'barber') ? 'bg-warning' : 'bg-info';
                                echo "<span class='badge $badge'>" . strtoupper($role) . "</span>";
                            } else {
                                echo '<span class="text-danger">Not set</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Login Time:</strong></td>
                        <td>
                            <?php 
                            if (isset($_SESSION['login_time'])) {
                                echo date('Y-m-d H:i:s', $_SESSION['login_time']);
                            } else {
                                echo '<span class="text-danger">Not set</span>';
                            }
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Session Configuration</h5>
                <table class="table table-dark table-striped">
                    <tr>
                        <td><strong>Cookie Lifetime:</strong></td>
                        <td><?php echo ini_get('session.cookie_lifetime'); ?> seconds (<?php echo ini_get('session.cookie_lifetime') / 3600; ?> hours)</td>
                    </tr>
                    <tr>
                        <td><strong>GC Max Lifetime:</strong></td>
                        <td><?php echo ini_get('session.gc_maxlifetime'); ?> seconds</td>
                    </tr>
                    <tr>
                        <td><strong>HTTP Only:</strong></td>
                        <td><?php echo ini_get('session.cookie_httponly') ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Strict Mode:</strong></td>
                        <td><?php echo ini_get('session.use_strict_mode') ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <tr>
                        <td><strong>SameSite:</strong></td>
                        <td><?php echo ini_get('session.cookie_samesite') ?: 'Not set'; ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h5 class="card-title">Actions</h5>
                <div class="d-grid gap-2">
                    <a href="logout.php" class="btn btn-danger">Logout Now</a>
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                    <button onclick="window.location.reload()" class="btn btn-secondary">Refresh Page</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">🔍 Troubleshooting Tips</h5>
        <div class="alert alert-info">
            <strong>If you're experiencing automatic user changes:</strong>
            <ol class="mb-0">
                <li><strong>Use different browsers</strong> for testing different users (e.g., Chrome for admin, Firefox for customer)</li>
                <li><strong>Use Incognito/Private windows</strong> to test multiple users</li>
                <li><strong>Always logout</strong> before logging in as a different user</li>
                <li><strong>Clear browser cookies</strong> if the issue persists</li>
                <li>Check the "Current Session Data" above to see who you're logged in as</li>
            </ol>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
