<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

startSecureSession();
requireLogin();
requireRole('admin');

if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Invalid template ID');
    redirect('index.php');
}

$templateId = (int)$_GET['id'];

$db = getDB();
$stmt = $db->prepare("SELECT * FROM templates WHERE id = ?");
$stmt->execute([$templateId]);
$template = $stmt->fetch();

if (!$template) {
    setFlashMessage('error', 'Template not found');
    redirect('index.php');
}

// Check if template is used by any events
$stmt = $db->prepare("SELECT COUNT(*) as count FROM events WHERE template_id = ?");
$stmt->execute([$templateId]);
$eventCount = $stmt->fetch()['count'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
    } else {
        // Delete template file if exists
        if (!empty($template['template_file'])) {
            $filePath = UPLOAD_TEMPLATE_PATH . $template['template_file'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        
        // Log before deletion
        logAudit(getCurrentUserId(), 'delete', 'templates', $templateId, $template);
        
        // Delete template
        $stmt = $db->prepare("DELETE FROM templates WHERE id = ?");
        if ($stmt->execute([$templateId])) {
            setFlashMessage('success', 'Template deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete template');
        }
        redirect('index.php');
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Delete Template</h2>

<div class="alert alert-danger">
    <h5>⚠️ Warning: This action cannot be undone!</h5>
    <p>You are about to delete the following template:</p>
    <ul>
        <li><strong>Template Name:</strong> <?php echo sanitize($template['template_name']); ?></li>
        <?php if ($eventCount > 0): ?>
            <li><strong>Warning:</strong> This template is used by <?php echo $eventCount; ?> event(s). Those events will have no template after deletion.</li>
        <?php endif; ?>
    </ul>
</div>

<form method="POST" action="">
    <?php echo csrfField(); ?>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash"></i> Yes, Delete Template
        </button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../_footer.php'; ?>
