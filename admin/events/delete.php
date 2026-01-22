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

if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Invalid event ID');
    redirect('index.php');
}

$eventId = (int)$_GET['id'];

// Get event
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    setFlashMessage('error', 'Event not found');
    redirect('index.php');
}

// Check if there are certificates for this event
$stmt = $db->prepare("SELECT COUNT(*) as count FROM certificates WHERE event_id = ?");
$stmt->execute([$eventId]);
$certCount = $stmt->fetch()['count'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        setFlashMessage('error', 'Invalid security token');
    } else {
        // Log before deletion
        logAudit(getCurrentUserId(), 'delete', 'events', $eventId, $event);
        
        // Delete event (cascade will delete certificates)
        $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
        if ($stmt->execute([$eventId])) {
            setFlashMessage('success', 'Event deleted successfully');
        } else {
            setFlashMessage('error', 'Failed to delete event');
        }
        redirect('index.php');
    }
}

include __DIR__ . '/../_header.php';
?>

<h2>Delete Event</h2>

<div class="alert alert-danger">
    <h5>⚠️ Warning: This action cannot be undone!</h5>
    <p>You are about to delete the following event:</p>
    <ul>
        <li><strong>Event Name:</strong> <?php echo sanitize($event['event_name']); ?></li>
        <li><strong>Date:</strong> <?php echo formatDate($event['event_date']); ?></li>
        <li><strong>Organizer:</strong> <?php echo sanitize($event['organizer']); ?></li>
        <?php if ($certCount > 0): ?>
            <li><strong>Certificates:</strong> <?php echo $certCount; ?> certificates will also be deleted</li>
        <?php endif; ?>
    </ul>
</div>

<form method="POST" action="">
    <?php echo csrfField(); ?>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash"></i> Yes, Delete Event
        </button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../_footer.php'; ?>
