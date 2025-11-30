<?php
require_once __DIR__ . '/../src/bootstrap.php';
include __DIR__ . '/includes/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="alert alert-danger">Missing resume id</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}
$db = get_db();
$stmt = $db->prepare('SELECT * FROM resumes WHERE id = ?');
$stmt->execute([$id]);
$resume = $stmt->fetch();
if (!$resume) {
    echo '<div class="alert alert-danger">Resume not found</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}
$parsed = $resume['parsed_json'] ? json_decode($resume['parsed_json'], true) : null;
?>

<div class="mb-3"><a href="dashboard.php" class="btn btn-sm btn-link">← Back</a></div>
<div class="card">
    <div class="card-body">
        <h3 class="card-title"><?php echo htmlspecialchars($resume['filename']); ?></h3>
        <p class="text-muted"><strong>Uploaded:</strong> <?php echo htmlspecialchars($resume['created_at']); ?></p>

        <h5 class="mt-3">Parsed JSON</h5>
        <?php if ($parsed): ?>
            <pre class="p-3 bg-light border rounded" style="white-space:pre-wrap;"><?php echo htmlspecialchars(json_encode($parsed, JSON_PRETTY_PRINT)); ?></pre>
        <?php else: ?>
            <div class="alert alert-warning">No parsed JSON available. Run analysis to populate parsed data.</div>
        <?php endif; ?>

        <h5 class="mt-4">Raw Text (truncated)</h5>
        <pre class="p-3 bg-light border rounded" style="max-height:300px; overflow:auto"><?php echo htmlspecialchars(substr($resume['text'] ?? '', 0, 2000)); ?></pre>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>