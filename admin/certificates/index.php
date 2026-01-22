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
$eventFilter = isset($_GET['event_id']) ? (int)$_GET['event_id'] : null;
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;

// Build query
$whereConditions = [];
$params = [];

if ($eventFilter) {
    $whereConditions[] = "c.event_id = ?";
    $params[] = $eventFilter;
}

if ($statusFilter) {
    $whereConditions[] = "c.status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total certificates count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM certificates c $whereClause");
$stmt->execute($params);
$totalCertificates = $stmt->fetch()['count'];

// Pagination
$pagination = paginate($totalCertificates, $page);

// Get certificates
$stmt = $db->prepare("SELECT c.*, e.event_name 
                     FROM certificates c 
                     LEFT JOIN events e ON c.event_id = e.id 
                     $whereClause
                     ORDER BY c.issued_at DESC 
                     LIMIT ? OFFSET ?");
$stmt->execute(array_merge($params, [$pagination['records_per_page'], $pagination['offset']]));
$certificates = $stmt->fetchAll();

// Get events for filter dropdown
$eventsStmt = $db->query("SELECT id, event_name FROM events ORDER BY event_name");
$events = $eventsStmt->fetchAll();

include __DIR__ . '/../_header.php';
?>

<h2>Certificate Management</h2>

<?php displayFlashMessage(); ?>

<div class="mb-3">
    <a href="generate.php" class="btn btn-success"><i class="bi bi-plus-circle"></i> Generate Certificate</a>
    <a href="import.php" class="btn btn-info"><i class="bi bi-upload"></i> Batch Import (CSV)</a>
    <a href="export.php" class="btn btn-primary"><i class="bi bi-download"></i> Export PDFs</a>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Filter by Event</label>
                <select name="event_id" class="form-control" onchange="this.form.submit()">
                    <option value="">All Events</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['id']; ?>" <?php echo $eventFilter == $event['id'] ? 'selected' : ''; ?>>
                            <?php echo sanitize($event['event_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="valid" <?php echo $statusFilter === 'valid' ? 'selected' : ''; ?>>Valid</option>
                    <option value="revoked" <?php echo $statusFilter === 'revoked' ? 'selected' : ''; ?>>Revoked</option>
                    <option value="expired" <?php echo $statusFilter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <div>
                    <a href="index.php" class="btn btn-secondary">Clear Filters</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($certificates)): ?>
    <div class="alert alert-info">
        No certificates found. <a href="generate.php">Generate your first certificate</a>.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Certificate #</th>
                            <th>Recipient</th>
                            <th>Event</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Issued</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                            <tr>
                                <td><code><?php echo sanitize($cert['certificate_number']); ?></code></td>
                                <td><?php echo sanitize($cert['recipient_name']); ?></td>
                                <td><?php echo sanitize($cert['event_name']); ?></td>
                                <td>
                                    <?php if ($cert['email_sent']): ?>
                                        <span class="badge bg-success">✉️ Sent</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Not Sent</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo getStatusBadge($cert['status']); ?></td>
                                <td><?php echo formatDateTime($cert['issued_at'], 'd M Y'); ?></td>
                                <td>
                                    <?php if ($cert['pdf_path'] && file_exists(__DIR__ . '/../../' . $cert['pdf_path'])): ?>
                                        <a href="<?php echo APP_URL . '/' . $cert['pdf_path']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                            <i class="bi bi-download"></i> Download
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($cert['status'] === 'valid'): ?>
                                        <a href="revoke.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-danger">
                                            <i class="bi bi-x-circle"></i> Revoke
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if (!$cert['email_sent']): ?>
                                        <a href="send_email.php?id=<?php echo $cert['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-envelope"></i> Send
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php echo renderPagination($pagination, 'index.php' . ($eventFilter ? '?event_id=' . $eventFilter : '') . ($statusFilter ? '&status=' . $statusFilter : '')); ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../_footer.php'; ?>
