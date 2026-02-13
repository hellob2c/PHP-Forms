<?php
// send_contact.php — processes contact form, attaches uploaded file, and emails both admin and the sender.
// Admin email:
$adminEmail = 'mkcmukesh@hellob2c.com';

// ---------- SMTP / PHPMailer option (recommended for production) ----------
// If you want to use SMTP with authentication (Gmail/SendGrid/other) install PHPMailer
// via Composer: `composer require phpmailer/phpmailer` then paste your SMTP password below
// in $smtpPassword. See the PHPMailer block further down.

// ---------- Simple PHP mail() fallback (works where mail() is configured) ----------
// This script will attempt to send using mail() by default and include attachment.

function fail($msg) {
    echo '<h3>Error</h3><p>' . htmlspecialchars($msg) . '</p>';
    echo '<p><a href="contact.html">Return to form</a></p>';
    exit;
}

// Basic validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail('Invalid request method.');
}

$name = trim($_POST['name'] ?? '');
$senderEmail = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'New contact form submission');
$message = trim($_POST['message'] ?? '');

if (!$name || !$senderEmail || !$subject || !$message) {
    fail('Please fill all required fields.');
}

// Handle upload (any file type allowed)
$hasAttachment = false;
$attachmentName = '';
$attachmentContent = '';
$attachmentType = '';

if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
    $f = $_FILES['attachment'];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        fail('File upload error (code: ' . $f['error'] . ').');
    }

    // Optional: enforce a maximum size (example 12MB). Remove or adjust as needed.
    $maxBytes = 12 * 1024 * 1024;
    if ($f['size'] > $maxBytes) {
        fail('Attachment is too large. Max 12 MB allowed.');
    }

    $attachmentName = basename($f['name']);
    // determine mime type safely (use finfo if available, fall back to mime_content_type, otherwise default)
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $attachmentType = finfo_file($finfo, $f['tmp_name']) ?: 'application/octet-stream';
        finfo_close($finfo);
    } elseif (function_exists('mime_content_type')) {
        $attachmentType = mime_content_type($f['tmp_name']) ?: 'application/octet-stream';
    } else {
        $attachmentType = 'application/octet-stream';
    }
    $attachmentContent = file_get_contents($f['tmp_name']);
    $hasAttachment = $attachmentContent !== false;
}

// Build email bodies
$siteName = 'Form Hub';
$adminSubject = "[Contact] $subject";
$userSubject = "Thanks for contacting $siteName — we've received your message";

$adminBody = "Name: $name\nEmail: $senderEmail\nSubject: $subject\n\nMessage:\n$message\n";

$userBody = "Hello $name,\n\nThank you for contacting $siteName. We received your message:\n\n---\n$subject\n\n$message\n---\n\nWe will get back to you shortly.\n\nBest regards,\n$siteName team";

// Attempt to use PHPMailer if available (recommended). If not, fall back to mail().
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // PHPMailer is installed via Composer
    require __DIR__ . '/vendor/autoload.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;

    // ----- Paste your SMTP password here if using SMTP -----
    $smtpHost = 'smtp.example.com';         // set your SMTP host
    $smtpPort = 587;                        // usually 587 (tls) or 465 (ssl)
    $smtpUser = 'smtp-user@example.com';    // SMTP username
    $smtpPassword = 'Mkcmukesh@1234';                     // <-- Paste your SMTP password here
    $smtpSecure = 'tls';                    // 'tls' or 'ssl' or empty for none
    // ------------------------------------------------------------------

    $mail = new PHPMailer(true);
    try {
        // SMTP settings (uncomment to use SMTP)
        if ($smtpPassword) {
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPassword; // <- paste password above
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = $smtpPort;
        }

        // Send to admin
        $mail->setFrom($smtpUser ?: $adminEmail, $siteName);
        $mail->addAddress($adminEmail);
        $mail->addReplyTo($senderEmail, $name);
        if ($hasAttachment) {
            // attach the uploaded file
            $mail->addStringAttachment($attachmentContent, $attachmentName, 'base64', $attachmentType);
        }
        $mail->Subject = $adminSubject;
        $mail->Body = $adminBody;
        $mail->send();

        // Send copy to sender
        $mail->clearAllRecipients();
        $mail->clearAttachments();
        $mail->setFrom($smtpUser ?: $adminEmail, $siteName);
        $mail->addAddress($senderEmail, $name);
        if ($hasAttachment) {
            $mail->addStringAttachment($attachmentContent, $attachmentName, 'base64', $attachmentType);
        }
        $mail->Subject = $userSubject;
        $mail->Body = $userBody;
        $mail->send();

        // redirect to thank you page
        header('Location: thankyou.html');
        exit;

    } catch (Exception $e) {
        fail('Mailer error: ' . $mail->ErrorInfo);
    }
}

// FALLBACK: Build multipart MIME email with attachment using PHP mail().
$boundary = md5(time());
$eol = "\r\n";

// Headers common to both messages
$headersCommon = [];
$headersCommon[] = 'From: '. $adminEmail;
$headersCommon[] = 'Reply-To: ' . $senderEmail;
$headersCommon[] = 'MIME-Version: 1.0';

// Function to send with optional attachment
function send_multipart_mail($to, $subject, $plainText, $fromEmail, $senderEmail, $attachmentName, $attachmentContent, $attachmentType) {
    global $boundary, $eol;

    $headers = [];
    $headers[] = 'From: ' . $fromEmail;
    $headers[] = 'Reply-To: ' . $senderEmail;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $body = "--" . $boundary . $eol;
    $body .= 'Content-Type: text/plain; charset="utf-8"' . $eol;
    $body .= 'Content-Transfer-Encoding: 7bit' . $eol . $eol;
    $body .= $plainText . $eol . $eol;

    if ($attachmentContent !== '') {
        $body .= '--' . $boundary . $eol;
        $body .= 'Content-Type: ' . ($attachmentType ?: 'application/octet-stream') . '; name="' . $attachmentName . '"' . $eol;
        $body .= 'Content-Transfer-Encoding: base64' . $eol;
        $body .= 'Content-Disposition: attachment; filename="' . $attachmentName . '"' . $eol . $eol;
        $body .= chunk_split(base64_encode($attachmentContent)) . $eol . $eol;
    }

    $body .= '--' . $boundary . '--';

    return mail($to, $subject, $body, implode($eol, $headers));
}

// send to admin
$sentAdmin = send_multipart_mail($adminEmail, $adminSubject, $adminBody, $adminEmail, $senderEmail, $attachmentName, $attachmentContent, $attachmentType);
// send copy to user
$sentUser = send_multipart_mail($senderEmail, $userSubject, $userBody, $adminEmail, $senderEmail, $attachmentName, $attachmentContent, $attachmentType);

if ($sentAdmin && $sentUser) {
    header('Location: thankyou.html');
    exit;
}

fail('Unable to send email — please check server mail configuration.');
