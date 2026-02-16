<?php
// api/download.php
// Secure-ish download using token map. Prevents direct listing of upload folder.
// NOTE: For stronger security, add auth or one-time tokens.

$token = $_GET['token'] ?? '';
$token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
if (strlen($token) < 10) { http_response_code(400); echo "Invalid token"; exit; }

$dataFile = __DIR__ . '/../data/uploads.json';
$uploadsDir = __DIR__ . '/../uploads';
$map = json_decode(@file_get_contents($dataFile), true);
if (!is_array($map) || empty($map[$token])) { http_response_code(404); echo "Not found"; exit; }

$file = basename($map[$token]);
$path = $uploadsDir . '/' . $file;
if (!is_file($path)) { http_response_code(404); echo "Not found"; exit; }

$mime = 'application/octet-stream';
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimeMap = [
  'pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png',
  'doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
if (!empty($mimeMap[$ext])) $mime = $mimeMap[$ext];

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . preg_replace('/^([a-f0-9]{32})_/', '', $file) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
