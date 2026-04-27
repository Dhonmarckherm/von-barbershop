<?php
/**
 * Admin Settings Page
 * Edit barbershop name and other site settings.
 */
$pageTitle = 'Settings';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';
require_once 'config/db.php';

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $barbershopName = trim($_POST['barbershop_name'] ?? '');

    if (empty($barbershopName) || strlen($barbershopName) < 2) {
        $errors[] = 'Barbershop name must be at least 2 characters.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('barbershop_name', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$barbershopName, $barbershopName]);
        $success = 'Settings updated successfully!';
    }
}

$currentName = 'The Gentlemen\'s Barbershop';
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'barbershop_name'");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) {
        $currentName = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Use default
}

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h2 class="mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Barbershop Settings</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body p-4">
                <h5 class="card-title mb-3" style="color: var(--barber-gold); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px;">General</h5>
                <form method="POST" action="admin_settings.php">
                    <div class="mb-3">
                        <label for="barbershop_name" class="form-label">Barbershop Name</label>
                        <input type="text" class="form-control" id="barbershop_name" name="barbershop_name" required
                               value="<?php echo htmlspecialchars($currentName); ?>">
                        <div class="form-text">This name appears on the homepage, emails, and browser tab.</div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
