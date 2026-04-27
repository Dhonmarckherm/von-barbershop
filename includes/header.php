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
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>✂️</text></svg>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Oswald:wght@300;400;500;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/rubiks.jpg" alt="Logo" style="height: 40px; width: auto; margin-right: 10px; border-radius: 8px;">
                <?php echo htmlspecialchars($siteName); ?>
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
                            <li class="nav-item"><a class="nav-link" href="admin_users.php">Profile</a></li>
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
