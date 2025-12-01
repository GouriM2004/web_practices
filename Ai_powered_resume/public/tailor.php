<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/ParserService.php';
include __DIR__ . '/includes/header.php';

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

<div class="mb-3"><a href="dashboard.php" class="btn btn-sm btn-link">← Back</a></div>
<div class="card">
    <div class="card-body">
        <h3 class="card-title">Tailor Resume</h3>
        <?php if (!$resume): ?>
            <div class="alert alert-warning">No resume selected. Go to the dashboard and pick a resume to tailor.</div>
        <?php else: ?>
            <h5><?php echo htmlspecialchars($resume['filename']); ?></h5>
            <p class="text-muted">Parsed skills: <?php echo htmlspecialchars(implode(', ', $parsed['skills'] ?? [])); ?></p>

            <form id="tailorForm">
                <div class="mb-3">
                    <label class="form-label">Paste Job Description</label>
                    <textarea id="jobText" name="job_text" class="form-control" rows="8"></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Analyze & Suggest</button>
            </form>

            <div id="tailorResult" style="display:none" class="mt-4">
                <h5>Results</h5>
                <div id="tailorResultBody"></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    document.getElementById('tailorForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const jobText = document.getElementById('jobText').value;
        const resumeId = <?php echo json_encode($resume_id); ?>;
        const payload = {
            resume_id: resumeId,
            job_text: jobText
        };
        const res = await fetch('api/tailor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        document.getElementById('tailorResult').style.display = 'block';
        let html = '';
        if (data.ats) {
            html += '<p><strong>ATS Score:</strong> ' + data.ats.ats_score + '</p>';
            html += '<p><strong>Missing skills:</strong> ' + (data.ats.missing_skills || []).join(', ') + '</p>';
        }
        if (data.tailored && data.tailored.length) {
            html += '<h6>Tailored Examples</h6><ul>';
            data.tailored.forEach(function(t) {
                html += '<li><strong>' + t.skill + ':</strong> ' + t.example + '</li>';
            });
            html += '</ul>';
        }
        document.getElementById('tailorResultBody').innerHTML = html;
    });
</script>
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