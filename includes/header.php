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
    // On ALL other pages, restore from cookies ONLY if session is not already set
    // This prevents conflicts with auth_check.php which may have already validated the session
    if (!isset($_SESSION['user_id']) && isset($_COOKIE['auth_user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['auth_user_id'];
        $_SESSION['name'] = $_COOKIE['auth_name'] ?? '';
        $_SESSION['email'] = $_COOKIE['auth_email'] ?? '';
        $_SESSION['role'] = $_COOKIE['auth_role'] ?? 'customer';
        $_SESSION['login_time'] = time();
    }
    $isLoggedIn = isset($_SESSION['user_id']);
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

    <!-- Web Push Notifications (for browsers and PWA) -->
<?php if ($isLoggedIn && isset($_SESSION['user_id'])): ?>
    <script>
    window.currentUserId = <?php echo (int)$_SESSION['user_id']; ?>;
    </script>
<?php endif; ?>
    <script src="/www/js/web-push-notifications.js?v=2"></script>

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
            const installBtn = banner.querySelector('.btn-install');
            
            if (deferredPrompt) {
                // Show installing overlay
                showInstallingOverlay();
                
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        // Keep showing overlay for realistic installation time (25 seconds)
                        setTimeout(() => {
                            hideInstallingOverlay();
                            showSuccessPopup();
                        }, 25000);
                    } else {
                        console.log('User dismissed the install prompt');
                        // Hide overlay and reset
                        hideInstallingOverlay();
                    }
                    deferredPrompt = null;
                });
            }
        }
        
        function showInstallingOverlay() {
            const overlay = document.createElement('div');
            overlay.id = 'install-overlay';
            overlay.innerHTML = `
                <div class="overlay-content">
                    <div class="overlay-spinner"></div>
                    <h5>Installing App...</h5>
                    <p class="install-status">Setting up VON BARBER STUDIO on your device...</p>
                    <div class="install-progress">
                        <div class="progress-bar"></div>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            
            // Stage 1: 10% - Initializing (0-2s)
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '10%';
                }
            }, 2000);
            
            // Stage 2: 20% - Preparing (2-4s)
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '20%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Preparing installation...';
                }
            }, 4000);
            
            // Stage 3: 30% - Downloading (4-6s)
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '30%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Downloading app package...';
                }
            }, 6000);
            
            // Stage 4: 40% (6-8s)
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '40%';
                }
            }, 8000);
            
            // Stage 5: 50% - Extracting (8-10s)
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '50%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Extracting app files...';
                }
            }, 10000);
            
            // Stage 6: 60% (10-12s)
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '60%';
                }
            }, 12000);
            
            // Stage 7: 70% - Installing (12-15s)
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '70%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Installing application...';
                }
            }, 15000);
            
            // Stage 8: 80% - Configuring (15-18s) - STARTING TO SLOW DOWN
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '80%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Configuring app settings...';
                }
            }, 18000);
            
            // Stage 9: 85% - Almost there (18-20s) - SLOWING DOWN
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '85%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Optimizing performance...';
                }
            }, 20000);
            
            // Stage 10: 90% - Creating shortcut (20-22s) - VERY SLOW
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '90%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Creating home screen shortcut...';
                }
            }, 22000);
            
            // Stage 11: 95% - Almost done (22-23.5s) - EXTREMELY SLOW
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '95%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Almost done...';
                }
            }, 23500);
            
            // Stage 12: 100% - Finalizing (23.5-25s) - FINAL STEP
            setTimeout(() => {
                const progressBar = overlay.querySelector('.progress-bar');
                if (progressBar) {
                    progressBar.style.width = '100%';
                }
                const statusText = overlay.querySelector('.install-status');
                if (statusText) {
                    statusText.textContent = 'Finalizing installation...';
                }
            }, 24500);
        }
        
        function hideInstallingOverlay() {
            const overlay = document.getElementById('install-overlay');
            if (overlay) {
                overlay.remove();
            }
        }
        
        function showSuccessPopup() {
            const popup = document.createElement('div');
            popup.id = 'install-success-popup';
            popup.innerHTML = `
                <div class="popup-content">
                    <div class="popup-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h5>Successfully Installed!</h5>
                    <p>VON BARBER STUDIO is now installed on your device. You can access it anytime from your home screen for faster booking!</p>
                    <button class="popup-btn" onclick="closeSuccessPopup()">Got it!</button>
                </div>
            `;
            document.body.appendChild(popup);
        }
        
        function closeSuccessPopup() {
            const popup = document.getElementById('install-success-popup');
            const banner = document.getElementById('pwa-install-banner');
            if (popup) popup.remove();
            if (banner) banner.style.display = 'none';
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
        <div class="banner-container">
            <div class="banner-header">
                <div class="banner-app-icon">
                    <i class="bi bi-phone"></i>
                </div>
                <div class="banner-title-section">
                    <h6>VON BARBER STUDIO</h6>
                    <p>Book your next cut faster!</p>
                </div>
                <button class="btn-close" onclick="closePWABanner()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <div class="banner-action">
                <button class="btn-install" onclick="installPWA()">
                    <i class="bi bi-download"></i> Install App
                </button>
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
    <nav class="bottom-nav">
        <div class="container">
            <div class="bottom-nav-container">
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
                        <a href="admin_settings.php" class="bottom-nav-item <?php echo $current_page == 'admin_settings.php' ? 'active' : ''; ?>">
                            <i class="bi bi-gear"></i>
                            <span>Settings</span>
                        </a>
                        <a href="javascript:void(0)" onclick="showLogoutModal()" class="bottom-nav-item" id="logoutNavBtn">
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
                        <a href="javascript:void(0)" onclick="showLogoutModal()" class="bottom-nav-item">
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
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #0a0a0a;
            border-top: 1px solid rgba(197, 160, 89, 0.3);
            padding: 12px 0 8px 0;
            z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.6);
        }
        .bottom-nav-container {
            position: relative;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #888;
            font-size: 10px;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 20px;
            position: relative;
            transition: color 0.3s ease;
            z-index: 1;
            -webkit-tap-highlight-color: transparent;
            cursor: pointer;
        }
        .bottom-nav-item i {
            font-size: 22px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }
        .bottom-nav-item span {
            transition: all 0.3s ease;
            opacity: 0.7;
        }
        /* Sliding indicator background */
        .bottom-nav-indicator {
            position: absolute;
            background: linear-gradient(29deg, #212529 0%, rgba(255, 255, 255, 0.95) 100%);
            border-radius: 20px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 2px 8px rgba(33, 37, 41, 0.4);
            z-index: 0;
            pointer-events: none; /* Allow clicks to pass through to buttons */
        }
        /* Active state */
        .bottom-nav-item.active {
            color: #ffffff;
            z-index: 2; /* Ensure active item is above indicator */
        }
        .bottom-nav-item.active i {
            transform: scale(1.1);
        }
        .bottom-nav-item.active span {
            opacity: 1;
            font-weight: 600;
        }
        /* Hover effect */
        .bottom-nav-item:hover {
            color: #ffffff;
            z-index: 2;
        }
        .bottom-nav-item:hover i {
            transform: translateY(-2px);
        }
        /* Click/press feedback */
        .bottom-nav-item:active {
            transform: scale(0.95);
            z-index: 2;
        }
        
        /* Ensure logout button is always visible */
        #logoutNavBtn {
            z-index: 3 !important;
            pointer-events: auto !important;
        }
        #logoutNavBtn:hover {
            z-index: 3 !important;
        }
        
        /* iOS Safari animation fixes */
        @supports (-webkit-touch-callout: none) {
            .bottom-nav-item,
            .bottom-nav-item i,
            .bottom-nav-item span,
            .bottom-nav-indicator {
                -webkit-animation-play-state: running !important;
                animation-play-state: running !important;
                -webkit-transform: translateZ(0) !important;
                transform: translateZ(0) !important;
                -webkit-backface-visibility: hidden !important;
                backface-visibility: hidden !important;
                will-change: transform !important;
            }
            .bottom-nav-item:active {
                -webkit-transform: scale(0.95) translateZ(0) !important;
                transform: scale(0.95) translateZ(0) !important;
            }
            .bottom-nav-item:hover i,
            .bottom-nav-item:active i {
                -webkit-transform: translateY(-2px) translateZ(0) !important;
                transform: translateY(-2px) translateZ(0) !important;
            }
            .bottom-nav-item.active i {
                -webkit-transform: scale(1.1) translateZ(0) !important;
                transform: scale(1.1) translateZ(0) !important;
            }
            .bottom-nav-indicator {
                -webkit-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                -webkit-transform: translateZ(0) !important;
                transform: translateZ(0) !important;
            }
        }
    </style>
    
    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border-radius: 20px; padding: 30px; max-width: 320px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.1); text-align: center; animation: modalSlideIn 0.3s ease;">
            <div style="margin-bottom: 20px;">
                <i class="bi bi-box-arrow-right" style="font-size: 48px; color: #ff6b6b; display: block; margin-bottom: 15px;"></i>
                <h3 style="color: #ffffff; margin: 0 0 10px 0; font-family: 'Inter', sans-serif; font-weight: 600; font-size: 20px;">Log Out?</h3>
                <p style="color: #b0b0b0; margin: 0; font-size: 14px; font-family: 'Inter', sans-serif;">Are you sure you want to log out?</p>
            </div>
            <div style="display: flex; gap: 12px; margin-top: 25px;">
                <button onclick="closeLogoutModal()" style="flex: 1; padding: 12px; border: 1px solid rgba(255,255,255,0.2); background: transparent; color: #ffffff; border-radius: 12px; font-size: 15px; font-weight: 500; cursor: pointer; transition: all 0.3s ease; font-family: 'Inter', sans-serif;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='transparent'">No</button>
                <a href="logout.php" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); color: #ffffff; border-radius: 12px; font-size: 15px; font-weight: 600; text-decoration: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s ease; font-family: 'Inter', sans-serif; box-shadow: 0 4px 15px rgba(255,107,107,0.3);" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255,107,107,0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255,107,107,0.3)'">Yes</a>
            </div>
        </div>
    </div>
    
    <style>
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
    
    <script>
    // Logout Modal Functions
    function showLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.style.display = 'flex';
        // Prevent body scroll when modal is open
        document.body.style.overflow = 'hidden';
    }
    
    function closeLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.style.display = 'none';
        // Restore body scroll
        document.body.style.overflow = '';
    }
    
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        const modal = document.getElementById('logoutModal');
        if (e.target === modal) {
            closeLogoutModal();
        }
    });
    
    // Sliding indicator logic
    document.addEventListener('DOMContentLoaded', function() {
        const navContainer = document.querySelector('.bottom-nav-container');
        const activeItem = document.querySelector('.bottom-nav-item.active');
        
        if (navContainer && activeItem) {
            // Create indicator
            const indicator = document.createElement('div');
            indicator.className = 'bottom-nav-indicator';
            navContainer.appendChild(indicator);
            
            // Position indicator under active item
            function updateIndicator(item) {
                if (!item) return;
                const rect = item.getBoundingClientRect();
                const containerRect = navContainer.getBoundingClientRect();
                
                indicator.style.width = (rect.width + 8) + 'px';
                indicator.style.height = (rect.height + 4) + 'px';
                indicator.style.left = (rect.left - containerRect.left - 4) + 'px';
                indicator.style.top = (rect.top - containerRect.top - 2) + 'px';
            }
            
            // Initial position
            setTimeout(() => updateIndicator(activeItem), 100);
            
            // Update on click
            document.querySelectorAll('.bottom-nav-item').forEach(item => {
                item.addEventListener('click', function() {
                    // Remove active from all
                    document.querySelectorAll('.bottom-nav-item').forEach(i => i.classList.remove('active'));
                    // Add active to clicked (except logout button)
                    if (this.id !== 'logoutNavBtn') {
                        this.classList.add('active');
                        // Move indicator
                        updateIndicator(this);
                    }
                });
                
                // iOS touch feedback
                item.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                    this.style.transition = 'transform 0.1s ease';
                }, { passive: true });
                
                item.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 100);
                }, { passive: true });
            });
            
            // Update on resize
            window.addEventListener('resize', () => updateIndicator(document.querySelector('.bottom-nav-item.active')));
        }
    });
    </script>

    <!-- Theme Toggle Button (Visible on ALL pages) -->
    <style>
    /* Theme Variables */
    :root {
        --bg-primary: #1a1a1a;
        --bg-secondary: #2d2d2d;
        --bg-card: #242424;
        --text-primary: #ffffff;
        --text-secondary: #b0b0b0;
        --text-muted: #808080;
        --border-color: #404040;
        --input-bg: #1a1a1a;
        --input-border: #505050;
        --navbar-bg: #0a0a0a;
        --bottom-nav-bg: #0a0a0a;
    }

    [data-theme="light"] {
        --bg-primary: #f5f5f5;
        --bg-secondary: #ffffff;
        --bg-card: #ffffff;
        --text-primary: #1a1a1a;
        --text-secondary: #4a4a4a;
        --text-muted: #6a6a6a;
        --border-color: #e0e0e0;
        --input-bg: #ffffff;
        --input-border: #c0c0c0;
        --navbar-bg: #ffffff;
        --bottom-nav-bg: #ffffff;
    }

    /* Global Theme Application */
    body {
        background: var(--bg-primary) !important;
        color: var(--text-primary) !important;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    /* Navbar Theme */
    .navbar {
        background: var(--navbar-bg) !important;
        transition: all 0.4s ease !important;
    }

    .navbar-brand {
        color: var(--text-primary) !important;
        transition: color 0.4s ease !important;
    }

    /* Bottom Navigation Theme */
    .bottom-nav {
        background: var(--bottom-nav-bg) !important;
        border-top-color: var(--border-color) !important;
        transition: all 0.4s ease !important;
    }

    .bottom-nav-item {
        color: var(--text-muted) !important;
        transition: all 0.3s ease !important;
    }

    .bottom-nav-item.active,
    .bottom-nav-item:hover {
        color: var(--text-primary) !important;
    }

    /* Card Theme */
    .card {
        background: var(--bg-card) !important;
        border-color: var(--border-color) !important;
        transition: all 0.4s ease !important;
    }

    .card-body h2,
    .card-body h3,
    .card-body h4,
    .card-body h5 {
        color: var(--text-primary) !important;
    }

    .card-body p,
    .card-body span,
    .card-body div {
        color: var(--text-primary) !important;
    }

    /* Form Elements Theme */
    .form-label {
        color: var(--text-primary) !important;
        font-weight: 600;
    }

    .form-control {
        background: var(--input-bg) !important;
        border-color: var(--input-border) !important;
        color: var(--text-primary) !important;
        transition: all 0.3s ease !important;
    }

    .form-control:focus {
        background: var(--input-bg) !important;
        border-color: #d4af37 !important;
        color: var(--text-primary) !important;
        box-shadow: 0 0 0 0.25rem rgba(212, 175, 55, 0.25) !important;
    }

    .form-control::placeholder {
        color: var(--text-muted) !important;
    }

    /* Alert Theme */
    .alert-success {
        background: rgba(40, 167, 69, 0.15) !important;
        border-color: rgba(40, 167, 69, 0.4) !important;
        color: #28a745 !important;
    }

    .alert-danger {
        background: rgba(220, 53, 69, 0.15) !important;
        border-color: rgba(220, 53, 69, 0.4) !important;
        color: #dc3545 !important;
    }

    /* Text Elements Theme */
    h1, h2, h3, h4, h5, h6 {
        color: var(--text-primary) !important;
    }

    p, span, div, label {
        color: var(--text-primary) !important;
    }

    a {
        color: #d4af37 !important;
    }

    /* Theme Toggle Button - Premium Slider Style */
    #themeToggleBtn {
        position: fixed !important;
        bottom: 100px !important;
        right: 20px !important;
        z-index: 99999 !important;
        width: 70px !important;
        height: 36px !important;
        border-radius: 18px !important;
        border: 2px solid #d4af37 !important;
        background: linear-gradient(135deg, #2d2d2d 0%, #1a1a1a 100%) !important;
        cursor: pointer !important;
        display: flex !important;
        align-items: center !important;
        padding: 3px !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.4), 0 0 0 1px rgba(212, 175, 55, 0.3) !important;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) !important;
        -webkit-tap-highlight-color: transparent !important;
    }

    /* Toggle Knob */
    #themeToggleBtn::before {
        content: '' !important;
        position: absolute !important;
        left: 3px !important;
        width: 26px !important;
        height: 26px !important;
        border-radius: 50% !important;
        background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%) !important;
        box-shadow: 0 2px 8px rgba(212, 175, 55, 0.5) !important;
        transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) !important;
    }

    /* Light mode - move knob to right */
    [data-theme="light"] #themeToggleBtn {
        background: linear-gradient(135deg, #e8e8e8 0%, #f5f5f5 100%) !important;
        border-color: #d4af37 !important;
    }

    [data-theme="light"] #themeToggleBtn::before {
        left: 37px !important;
        background: linear-gradient(135deg, #d4af37 0%, #f4d03f 100%) !important;
    }

    /* Toggle Icons */
    #themeToggleBtn .toggle-icon {
        position: absolute !important;
        font-size: 16px !important;
        transition: all 0.4s ease !important;
        z-index: 1 !important;
    }

    #themeToggleBtn .icon-moon {
        left: 8px !important;
        opacity: 1 !important;
    }

    #themeToggleBtn .icon-sun {
        right: 8px !important;
        opacity: 0.5 !important;
    }

    [data-theme="light"] #themeToggleBtn .icon-moon {
        opacity: 0.5 !important;
    }

    [data-theme="light"] #themeToggleBtn .icon-sun {
        opacity: 1 !important;
    }

    #themeToggleBtn:hover {
        transform: scale(1.05) !important;
        box-shadow: 0 6px 20px rgba(0,0,0,0.5), 0 0 0 2px rgba(212, 175, 55, 0.5) !important;
    }

    #themeToggleBtn:active {
        transform: scale(0.98) !important;
    }

    /* Theme Change Animation */
    .theme-changing * {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    @keyframes themeSwitch {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .theme-switching {
        animation: themeSwitch 0.4s ease !important;
    }
    </style>

    <button id="themeToggleBtn" title="Toggle Dark/Light Mode" aria-label="Toggle theme">
        <span class="toggle-icon icon-moon">🌙</span>
        <span class="toggle-icon icon-sun">☀️</span>
    </button>

    <script>
    // Theme Toggle Logic (Applied to ALL pages)
    (function() {
        const themeToggle = document.getElementById('themeToggleBtn');
        
        if (!themeToggle) return;
        
        // Load saved theme or default to dark
        const savedTheme = localStorage.getItem('theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        // Toggle on click
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Add switching animation to body
            document.body.classList.add('theme-switching');
            
            // Update theme
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            // Remove animation class
            setTimeout(() => {
                document.body.classList.remove('theme-switching');
            }, 400);
        });
    })();
    </script>

    <div class="container mt-4">
