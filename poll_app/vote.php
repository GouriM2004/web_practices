<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $poll_id   = (int)($_POST['poll_id'] ?? 0);
  $option_ids = $_POST['option_id'] ?? [];
  $ip        = $_SERVER['REMOTE_ADDR'] ?? '';

  $pollModel = new Poll();

  // normalize to array
  if (!is_array($option_ids)) {
    $option_ids = [$option_ids];
  }

  // filter valid IDs
  $option_ids = array_filter(array_map('intval', $option_ids), function ($id) {
    return $id > 0;
  });

  if ($poll_id && !empty($option_ids)) {
    $ok = $pollModel->recordVote($poll_id, $option_ids, $ip);
    if ($ok) {
      header("Location: results.php?poll_id=" . $poll_id);
      exit;
    } else {
      $msg = "You have already voted in this poll.";
    }
  } else {
    $msg = "Invalid vote submission.";
  }
} else {
  header("Location: index.php");
  exit;
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Vote - Poll System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand" href="index.php">Poll System</a>
    </div>
  </nav>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-body text-center">
            <h5 class="card-title mb-3">Vote Status</h5>
            <p><?= htmlspecialchars($msg ?? 'Unknown status.') ?></p>
            <a href="index.php" class="btn btn-primary mt-2">Back to Poll</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>