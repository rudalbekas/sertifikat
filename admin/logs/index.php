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
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Get total logs count
$stmt = $db->query("SELECT COUNT(*) as count FROM audit_logs");
$totalLogs = $stmt->fetch()['count'];

// Pagination
$pagination = paginate($totalLogs, $page);

// Get logs
$stmt = $db->prepare("SELECT al.*, u.username 
                     FROM audit_logs al 
                     LEFT JOIN users u ON al.user_id = u.id 
                     ORDER BY al.created_at DESC 
                     LIMIT ? OFFSET ?");
$stmt->execute([$pagination['records_per_page'], $pagination['offset']]);
$logs = $stmt->fetchAll();

include __DIR__ . '/../_header.php';
?>

<h2>Audit Logs</h2>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                        <th>Timestamp</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No audit logs yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['id']; ?></td>
                                <td><?php echo sanitize($log['username'] ?? 'System'); ?></td>
                                <td>
                                    <?php
                                    $badges = [
                                        'create' => 'success',
                                        'update' => 'info',
                                        'delete' => 'danger',
                                        'revoke' => 'warning',
                                        'login' => 'primary',
                                        'logout' => 'secondary'
                                    ];
                                    $badgeClass = $badges[$log['action']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeClass; ?>">
                                        <?php echo sanitize($log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo sanitize($log['table_name']); ?></td>
                                <td><?php echo $log['record_id']; ?></td>
                                <td><code><?php echo sanitize($log['ip_address']); ?></code></td>
                                <td><?php echo formatDateTime($log['created_at'], 'd M Y H:i:s'); ?></td>
                                <td>
                                    <?php if (!empty($log['new_values'])): ?>
                                        <button class="btn btn-sm btn-outline-secondary" 
                                                onclick="alert('<?php echo htmlspecialchars(addslashes($log['new_values'])); ?>')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php echo renderPagination($pagination, 'index.php'); ?>
    </div>
</div>

<?php include __DIR__ . '/../_footer.php'; ?>
