<?php
require_once __DIR__ . '/../src/bootstrap.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo 'Missing resume id';
    exit;
}
$db = get_db();
$stmt = $db->prepare('SELECT * FROM resumes WHERE id = ?');
$stmt->execute([$id]);
$resume = $stmt->fetch();
if (!$resume) {
    echo 'Resume not found';
    exit;
}
$parsed = $resume['parsed_json'] ? json_decode($resume['parsed_json'], true) : null;
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>View Resume</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">
    <div class="container">
        <a href="dashboard.php">← Back</a>
        <h1 class="mt-3"><?php echo htmlspecialchars($resume['filename']); ?></h1>
        <p><strong>Uploaded:</strong> <?php echo htmlspecialchars($resume['created_at']); ?></p>

        <h4>Parsed JSON</h4>
        <?php if ($parsed): ?>
            <pre class="p-3 bg-light border rounded" style="white-space:pre-wrap;"><?php echo htmlspecialchars(json_encode($parsed, JSON_PRETTY_PRINT)); ?></pre>
        <?php else: ?>
            <div class="alert alert-warning">No parsed JSON available. Run analysis to populate parsed data.</div>
        <?php endif; ?>

        <h4 class="mt-4">Raw Text (truncated)</h4>
        <pre class="p-3 bg-light border rounded" style="max-height:300px; overflow:auto"><?php echo htmlspecialchars(substr($resume['text'] ?? '', 0, 2000)); ?></pre>
    </div>
</body>

</html>