<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';
$pollModel = new Poll();
$poll = $pollModel->getActivePoll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Online Poll</title>
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
      <?php if(!$poll): ?>
        <div class="alert alert-info text-center">
          No active poll at the moment.
        </div>
      <?php else: ?>
        <div class="card shadow-sm">
          <div class="card-header bg-white">
            <h4 class="mb-0">Current Poll</h4>
          </div>
          <div class="card-body">
            <h5 class="card-title mb-3">
              <?= htmlspecialchars($poll['question']) ?>
            </h5>

            <form action="vote.php" method="post">
              <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">

              <?php
                $options = $pollModel->getOptions((int)$poll['id']);
                foreach ($options as $opt):
              ?>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="option_id"
                         id="opt<?= $opt['id'] ?>" value="<?= $opt['id'] ?>" required>
                  <label class="form-check-label" for="opt<?= $opt['id'] ?>">
                    <?= htmlspecialchars($opt['option_text']) ?>
                  </label>
                </div>
              <?php endforeach; ?>

              <button type="submit" class="btn btn-primary mt-3">Submit Vote</button>
              <a href="results.php?poll_id=<?= $poll['id'] ?>" class="btn btn-outline-secondary mt-3 ms-2">
                View Results
              </a>
            </form>
          </div>
        </div>
      <?php endif; ?>

      <div class="text-center mt-4">
        <small class="text-muted">
          Admin? <a href="admin/login.php">Go to Admin Panel</a>
        </small>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
