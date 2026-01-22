<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();

$db = getDB();
$certificateData = null;
$error = '';
$showCaptcha = false;

$clientIP = getClientIP();

// Check rate limiting
if (checkRateLimit($clientIP)) {
    $error = 'Too many verification attempts. Please try again later.';
}

// Check if CAPTCHA should be shown
$failedAttempts = getFailedAttempts($clientIP);
if ($failedAttempts >= CAPTCHA_THRESHOLD) {
    $showCaptcha = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    // Verify CSRF token (even for public pages)
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $input = cleanInput($_POST['certificate_number'] ?? $_POST['token'] ?? '');
        
        if (empty($input)) {
            $error = 'Please enter a certificate number or scan QR code';
        } else {
        // Check if it's a verification token or certificate number
        $stmt = null;
        
        if (strpos($input, '-') !== false && strlen($input) > 50) {
            // Looks like a verification token
            $certificateId = verifyVerificationToken($input);
            
            if ($certificateId) {
                $stmt = $db->prepare("SELECT c.*, e.event_name, e.event_date, e.organizer 
                                     FROM certificates c 
                                     LEFT JOIN events e ON c.event_id = e.id 
                                     WHERE c.id = ?");
                $stmt->execute([$certificateId]);
            }
        } else {
            // Regular certificate number
            $stmt = $db->prepare("SELECT c.*, e.event_name, e.event_date, e.organizer 
                                 FROM certificates c 
                                 LEFT JOIN events e ON c.event_id = e.id 
                                 WHERE c.certificate_number = ?");
            $stmt->execute([$input]);
        }
        
        if ($stmt && ($certificateData = $stmt->fetch())) {
            // Certificate found
            logVerificationAttempt($clientIP, $input, true);
        } else {
            $error = 'Certificate not found or invalid token';
            logVerificationAttempt($clientIP, $input, false);
            }
        }
    }
}

// Handle GET token parameter (from QR code)
if (!$certificateData && isset($_GET['token']) && !$error) {
    $token = cleanInput($_GET['token']);
    $certificateId = verifyVerificationToken($token);
    
    if ($certificateId) {
        $stmt = $db->prepare("SELECT c.*, e.event_name, e.event_date, e.organizer 
                             FROM certificates c 
                             LEFT JOIN events e ON c.event_id = e.id 
                             WHERE c.id = ?");
        $stmt->execute([$certificateId]);
        $certificateData = $stmt->fetch();
        
        if ($certificateData) {
            logVerificationAttempt($clientIP, $token, true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Certificate - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background elements */
        body::before {
            content: '';
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            top: -250px;
            right: -250px;
            animation: float 20s ease-in-out infinite;
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: fixed;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -200px;
            left: -200px;
            animation: float 15s ease-in-out infinite reverse;
            z-index: 0;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            33% { transform: translate(30px, -50px) rotate(120deg); }
            66% { transform: translate(-20px, 20px) rotate(240deg); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .verify-container {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            animation: fadeInUp 0.6s ease-out;
        }
        
        .verify-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 1px rgba(0, 0, 0, 0.1);
            padding: 50px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        
        .verify-card:hover {
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.35);
            transform: translateY(-5px);
        }
        
        .verify-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .verify-header .icon-wrapper {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            animation: scaleIn 0.5s ease-out 0.2s both;
        }
        
        .verify-header .icon-wrapper i {
            font-size: 40px;
            color: white;
        }
        
        .verify-header h1 {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 10px;
            letter-spacing: -1px;
        }
        
        .verify-header p {
            color: #6c757d;
            font-size: 16px;
            font-weight: 400;
        }
        
        .form-label {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 12px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control-lg {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 16px 20px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control-lg:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: white;
            transform: translateY(-2px);
        }
        
        .btn-verify {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 16px;
            font-weight: 700;
            font-size: 16px;
            border-radius: 12px;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-verify:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5568d3 0%, #63408a 100%);
        }
        
        .btn-verify:active:not(:disabled) {
            transform: translateY(-1px);
        }
        
        .btn-verify:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .status-badge {
            font-size: 20px;
            padding: 16px 32px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin: 30px 0;
            font-weight: 700;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: scaleIn 0.5s ease-out;
            letter-spacing: 1px;
        }
        
        .status-badge i {
            font-size: 28px;
        }
        
        .certificate-details {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            padding: 30px;
            margin-top: 30px;
            border: 1px solid #dee2e6;
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }
        
        .certificate-details h4 {
            color: #2d3748;
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 22px;
            padding-bottom: 15px;
            border-bottom: 3px solid #667eea;
        }
        
        .certificate-details .row {
            padding: 12px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        
        .certificate-details .row:last-child {
            border-bottom: none;
        }
        
        .certificate-details .row:hover {
            background: rgba(102, 126, 234, 0.05);
            padding-left: 10px;
            border-radius: 8px;
        }
        
        .certificate-details strong {
            color: #4a5568;
            font-weight: 600;
            font-size: 14px;
        }
        
        .certificate-details code {
            background: white;
            padding: 6px 12px;
            border-radius: 8px;
            color: #667eea;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            animation: fadeInUp 0.4s ease-out;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee 0%, #fdd 100%);
            color: #c53030;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            color: #92400e;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 32px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5568d3 0%, #63408a 100%);
        }
        
        .back-link {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .back-link:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
            color: white;
        }
        
        .qr-hint {
            color: #6c757d;
            font-size: 14px;
            font-weight: 500;
            margin-top: 20px;
            padding: 16px;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            border: 1px dashed rgba(102, 126, 234, 0.3);
        }
        
        .qr-hint i {
            color: #667eea;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <div class="icon-wrapper">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h1>Certificate Verification</h1>
                <p class="text-muted">Verify the authenticity of your certificate instantly</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger text-center">
                    <i class="bi bi-exclamation-circle-fill"></i> <?php echo sanitize($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$certificateData): ?>
                <!-- Verification Form -->
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-4">
                        <label for="certificate_number" class="form-label">
                            <i class="bi bi-hash"></i> Certificate Number or Token
                        </label>
                        <input type="text" class="form-control form-control-lg" id="certificate_number" 
                               name="certificate_number" placeholder="Enter your certificate number or token" required autofocus>
                    </div>
                    
                    <?php if ($showCaptcha): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Security Notice:</strong> Multiple failed attempts detected. Please wait before trying again.
                            <br><br>
                            <small><strong>Note:</strong> Full CAPTCHA integration requires external service (e.g., Google reCAPTCHA). 
                            For now, rate limiting is enforced.</small>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary btn-verify" <?php echo $showCaptcha ? 'disabled' : ''; ?>>
                        <i class="bi bi-search"></i> Verify Certificate
                    </button>
                </form>
                
                <div class="text-center qr-hint">
                    <i class="bi bi-qr-code"></i>
                    <strong>Quick Tip:</strong> You can also scan the QR code on your certificate for instant verification
                </div>
            <?php else: ?>
                <!-- Certificate Details -->
                <div class="text-center">
                    <?php if ($certificateData['status'] === 'valid'): ?>
                        <div class="status-badge bg-success text-white">
                            <i class="bi bi-check-circle-fill"></i> VALID CERTIFICATE
                        </div>
                    <?php elseif ($certificateData['status'] === 'revoked'): ?>
                        <div class="status-badge bg-danger text-white">
                            <i class="bi bi-x-circle-fill"></i> CERTIFICATE REVOKED
                        </div>
                    <?php elseif ($certificateData['status'] === 'expired'): ?>
                        <div class="status-badge bg-warning text-dark">
                            <i class="bi bi-clock-fill"></i> CERTIFICATE EXPIRED
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="certificate-details">
                    <h4><i class="bi bi-file-earmark-text"></i> Certificate Information</h4>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-hash"></i> Certificate Number:</strong></div>
                        <div class="col-md-8"><code><?php echo sanitize($certificateData['certificate_number']); ?></code></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-person-fill"></i> Recipient:</strong></div>
                        <div class="col-md-8"><?php echo sanitize($certificateData['recipient_name']); ?></div>
                    </div>
                    
                    <?php if (!empty($certificateData['recipient_email'])): ?>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-envelope-fill"></i> Email:</strong></div>
                        <div class="col-md-8"><?php echo maskEmail($certificateData['recipient_email']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($certificateData['institution'])): ?>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-building"></i> Institution:</strong></div>
                        <div class="col-md-8"><?php echo sanitize($certificateData['institution']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-calendar-event"></i> Event:</strong></div>
                        <div class="col-md-8"><?php echo sanitize($certificateData['event_name']); ?></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-calendar-check"></i> Event Date:</strong></div>
                        <div class="col-md-8"><?php echo formatDate($certificateData['event_date']); ?></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-briefcase-fill"></i> Organizer:</strong></div>
                        <div class="col-md-8"><?php echo sanitize($certificateData['organizer']); ?></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-calendar3"></i> Issued Date:</strong></div>
                        <div class="col-md-8"><?php echo formatDateTime($certificateData['issued_at']); ?></div>
                    </div>
                    
                    <?php if ($certificateData['status'] === 'revoked' && !empty($certificateData['revoke_reason'])): ?>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong><i class="bi bi-info-circle-fill"></i> Revoke Reason:</strong></div>
                        <div class="col-md-8 text-danger"><?php echo sanitize($certificateData['revoke_reason']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">
                        <i class="bi bi-arrow-clockwise"></i> Verify Another Certificate
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center">
            <a href="<?php echo APP_URL; ?>" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
