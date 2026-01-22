<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../libraries/simplexlsx/loader.php';
require_once __DIR__ . '/../../libraries/phpqrcode/loader.php';

startSecureSession();
requireLogin();

$db = getDB();
$error = '';
$success = '';
$importResults = [];

// Get events for dropdown
$stmt = $db->query("SELECT id, event_name FROM events ORDER BY event_name");
$events = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $eventId = (int)$_POST['event_id'];
        
        if (empty($eventId)) {
            $error = 'Please select an event';
        } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = 'Please select a CSV file to import';
        } else {
            $fileErrors = validateFileUpload($_FILES['csv_file']);
            
            if (!empty($fileErrors)) {
                $error = implode('<br>', $fileErrors);
            } else {
                $uploadedFile = $_FILES['csv_file'];
                $fileExtension = getFileExtension($uploadedFile['name']);
                
                // Save uploaded file
                $fileName = generateSecureFilename($uploadedFile['name']);
                $targetPath = UPLOAD_CSV_PATH . $fileName;
                
                if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                    // Parse CSV file
                    try {
                        if ($fileExtension === 'csv') {
                            $rows = SimpleXLSXReader::parseCSV($targetPath);
                        } else {
                            $rows = SimpleXLSXReader::parseXLSX($targetPath);
                        }
                        
                        $successCount = 0;
                        $errorCount = 0;
                        
                        foreach ($rows as $row) {
                            try {
                                $recipientName = isset($row['name']) ? cleanInput($row['name']) : '';
                                $recipientEmail = isset($row['email']) ? cleanInput($row['email']) : '';
                                $institution = isset($row['institution']) ? cleanInput($row['institution']) : '';
                                $grade = isset($row['grade']) ? cleanInput($row['grade']) : '';
                                
                                if (empty($recipientName)) {
                                    $errorCount++;
                                    continue;
                                }
                                
                                // Generate certificate number
                                $certificateNumber = generateCertificateNumber();
                                
                                // Generate verification token
                                $verificationToken = generateVerificationToken($certificateNumber);
                                
                                // Generate QR code
                                $qrFileName = $certificateNumber . '.png';
                                $qrFilePath = 'generated/qrcodes/' . $qrFileName;
                                $qrFullPath = __DIR__ . '/../../' . $qrFilePath;
                                
                                $verifyUrl = APP_URL . '/verify/index.php?token=' . urlencode($verificationToken);
                                QRcode::png($verifyUrl, $qrFullPath, 'L', 5, 2);
                                
                                // Insert certificate
                                $stmt = $db->prepare("INSERT INTO certificates 
                                    (certificate_number, event_id, recipient_name, recipient_email, institution, grade, 
                                     verification_token, qr_code_path, status, issued_at) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'valid', NOW())");
                                
                                if ($stmt->execute([
                                    $certificateNumber, $eventId, $recipientName, $recipientEmail,
                                    $institution, $grade, $verificationToken, $qrFilePath
                                ])) {
                                    $successCount++;
                                } else {
                                    $errorCount++;
                                }
                            } catch (Exception $e) {
                                $errorCount++;
                            }
                        }
                        
                        $importResults = [
                            'success' => $successCount,
                            'error' => $errorCount,
                            'total' => count($rows)
                        ];
                        
                        if ($successCount > 0) {
                            logAudit(getCurrentUserId(), 'batch_import', 'certificates', $eventId, null, [
                                'success_count' => $successCount,
                                'error_count' => $errorCount
                            ]);
                        }
                        
                        $success = "Import completed! Successfully imported $successCount certificates" . 
                                   ($errorCount > 0 ? " ($errorCount failed)" : "");
                        
                    } catch (Exception $e) {
                        $error = 'Failed to parse CSV file: ' . $e->getMessage();
                    }
                } else {
                    $error = 'Failed to upload file';
                }
            }
        }
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Batch Import Certificates</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo $success; ?>
        <br><a href="index.php">View Certificates →</a>
    </div>
<?php endif; ?>

<?php if (!empty($importResults)): ?>
    <div class="card mb-3">
        <div class="card-header">
            <h5>Import Results</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center">
                        <h3 class="text-primary"><?php echo $importResults['total']; ?></h3>
                        <p>Total Rows</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h3 class="text-success"><?php echo $importResults['success']; ?></h3>
                        <p>Successful</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <h3 class="text-danger"><?php echo $importResults['error']; ?></h3>
                        <p>Failed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (empty($events)): ?>
    <div class="alert alert-warning">
        No events available. <a href="../events/create.php">Create an event first</a>.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header">
            <h5>Upload CSV File</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                
                <div class="mb-3">
                    <label for="event_id" class="form-label">Event *</label>
                    <select class="form-control" id="event_id" name="event_id" required>
                        <option value="">-- Select Event --</option>
                        <?php foreach ($events as $event): ?>
                            <option value="<?php echo $event['id']; ?>">
                                <?php echo sanitize($event['event_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="csv_file" class="form-label">CSV File *</label>
                    <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv" required>
                    <small class="form-text text-muted">
                        Maximum file size: 10MB. Accepted format: CSV only.
                    </small>
                </div>
                
                <div class="alert alert-info">
                    <h6>CSV Format Requirements:</h6>
                    <ul class="mb-0">
                        <li>First row must be headers: <code>name,email,institution,grade</code></li>
                        <li><code>name</code> is required, other fields are optional</li>
                        <li>Example: <code>John Doe,john@example.com,ABC University,A</code></li>
                    </ul>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload"></i> Import Certificates
                    </button>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <a href="#" class="btn btn-info" onclick="downloadSampleCSV(); return false;">
                        <i class="bi bi-download"></i> Download Sample CSV
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<script>
function downloadSampleCSV() {
    const csv = "name,email,institution,grade\nJohn Doe,john@example.com,ABC University,A\nJane Smith,jane@example.com,XYZ College,B";
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'sample_import.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}
</script>

<?php include __DIR__ . '/../_footer.php'; ?>
