<?php
require_once __DIR__ . '/../config/session.php';
initializeSession();

$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'barber');

require_once __DIR__ . '/../config/settings.php';
$siteName = getSetting('barbershop_name', 'The Gentlemen\'s Barbershop');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?><?php echo htmlspecialchars($siteName); ?></title>
    <link rel="icon" type="image/jpeg" href="/assets/images/rubiks.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Oswald:wght@300;400;500;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#000000">
    <link rel="apple-touch-icon" href="/assets/images/rubiks.jpg">
    <script>
        let deferredPrompt;

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed', err));
            });
        }

        // iOS Detection
        const isIos = () => {
            const userAgent = window.navigator.userAgent.toLowerCase();
            return /iphone|ipad|ipod/.test(userAgent);
        }

        // Check if already in standalone mode
        const isInStandaloneMode = () => ('standalone' in window.navigator) && (window.navigator.standalone);

        window.addEventListener('load', () => {
            // Show iOS hint if on iOS and not already installed
            if (isIos() && !isInStandaloneMode()) {
                const iosHint = document.getElementById('ios-install-hint');
                if (iosHint) {
                    iosHint.style.display = 'block';
                }
            }
        });

        window.addEventListener('beforeinstallprompt', (e) => {
            // Prevent Chrome from automatically showing the prompt
            e.preventDefault();
            // Stash the event so it can be triggered later.
            deferredPrompt = e;
            // Update UI notify the user they can add to home screen
            const banner = document.getElementById('pwa-install-banner');
            if (banner) {
                banner.style.display = 'block';
            }
        });

        function installPWA() {
            const banner = document.getElementById('pwa-install-banner');
            if (banner) {
                banner.style.display = 'none';
            }
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                    } else {
                        console.log('User dismissed the install prompt');
                    }
                    deferredPrompt = null;
                });
            }
        }

        function closePWABanner() {
            const banner = document.getElementById('pwa-install-banner');
            const iosHint = document.getElementById('ios-install-hint');
            if (banner) banner.style.display = 'none';
            if (iosHint) iosHint.style.display = 'none';
        }
    </script>
</head>
<body>
    <!-- PWA Install Banner (Android) -->
    <div id="pwa-install-banner">
        <div class="banner-content">
            <img src="assets/images/rubiks.jpg" alt="Logo">
            <div class="banner-text">
                <h6>Install VON Barbershop</h6>
                <p>Book your next cut faster!</p>
            </div>
            <div class="banner-btns">
                <button class="btn-install" onclick="installPWA()">Install</button>
                <button class="btn-close" onclick="closePWABanner()">&times;</button>
            </div>
        </div>
    </div>

    <!-- iOS Install Hint -->
    <div id="ios-install-hint">
        <button class="btn-close" onclick="closePWABanner()">&times;</button>
        <div class="hint-content">
            <i class="bi bi-box-arrow-up"></i>
            <p>To install <b>VON Barbershop</b> on your iPhone:</p>
            <p>Tap the <b>Share</b> button and select <b>"Add to Home Screen"</b></p>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php" style="display: flex; align-items: center; gap: 10px;">
                <img src="/assets/images/von-barber-logo.png" alt="VON BARBER STUDIO" style="height: 40px; width: auto;">
                <span><?php echo htmlspecialchars($siteName); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <?php if ($isLoggedIn): ?>
                        <?php if ($isAdmin): ?>
                            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="admin_users.php">Manage Users</a></li>
                            <li class="nav-item"><a class="nav-link" href="admin_settings.php">Settings</a></li>
                            <li class="nav-item"><a class="nav-link" href="admin_announcements.php">Announcements</a></li>
                        <?php else: ?>
                            <li class="nav-item"><a class="nav-link" href="book.php">Book Now</a></li>
                            <li class="nav-item"><a class="nav-link" href="my_appointments.php">My Appointments</a></li>
                            <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                Logout (<span style="color: var(--accent-gold);"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></span>)
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
