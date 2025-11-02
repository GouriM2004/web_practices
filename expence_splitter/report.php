<?php
require_once __DIR__ . '/includes/autoload.php';
require_once __DIR__ . '/includes/Config.php';

$token = $_GET['token'] ?? '';
if (empty($token)) {
    http_response_code(400);
    echo 'Missing token';
    exit;
}

$reportModel = new Report();
$r = $reportModel->getByToken($token);
if (!$r) {
    http_response_code(404);
    echo 'Report not found';
    exit;
}

// check expiry
if (!empty($r['expires_at']) && strtotime($r['expires_at']) < time()) {
    http_response_code(410);
    echo 'Report expired';
    exit;
}

$fileRel = $r['file_path'];
$filePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $fileRel);
$exportsDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'exports');
if (!$filePath || strpos($filePath, $exportsDir) !== 0 || !file_exists($filePath)) {
    http_response_code(404);
    echo 'File missing';
    exit;
}

$fname = basename($filePath);
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
// Map extensions to MIME types
$mime = 'application/octet-stream';
if ($ext === 'pdf') $mime = 'application/pdf';
elseif ($ext === 'xlsx') $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
elseif ($ext === 'csv') $mime = 'text/csv';
elseif ($ext === 'html' || $ext === 'htm') $mime = 'text/html';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
