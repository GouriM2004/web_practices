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

        <form method="post">
            <div class="mb-3">
                <textarea name="text" class="form-control" rows="10" placeholder="Paste text here..."></textarea>
            </div>
            <button class="btn btn-primary">Parse</button>
        </form>
    </div>
</div>

<?php if ($result): ?>
    <div class="mt-4">
        <h4>Parsed Result</h4>
        <div class="card">
            <div class="card-body">
                <pre style="white-space:pre-wrap"><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)); ?></pre>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>