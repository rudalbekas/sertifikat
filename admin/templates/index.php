<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

startSecureSession();
requireLogin();

$db = getDB();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Get total templates count
$stmt = $db->query("SELECT COUNT(*) as count FROM templates");
$totalTemplates = $stmt->fetch()['count'];

// Pagination
$pagination = paginate($totalTemplates, $page);

// Get templates
$stmt = $db->prepare("SELECT * FROM templates ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$pagination['records_per_page'], $pagination['offset']]);
$templates = $stmt->fetchAll();

include __DIR__ . '/../_header.php';
?>

<h2>Certificate Templates</h2>

<?php displayFlashMessage(); ?>

<div class="mb-3">
    <?php if (currentUserHasRole(['admin', 'designer'])): ?>
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create New Template</a>
    <?php endif; ?>
</div>

<?php if (empty($templates)): ?>
    <div class="alert alert-info">
        No templates created yet. <a href="create.php">Create your first template</a>.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($templates as $template): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if (!empty($template['template_file']) && file_exists(__DIR__ . '/../../uploads/templates/' . $template['template_file'])): ?>
                        <img src="<?php echo APP_URL; ?>/uploads/templates/<?php echo sanitize($template['template_file']); ?>" 
                             class="card-img-top" alt="Template" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-file-earmark-image" style="font-size: 48px; color: white;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo sanitize($template['template_name']); ?></h5>
                        <p class="card-text text-muted small">
                            Created: <?php echo formatDateTime($template['created_at'], 'd M Y'); ?>
                        </p>
                        <div class="d-flex gap-2">
                            <?php if (currentUserHasRole(['admin', 'designer'])): ?>
                            <a href="edit.php?id=<?php echo $template['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <?php endif; ?>
                            <?php if (currentUserHasRole('admin')): ?>
                            <a href="delete.php?id=<?php echo $template['id']; ?>" 
                               class="btn btn-sm btn-danger" 
                               onclick="return confirm('Are you sure?')">
                                <i class="bi bi-trash"></i> Delete
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php echo renderPagination($pagination, 'index.php'); ?>
<?php endif; ?>

<?php include __DIR__ . '/../_footer.php'; ?>
