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

// Get total events count
$stmt = $db->query("SELECT COUNT(*) as count FROM events");
$totalEvents = $stmt->fetch()['count'];

// Pagination
$pagination = paginate($totalEvents, $page);

// Get events
$stmt = $db->prepare("SELECT e.*, u.username as created_by_name, t.template_name 
                     FROM events e 
                     LEFT JOIN users u ON e.created_by = u.id 
                     LEFT JOIN templates t ON e.template_id = t.id 
                     ORDER BY e.created_at DESC 
                     LIMIT ? OFFSET ?");
$stmt->execute([$pagination['records_per_page'], $pagination['offset']]);
$events = $stmt->fetchAll();

// Get certificate count per event
$certificateCounts = [];
$stmt = $db->query("SELECT event_id, COUNT(*) as count FROM certificates GROUP BY event_id");
foreach ($stmt->fetchAll() as $row) {
    $certificateCounts[$row['event_id']] = $row['count'];
}

include __DIR__ . '/../_header.php';
?>

<h2>Event Management</h2>

<?php displayFlashMessage(); ?>

<div class="mb-3">
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create New Event</a>
</div>

<?php if (empty($events)): ?>
    <div class="alert alert-info">
        No events created yet. <a href="create.php">Create your first event</a>.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Date</th>
                            <th>Organizer</th>
                            <th>Template</th>
                            <th>Certificates</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo $event['id']; ?></td>
                                <td><?php echo sanitize($event['event_name']); ?></td>
                                <td><?php echo formatDate($event['event_date']); ?></td>
                                <td><?php echo sanitize($event['organizer']); ?></td>
                                <td><?php echo sanitize($event['template_name'] ?? 'None'); ?></td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $certificateCounts[$event['id']] ?? 0; ?>
                                    </span>
                                </td>
                                <td><?php echo sanitize($event['created_by_name']); ?></td>
                                <td>
                                    <a href="edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <a href="../certificates/index.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-award"></i> Certificates
                                    </a>
                                    <?php if (currentUserHasRole('admin')): ?>
                                    <a href="delete.php?id=<?php echo $event['id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Are you sure? This will also delete all certificates for this event.')">
                                        <i class="bi bi-trash"></i> Delete
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php echo renderPagination($pagination, 'index.php'); ?>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../_footer.php'; ?>
