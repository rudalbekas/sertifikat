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
    setFlashMessage('error', 'Invalid user ID');
    redirect('index.php');
}

$userId = (int)$_GET['id'];

// Prevent deleting yourself
if ($userId === getCurrentUserId()) {
    setFlashMessage('error', 'You cannot delete your own account');
    redirect('index.php');
}

$user = getUserById($userId);

if (!$user) {
    setFlashMessage('error', 'User not found');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
    } else {
        if (deleteUser($userId)) {
            setFlashMessage('success', 'User deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete user');
        }
        redirect('index.php');
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Delete User</h2>

<div class="alert alert-danger">
    <h5>⚠️ Warning: This action cannot be undone!</h5>
    <p>You are about to delete the following user:</p>
    <ul>
        <li><strong>Username:</strong> <?php echo sanitize($user['username']); ?></li>
        <li><strong>Email:</strong> <?php echo sanitize($user['email']); ?></li>
        <li><strong>Role:</strong> <?php echo sanitize($user['role']); ?></li>
    </ul>
</div>

<form method="POST" action="">
    <?php echo csrfField(); ?>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash"></i> Yes, Delete User
        </button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../_footer.php'; ?>
