<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';
$pollModel = new Poll();
$requestedPollId = (int)($_GET['poll_id'] ?? 0);
$poll = $requestedPollId ? $pollModel->getPollById($requestedPollId) : $pollModel->getActivePoll();
$voterLogged = VoterAuth::check();
$voterName = $voterLogged ? VoterAuth::name() : null;
$voterId = $voterLogged ? VoterAuth::id() : null;
$lastLocation = $voterId ? $pollModel->getLastVoterLocation($voterId) : null;
$recommendations = $pollModel->getRecommendedPolls($voterId, $lastLocation, 5);
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

                <div class="mb-3 mt-3">
                  <label class="form-label fw-bold">How confident are you in your choice?</label>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="confidence_level" id="confidence_very" value="very_sure" required <?= $voterLogged ? '' : 'disabled' ?>>
                    <label class="form-check-label" for="confidence_very">
                      😊 Very sure
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="confidence_level" id="confidence_somewhat" value="somewhat_sure" checked <?= $voterLogged ? '' : 'disabled' ?>>
                    <label class="form-check-label" for="confidence_somewhat">
                      🤔 Somewhat sure
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="confidence_level" id="confidence_guessing" value="just_guessing" <?= $voterLogged ? '' : 'disabled' ?>>
                    <label class="form-check-label" for="confidence_guessing">
                      🤷 Just guessing
                    </label>
                  </div>
                  <small class="form-text text-muted">This helps us understand how confident voters are overall</small>
                </div>

                <div class="mb-3 mt-3">
                  <label for="location" class="form-label">Location (Optional)</label>
                  <input type="text" class="form-control" id="location" name="location"
                    placeholder="e.g., California, New York, Texas" <?= $voterLogged ? '' : 'disabled' ?>>
                  <small class="form-text text-muted">Help us show geographical voting patterns</small>
                </div>

                <button type="submit" class="btn btn-primary mt-3" <?= $voterLogged ? '' : 'disabled' ?>>Submit Vote</button>
                <a href="results.php?poll_id=<?= $poll['id'] ?>" class="btn btn-outline-secondary mt-3 ms-2">
                  View Results
                </a>
                <a href="live_dashboard.php?poll_id=<?= $poll['id'] ?>" class="btn btn-outline-info mt-3 ms-2">
                  Live Dashboard
                </a>
                <div class="mt-2">
                  <small class="text-muted">You can change your vote within <?= (int)Config::VOTE_CHANGE_WINDOW_MINUTES ?> minutes.</small>
                </div>
              </form>
              <div class="card mt-3">
                <div class="card-body">
                  <h6 class="mb-2">Share this poll</h6>
                  <div class="d-flex flex-wrap gap-2 mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="copyLinkBtn">Copy Link</button>
                    <a class="btn btn-success btn-sm" id="waShareBtn" target="_blank" rel="noopener">Share on WhatsApp</a>
                    <button type="button" class="btn btn-outline-dark btn-sm" id="showQrBtn">Show QR</button>
                  </div>
                  <div id="qrWrap" class="mt-2" style="display:none;">
                    <img id="qrImg" src="" alt="QR Code" class="img-fluid" style="max-width:220px;">
                  </div>
                  <small class="text-muted d-block mt-2" id="shareLinkText"></small>
                </div>
              </div>
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

        <?php if (!empty($recommendations)): ?>
          <div class="card shadow-sm mt-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Recommended for you</h5>
              <small class="text-muted">Based on your past votes and location</small>
            </div>
            <div class="card-body">
              <?php foreach ($recommendations as $rec): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div>
                    <strong><?= htmlspecialchars($rec['question']) ?></strong><br>
                    <small class="text-muted">Category: <?= htmlspecialchars($rec['category'] ?? 'General') ?><?= !empty($rec['location_tag']) ? ' · ' . htmlspecialchars($rec['location_tag']) : '' ?></small>
                  </div>
                  <div class="btn-group btn-group-sm">
                    <a href="index.php?poll_id=<?= (int)$rec['id'] ?>" class="btn btn-outline-primary">Open</a>
                    <a href="results.php?poll_id=<?= (int)$rec['id'] ?>" class="btn btn-outline-secondary">Results</a>
                    <a href="live_dashboard.php?poll_id=<?= (int)$rec['id'] ?>" class="btn btn-outline-info">Live</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <?php if ($poll): ?>
    <script>
      (function() {
        const pollId = <?= (int)$poll['id'] ?>;
        const shareUrl = new URL(window.location.href);
        shareUrl.searchParams.set('poll_id', pollId);
        const link = shareUrl.toString();

        const shareLinkText = document.getElementById('shareLinkText');
        const copyBtn = document.getElementById('copyLinkBtn');
        const waBtn = document.getElementById('waShareBtn');
        const qrBtn = document.getElementById('showQrBtn');
        const qrWrap = document.getElementById('qrWrap');
        const qrImg = document.getElementById('qrImg');

        if (shareLinkText) shareLinkText.textContent = link;
        if (waBtn) waBtn.href = 'https://wa.me/?text=' + encodeURIComponent('Vote on this poll: ' + link);

        if (copyBtn) {
          copyBtn.addEventListener('click', async () => {
            try {
              await navigator.clipboard.writeText(link);
              copyBtn.textContent = 'Copied!';
              setTimeout(() => copyBtn.textContent = 'Copy Link', 2000);
            } catch (err) {
              alert('Could not copy link: ' + err);
            }
          });
        }

        if (qrBtn && qrImg) {
          qrBtn.addEventListener('click', () => {
            qrWrap.style.display = 'block';
            qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(link);
          });
        }
      })();
    </script>
  <?php endif; ?>
</body>

</html>