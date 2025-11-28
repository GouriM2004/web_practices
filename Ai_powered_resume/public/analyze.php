<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Services/ParserService.php';

// Simple analyze form that posts resume text or job description
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
    <title>Analyze</title>
</head>

<body>
    <h2>Analyze (demo)</h2>
    <form method="post">
        <label>Paste resume text or job description:</label><br />
        <textarea name="text" rows="10" cols="80"></textarea><br />
        <button type="submit">Parse</button>
    </form>

    <?php if ($result): ?>
        <h3>Parsed Result</h3>
        <pre><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)); ?></pre>
    <?php endif; ?>
</body>

</html>