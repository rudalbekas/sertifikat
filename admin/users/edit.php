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
$user = getUserById($userId);

if (!$user) {
    setFlashMessage('error', 'User not found');
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $username = cleanInput($_POST['username'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $role = cleanInput($_POST['role'] ?? 'verifikator');
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($username) || empty($email)) {
            $error = 'Please fill in all required fields';
        } elseif (!validateEmail($email)) {
            $error = 'Invalid email address';
        } else {
            if (updateUser($userId, $username, $email, $role)) {
                // Update password if provided
                if (!empty($newPassword)) {
                    if (strlen($newPassword) >= 6) {
                        updatePassword($userId, $newPassword);
                    } else {
                        $error = 'Password must be at least 6 characters';
                    }
                }
                
                if (empty($error)) {
                    setFlashMessage('success', 'User updated successfully!');
                    redirect('index.php');
                }
            } else {
                $error = 'Failed to update user';
            }
        }
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Edit User</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="mb-3">
                <label for="username" class="form-label">Username *</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo sanitize($user['username']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo sanitize($user['email']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="role" class="form-label">Role *</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="verifikator" <?php echo $user['role'] === 'verifikator' ? 'selected' : ''; ?>>Verifikator</option>
                    <option value="designer" <?php echo $user['role'] === 'designer' ? 'selected' : ''; ?>>Designer</option>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>
            
            <hr>
            
            <h5>Change Password (Optional)</h5>
            <p class="text-muted">Leave blank to keep current password</p>
            
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" minlength="6">
                <small class="form-text text-muted">Minimum 6 characters</small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../_footer.php'; ?>
