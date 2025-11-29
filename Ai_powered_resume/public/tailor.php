<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/ParserService.php';

$resume_id = $_GET['resume_id'] ?? null;
$db = get_db();
$resume = null;
$parsed = null;
$tailor_result = null;
if ($resume_id) {
    $stmt = $db->prepare('SELECT * FROM resumes WHERE id = ?');
    $stmt->execute([$resume_id]);
    $resume = $stmt->fetch();
    $parsed = $resume['parsed_json'] ? json_decode($resume['parsed_json'], true) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $job_text = $_POST['job_text'] ?? '';
    // For now, call the parser service on the job_text to get JD keywords and show missing skills
    $jd_parsed = ParserService::parseText($job_text) ?: [];
    $resume_skills = $parsed['skills'] ?? [];
    $jd_skills = $jd_parsed['skills'] ?? [];
    $missing = array_values(array_diff($jd_skills, $resume_skills));
    $tailor_result = [
        'jd_skills' => $jd_skills,
        'resume_skills' => $resume_skills,
        'missing_skills' => $missing,
        'suggestions' => array_map(function ($s) {
            return "Add a bullet emphasizing $s (example): \"Worked with $s to ...\"";
        }, $missing)
    ];
}
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Tailor Resume</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">
    <div class="container">
        <a href="dashboard.php">← Back</a>
        <h1 class="mt-3">Tailor Resume</h1>
        <?php if (!$resume): ?>
            <div class="alert alert-warning">No resume selected. Go to the dashboard and pick a resume to tailor.</div>
        <?php else: ?>
            <h4><?php echo htmlspecialchars($resume['filename']); ?></h4>
            <p>Parsed skills: <?php echo htmlspecialchars(implode(', ', $parsed['skills'] ?? [])); ?></p>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label">Paste Job Description</label>
                    <textarea name="job_text" class="form-control" rows="8"></textarea>
                </div>
                <button class="btn btn-primary">Analyze & Suggest</button>
            </form>

            <?php if ($tailor_result): ?>
                <h4 class="mt-4">Results</h4>
                <p><strong>Job skills:</strong> <?php echo htmlspecialchars(implode(', ', $tailor_result['jd_skills'])); ?></p>
                <p><strong>Missing skills:</strong> <?php echo htmlspecialchars(implode(', ', $tailor_result['missing_skills'])); ?></p>
                <h5>Suggestions</h5>
                <ul>
                    <?php foreach ($tailor_result['suggestions'] as $s): ?>
                        <li><?php echo htmlspecialchars($s); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>

</html>