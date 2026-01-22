<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

startSecureSession();
requireLogin();

$db = getDB();
$error = '';
$success = '';

// Get templates for dropdown
$stmt = $db->query("SELECT id, template_name FROM templates ORDER BY template_name");
$templates = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $eventName = cleanInput($_POST['event_name'] ?? '');
        $eventDate = cleanInput($_POST['event_date'] ?? '');
        $organizer = cleanInput($_POST['organizer'] ?? '');
        $templateId = !empty($_POST['template_id']) ? (int)$_POST['template_id'] : null;
        
        if (empty($eventName) || empty($eventDate) || empty($organizer)) {
            $error = 'Please fill in all required fields';
        } else {
            $stmt = $db->prepare("INSERT INTO events (event_name, event_date, organizer, template_id, created_by, created_at) 
                                 VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$eventName, $eventDate, $organizer, $templateId, getCurrentUserId()])) {
                $eventId = $db->lastInsertId();
                logAudit(getCurrentUserId(), 'create', 'events', $eventId, null, ['event_name' => $eventName]);
                setFlashMessage('success', 'Event created successfully!');
                redirect('index.php');
            } else {
                $error = 'Failed to create event';
            }
        }
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Create New Event</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo sanitize($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="mb-3">
                <label for="event_name" class="form-label">Event Name *</label>
                <input type="text" class="form-control" id="event_name" name="event_name" required>
            </div>
            
            <div class="mb-3">
                <label for="event_date" class="form-label">Event Date *</label>
                <input type="date" class="form-control" id="event_date" name="event_date" required>
            </div>
            
            <div class="mb-3">
                <label for="organizer" class="form-label">Organizer *</label>
                <input type="text" class="form-control" id="organizer" name="organizer" required>
            </div>
            
            <div class="mb-3">
                <label for="template_id" class="form-label">Certificate Template</label>
                <select class="form-control" id="template_id" name="template_id">
                    <option value="">-- No Template --</option>
                    <?php foreach ($templates as $template): ?>
                        <option value="<?php echo $template['id']; ?>">
                            <?php echo sanitize($template['template_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Create Event</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../_footer.php'; ?>
