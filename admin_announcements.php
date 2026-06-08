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
            // Load push notification helper
            require_once __DIR__ . '/includes/push_helper.php';
            
            foreach ($recipients as $recipient) {
                try {
                    // Get user_id for push notification
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$recipient['email']]);
                    $user = $stmt->fetch();
                    
                    // Send push notification to customer
                    if ($user) {
                        sendPushNotification($pdo, $user['id'], '📢 Announcement', $subject, '/index.php');
                    }
                    
                    $brevoKey = getenv('BREVO_API_KEY') ?: ($_ENV['BREVO_API_KEY'] ?? null) ?: ($_SERVER['BREVO_API_KEY'] ?? null);
                    
                    error_log("Announcement: Sending to " . $recipient['email']);
                    error_log("Announcement: Brevo key exists: " . ($brevoKey ? 'YES' : 'NO'));
                    
                    if ($brevoKey && strpos($brevoKey, 'xkeysib-') === 0) {
                        // Use Brevo HTTP API (faster)
                        error_log("Announcement: Using Brevo HTTP API");
                        $htmlContent = '
                            <div style="font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #000000; color: #f5f5f5; border: 1px solid #c0c0c0;">
                                <h2 style="color: #c0c0c0; font-family: Playfair Display, serif;">' . htmlspecialchars($subject) . '</h2>
                                <div style="margin-top: 20px; line-height: 1.6;">' . nl2br(htmlspecialchars($message)) . '</div>
                                <hr style="border-color: rgba(192,192,192,0.3); margin: 30px 0;">
                                <p style="color: #808080; font-size: 0.85rem;">This message was sent by your barber.</p>
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
                            <div style="font-family: Inter, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #000000; color: #f5f5f5; border: 1px solid #c0c0c0;">
                                <h2 style="color: #c0c0c0; font-family: Playfair Display, serif;">' . htmlspecialchars($subject) . '</h2>
                                <div style="margin-top: 20px; line-height: 1.6;">' . nl2br(htmlspecialchars($message)) . '</div>
                                <hr style="border-color: rgba(192,192,192,0.3); margin: 30px 0;">
                                <p style="color: #808080; font-size: 0.85rem;">This message was sent by your barber.</p>
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
                
                // Send push notification to all users
                require_once __DIR__ . '/includes/push_helper.php';
                // Note: This sends to user_id=NULL which won't work, need to broadcast
                // For now, skip push for announcements or implement broadcast separately
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
            <div class="alert alert-success" style="background: rgba(40,167,69,0.2); border: 1px solid #28a745; color: #90EE90; border-radius: 8px; padding: 15px 20px;">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #ff6b6b; border-radius: 8px; padding: 15px 20px;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <ul class="mb-0 mt-2" style="padding-left: 20px;">
                    <?php foreach ($errors as $e): ?>
                        <li><?php echo htmlspecialchars($e); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card" style="background: #000000; border: 1px solid rgba(192,192,192,0.3); border-radius: 12px;">
            <div class="card-body p-4">
                <form method="POST" action="admin_announcements.php">
                    <div class="mb-4">
                        <label for="subject" class="form-label" style="color: #f5f5f5; font-weight: 600; font-size: 14px;">
                            <i class="bi bi-envelope"></i> Subject
                        </label>
                        <input type="text" class="form-control" id="subject" name="subject" required placeholder="e.g. New Hours, Special Promotion, Holiday Closure..."
                               value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>"
                               style="background: rgba(255,255,255,0.08); border: 1px solid rgba(192,192,192,0.4); color: #f5f5f5; padding: 12px 15px; border-radius: 8px; font-size: 15px;">
                        <style>
                            #subject::placeholder {
                                color: rgba(245, 245, 245, 0.5);
                                font-style: italic;
                            }
                        </style>
                    </div>

                    <div class="mb-4">
                        <label for="message" class="form-label" style="color: #f5f5f5; font-weight: 600; font-size: 14px;">
                            <i class="bi bi-chat-left-text"></i> Message
                        </label>
                        <textarea class="form-control" id="message" name="message" rows="6" required placeholder="Write your announcement here..."
                                  style="background: rgba(255,255,255,0.08); border: 1px solid rgba(192,192,192,0.4); color: #f5f5f5; padding: 12px 15px; border-radius: 8px; font-size: 15px; resize: vertical;"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        <style>
                            #message::placeholder {
                                color: rgba(245, 245, 245, 0.5);
                                font-style: italic;
                            }
                        </style>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="color: #f5f5f5; font-weight: 600; font-size: 14px;">
                            <i class="bi bi-people"></i> Send To
                        </label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target" id="targetAll" value="all" checked>
                            <label class="form-check-label" for="targetAll" style="color: #f5f5f5;">All Customers (<?php echo count($customers); ?>)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="target" id="targetSpecific" value="specific">
                            <label class="form-check-label" for="targetSpecific" style="color: #f5f5f5;">Specific Customer</label>
                        </div>
                    </div>

                    <div class="mb-4" id="specificEmailBox" style="display: none;">
                        <label for="specific_email" class="form-label" style="color: #f5f5f5; font-weight: 600; font-size: 14px;">
                            <i class="bi bi-person"></i> Customer Email
                        </label>
                        <input type="email" class="form-control" id="specific_email" name="specific_email"
                               placeholder="customer@example.com"
                               style="background: rgba(255,255,255,0.08); border: 1px solid rgba(192,192,192,0.4); color: #f5f5f5; padding: 12px 15px; border-radius: 8px; font-size: 15px;">
                        <style>
                            #specific_email::placeholder {
                                color: rgba(245, 245, 245, 0.5);
                                font-style: italic;
                            }
                        </style>
                        <div class="form-text mt-2" style="color: #b0b0b0;">
                            <strong style="color: #c0c0c0;"><i class="bi bi-info-circle"></i> Registered customers:</strong>
                            <ul class="mb-0 mt-2" style="padding-left: 1.2rem; color: #b0b0b0; list-style-type: none;">
                                <?php foreach ($customers as $c): ?>
                                    <li style="margin-bottom: 6px; padding: 8px; background: rgba(192,192,192,0.05); border-radius: 6px; border-left: 3px solid #c0c0c0;">
                                        <i class="bi bi-person-fill" style="color: #c0c0c0;"></i> 
                                        <strong style="color: #f5f5f5;"><?php echo htmlspecialchars($c['name']); ?></strong> 
                                        <span style="color: #808080;">—</span> 
                                        <span style="color: #c0c0c0;"><?php echo htmlspecialchars($c['email']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn" 
                                style="background: linear-gradient(135deg, #c0c0c0 0%, #d4d4d4 100%); border: none; color: #000000; padding: 14px 24px; border-radius: 8px; font-weight: 600; font-size: 16px;">
                            <i class="bi bi-send"></i> Send Announcement
                        </button>
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
