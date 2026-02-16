<?php
// api/submit.php
// Handles mini form, enquiry form and contact form (multipart with optional docs[]).
// Sends email to Admin + (optional) confirmation to Client.
// Uses mail() by default. If mail() fails on XAMPP/Windows, use SMTP/PHPMailer.

header('Content-Type: application/json; charset=utf-8');

$config = [
  'site_name' => 'S&Y Property Consultants',
  'admin_email' => 'info@sypropertyconsultants.in',

  // OPTIONAL: set a real from email on your domain to improve deliverability
  // Example: 'no-reply@sypropertyconsultants.in'
  'from_email' => '',

  // Client confirmation
  'send_client_confirmation' => true,
  'client_subject' => 'Thanks — We received your enquiry',

  'max_total_upload_bytes' => 10 * 1024 * 1024,
  'allowed_ext' => ['pdf','jpg','jpeg','png','doc','docx'],
  'uploads_dir' => __DIR__ . '/../uploads',
  'data_file' => __DIR__ . '/../data/uploads.json',
];

function respond($ok, $message, $extra = []) {
  echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  respond(false, 'Invalid request method. Use POST.');
}

function clean($v) {
  $v = trim((string)$v);
  $v = str_replace(["\r","\n"], " ", $v);
  return $v;
}

$name    = clean($_POST['name'] ?? '');
$phone   = clean($_POST['phone'] ?? '');
$email   = clean($_POST['email'] ?? '');
$service = clean($_POST['service'] ?? '');
$message = clean($_POST['message'] ?? '');

if ($name === '' || $phone === '') {
  respond(false, 'Name and phone are required.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respond(false, 'Please enter a valid email.');
}
if ($message === '' && $service === '') {
  $message = 'Callback requested via website.';
}

// Ensure upload + data folders exist
$uploadsDir = $config['uploads_dir'];
$dataFile   = $config['data_file'];
$dataDir    = dirname($dataFile);

if (!is_dir($uploadsDir)) {
  if (!@mkdir($uploadsDir, 0755, true)) {
    respond(false, 'Server error: uploads directory could not be created.');
  }
}
if (!is_dir($dataDir)) {
  if (!@mkdir($dataDir, 0755, true)) {
    respond(false, 'Server error: data directory could not be created.');
  }
}
if (!is_file($dataFile)) {
  if (@file_put_contents($dataFile, '{}') === false) {
    respond(false, 'Server error: data file could not be created.');
  }
}

// Upload handling
$uploaded = [];
$total = 0;

if (!empty($_FILES['docs']) && is_array($_FILES['docs']['name'])) {
  $count = count($_FILES['docs']['name']);
  for ($i=0; $i<$count; $i++) {
    $err = $_FILES['docs']['error'][$i];
    if ($err === UPLOAD_ERR_NO_FILE) continue;
    if ($err !== UPLOAD_ERR_OK) respond(false, 'File upload error.');

    $tmp  = $_FILES['docs']['tmp_name'][$i];
    $orig = basename($_FILES['docs']['name'][$i]);
    $size = (int)($_FILES['docs']['size'][$i] ?? 0);

    $total += $size;
    if ($total > $config['max_total_upload_bytes']) {
      respond(false, 'Total attachment size must be under 10MB.');
    }

    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $config['allowed_ext'], true)) {
      respond(false, 'Unsupported file type: ' . $ext);
    }

    $token = bin2hex(random_bytes(16));
    $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $orig);
    $dest = $uploadsDir . '/' . $token . '_' . $safeName;

    if (!move_uploaded_file($tmp, $dest)) {
      respond(false, 'Failed to save uploaded file.');
    }

    $uploaded[] = [
      'token' => $token,
      'file' => basename($dest),
      'name' => $orig,
      'size' => $size,
    ];
  }
}

// Store download token map
if (!empty($uploaded)) {
  $map = json_decode(@file_get_contents($dataFile), true);
  if (!is_array($map)) $map = [];
  foreach ($uploaded as $u) {
    $map[$u['token']] = $u['file'];
  }
  @file_put_contents($dataFile, json_encode($map));
}

// Download link base (download.php in same /api folder)
$proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host  = $_SERVER['HTTP_HOST'] ?? '';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // usually /api
$downloadBase = $proto . '://' . $host . $scriptDir . '/download.php?token=';

// Build ADMIN email body
$subjectAdmin = $config['site_name'] . ' — New Enquiry';

$bodyLines = [];
$bodyLines[] = "New enquiry received:";
$bodyLines[] = "Name: $name";
$bodyLines[] = "Phone: $phone";
if ($email !== '')   $bodyLines[] = "Email: $email";
if ($service !== '') $bodyLines[] = "Service: $service";
$bodyLines[] = "Message: $message";
$bodyLines[] = "";

if (!empty($uploaded)) {
  $bodyLines[] = "Uploaded documents (download links):";
  foreach ($uploaded as $u) {
    $url = $downloadBase . urlencode($u['token']);
    $bodyLines[] = "- {$u['name']} ({$u['size']} bytes): $url";
  }
}

$adminBody = implode("\n", $bodyLines);

// Headers (admin)
$hostForFrom = $_SERVER['HTTP_HOST'] ?? 'localhost';
$fromEmail = $config['from_email'] !== ''
  ? $config['from_email']
  : ('no-reply@' . preg_replace('/^www\./', '', $hostForFrom));

$adminHeaders = [];
$adminHeaders[] = 'MIME-Version: 1.0';
$adminHeaders[] = 'Content-type: text/plain; charset=utf-8';
$adminHeaders[] = 'From: ' . $config['site_name'] . ' <' . $fromEmail . '>';
if ($email !== '') $adminHeaders[] = 'Reply-To: ' . $email;

// Send ADMIN mail
$emailSentAdmin = @mail($config['admin_email'], $subjectAdmin, $adminBody, implode("\r\n", $adminHeaders));

// Send CLIENT confirmation (optional)
$emailSentClient = false;
if ($config['send_client_confirmation'] && $email !== '') {
  $subjectClient = $config['client_subject'];

  $clientLines = [];
  $clientLines[] = "Hi $name,";
  $clientLines[] = "";
  $clientLines[] = "Thank you for contacting " . $config['site_name'] . ".";
  $clientLines[] = "We have received your enquiry and will contact you shortly.";
  $clientLines[] = "";
  $clientLines[] = "Your details:";
  $clientLines[] = "Phone: $phone";
  if ($service !== '') $clientLines[] = "Service: $service";
  if ($message !== '') $clientLines[] = "Message: $message";
  $clientLines[] = "";
  $clientLines[] = "Regards,";
  $clientLines[] = $config['site_name'];

  $clientBody = implode("\n", $clientLines);

  $clientHeaders = [];
  $clientHeaders[] = 'MIME-Version: 1.0';
  $clientHeaders[] = 'Content-type: text/plain; charset=utf-8';
  $clientHeaders[] = 'From: ' . $config['site_name'] . ' <' . $fromEmail . '>';
  $clientHeaders[] = 'Reply-To: ' . $config['admin_email'];

  $emailSentClient = @mail($email, $subjectClient, $clientBody, implode("\r\n", $clientHeaders));
}

// Response
if (!$emailSentAdmin) {
  respond(true, 'Saved, but admin email could not be sent. Configure SMTP / PHPMailer.', [
    'email_sent_admin' => false,
    'email_sent_client' => (bool)$emailSentClient
  ]);
}

respond(true, 'Sent successfully.', [
  'email_sent_admin' => true,
  'email_sent_client' => (bool)$emailSentClient
]);
