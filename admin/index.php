<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

startSecureSession();
requireLogin();

$db = getDB();

// Get statistics
$stats = [];

// Total certificates
$stmt = $db->query("SELECT COUNT(*) as count FROM certificates");
$stats['total_certificates'] = $stmt->fetch()['count'];

// Valid certificates
$stmt = $db->query("SELECT COUNT(*) as count FROM certificates WHERE status = 'valid'");
$stats['valid_certificates'] = $stmt->fetch()['count'];

// Revoked certificates
$stmt = $db->query("SELECT COUNT(*) as count FROM certificates WHERE status = 'revoked'");
$stats['revoked_certificates'] = $stmt->fetch()['count'];

// Total events
$stmt = $db->query("SELECT COUNT(*) as count FROM events");
$stats['total_events'] = $stmt->fetch()['count'];

// Recent certificates
$stmt = $db->query("SELECT c.*, e.event_name FROM certificates c 
                    LEFT JOIN events e ON c.event_id = e.id 
                    ORDER BY c.issued_at DESC LIMIT 5");
$recentCertificates = $stmt->fetchAll();

// Recent events
$stmt = $db->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 5");
$recentEvents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
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
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        .stat-card.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.green { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.red { background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); }
        .stat-card.purple { background: linear-gradient(135deg, #7F00FF 0%, #E100FF 100%); }
        .stat-card h3 { font-size: 36px; font-weight: bold; margin: 0; }
        .stat-card p { margin: 0; opacity: 0.9; }
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
                    <a href="index.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="events/index.php"><i class="bi bi-calendar-event"></i> Events</a>
                    <a href="certificates/index.php"><i class="bi bi-award"></i> Certificates</a>
                    <a href="templates/index.php"><i class="bi bi-file-earmark-image"></i> Templates</a>
                    <?php if (currentUserHasRole('admin')): ?>
                    <a href="users/index.php"><i class="bi bi-people"></i> Users</a>
                    <a href="logs/index.php"><i class="bi bi-clock-history"></i> Audit Logs</a>
                    <?php endif; ?>
                    <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard</h2>
                
                <?php displayFlashMessage(); ?>
                
                <!-- Statistics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card blue">
                            <h3><?php echo $stats['total_certificates']; ?></h3>
                            <p>Total Certificates</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card green">
                            <h3><?php echo $stats['valid_certificates']; ?></h3>
                            <p>Valid Certificates</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card red">
                            <h3><?php echo $stats['revoked_certificates']; ?></h3>
                            <p>Revoked Certificates</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card purple">
                            <h3><?php echo $stats['total_events']; ?></h3>
                            <p>Total Events</p>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <!-- Recent Certificates -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Certificates</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentCertificates)): ?>
                                    <p class="text-muted">No certificates generated yet.</p>
                                    <a href="certificates/generate.php" class="btn btn-primary btn-sm">Generate First Certificate</a>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Certificate #</th>
                                                    <th>Recipient</th>
                                                    <th>Event</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentCertificates as $cert): ?>
                                                    <tr>
                                                        <td><?php echo sanitize($cert['certificate_number']); ?></td>
                                                        <td><?php echo sanitize($cert['recipient_name']); ?></td>
                                                        <td><?php echo sanitize($cert['event_name']); ?></td>
                                                        <td><?php echo getStatusBadge($cert['status']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="certificates/index.php" class="btn btn-sm btn-outline-primary">View All →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Events -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Recent Events</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentEvents)): ?>
                                    <p class="text-muted">No events created yet.</p>
                                    <a href="events/create.php" class="btn btn-primary btn-sm">Create First Event</a>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Event Name</th>
                                                    <th>Date</th>
                                                    <th>Organizer</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentEvents as $event): ?>
                                                    <tr>
                                                        <td><?php echo sanitize($event['event_name']); ?></td>
                                                        <td><?php echo formatDate($event['event_date']); ?></td>
                                                        <td><?php echo sanitize($event['organizer']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <a href="events/index.php" class="btn btn-sm btn-outline-primary">View All →</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <a href="events/create.php" class="btn btn-primary me-2"><i class="bi bi-plus-circle"></i> Create Event</a>
                                <a href="certificates/generate.php" class="btn btn-success me-2"><i class="bi bi-award"></i> Generate Certificate</a>
                                <a href="certificates/import.php" class="btn btn-info me-2"><i class="bi bi-upload"></i> Batch Import</a>
                                <a href="../verify/index.php" class="btn btn-secondary" target="_blank"><i class="bi bi-check-circle"></i> Verify Certificate</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
