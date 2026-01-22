<?php
/**
 * Installation Verification Script
 * This script checks if all required files and directories are in place
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Check - Certificate System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .container { max-width: 800px; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .check-item { padding: 10px; margin: 5px 0; border-radius: 5px; }
        .check-pass { background: #d4edda; color: #155724; }
        .check-fail { background: #f8d7da; color: #721c24; }
        .check-warning { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🔧 Installation Check</h1>
        <p class="text-muted">Verifying Certificate Management System installation...</p>
        
        <hr>
        
        <?php
        $checks = [];
        $allPassed = true;
        
        // Check 1: PHP Version
        $phpVersion = phpversion();
        $phpOk = version_compare($phpVersion, '7.4.0', '>=');
        $checks[] = [
            'name' => 'PHP Version',
            'status' => $phpOk,
            'message' => $phpOk ? "✓ PHP $phpVersion (OK)" : "✗ PHP $phpVersion (Requires 7.4+)",
            'severity' => $phpOk ? 'pass' : 'fail'
        ];
        if (!$phpOk) $allPassed = false;
        
        // Check 2: Config file
        $configExists = file_exists(__DIR__ . '/config/config.php');
        $checks[] = [
            'name' => 'Configuration File',
            'status' => $configExists,
            'message' => $configExists ? '✓ config/config.php exists' : '✗ config/config.php is missing',
            'severity' => $configExists ? 'pass' : 'fail'
        ];
        if (!$configExists) $allPassed = false;
        
        // Check 3: Try to load config
        $configLoaded = false;
        if ($configExists) {
            try {
                require_once __DIR__ . '/config/config.php';
                $configLoaded = defined('DB_HOST');
                $checks[] = [
                    'name' => 'Configuration Loaded',
                    'status' => $configLoaded,
                    'message' => $configLoaded ? '✓ Configuration loaded successfully' : '✗ Configuration has errors',
                    'severity' => $configLoaded ? 'pass' : 'fail'
                ];
            } catch (Exception $e) {
                $checks[] = [
                    'name' => 'Configuration Loaded',
                    'status' => false,
                    'message' => '✗ Error loading config: ' . $e->getMessage(),
                    'severity' => 'fail'
                ];
                $allPassed = false;
            }
        }
        
        // Check 4: Database connection
        if ($configLoaded) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                
                // Check if database exists (using prepared statement to prevent SQL injection)
                $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
                $stmt->execute([DB_NAME]);
                $dbExists = $stmt->rowCount() > 0;
                
                $checks[] = [
                    'name' => 'Database Connection',
                    'status' => true,
                    'message' => '✓ Can connect to MySQL server',
                    'severity' => 'pass'
                ];
                
                $checks[] = [
                    'name' => 'Database Exists',
                    'status' => $dbExists,
                    'message' => $dbExists ? '✓ Database "' . DB_NAME . '" exists' : '✗ Database "' . DB_NAME . '" not found',
                    'severity' => $dbExists ? 'pass' : 'fail'
                ];
                if (!$dbExists) $allPassed = false;
                
            } catch (PDOException $e) {
                $checks[] = [
                    'name' => 'Database Connection',
                    'status' => false,
                    'message' => '✗ Cannot connect to database: ' . $e->getMessage(),
                    'severity' => 'fail'
                ];
                $allPassed = false;
            }
        }
        
        // Check 5: Required directories
        $requiredDirs = [
            'uploads/csv',
            'uploads/templates',
            'generated/certificates',
            'generated/qrcodes',
            'logs'
        ];
        
        foreach ($requiredDirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            
            if (!$exists) {
                $checks[] = [
                    'name' => "Directory: $dir",
                    'status' => false,
                    'message' => "✗ Directory missing: $dir",
                    'severity' => 'fail'
                ];
                $allPassed = false;
            } elseif (!$writable) {
                $checks[] = [
                    'name' => "Directory: $dir",
                    'status' => false,
                    'message' => "⚠ Directory not writable: $dir",
                    'severity' => 'warning'
                ];
            } else {
                $checks[] = [
                    'name' => "Directory: $dir",
                    'status' => true,
                    'message' => "✓ Directory OK: $dir",
                    'severity' => 'pass'
                ];
            }
        }
        
        // Check 6: PHP Extensions
        $requiredExtensions = ['pdo', 'pdo_mysql', 'gd', 'mbstring', 'session'];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'name' => "PHP Extension: $ext",
                'status' => $loaded,
                'message' => $loaded ? "✓ Extension $ext loaded" : "✗ Extension $ext not found",
                'severity' => $loaded ? 'pass' : 'fail'
            ];
            if (!$loaded) $allPassed = false;
        }
        
        // Display results
        foreach ($checks as $check) {
            $class = 'check-' . $check['severity'];
            echo '<div class="check-item ' . $class . '">';
            echo '<strong>' . htmlspecialchars($check['name']) . ':</strong> ';
            echo htmlspecialchars($check['message']);
            echo '</div>';
        }
        ?>
        
        <hr>
        
        <?php if ($allPassed): ?>
            <div class="alert alert-success">
                <h4>✅ Installation Check Passed!</h4>
                <p class="mb-0">All required components are in place. You can proceed to use the application.</p>
                <div class="mt-3">
                    <a href="index.php" class="btn btn-primary">Go to Home Page</a>
                    <a href="admin/login.php" class="btn btn-secondary">Admin Login</a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <h4>❌ Installation Issues Detected</h4>
                <p>Please fix the issues above before using the application.</p>
                <p class="mb-0"><strong>Next Steps:</strong></p>
                <ul>
                    <li>If config.php is missing, it should already be in the repository at config/config.php</li>
                    <li>Update database credentials in config/config.php (default: root with empty password for XAMPP)</li>
                    <li>Create the database and import database/schema.sql and database/seed.sql</li>
                    <li>Ensure all directories have proper permissions (755 for directories)</li>
                    <li>Check XAMPP_INSTALL.md for detailed instructions</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <small class="text-muted">
                <a href="XAMPP_INSTALL.md" target="_blank">View Installation Guide</a> | 
                <a href="README.md" target="_blank">View README</a>
            </small>
        </div>
    </div>
</body>
</html>
