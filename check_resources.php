<?php
/**
 * Resource Check Diagnostic Tool
 * Helps identify 404 errors and missing resources
 */
require_once 'config/session.php';
initializeSession();

$pageTitle = 'Resource Check';
require_once 'includes/header.php';
?>

<h2 class="mb-4">🔍 Resource Check & 404 Diagnostics</h2>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Essential Resources Status</h5>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>Resource</th>
                    <th>Path</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $resources = [
                    'CSS File' => __DIR__ . '/assets/css/style.css',
                    'Config: DB' => __DIR__ . '/config/db.php',
                    'Config: Session' => __DIR__ . '/config/session.php',
                    'Config: Mailer' => __DIR__ . '/config/mailer.php',
                    'Config: Settings' => __DIR__ . '/config/settings.php',
                    'Include: Header' => __DIR__ . '/includes/header.php',
                    'Include: Footer' => __DIR__ . '/includes/footer.php',
                    'Include: Auth Check' => __DIR__ . '/includes/auth_check.php',
                ];

                foreach ($resources as $name => $path) {
                    $exists = file_exists($path);
                    $status = $exists ? '<span class="badge bg-success">✅ Exists</span>' : '<span class="badge bg-danger">❌ Missing</span>';
                    $relativePath = str_replace(__DIR__ . '/', '', $path);
                    echo "<tr><td><strong>$name</strong></td><td><code>$relativePath</code></td><td>$status</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Web-Accessible Resources</h5>
        <table class="table table-dark table-striped">
            <thead>
                <tr>
                    <th>Resource</th>
                    <th>URL Path</th>
                    <th>Expected</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>CSS Stylesheet</strong></td>
                    <td><code>/Barbershop_booking-system/assets/css/style.css</code></td>
                    <td><span class="badge bg-success">Fixed</span></td>
                </tr>
                <tr>
                    <td><strong>Bootstrap CSS</strong></td>
                    <td><code>https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css</code></td>
                    <td><span class="badge bg-info">CDN</span></td>
                </tr>
                <tr>
                    <td><strong>Bootstrap JS</strong></td>
                    <td><code>https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js</code></td>
                    <td><span class="badge bg-info">CDN</span></td>
                </tr>
                <tr>
                    <td><strong>Google Fonts</strong></td>
                    <td><code>https://fonts.googleapis.com/css2</code></td>
                    <td><span class="badge bg-info">CDN</span></td>
                </tr>
                <tr>
                    <td><strong>FullCalendar</strong></td>
                    <td><code>https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js</code></td>
                    <td><span class="badge bg-info">CDN (Admin only)</span></td>
                </tr>
                <tr>
                    <td><strong>Favicon</strong></td>
                    <td><code>Inline SVG (no external file)</code></td>
                    <td><span class="badge bg-success">Fixed</span></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">🛠️ Fixes Applied</h5>
        <div class="alert alert-success">
            <ul class="mb-0">
                <li><strong>CSS Path:</strong> Changed from relative <code>assets/css/style.css</code> to absolute <code>/Barbershop_booking-system/assets/css/style.css</code></li>
                <li><strong>Favicon:</strong> Added inline SVG favicon to prevent favicon.ico 404 error</li>
                <li><strong>Session:</strong> All session configurations now use centralized config/session.php</li>
            </ul>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-body">
        <h5 class="card-title">📋 How to Debug 404 Errors</h5>
        <div class="alert alert-info">
            <ol class="mb-0">
                <li><strong>Open Browser DevTools:</strong> Press F12</li>
                <li><strong>Go to Console tab:</strong> Look for red 404 errors</li>
                <li><strong>Check Network tab:</strong> See which files failed to load</li>
                <li><strong>Common Issues:</strong>
                    <ul>
                        <li>Wrong file path (relative vs absolute)</li>
                        <li>Missing files in the directory</li>
                        <li>Typo in filename</li>
                        <li>Wrong case (Style.css vs style.css)</li>
                    </ul>
                </li>
            </ol>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
