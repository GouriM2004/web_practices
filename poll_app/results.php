<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';
$pollModel = new Poll();

$poll_id = (int)($_GET['poll_id'] ?? 0);
$poll = $pollModel->getPollById($poll_id);
if (!$poll) {
  die("Poll not found.");
}

$options = $pollModel->getOptions($poll_id);
$publicVoters = $pollModel->getPublicVoters($poll_id);
$totalVotes = 0;
foreach ($options as $o) $totalVotes += $o['votes'];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Poll Results</title>
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
      <div class="col-md-8">

        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <h4 class="mb-0">Results</h4>
          </div>
          <div class="card-body">
            <h5 class="card-title mb-3"><?= htmlspecialchars($poll['question']) ?></h5>
            <p class="text-muted mb-4">Total votes: <strong><?= $totalVotes ?></strong></p>

            <?php if (empty($options)): ?>
              <div class="alert alert-info">No options found for this poll.</div>
            <?php else: ?>
              <?php foreach ($options as $opt):
                $percent = $totalVotes ? round(($opt['votes'] / $totalVotes) * 100) : 0;
              ?>
                <div class="mb-3">
                  <div class="d-flex justify-content-between">
                    <span><?= htmlspecialchars($opt['option_text']) ?></span>
                    <span><?= $opt['votes'] ?> votes (<?= $percent ?>%)</span>
                  </div>
                  <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $percent ?>">
                    <div class="progress-bar" style="width: <?= $percent ?>%;"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>

            <a href="index.php" class="btn btn-outline-secondary mt-3">Back to Poll</a>

            <?php if (!empty($publicVoters)): ?>
              <div class="mt-4">
                <h6>Voters who chose to display their names:</h6>
                <ul class="list-unstyled mb-0">
                  <?php foreach ($publicVoters as $v): ?>
                    <li>• <?= htmlspecialchars($v['voter_name']) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>