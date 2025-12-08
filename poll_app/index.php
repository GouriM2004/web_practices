<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';
$pollModel = new Poll();
$poll = $pollModel->getActivePoll();
$voterLogged = VoterAuth::check();
$voterName = $voterLogged ? VoterAuth::name() : null;
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
      <div class="d-flex align-items-center text-white">
        <?php if ($voterLogged): ?>
          <span class="me-3">Hello, <?= htmlspecialchars($voterName) ?></span>
          <a href="voter_logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        <?php else: ?>
          <a href="voter_login.php?redirect=index.php" class="btn btn-outline-light btn-sm">Voter Login</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <?php if (!$poll): ?>
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
              <?php if ($poll['allow_multiple']): ?>
                <p class="text-muted small mb-3"><em>You may select multiple options</em></p>
              <?php endif; ?>

              <?php if (!$voterLogged): ?>
                <div class="alert alert-warning">
                  Please <a href="voter_login.php?redirect=index.php" class="alert-link">log in</a> to vote.
                </div>
              <?php endif; ?>

              <form action="vote.php" method="post" id="pollForm">
                <input type="hidden" name="poll_id" value="<?= $poll['id'] ?>">
                <input type="hidden" name="is_public" value="0">

                <?php
                $options = $pollModel->getOptions((int)$poll['id']);
                $inputType = $poll['allow_multiple'] ? 'checkbox' : 'radio';
                $inputName = $poll['allow_multiple'] ? 'option_id[]' : 'option_id';
                foreach ($options as $opt):
                ?>
                  <div class="form-check mb-2">
                    <input class="form-check-input" type="<?= $inputType ?>" name="<?= $inputName ?>"
                      id="opt<?= $opt['id'] ?>" value="<?= $opt['id'] ?>" <?= $poll['allow_multiple'] ? '' : 'required' ?> <?= $voterLogged ? '' : 'disabled' ?>>
                    <label class="form-check-label" for="opt<?= $opt['id'] ?>">
                      <?= htmlspecialchars($opt['option_text']) ?>
                    </label>
                  </div>
                <?php endforeach; ?>

                <div class="form-check mt-3">
                  <input class="form-check-input" type="checkbox" id="publicVote" name="is_public" value="1" <?= $voterLogged ? '' : 'disabled' ?>>
                  <label class="form-check-label" for="publicVote">
                    Show my name on the results page
                  </label>
                </div>

                <button type="submit" class="btn btn-primary mt-3" <?= $voterLogged ? '' : 'disabled' ?>>Submit Vote</button>
                <a href="results.php?poll_id=<?= $poll['id'] ?>" class="btn btn-outline-secondary mt-3 ms-2">
                  View Results
                </a>
              </form>
              <?php if ($poll['allow_multiple']): ?>
                <script>
                  document.getElementById('pollForm').addEventListener('submit', function(e) {
                    const checkboxes = document.querySelectorAll('input[name="option_id[]"]:checked');
                    if (checkboxes.length === 0) {
                      e.preventDefault();
                      alert('Please select at least one option.');
                      return false;
                    }
                  });
                </script>
              <?php endif; ?>
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