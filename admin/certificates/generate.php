<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../libraries/phpqrcode/loader.php';

startSecureSession();
requireLogin();

$db = getDB();
$error = '';

// Get events for dropdown
$stmt = $db->query("SELECT id, event_name FROM events ORDER BY event_name");
$events = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $eventId = (int)$_POST['event_id'];
        $recipientName = cleanInput($_POST['recipient_name'] ?? '');
        $recipientEmail = cleanInput($_POST['recipient_email'] ?? '');
        $institution = cleanInput($_POST['institution'] ?? '');
        $grade = cleanInput($_POST['grade'] ?? '');
        
        if (empty($eventId) || empty($recipientName)) {
            $error = 'Please fill in all required fields';
        } elseif (!empty($recipientEmail) && !validateEmail($recipientEmail)) {
            $error = 'Invalid email address';
        } else {
            // Generate certificate number
            $certificateNumber = generateCertificateNumber();
            
            // Generate verification token with HMAC
            $verificationToken = generateVerificationToken($certificateNumber);
            
            // Generate QR code
            $qrFileName = $certificateNumber . '.png';
            $qrFilePath = 'generated/qrcodes/' . $qrFileName;
            $qrFullPath = __DIR__ . '/../../' . $qrFilePath;
            
            $verifyUrl = APP_URL . '/verify/index.php?token=' . urlencode($verificationToken);
            QRcode::png($verifyUrl, $qrFullPath, 'L', 5, 2);
            
            // Insert certificate into database
            $stmt = $db->prepare("INSERT INTO certificates 
                (certificate_number, event_id, recipient_name, recipient_email, institution, grade, 
                 verification_token, qr_code_path, status, issued_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'valid', NOW())");
            
            if ($stmt->execute([
                $certificateNumber, $eventId, $recipientName, $recipientEmail, 
                $institution, $grade, $verificationToken, $qrFilePath
            ])) {
                $certificateId = $db->lastInsertId();
                
                // Log the action
                logAudit(getCurrentUserId(), 'create', 'certificates', $certificateId, null, [
                    'certificate_number' => $certificateNumber,
                    'recipient_name' => $recipientName
                ]);
                
                setFlashMessage('success', 'Certificate generated successfully! Certificate #: ' . $certificateNumber);
                redirect('index.php');
            } else {
                $error = 'Failed to generate certificate';
            }
        }
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Generate Certificate</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<?php if (empty($events)): ?>
    <div class="alert alert-warning">
        No events available. <a href="../events/create.php">Create an event first</a>.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label for="event_id" class="form-label">Event *</label>
                    <select class="form-control" id="event_id" name="event_id" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo $event['id']; ?>">
                                <?php echo sanitize($event['event_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="recipient_name" class="form-label">Recipient Name *</label>
                    <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                </div>
                
                <div class="mb-3">
                    <label for="recipient_email" class="form-label">Recipient Email</label>
                    <input type="email" class="form-control" id="recipient_email" name="recipient_email">
                    <small class="form-text text-muted">Optional - for sending certificate via email</small>
                </div>
                
                <div class="mb-3">
                    <label for="institution" class="form-label">Institution / Organization</label>
                    <input type="text" class="form-control" id="institution" name="institution">
                </div>
                
                <div class="mb-3">
                    <label for="grade" class="form-label">Grade / Score</label>
                    <input type="text" class="form-control" id="grade" name="grade">
                    <small class="form-text text-muted">Optional - e.g., A, B, 90, etc.</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Generate Certificate</button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../_footer.php'; ?>
