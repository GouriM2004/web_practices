<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/ParserService.php';

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = $_POST['text'] ?? '';
    $parsed = ParserService::parseText($text);
    $result = $parsed;
}
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Analyze - Resume Tailor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Analyze</h1>
            <a href="dashboard.php" class="btn btn-link">Dashboard</a>
        </div>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Paste resume text or job description</label>
                <textarea name="text" class="form-control" rows="10" placeholder="Paste text here..."></textarea>
            </div>
            <button class="btn btn-primary">Parse</button>
        </form>

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
    </div>
</body>

</html>