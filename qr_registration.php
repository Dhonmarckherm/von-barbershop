<?php
/**
 * QR Code for Registration
 * Generates a QR code that links directly to the registration page.
 */
$pageTitle = 'Registration QR Code';
require_once 'includes/auth_check.php';
require_once 'includes/admin_check.php';

// Get the registration URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$registerUrl = $protocol . '://' . $host . '/register.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .qr-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .qr-title {
            color: var(--barber-gold);
            font-family: 'Playfair Display', serif;
            margin-bottom: 20px;
        }
        .qr-subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        #qrcode {
            display: inline-block;
            padding: 20px;
            background: white;
            border: 3px solid var(--barber-gold);
            border-radius: 10px;
        }
        .qr-url {
            margin-top: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 0.9rem;
            word-break: break-all;
        }
        .print-btn {
            margin-top: 20px;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .qr-container, .qr-container * {
                visibility: visible;
            }
            .qr-container {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                box-shadow: none;
            }
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="qr-container">
                    <h2 class="qr-title">
                        <i class="bi bi-qr-code"></i> Registration QR Code
                    </h2>
                    <p class="qr-subtitle">Scan to register and book your appointment</p>
                    
                    <div id="qrcode"></div>
                    
                    <div class="qr-url">
                        <strong>URL:</strong> <?php echo htmlspecialchars($registerUrl); ?>
                    </div>
                    
                    <button class="btn btn-primary print-btn" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print QR Code
                    </button>
                    
                    <div class="mt-3">
                        <a href="admin_dashboard.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR code
        new QRCode(document.getElementById("qrcode"), {
            text: "<?php echo $registerUrl; ?>",
            width: 256,
            height: 256,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>
