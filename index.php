<?php
$pageTitle = 'Home';
require_once 'config/db.php';
require_once 'includes/header.php';

// Fetch reviews for homepage display
$stmt = $pdo->query("
    SELECT r.rating, r.comment, r.created_at, u.name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $pdo->query("SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews FROM reviews");
$ratingStats = $stmt->fetch();
$averageRating = $ratingStats['avg_rating'] ? round($ratingStats['avg_rating'], 1) : 0;
$totalReviews = $ratingStats['total_reviews'];
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
                            <div class="d-flex justify-content-center gap-2">
                                <!-- Facebook -->
                                <a href="https://www.facebook.com/Vonvoning" target="_blank" style="color: var(--barber-gold); font-size: 1.5rem; transition: all 0.3s ease; display: inline-block;" onmouseover="this.style.color='#1877F2'; this.style.transform='scale(1.2)'" onmouseout="this.style.color='var(--barber-gold)'; this.style.transform='scale(1)'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
                                    </svg>
                                </a>
                                <!-- Instagram -->
                                <a href="https://www.instagram.com/voncarlos.mp4?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==" target="_blank" style="color: var(--barber-gold); font-size: 1.5rem; transition: all 0.3s ease; display: inline-block;" onmouseover="this.style.color='#E4405F'; this.style.transform='scale(1.2)'" onmouseout="this.style.color='var(--barber-gold)'; this.style.transform='scale(1)'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/>
                                    </svg>
                                </a>
                                <!-- TikTok -->
                                <a href="https://www.tiktok.com/@voncarlos.mp4" target="_blank" style="color: var(--barber-gold); font-size: 1.5rem; transition: all 0.3s ease; display: inline-block;" onmouseover="this.style.color='#000000'; this.style.transform='scale(1.2)'" onmouseout="this.style.color='var(--barber-gold)'; this.style.transform='scale(1)'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M9 0h1.98c.144.715.54 1.617 1.235 2.512C12.895 3.389 13.797 4 15 4v2c-1.753 0-3.07-.814-4-1.829V11a5 5 0 1 1-5-5v2a3 3 0 1 0 3 3V0Z"/>
                                    </svg>
                                </a>
                            </div>
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

<!-- Customer Reviews Section -->
<div class="row mt-5 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h2 style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">
                        <i class="bi bi-star-fill" style="color: #ffc107;"></i> Customer Reviews
                    </h2>
                    
                    <?php if ($totalReviews > 0): ?>
                        <div class="mt-3">
                            <div class="display-4 mb-2" style="color: var(--barber-gold); font-weight: bold;">
                                <?php echo $averageRating; ?>
                            </div>
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star<?php echo ($i <= $averageRating) ? '-fill' : ''; ?>" 
                                       style="color: <?php echo ($i <= $averageRating) ? '#ffc107' : '#ddd'; ?>; font-size: 2rem;"></i>
                                <?php endfor; ?>
                            </div>
                            <p style="color: var(--barber-gray); margin: 0;">
                                Based on <?php echo $totalReviews; ?> review<?php echo $totalReviews != 1 ? 's' : ''; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <p style="color: var(--barber-gray);" class="mt-3">Be the first to leave a review!</p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($reviews)): ?>
                    <hr style="border-color: rgba(197,160,89,0.3); margin: 30px 0;">
                    <div class="row">
                        <?php foreach ($reviews as $review): ?>
                            <div class="col-md-6 mb-3">
                                <div class="p-3" style="background: #f8f9fa; border-radius: 10px; border-left: 4px solid var(--barber-gold);">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong style="color: var(--barber-dark);">
                                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($review['name']); ?>
                                            </strong>
                                        </div>
                                        <small style="color: var(--barber-gray);">
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div class="mb-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?php echo ($i <= $review['rating']) ? '-fill' : ''; ?>" 
                                               style="color: <?php echo ($i <= $review['rating']) ? '#ffc107' : '#ddd'; ?>;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($review['comment']): ?>
                                        <p style="color: var(--barber-gray); margin: 0; font-size: 0.95rem;">
                                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
