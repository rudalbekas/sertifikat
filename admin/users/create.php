<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

startSecureSession();
requireLogin();
requireRole('admin');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $username = cleanInput($_POST['username'] ?? '');
        $email = cleanInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $role = cleanInput($_POST['role'] ?? 'verifikator');
        
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'Please fill in all required fields';
        } elseif (!validateEmail($email)) {
            $error = 'Invalid email address';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
        } else {
            $result = registerUser($username, $email, $password, $role);
            
            if ($result['success']) {
                setFlashMessage('success', 'User created successfully!');
                redirect('index.php');
            } else {
                $error = $result['message'];
            }
        }
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Create New User</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="mb-3">
                <label for="username" class="form-label">Username *</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password *</label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                <small class="form-text text-muted">Minimum 6 characters</small>
            </div>
            
            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirm Password *</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
            </div>
            
            <div class="mb-3">
                <label for="role" class="form-label">Role *</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="verifikator">Verifikator</option>
                    <option value="designer">Designer</option>
                    <option value="admin">Admin</option>
                </select>
                <small class="form-text text-muted">
                    <strong>Admin:</strong> Full access | 
                    <strong>Designer:</strong> Create/manage certificates | 
                    <strong>Verifikator:</strong> View only
                </small>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create User</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../_footer.php'; ?>
