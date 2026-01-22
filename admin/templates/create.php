<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

startSecureSession();
requireLogin();
requireRole(['admin', 'designer']);

$db = getDB();
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
            $templateFile = '';
            
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
                        $templateFile = $fileName;
                    } else {
                        $error = 'Failed to upload file';
                    }
                }
            }
            
            if (empty($error)) {
                $stmt = $db->prepare("INSERT INTO templates (template_name, template_file, layout_config, created_at) VALUES (?, ?, ?, NOW())");
                if ($stmt->execute([$templateName, $templateFile, $layoutConfig])) {
                    $templateId = $db->lastInsertId();
                    logAudit(getCurrentUserId(), 'create', 'templates', $templateId, null, ['template_name' => $templateName]);
                    setFlashMessage('success', 'Template created successfully!');
                    redirect('index.php');
                } else {
                    $error = 'Failed to create template';
                }
            }
        }
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Create New Template</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            
            <div class="mb-3">
                <label for="template_name" class="form-label">Template Name *</label>
                <input type="text" class="form-control" id="template_name" name="template_name" required>
            </div>
            
            <div class="mb-3">
                <label for="template_file" class="form-label">Template File (Image/PDF)</label>
                <input type="file" class="form-control" id="template_file" name="template_file" accept="image/*,.pdf">
                <small class="form-text text-muted">
                    Upload certificate background template. Recommended: A4 landscape (297mm x 210mm), JPG/PNG format.
                </small>
            </div>
            
            <div class="mb-3">
                <label for="layout_config" class="form-label">Layout Configuration (JSON)</label>
                <textarea class="form-control" id="layout_config" name="layout_config" rows="6" 
                          placeholder='{"name_x": 100, "name_y": 120, "qr_x": 250, "qr_y": 160}'></textarea>
                <small class="form-text text-muted">
                    Optional: JSON configuration for text positions, QR code placement, etc.
                </small>
            </div>
            
            <div class="alert alert-info">
                <strong>Layout Configuration Guide:</strong>
                <ul class="mb-0">
                    <li><code>name_x, name_y</code>: Position for recipient name</li>
                    <li><code>cert_number_x, cert_number_y</code>: Position for certificate number</li>
                    <li><code>qr_x, qr_y</code>: Position for QR code</li>
                    <li><code>date_x, date_y</code>: Position for date</li>
                </ul>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Template</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../_footer.php'; ?>
