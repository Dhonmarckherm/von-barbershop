<?php
$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<div class="hero">
    <!-- Animated Background Particles -->
    <div class="hero-particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    
    <div class="container hero-content">
        <?php if ($isLoggedIn && !$isAdmin): ?>
            <div class="hero-greeting">
                <p class="hero-greeting-text">
                    Welcome back, <span class="hero-greeting-name"><?php echo htmlspecialchars($_SESSION['name']); ?></span>!
                </p>
            </div>
        <?php endif; ?>
        
        <p class="hero-subtitle">Est. 2025 — Premium Grooming</p>
        
        <h1 class="hero-title">
            <span class="hero-title-letter" style="--delay: 0">V</span>
            <span class="hero-title-letter" style="--delay: 1">.</span>
            <span class="hero-title-letter" style="--delay: 2">O</span>
            <span class="hero-title-letter" style="--delay: 3">.</span>
            <span class="hero-title-letter" style="--delay: 4">N</span>
        </h1>
        
        <div class="hero-divider">
            <div class="hero-divider-line"></div>
            <div class="hero-divider-dot"></div>
            <div class="hero-divider-line"></div>
        </div>
        
        <p class="hero-description">Where tradition meets style. Expert cuts, classic shaves, and the finest grooming experience.</p>
        
        <p class="hero-punchline">Ako si VON, as in <span class="punchline-highlight">V.O.N</span></p>
        
        <div class="hero-buttons">
            <?php if ($isLoggedIn): ?>
                <?php if ($isAdmin): ?>
                    <a href="admin_dashboard.php" class="btn btn-light btn-lg hero-btn">
                        <span>Dashboard</span>
                        <span class="btn-shimmer"></span>
                    </a>
                <?php else: ?>
                    <a href="book.php" class="btn btn-primary btn-lg hero-btn">
                        <span>Book Appointment</span>
                        <span class="btn-shimmer"></span>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn btn-primary btn-lg hero-btn">
                    <span>Book Appointment</span>
                    <span class="btn-shimmer"></span>
                </a>
                <p class="hero-register">
                    <a href="register.php">New here? Create an Account</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Scroll Indicator -->
    <div class="scroll-indicator">
        <div class="scroll-mouse">
            <div class="scroll-wheel"></div>
        </div>
        <p>Scroll Down</p>
    </div>
</div>

<div class="row mt-5 mb-4">
    <div class="col-12 text-center mb-4">
        <h2 style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Our Services</h2>
        <div style="width: 60px; height: 3px; background: var(--barber-red); margin: 0.75rem auto;"></div>
    </div>
</div>

<div class="row text-center">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Haircut</h5>
                <p class="card-text">Classic and modern haircuts tailored to your unique style and face shape.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Beard Trim</h5>
                <p class="card-text">Precision beard shaping, trimming, and styling to keep you looking sharp.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Home Service</h5>
                <p class="card-text">Professional barber services at the comfort of your home. We come to you!</p>
            </div>
        </div>
    </div>
</div>

<div class="row mt-5 mb-5">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center p-5">
                <h3 style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Why Choose Us?</h3>
                <div style="width: 60px; height: 3px; background: var(--barber-red); margin: 0.75rem auto 2rem;"></div>
                <div class="row">
                    <div class="col-md-4">
                        <h5 style="color: var(--barber-gold); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px;">Expert Barbers</h5>
                        <p style="color: var(--barber-gray);">Years of experience in classic and modern grooming techniques.</p>
                    </div>
                    <div class="col-md-4">
                        <h5 style="color: var(--barber-gold); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px;">Follow Us</h5>
                        <p style="color: var(--barber-gray);">
                            <a href="https://www.facebook.com/Vonvoning" target="_blank" style="text-decoration: none;">
                                Facebook
                            </a>
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h5 style="color: var(--barber-gold); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px;">Easy Booking</h5>
                        <p style="color: var(--barber-gray);">Book your appointment online in just a few clicks.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
