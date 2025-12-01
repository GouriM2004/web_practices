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
}

$ats = ATSService::analyze($parsed_resume, $parsed_job, $resume['text'] ?? '');

// naive tailor suggestions: for each missing skill, produce sample bullets by reusing existing bullets
$tailored = [];
$existingBullets = [];
if (!empty($parsed_resume['experience'])) {
    foreach ($parsed_resume['experience'] as $e) {
        if (!empty($e['raw'])) {
            // split bullets by semicolon or \n
            $parts = preg_split('/[\n;\r]+/', $e['raw']);
            foreach ($parts as $p) {
                $p = trim($p);
                if (strlen($p) > 20) $existingBullets[] = $p;
            }
        }
    }
}

foreach ($ats['missing_skills'] as $ms) {
    $example = "We recommend adding a bullet about {$ms}. Example: 'Worked with {$ms} to ...'";
    // if we have an existing bullet, produce a simple rewrite template
    if (!empty($existingBullets)) {
        $example = "Rewrite example (use facts from resume): '" . htmlspecialchars($existingBullets[0]) . "' — emphasize {$ms}.";
    }
    $tailored[] = [
        'skill' => $ms,
        'example' => $example
    ];
}

echo json_encode([
    'ats' => $ats,
    'tailored' => $tailored
]);
