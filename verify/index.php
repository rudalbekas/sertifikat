<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/functions.php';

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
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .verify-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .verify-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
            margin-bottom: 20px;
        }
        .verify-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .verify-header h1 {
            color: #667eea;
            font-size: 28px;
            font-weight: bold;
        }
        .certificate-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .status-badge {
            font-size: 24px;
            padding: 10px 20px;
            border-radius: 10px;
            display: inline-block;
            margin: 20px 0;
        }
        .btn-verify {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-card">
            <div class="verify-header">
                <h1>🔍 Certificate Verification</h1>
                <p class="text-muted">Verify the authenticity of a certificate</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger text-center">
                    <i class="bi bi-x-circle"></i> <?php echo sanitize($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$certificateData): ?>
                <!-- Verification Form -->
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="certificate_number" class="form-label">Certificate Number or Token</label>
                        <input type="text" class="form-control form-control-lg" id="certificate_number" 
                               name="certificate_number" placeholder="Enter certificate number" required autofocus>
                    </div>
                    
                    <?php if ($showCaptcha): ?>
                        <div class="alert alert-warning">
                            <small>⚠️ Multiple failed attempts detected. Please verify you're human.</small>
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary btn-verify">
                        <i class="bi bi-search"></i> Verify Certificate
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Or scan the QR code on your certificate
                    </small>
                </div>
            <?php else: ?>
                <!-- Certificate Details -->
                <div class="text-center">
                    <?php if ($certificateData['status'] === 'valid'): ?>
                        <div class="status-badge bg-success text-white">
                            ✅ VALID CERTIFICATE
                        </div>
                    <?php elseif ($certificateData['status'] === 'revoked'): ?>
                        <div class="status-badge bg-danger text-white">
                            ❌ CERTIFICATE REVOKED
                        </div>
                    <?php elseif ($certificateData['status'] === 'expired'): ?>
                        <div class="status-badge bg-warning text-white">
                            ⏰ CERTIFICATE EXPIRED
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="certificate-details">
                    <h4 class="mb-3">Certificate Information</h4>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Certificate Number:</strong></div>
                        <div class="col-md-8"><code><?php echo sanitize($certificateData['certificate_number']); ?></code></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Recipient:</strong></div>
                        <div class="col-md-8"><?php echo sanitize($certificateData['recipient_name']); ?></div>
                    </div>
                    
                    <?php if (!empty($certificateData['recipient_email'])): ?>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Email:</strong></div>
                        <div class="col-md-8"><?php echo maskEmail($certificateData['recipient_email']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($certificateData['institution'])): ?>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Institution:</strong></div>
                        <div class="col-md-8"><?php echo sanitize($certificateData['institution']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Event:</strong></div>
                        <div class="col-md-8"><?php echo sanitize($certificateData['event_name']); ?></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Event Date:</strong></div>
                        <div class="col-md-8"><?php echo formatDate($certificateData['event_date']); ?></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Organizer:</strong></div>
                        <div class="col-md-8"><?php echo sanitize($certificateData['organizer']); ?></div>
                    </div>
                    
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Issued Date:</strong></div>
                        <div class="col-md-8"><?php echo formatDateTime($certificateData['issued_at']); ?></div>
                    </div>
                    
                    <?php if ($certificateData['status'] === 'revoked' && !empty($certificateData['revoke_reason'])): ?>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Revoke Reason:</strong></div>
                        <div class="col-md-8 text-danger"><?php echo sanitize($certificateData['revoke_reason']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">Verify Another Certificate</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center">
            <a href="<?php echo APP_URL; ?>" class="text-white text-decoration-none">← Back to Home</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
