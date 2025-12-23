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
$confidenceStats = $pollModel->getConfidenceStats($poll_id);
$layeredResults = $pollModel->getVoterTypeLayeredResults($poll_id);
$layeredOptions = $layeredResults['options'] ?? [];
$layeredTotals = $layeredResults['totals'] ?? ['by_type' => [], 'weighted_total' => 0];
$layeredWeights = $layeredResults['weights'] ?? ['expert' => 2.0, 'student' => 1.5, 'public' => 1.0];
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

            <?php if (!empty($layeredOptions)): ?>
              <?php
              $expertTotal = $layeredTotals['by_type']['expert'] ?? 0;
              $studentTotal = $layeredTotals['by_type']['student'] ?? 0;
              $publicTotal = $layeredTotals['by_type']['public'] ?? 0;
              $weightedTotal = $layeredTotals['weighted_total'] ?? 0;
              ?>
              <div class="card mt-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                  <h6 class="mb-0">Layered Results (Expert / Student / Public)</h6>
                  <small class="text-muted">Weights: Expert x<?= $layeredWeights['expert'] ?> | Student x<?= $layeredWeights['student'] ?> | Public x<?= $layeredWeights['public'] ?></small>
                </div>
                <div class="card-body">
                  <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-primary">Experts: <?= $expertTotal ?></span>
                    <span class="badge bg-info text-dark">Students: <?= $studentTotal ?></span>
                    <span class="badge bg-secondary">Public: <?= $publicTotal ?></span>
                    <span class="badge bg-dark">Weighted total: <?= number_format((float)$weightedTotal, 1) ?></span>
                  </div>

                  <?php foreach ($layeredOptions as $opt):
                    $byType = $opt['by_type'] ?? [];
                    $expert = $byType['expert']['count'] ?? 0;
                    $student = $byType['student']['count'] ?? 0;
                    $public = $byType['public']['count'] ?? 0;
                  ?>
                    <div class="mb-3">
                      <div class="d-flex justify-content-between">
                        <strong><?= htmlspecialchars($opt['text']) ?></strong>
                        <span><?= number_format((float)$opt['weighted_votes'], 1) ?> weighted (<?= $opt['weighted_percentage'] ?>%)</span>
                      </div>
                      <div class="progress mb-2" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: <?= $opt['weighted_percentage'] ?>%;"></div>
                      </div>
                      <div class="d-flex flex-wrap gap-3 small text-muted">
                        <span>Expert: <?= $expert ?> (×<?= $layeredWeights['expert'] ?>)</span>
                        <span>Student: <?= $student ?> (×<?= $layeredWeights['student'] ?>)</span>
                        <span>Public: <?= $public ?> (×<?= $layeredWeights['public'] ?>)</span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <a href="index.php" class="btn btn-outline-secondary mt-3">Back to Poll</a>
            <a href="live_dashboard.php?poll_id=<?= $poll_id ?>" class="btn btn-outline-info mt-3 ms-2">Live Dashboard</a>

            <?php if (!empty($confidenceStats['overall']) && $totalVotes > 0): ?>
              <div class="card mt-4">
                <div class="card-header bg-white">
                  <h6 class="mb-0">Voter Confidence Indicator</h6>
                </div>
                <div class="card-body">
                  <p class="text-muted small mb-3">How confident voters are in their choices</p>
                  <?php
                  $confidenceLabels = [
                    'very_sure' => ['label' => 'Very sure', 'icon' => '😊', 'color' => 'success'],
                    'somewhat_sure' => ['label' => 'Somewhat sure', 'icon' => '🤔', 'color' => 'warning'],
                    'just_guessing' => ['label' => 'Just guessing', 'icon' => '🤷', 'color' => 'secondary']
                  ];
                  foreach ($confidenceStats['overall'] as $stat):
                    $config = $confidenceLabels[$stat['confidence_level']] ?? ['label' => $stat['confidence_level'], 'icon' => '', 'color' => 'info'];
                  ?>
                    <div class="mb-2">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span><?= $config['icon'] ?> <?= htmlspecialchars($config['label']) ?></span>
                        <span class="badge bg-<?= $config['color'] ?>"><?= $stat['count'] ?> (<?= $stat['percentage'] ?>%)</span>
                      </div>
                      <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-<?= $config['color'] ?>" role="progressbar"
                          style="width: <?= $stat['percentage'] ?>%;"
                          aria-valuenow="<?= $stat['percentage'] ?>"
                          aria-valuemin="0"
                          aria-valuemax="100">
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

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
  <script>
    (function() {
      const pollId = <?= $poll_id ?>;
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
      if (waBtn) waBtn.href = 'https://wa.me/?text=' + encodeURIComponent('Check this poll: ' + link);

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
</body>

</html>