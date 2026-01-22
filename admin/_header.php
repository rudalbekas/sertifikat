<!-- Admin Header Template -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            transition: background 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.2);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="p-3">
                    <h4 class="text-white">🎓 Certificate System</h4>
                    <p class="text-white-50 small">Welcome, <?php echo sanitize(getCurrentUsername()); ?></p>
                </div>
                <nav>
                    <a href="<?php echo APP_URL; ?>/admin/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="<?php echo APP_URL; ?>/admin/events/index.php"><i class="bi bi-calendar-event"></i> Events</a>
                    <a href="<?php echo APP_URL; ?>/admin/certificates/index.php"><i class="bi bi-award"></i> Certificates</a>
                    <a href="<?php echo APP_URL; ?>/admin/templates/index.php"><i class="bi bi-file-earmark-image"></i> Templates</a>
                    <?php if (currentUserHasRole('admin')): ?>
                    <a href="<?php echo APP_URL; ?>/admin/users/index.php"><i class="bi bi-people"></i> Users</a>
                    <a href="<?php echo APP_URL; ?>/admin/logs/index.php"><i class="bi bi-clock-history"></i> Audit Logs</a>
                    <?php endif; ?>
                    <a href="<?php echo APP_URL; ?>/admin/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
