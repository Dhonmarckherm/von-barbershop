<?php
require_once 'config/session.php';
initializeSession();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Session Debug</h1>
        
        <table class="table table-bordered mt-3">
            <tr>
                <th>Session ID</th>
                <td><?php echo session_id(); ?></td>
            </tr>
            <tr>
                <th>User ID</th>
                <td><?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '<span class="text-danger">NOT SET</span>'; ?></td>
            </tr>
            <tr>
                <th>Name</th>
                <td><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : '<span class="text-danger">NOT SET</span>'; ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo isset($_SESSION['email']) ? $_SESSION['email'] : '<span class="text-danger">NOT SET</span>'; ?></td>
            </tr>
            <tr>
                <th>Role</th>
                <td>
                    <?php 
                    if (isset($_SESSION['role'])) {
                        $role = $_SESSION['role'];
                        $badge = ($role === 'admin' || $role === 'barber') ? 'bg-warning' : 'bg-info';
                        echo "<span class='badge $badge'>" . strtoupper($role) . "</span>";
                    } else {
                        echo '<span class="text-danger">NOT SET</span>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th>Login Time</th>
                <td><?php echo isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : '<span class="text-danger">NOT SET</span>'; ?></td>
            </tr>
            <tr>
                <th>All Session Data</th>
                <td><pre><?php print_r($_SESSION); ?></pre></td>
            </tr>
        </table>

        <div class="mt-3">
            <a href="login.php" class="btn btn-primary">Login</a>
            <a href="logout.php" class="btn btn-danger">Logout</a>
            <button onclick="location.reload()" class="btn btn-secondary">Refresh</button>
        </div>

        <div class="mt-4">
            <h3>API Test</h3>
            <button id="testBtn" class="btn btn-success">Test API Call</button>
            <div id="result" class="mt-2"></div>
        </div>
    </div>

    <script>
        document.getElementById('testBtn').addEventListener('click', async function() {
            const result = document.getElementById('result');
            result.innerHTML = '<p>Testing...</p>';
            
            try {
                const response = await fetch('api/update_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'appointment_id=1&status=accepted'
                });
                
                const data = await response.json();
                result.innerHTML = `
                    <div class="alert alert-${response.ok ? 'success' : 'danger'}">
                        <strong>Status:</strong> ${response.status}<br>
                        <strong>Response:</strong> <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (err) {
                result.innerHTML = `<div class="alert alert-danger">Error: ${err.message}</div>`;
            }
        });
    </script>
</body>
</html>
