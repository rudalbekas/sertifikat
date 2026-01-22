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

// Get total users count
$totalUsers = countUsers();

// Pagination
$pagination = paginate($totalUsers, $page);

// Get users
$users = getAllUsers($pagination['records_per_page'], $pagination['offset']);

include __DIR__ . '/../_header.php';
?>

<h2>User Management</h2>

<?php displayFlashMessage(); ?>

<div class="mb-3">
    <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Create New User</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo sanitize($user['username']); ?></td>
                            <td><?php echo sanitize($user['email']); ?></td>
                            <td>
                                <?php
                                $roleBadges = [
                                    'admin' => 'danger',
                                    'designer' => 'warning',
                                    'verifikator' => 'info'
                                ];
                                $badgeClass = $roleBadges[$user['role']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badgeClass; ?>">
                                    <?php echo sanitize($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDateTime($user['created_at'], 'd M Y'); ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <?php if ($user['id'] != getCurrentUserId()): ?>
                                <a href="delete.php?id=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">
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

<?php include __DIR__ . '/../_footer.php'; ?>
