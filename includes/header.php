<?php
require_once __DIR__ . '/../config/session.php';
initializeSession();

$current_page = basename($_SERVER['PHP_SELF']);
$auth_pages = ['login.php', 'register.php'];

// On login/register pages, clear everything to allow fresh login
if (in_array($current_page, $auth_pages)) {
    if (isset($_GET['logout']) && $_GET['logout'] == '1') {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    $isLoggedIn = false;
} else {
    // On ALL other pages, ALWAYS restore from cookies
    if (isset($_COOKIE['auth_user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['auth_user_id'];
        $_SESSION['name'] = $_COOKIE['auth_name'] ?? '';
        $_SESSION['email'] = $_COOKIE['auth_email'] ?? '';
        $_SESSION['role'] = $_COOKIE['auth_role'] ?? 'customer';
        $_SESSION['login_time'] = time();
        $isLoggedIn = true;
    } else {
        $isLoggedIn = isset($_SESSION['user_id']);
    }
}

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
    <link rel="icon" type="image/png" href="/assets/images/rubiks.jpg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Oswald:wght@300;400;500;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <link rel="stylesheet" href="/assets/css/style.css">

    <!-- Capacitor Push Notifications -->
    <script src="https://cdn.jsdelivr.net/npm/@capacitor/core@latest/dist/capacitor.js"></script>
    <script src="/www/js/push-notifications.js?v=2"></script>

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
            // Only show banner on mobile devices
            if (window.innerWidth <= 768) {
                const banner = document.getElementById('pwa-install-banner');
                if (banner) {
                    banner.style.display = 'block';
                }
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
            <img src="assets/images/rubiks.jpg" alt="VON BARBER STUDIO">
            <div class="banner-text">
                <h6>Install VON BARBER STUDIO</h6>
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
            <p>To install <b>VON BARBER STUDIO</b> on your iPhone:</p>
            <p>Tap the <b>Share</b> button and select <b>"Add to Home Screen"</b></p>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php echo htmlspecialchars($siteName); ?>
            </a>
        </div>
    </nav>
    
    <!-- Bottom Navigation Bar (Mobile App Style) -->
    <nav class="bottom-nav" style="position: fixed; bottom: 0; left: 0; right: 0; background: #000000; border-top: 2px solid var(--barber-gold); padding: 8px 0; z-index: 1000; box-shadow: 0 -4px 20px rgba(0,0,0,0.5);">
        <div class="container">
            <div class="d-flex justify-content-around align-items-center">
                <?php if ($isLoggedIn): ?>
                    <?php if ($isAdmin): ?>
                        <!-- Admin Bottom Nav -->
                        <a href="index.php" class="bottom-nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                            <i class="bi bi-house-door"></i>
                            <span>Home</span>
                        </a>
                        <a href="admin_dashboard.php" class="bottom-nav-item <?php echo $current_page == 'admin_dashboard.php' ? 'active' : ''; ?>">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="admin_users.php" class="bottom-nav-item <?php echo $current_page == 'admin_users.php' ? 'active' : ''; ?>">
                            <i class="bi bi-people"></i>
                            <span>Users</span>
                        </a>
                        <a href="admin_announcements.php" class="bottom-nav-item <?php echo $current_page == 'admin_announcements.php' ? 'active' : ''; ?>">
                            <i class="bi bi-megaphone"></i>
                            <span>Announce</span>
                        </a>
                        <a href="logout.php" class="bottom-nav-item">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                        <!-- Customer Bottom Nav -->
                        <a href="index.php" class="bottom-nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                            <i class="bi bi-house-door"></i>
                            <span>Home</span>
                        </a>
                        <a href="book.php" class="bottom-nav-item <?php echo $current_page == 'book.php' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-plus"></i>
                            <span>Book</span>
                        </a>
                        <a href="my_appointments.php" class="bottom-nav-item <?php echo $current_page == 'my_appointments.php' ? 'active' : ''; ?>">
                            <i class="bi bi-calendar-check"></i>
                            <span>My Appts</span>
                        </a>
                        <a href="profile.php" class="bottom-nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
                            <i class="bi bi-person"></i>
                            <span>Profile</span>
                        </a>
                        <a href="logout.php" class="bottom-nav-item">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Guest Bottom Nav -->
                    <a href="index.php" class="bottom-nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <i class="bi bi-house-door"></i>
                        <span>Home</span>
                    </a>
                    <a href="login.php" class="bottom-nav-item <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <span>Login</span>
                    </a>
                    <a href="register.php" class="bottom-nav-item <?php echo $current_page == 'register.php' ? 'active' : ''; ?>">
                        <i class="bi bi-person-plus"></i>
                        <span>Register</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Add padding at bottom to prevent content from being hidden behind nav -->
    <style>
        body {
            padding-bottom: 80px !important;
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #b0b0b0;
            font-size: 11px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 8px 12px;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
            -webkit-tap-highlight-color: transparent;
            cursor: pointer;
        }
        .bottom-nav-item::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(197, 160, 89, 0.15);
            transform: translate(-50%, -50%);
            transition: width 0.4s ease, height 0.4s ease;
            pointer-events: none;
        }
        .bottom-nav-item:hover::before,
        .bottom-nav-item:active::before {
            width: 100px;
            height: 100px;
        }
        .bottom-nav-item i {
            font-size: 24px;
            margin-bottom: 4px;
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            position: relative;
            z-index: 1;
            animation-play-state: running !important;
            -webkit-animation-play-state: running !important;
        }
        .bottom-nav-item span {
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
            animation-play-state: running !important;
            -webkit-animation-play-state: running !important;
        }
        .bottom-nav-item:hover {
            color: var(--barber-gold);
            transform: translateY(-3px);
        }
        .bottom-nav-item:hover i {
            transform: scale(1.2) rotate(5deg);
        }
        .bottom-nav-item:active {
            transform: translateY(-1px) scale(0.95);
        }
        .bottom-nav-item:active i {
            transform: scale(0.9);
        }
        .bottom-nav-item.active {
            color: var(--barber-gold);
        }
        .bottom-nav-item.active::before {
            width: 80px;
            height: 80px;
            background: rgba(197, 160, 89, 0.2);
        }
        .bottom-nav-item.active i {
            animation: iconBounce 0.6s ease;
            animation-play-state: running !important;
            -webkit-animation-play-state: running !important;
        }
        @keyframes iconBounce {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.2) rotate(-10deg); }
            50% { transform: scale(1.15) rotate(10deg); }
            75% { transform: scale(1.2) rotate(-5deg); }
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        .bottom-nav-item:hover span {
            transform: translateY(-1px);
        }
        
        /* Enable animations on mobile devices */
        @media (max-width: 768px) {
            .bottom-nav-item,
            .bottom-nav-item i,
            .bottom-nav-item span {
                animation-play-state: running !important;
                -webkit-animation-play-state: running !important;
            }
            .bottom-nav-item.active i {
                animation: iconBounce 0.6s ease !important;
                animation-play-state: running !important;
                -webkit-animation-play-state: running !important;
            }
            .bottom-nav-item::before {
                transition: width 0.4s ease !important, height 0.4s ease !important;
            }
        }
    </style>
    <div class="container mt-4">
