<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/Services/ParserService.php';
require_once __DIR__ . '/../../src/Services/ATSService.php';

header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$resume_id = $body['resume_id'] ?? null;
$job_text = $body['job_text'] ?? ($body['jd_text'] ?? '');

if (!$resume_id) {
    http_response_code(400);
    echo json_encode(['error' => 'resume_id is required']);
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT * FROM resumes WHERE id = ?');
$stmt->execute([$resume_id]);
$resume = $stmt->fetch();
if (!$resume) {
    http_response_code(404);
    echo json_encode(['error' => 'resume not found']);
    exit;
}

// ensure parsed_json exists
$parsed_resume = [];
if (!empty($resume['parsed_json'])) {
    $parsed_resume = json_decode($resume['parsed_json'], true);
} else {
    if (!empty($resume['text'])) {
        $parsed = ParserService::parseText($resume['text']);
        if ($parsed) {
            $parsed_resume = $parsed;
            $upd = $db->prepare('UPDATE resumes SET parsed_json = ? WHERE id = ?');
            $upd->execute([json_encode($parsed_resume), $resume_id]);
        }
    }
}

$parsed_job = [];
if (!empty($job_text)) {
    $parsed_job = ParserService::parseText($job_text) ?: ['jd_text' => $job_text];
} elseif (!empty($body['job_id'])) {
    // optional: load job text from jobs table
    $jstmt = $db->prepare('SELECT jd_text FROM jobs WHERE id = ?');
    $jstmt->execute([$body['job_id']]);
    $job = $jstmt->fetch();
    if ($job) $parsed_job = ParserService::parseText($job['jd_text']) ?: ['jd_text' => $job['jd_text']];
}

$result = ATSService::analyze($parsed_resume, $parsed_job, $resume['text'] ?? '');

echo json_encode($result);
