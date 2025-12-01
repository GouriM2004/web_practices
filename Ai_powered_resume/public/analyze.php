<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/ParserService.php';
include __DIR__ . '/includes/header.php';

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'] ?? '';
    $parsed = ParserService::parseText($text);
    $result = $parsed;
}
?>

<div class="card">
    <div class="card-body">
        <h3 class="card-title">Analyze Text</h3>
        <p class="text-muted">Paste a resume or a job description to extract skills and entities.</p>

        <form id="analyzeForm">
            <div class="mb-3">
                <textarea id="analyzeText" name="text" class="form-control" rows="10" placeholder="Paste text here..."></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Parse</button>
        </form>
    </div>
</div>

<?php if ($result): ?>
    <div id="analyzeResult" class="mt-4" style="display:none">
        <h4>Parsed Result</h4>
        <div class="card">
            <div class="card-body" id="analyzeResultBody">
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    document.getElementById('analyzeForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const text = document.getElementById('analyzeText').value;
        const payload = {
            resume_id: 0,
            job_text: text
        };
        // resume_id 0 means analyze text directly; API requires resume_id, so we'll call parser then show parsed
        const res = await fetch('api/analyze.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        document.getElementById('analyzeResult').style.display = 'block';
        document.getElementById('analyzeResultBody').innerHTML = '<pre style="white-space:pre-wrap">' + JSON.stringify(data, null, 2) + '</pre>';
    });
</script>