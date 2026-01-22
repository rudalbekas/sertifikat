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
    <style>
        * {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "Noto Sans", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
        }
        
        body {
            background: #0d1117;
            min-height: 100vh;
            padding: 24px;
            color: #c9d1d9;
        }
        
        .verify-container {
            max-width: 1012px;
            margin: 0 auto;
        }
        
        .github-header {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 32px;
            margin-bottom: 24px;
        }
        
        .github-header h1 {
            color: #c9d1d9;
            font-size: 32px;
            font-weight: 600;
            margin: 0 0 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .github-header h1 i {
            color: #58a6ff;
        }
        
        .github-header p {
            color: #8b949e;
            margin: 0;
            font-size: 16px;
        }
        
        .verify-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 32px;
            margin-bottom: 16px;
        }
        
        .form-label {
            color: #c9d1d9;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
            display: block;
        }
        
        .form-control-lg {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 14px;
            color: #c9d1d9;
            transition: all 0.2s;
            width: 100%;
        }
        
        .form-control-lg:focus {
            background: #0d1117;
            border-color: #58a6ff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(88, 166, 255, 0.3);
            color: #c9d1d9;
        }
        
        .btn-github {
            background: #238636;
            border: 1px solid rgba(240, 246, 252, 0.1);
            color: #fff;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            cursor: pointer;
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-github:hover:not(:disabled) {
            background: #2ea043;
            color: #fff;
        }
        
        .btn-github:disabled {
            background: #21262d;
            color: #484f58;
            cursor: not-allowed;
        }
        
        .btn-secondary-github {
            background: #21262d;
            border: 1px solid rgba(240, 246, 252, 0.1);
            color: #c9d1d9;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-secondary-github:hover {
            background: #30363d;
            color: #c9d1d9;
            border-color: #8b949e;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 24px;
            font-size: 14px;
            font-weight: 600;
            margin: 16px 0;
        }
        
        .status-badge.success {
            background: rgba(46, 160, 67, 0.15);
            color: #3fb950;
            border: 1px solid rgba(46, 160, 67, 0.4);
        }
        
        .status-badge.danger {
            background: rgba(248, 81, 73, 0.15);
            color: #f85149;
            border: 1px solid rgba(248, 81, 73, 0.4);
        }
        
        .status-badge.warning {
            background: rgba(187, 128, 9, 0.15);
            color: #d29922;
            border: 1px solid rgba(187, 128, 9, 0.4);
        }
        
        .certificate-details {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 24px;
            margin-top: 24px;
        }
        
        .certificate-details h4 {
            color: #c9d1d9;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 16px;
            padding-bottom: 8px;
            border-bottom: 1px solid #21262d;
        }
        
        .cert-info-row {
            padding: 12px 0;
            border-bottom: 1px solid #21262d;
            display: flex;
            align-items: flex-start;
        }
        
        .cert-info-row:last-child {
            border-bottom: none;
        }
        
        .cert-info-label {
            color: #8b949e;
            font-weight: 500;
            font-size: 14px;
            min-width: 180px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .cert-info-label i {
            color: #58a6ff;
        }
        
        .cert-info-value {
            color: #c9d1d9;
            font-size: 14px;
            flex: 1;
        }
        
        .cert-info-value code {
            background: #161b22;
            padding: 4px 8px;
            border-radius: 4px;
            color: #58a6ff;
            font-family: ui-monospace,SFMono-Regular,SF Mono,Menlo,Consolas,Liberation Mono,monospace;
            font-size: 12px;
            border: 1px solid #30363d;
        }
        
        .alert {
            border-radius: 6px;
            padding: 16px;
            margin-bottom: 16px;
            border: 1px solid;
        }
        
        .alert-danger {
            background: rgba(248, 81, 73, 0.15);
            color: #f85149;
            border-color: rgba(248, 81, 73, 0.4);
        }
        
        .alert-warning {
            background: rgba(187, 128, 9, 0.15);
            color: #d29922;
            border-color: rgba(187, 128, 9, 0.4);
        }
        
        .info-box {
            background: rgba(56, 139, 253, 0.15);
            border: 1px solid rgba(56, 139, 253, 0.4);
            border-radius: 6px;
            padding: 16px;
            margin-top: 16px;
            color: #58a6ff;
            font-size: 14px;
        }
        
        .info-box i {
            margin-right: 8px;
        }
        
        .back-link {
            color: #58a6ff;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 16px;
        }
        
        .back-link:hover {
            text-decoration: underline;
            color: #58a6ff;
        }
        
        .btn-group-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-wrap: wrap;
        }
        
        .btn-group-actions .btn-secondary-github {
            flex: 1;
            justify-content: center;
            min-width: 200px;
        }
        
        @media (max-width: 768px) {
            .cert-info-row {
                flex-direction: column;
            }
            .cert-info-label {
                min-width: auto;
                margin-bottom: 4px;
            }
            .btn-group-actions {
                flex-direction: column;
            }
            .btn-group-actions .btn-secondary-github {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="github-header">
            <h1><i class="bi bi-shield-check-fill"></i> Certificate Verification</h1>
            <p>Verify the authenticity of your certificate</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo sanitize($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$certificateData): ?>
            <!-- Verification Form -->
            <div class="verify-card">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    
                    <div class="mb-4">
                        <label for="certificate_number" class="form-label">
                            Certificate Number or Token
                        </label>
                        <input type="text" class="form-control-lg" id="certificate_number" 
                               name="certificate_number" placeholder="Enter certificate number or scan QR code" required autofocus>
                    </div>
                    
                    <?php if ($showCaptcha): ?>
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            <strong>Security Notice:</strong> Multiple failed attempts detected. Please wait before trying again.
                        </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn-github" <?php echo $showCaptcha ? 'disabled' : ''; ?>>
                        <i class="bi bi-search"></i> Verify Certificate
                    </button>
                </form>
                
                <div class="info-box">
                    <i class="bi bi-qr-code-scan"></i>
                    <strong>Tip:</strong> You can also scan the QR code on your certificate for instant verification
                </div>
            </div>
        <?php else: ?>
            <!-- Certificate Details -->
            <div class="verify-card">
                <div class="text-center">
                    <?php if ($certificateData['status'] === 'valid'): ?>
                        <div class="status-badge success">
                            <i class="bi bi-check-circle-fill"></i> Valid Certificate
                        </div>
                    <?php elseif ($certificateData['status'] === 'revoked'): ?>
                        <div class="status-badge danger">
                            <i class="bi bi-x-circle-fill"></i> Certificate Revoked
                        </div>
                    <?php elseif ($certificateData['status'] === 'expired'): ?>
                        <div class="status-badge warning">
                            <i class="bi bi-clock-fill"></i> Certificate Expired
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="certificate-details">
                    <h4><i class="bi bi-file-earmark-text-fill"></i> Certificate Information</h4>
                    
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-hash"></i> Certificate Number
                        </div>
                        <div class="cert-info-value">
                            <code><?php echo sanitize($certificateData['certificate_number']); ?></code>
                        </div>
                    </div>
                    
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-person-fill"></i> Recipient
                        </div>
                        <div class="cert-info-value">
                            <?php echo sanitize($certificateData['recipient_name']); ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($certificateData['recipient_email'])): ?>
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-envelope-fill"></i> Email
                        </div>
                        <div class="cert-info-value">
                            <?php echo maskEmail($certificateData['recipient_email']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($certificateData['institution'])): ?>
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-building"></i> Institution
                        </div>
                        <div class="cert-info-value">
                            <?php echo sanitize($certificateData['institution']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-calendar-event-fill"></i> Event
                        </div>
                        <div class="cert-info-value">
                            <?php echo sanitize($certificateData['event_name']); ?>
                        </div>
                    </div>
                    
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-calendar-check-fill"></i> Event Date
                        </div>
                        <div class="cert-info-value">
                            <?php echo formatDate($certificateData['event_date']); ?>
                        </div>
                    </div>
                    
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-briefcase-fill"></i> Organizer
                        </div>
                        <div class="cert-info-value">
                            <?php echo sanitize($certificateData['organizer']); ?>
                        </div>
                    </div>
                    
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-calendar3-fill"></i> Issued Date
                        </div>
                        <div class="cert-info-value">
                            <?php echo formatDateTime($certificateData['issued_at']); ?>
                        </div>
                    </div>
                    
                    <?php if ($certificateData['status'] === 'revoked' && !empty($certificateData['revoke_reason'])): ?>
                    <div class="cert-info-row">
                        <div class="cert-info-label">
                            <i class="bi bi-info-circle-fill"></i> Revoke Reason
                        </div>
                        <div class="cert-info-value" style="color: #f85149;">
                            <?php echo sanitize($certificateData['revoke_reason']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="btn-group-actions">
                    <?php 
                    // Security: Validate PDF path to prevent directory traversal
                    $pdfPath = $certificateData['pdf_path'] ?? '';
                    $showDownload = false;
                    if (!empty($pdfPath)) {
                        // Remove any directory traversal attempts
                        $pdfPath = str_replace(['../', '..\\'], '', $pdfPath);
                        // Ensure path starts with expected directory
                        if (strpos($pdfPath, 'generated/certificates/') === 0) {
                            $fullPath = __DIR__ . '/../' . $pdfPath;
                            if (file_exists($fullPath)) {
                                $showDownload = true;
                            }
                        }
                    }
                    if ($showDownload): 
                    ?>
                    <a href="<?php echo APP_URL; ?>/<?php echo htmlspecialchars($pdfPath, ENT_QUOTES, 'UTF-8'); ?>" 
                       class="btn-secondary-github" download>
                        <i class="bi bi-download"></i> Download Certificate (PDF)
                    </a>
                    <?php endif; ?>
                    <a href="index.php" class="btn-secondary-github">
                        <i class="bi bi-arrow-clockwise"></i> Verify Another Certificate
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="text-center">
            <a href="<?php echo APP_URL; ?>" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to Home
            </a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
