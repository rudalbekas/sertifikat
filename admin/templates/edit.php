<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

startSecureSession();
requireLogin();
requireRole(['admin', 'designer']);

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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $templateName = cleanInput($_POST['template_name'] ?? '');
        $layoutConfig = cleanInput($_POST['layout_config'] ?? '');
        
        if (empty($templateName)) {
            $error = 'Please enter template name';
        } else {
            $templateFile = $template['template_file'];
            
            // Handle file upload if provided
            if (isset($_FILES['template_file']) && $_FILES['template_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
                $fileErrors = validateFileUpload($_FILES['template_file'], $allowedExtensions);
                
                if (!empty($fileErrors)) {
                    $error = implode('<br>', $fileErrors);
                } else {
                    $uploadedFile = $_FILES['template_file'];
                    $fileName = generateSecureFilename($uploadedFile['name']);
                    $targetPath = UPLOAD_TEMPLATE_PATH . $fileName;
                    
                    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                        // Delete old file
                        if (!empty($template['template_file'])) {
                            $oldPath = UPLOAD_TEMPLATE_PATH . $template['template_file'];
                            if (file_exists($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                        $templateFile = $fileName;
                    } else {
                        $error = 'Failed to upload file';
                    }
                }
            }
            
            if (empty($error)) {
                $oldValues = $template;
                $newValues = ['template_name' => $templateName, 'layout_config' => $layoutConfig];
                
                $stmt = $db->prepare("UPDATE templates SET template_name = ?, template_file = ?, layout_config = ? WHERE id = ?");
                if ($stmt->execute([$templateName, $templateFile, $layoutConfig, $templateId])) {
                    logAudit(getCurrentUserId(), 'update', 'templates', $templateId, $oldValues, $newValues);
                    setFlashMessage('success', 'Template updated successfully!');
                    redirect('index.php');
                } else {
                    $error = 'Failed to update template';
                }
            }
        }
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Edit Template</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            
            <div class="mb-3">
                <label for="template_name" class="form-label">Template Name *</label>
                <input type="text" class="form-control" id="template_name" name="template_name" 
                       value="<?php echo sanitize($template['template_name']); ?>" required>
            </div>
            
            <?php if (!empty($template['template_file']) && file_exists(UPLOAD_TEMPLATE_PATH . $template['template_file'])): ?>
            <div class="mb-3">
                <label class="form-label">Current Template</label>
                <div>
                    <img src="<?php echo APP_URL; ?>/uploads/templates/<?php echo sanitize($template['template_file']); ?>" 
                         alt="Template" style="max-width: 300px; border: 1px solid #ddd; padding: 5px;">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="mb-3">
                <label for="template_file" class="form-label">Replace Template File</label>
                <input type="file" class="form-control" id="template_file" name="template_file" accept="image/*,.pdf">
                <small class="form-text text-muted">
                    Leave blank to keep current file. Upload new file to replace.
                </small>
            </div>
            
            <div class="mb-3">
                <label for="layout_config" class="form-label">Layout Configuration (JSON)</label>
                <textarea class="form-control" id="layout_config" name="layout_config" rows="6"><?php echo sanitize($template['layout_config']); ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update Template</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../_footer.php'; ?>
