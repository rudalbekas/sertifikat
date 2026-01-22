<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST[CSRF_TOKEN_NAME]) || !verifyCSRFToken($_POST[CSRF_TOKEN_NAME])) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username = cleanInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password';
        } else {
            if (loginUser($username, $password)) {
                // Check for redirect parameter
                $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                redirect($redirect);
            } else {
                $error = 'Invalid username or password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            padding: 40px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #667eea;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: bold;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5568d3 0%, #63408a 100%);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>🎓 <?php echo sanitize(APP_NAME); ?></h1>
                <p class="text-muted">Please login to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo sanitize($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label for="username" class="form-label">Username or Email</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login">Login</button>
            </form>
            
            <div class="mt-3 text-center">
                <small class="text-muted">
                    Default: admin / admin123
                </small>
            </div>
            
            <div class="mt-3 text-center">
                <a href="../index.php" class="text-decoration-none">← Back to Home</a>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
