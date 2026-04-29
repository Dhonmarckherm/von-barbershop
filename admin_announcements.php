<?php
/**
 * Admin Announcements Page
 * Send announcement emails to all customers or specific customers.
 */
$pageTitle = 'Announcements';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';
require_once 'config/db.php';

$success = '';
$errors = [];
$sentCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $target = $_POST['target'] ?? 'all';
    $specificEmail = trim($_POST['specific_email'] ?? '');

    if (empty($subject) || strlen($subject) < 3) {
        $errors[] = 'Subject must be at least 3 characters.';
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters.';
    }

    if ($target === 'specific' && (empty($specificEmail) || !filter_var($specificEmail, FILTER_VALIDATE_EMAIL))) {
        $errors[] = 'Please enter a valid email address for the specific customer.';
    }

    if (empty($errors)) {
        require_once 'config/mailer.php';

        $recipients = [];
        if ($target === 'all') {
            $stmt = $pdo->query("SELECT email, name FROM users WHERE role = 'customer'");
            $recipients = $stmt->fetchAll();
        } else {
            $stmt = $pdo->prepare("SELECT email, name FROM users WHERE email = ? AND role = 'customer'");
            $stmt->execute([$specificEmail]);
            $recipients = $stmt->fetchAll();
            if (empty($recipients)) {
                $errors[] = 'No customer found with that email address.';
            }
        }

        if (empty($errors)) {
            foreach ($recipients as $recipient) {
                try {
                    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
                    
                    error_log("Announcement: Sending to " . $recipient['email']);
                    error_log("Announcement: Brevo key exists: " . ($brevoKey ? 'YES' : 'NO'));
                    
                    if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
                        // Use Brevo HTTP API (faster)
                        error_log("Announcement: Using Brevo HTTP API");
                        $htmlContent = '
                            <div style="font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #1a1a2e; color: #F5F0E8; border: 1px solid #C5A059;">
                                <h2 style="color: #C5A059; font-family: Playfair Display, serif;">' . htmlspecialchars($subject) . '</h2>
                                <div style="margin-top: 20px; line-height: 1.6;">' . nl2br(htmlspecialchars($message)) . '</div>
                                <hr style="border-color: rgba(197,160,89,0.3); margin: 30px 0;">
                                <p style="color: #8A8A9A; font-size: 0.85rem;">This message was sent by your barber.</p>
                            </div>
                        ';
                        
                        $emailResult = sendBrevoEmail($recipient['email'], $recipient['name'], $subject, $htmlContent);
                        error_log("Announcement: Brevo API result for " . $recipient['email'] . ": " . ($emailResult ? 'SUCCESS' : 'FAILED'));
                        
                        if ($emailResult) {
                            $sentCount++;
                        }
                    } else {
                        // Fallback to PHPMailer SMTP
                        error_log("Announcement: Using PHPMailer SMTP fallback");
                        $mail = getMailer();
                        $mail->addAddress($recipient['email'], $recipient['name']);
                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body = '
                            <div style="font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #1a1a2e; color: #F5F0E8; border: 1px solid #C5A059;">
                                <h2 style="color: #C5A059; font-family: Playfair Display, serif;">' . htmlspecialchars($subject) . '</h2>
                                <div style="margin-top: 20px; line-height: 1.6;">' . nl2br(htmlspecialchars($message)) . '</div>
                                <hr style="border-color: rgba(197,160,89,0.3); margin: 30px 0;">
                                <p style="color: #8A8A9A; font-size: 0.85rem;">This message was sent by your barber.</p>
                            </div>
                        ';
                        $mail->send();
                        $sentCount++;
                        error_log("Announcement: SMTP email sent successfully");
                    }
                } catch (Exception $e) {
                    error_log("Announcement failed to " . $recipient['email'] . ": " . $e->getMessage());
                }
            }

            if ($sentCount > 0) {
                $success = "Announcement sent successfully to {$sentCount} customer" . ($sentCount > 1 ? 's' : '') . "!";
            } else {
                $errors[] = 'Failed to send announcement. Please check your email configuration.';
            }
        }
    }
}

// Fetch customer list for reference
$stmt = $pdo->query("SELECT name, email FROM users WHERE role = 'customer' ORDER BY name ASC");
$customers = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <h2 class="mb-4" style="color: var(--barber-gold); font-family: 'Playfair Display', serif;">Announcements</h2>
        <p class="mb-4" style="color: var(--barber-gray); font-family: 'Oswald', sans-serif; text-transform: uppercase; letter-spacing: 2px; font-size: 0.85rem;">Send updates or promotions to your customers</p>

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
                <form method="POST" action="admin_announcements.php">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required
                               placeholder="e.g. New Hours, Special Promotion, Holiday Closure..."
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="6" required
                                  placeholder="Write your announcement here..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Send To</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target" id="targetAll" value="all" checked>
                            <label class="form-check-label" for="targetAll">All Customers (<?php echo count($customers); ?>)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target" id="targetSpecific" value="specific">
                            <label class="form-check-label" for="targetSpecific">Specific Customer</label>
                        </div>
                    </div>

                    <div class="mb-3" id="specificEmailBox" style="display: none;">
                        <label for="specific_email" class="form-label">Customer Email</label>
                        <input type="email" class="form-control" id="specific_email" name="specific_email"
                               placeholder="customer@example.com">
                        <div class="form-text mt-2">
                            <strong>Registered customers:</strong>
                            <ul class="mb-0 mt-1" style="padding-left: 1.2rem; color: var(--barber-gray);">
                                <?php foreach ($customers as $c): ?>
                                    <li><?php echo htmlspecialchars($c['name']); ?> &mdash; <?php echo htmlspecialchars($c['email']); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Send Announcement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetAll = document.getElementById('targetAll');
    const targetSpecific = document.getElementById('targetSpecific');
    const specificBox = document.getElementById('specificEmailBox');

    function toggleSpecific() {
        specificBox.style.display = targetSpecific.checked ? 'block' : 'none';
    }

    targetAll.addEventListener('change', toggleSpecific);
    targetSpecific.addEventListener('change', toggleSpecific);
});
</script>

<?php require_once 'includes/footer.php'; ?>
