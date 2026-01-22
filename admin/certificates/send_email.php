<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/mail.php';

startSecureSession();
requireLogin();

$db = getDB();

if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Invalid certificate ID');
    redirect('index.php');
}

$certId = (int)$_GET['id'];

// Get certificate
$stmt = $db->prepare("SELECT c.*, e.event_name FROM certificates c LEFT JOIN events e ON c.event_id = e.id WHERE c.id = ?");
$stmt->execute([$certId]);
$certificate = $stmt->fetch();

if (!$certificate) {
    setFlashMessage('error', 'Certificate not found');
    redirect('index.php');
}

if (empty($certificate['recipient_email'])) {
    setFlashMessage('error', 'No email address for this certificate');
    redirect('index.php');
}

if ($certificate['email_sent']) {
    setFlashMessage('warning', 'Email already sent for this certificate');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
    } else {
        // Generate download URL
        $downloadUrl = APP_URL . '/' . $certificate['pdf_path'];
        
        // Generate email body
        $subject = 'Your Certificate - ' . $certificate['event_name'];
        $body = generateCertificateEmailBody(
            $certificate['recipient_name'],
            $certificate['event_name'],
            $certificate['certificate_number'],
            $downloadUrl
        );
        
        // Queue email
        if (queueEmail($certId, $certificate['recipient_email'], $subject, $body)) {
            // Process queue immediately for this email
            processEmailQueue(1);
            
            setFlashMessage('success', 'Email sent successfully to ' . maskEmail($certificate['recipient_email']));
        } else {
            setFlashMessage('error', 'Failed to send email');
        }
        
        redirect('index.php');
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Send Certificate Email</h2>

<div class="card">
    <div class="card-body">
        <h5>Send Certificate to Recipient</h5>
        
        <div class="alert alert-info">
            <p><strong>Certificate:</strong> <?php echo sanitize($certificate['certificate_number']); ?></p>
            <p><strong>Recipient:</strong> <?php echo sanitize($certificate['recipient_name']); ?></p>
            <p><strong>Email:</strong> <?php echo sanitize($certificate['recipient_email']); ?></p>
            <p><strong>Event:</strong> <?php echo sanitize($certificate['event_name']); ?></p>
        </div>
        
        <p>An email will be sent with a link to download the certificate PDF.</p>
        
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-envelope"></i> Send Email
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../_footer.php'; ?>
