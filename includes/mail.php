<?php
/**
 * Email Functions
 * Simple email wrapper - PHPMailer will be integrated later
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Send email using PHP mail() function
 * For production, integrate PHPMailer
 */
function sendEmail($to, $subject, $body, $attachmentPath = null) {
    // For now, use simple PHP mail
    // In production, replace with PHPMailer
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    
    // Log email attempt
    error_log("Sending email to: $to, Subject: $subject");
    
    // In development, just log instead of sending
    if (APP_ENV === 'development') {
        error_log("Email body: $body");
        return true; // Simulate success
    }
    
    return mail($to, $subject, $body, $headers);
}

/**
 * Queue email for later sending
 */
function queueEmail($certificateId, $recipientEmail, $subject, $body) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO email_queue (certificate_id, recipient_email, subject, body, status, created_at) 
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    return $stmt->execute([$certificateId, $recipientEmail, $subject, $body]);
}

/**
 * Process email queue
 */
function processEmailQueue($limit = 10) {
    $db = getDB();
    
    // Get pending emails
    $stmt = $db->prepare("SELECT * FROM email_queue WHERE status = 'pending' AND attempts < 3 ORDER BY created_at ASC LIMIT ?");
    $stmt->execute([$limit]);
    $emails = $stmt->fetchAll();
    
    $sent = 0;
    $failed = 0;
    
    foreach ($emails as $email) {
        $success = sendEmail($email['recipient_email'], $email['subject'], $email['body']);
        
        if ($success) {
            // Mark as sent
            $updateStmt = $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
            $updateStmt->execute([$email['id']]);
            $sent++;
            
            // Update certificate
            $certStmt = $db->prepare("UPDATE certificates SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
            $certStmt->execute([$email['certificate_id']]);
        } else {
            // Increment attempts
            $updateStmt = $db->prepare("UPDATE email_queue SET attempts = attempts + 1, error_message = 'Failed to send' WHERE id = ?");
            $updateStmt->execute([$email['id']]);
            $failed++;
            
            // Mark as failed if max attempts reached
            if ($email['attempts'] + 1 >= 3) {
                $updateStmt = $db->prepare("UPDATE email_queue SET status = 'failed' WHERE id = ?");
                $updateStmt->execute([$email['id']]);
            }
        }
    }
    
    return ['sent' => $sent, 'failed' => $failed];
}

/**
 * Generate certificate email body
 */
function generateCertificateEmailBody($recipientName, $eventName, $certificateNumber, $downloadUrl) {
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .button { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>' . APP_NAME . '</h1>
            </div>
            <div class="content">
                <h2>Congratulations, ' . htmlspecialchars($recipientName) . '!</h2>
                <p>Your certificate for <strong>' . htmlspecialchars($eventName) . '</strong> is ready.</p>
                <p><strong>Certificate Number:</strong> ' . htmlspecialchars($certificateNumber) . '</p>
                <p>You can download your certificate using the button below:</p>
                <p style="text-align: center;">
                    <a href="' . htmlspecialchars($downloadUrl) . '" class="button">Download Certificate</a>
                </p>
                <p>You can also verify your certificate at any time by visiting our verification page and entering your certificate number.</p>
            </div>
            <div class="footer">
                <p>This is an automated email. Please do not reply.</p>
                <p>&copy; ' . date('Y') . ' ' . APP_NAME . '</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    return $body;
}
