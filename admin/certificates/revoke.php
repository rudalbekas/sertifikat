<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

startSecureSession();
requireLogin();
requireRole('admin');

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

if ($certificate['status'] !== 'valid') {
    setFlashMessage('error', 'Certificate is already revoked or expired');
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $revokeReason = cleanInput($_POST['revoke_reason'] ?? '');
        
        if (empty($revokeReason)) {
            $error = 'Please provide a reason for revocation';
        } else {
            $oldValues = ['status' => $certificate['status']];
            $newValues = ['status' => 'revoked', 'revoke_reason' => $revokeReason];
            
            $stmt = $db->prepare("UPDATE certificates SET status = 'revoked', revoked_at = NOW(), revoked_by = ?, revoke_reason = ? WHERE id = ?");
            if ($stmt->execute([getCurrentUserId(), $revokeReason, $certId])) {
                logAudit(getCurrentUserId(), 'revoke', 'certificates', $certId, $oldValues, $newValues);
                setFlashMessage('success', 'Certificate revoked successfully');
                redirect('index.php');
            } else {
                $error = 'Failed to revoke certificate';
            }
        }
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Revoke Certificate</h2>

<div class="alert alert-warning">
    <h5>⚠️ Warning: Revoke Certificate</h5>
    <p>You are about to revoke the following certificate:</p>
    <ul>
        <li><strong>Certificate Number:</strong> <?php echo sanitize($certificate['certificate_number']); ?></li>
        <li><strong>Recipient:</strong> <?php echo sanitize($certificate['recipient_name']); ?></li>
        <li><strong>Event:</strong> <?php echo sanitize($certificate['event_name']); ?></li>
    </ul>
    <p>Once revoked, this certificate will show as <strong>INVALID</strong> when verified publicly.</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="mb-3">
                <label for="revoke_reason" class="form-label">Reason for Revocation *</label>
                <textarea class="form-control" id="revoke_reason" name="revoke_reason" rows="4" required 
                          placeholder="Enter the reason why this certificate is being revoked..."></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x-circle"></i> Revoke Certificate
                </button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../_footer.php'; ?>
