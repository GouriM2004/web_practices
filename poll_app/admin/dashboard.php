<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_guard.php';

$pollModel = new Poll();
$trendAnalyzer = new TrendAnalyzer();

$currentAdminId = $_SESSION['admin_id'];

// Get pending duet invitations count
$pendingInvitations = $pollModel->getPendingDuetInvitations($currentAdminId);
$invitationCount = count($pendingInvitations);

if (isset($_GET['toggle'], $_GET['id'])) {
  $pollModel->toggleActive((int)$_GET['id']);
  header("Location: dashboard.php");
  exit;
}

if (isset($_GET['delete'], $_GET['id'])) {
  $pollModel->deletePoll((int)$_GET['id']);
  header("Location: dashboard.php");
  exit;
}

$polls = $pollModel->getAllPolls();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Admin Dashboard - Poll App</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <style>
    .duet-badge {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      font-size: 0.7rem;
      padding: 0.25rem 0.4rem;
    }

    .notification-badge {
      position: relative;
      top: -2px;
    }
  </style>
</head>

<body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="dashboard.php">Poll Admin</a>
      <div class="d-flex align-items-center">
        <a href="duet_polls.php" class="btn btn-outline-light btn-sm me-2 position-relative">
          <i class="bi bi-people-fill"></i> Duet Polls
          <?php if ($invitationCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
              <?= $invitationCount ?>
            </span>
          <?php endif; ?>
        </a>
        <span class="navbar-text me-3">
          Logged in as <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?>
        </span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">

    <?php if ($invitationCount > 0): ?>
      <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="bi bi-envelope-fill"></i>
        <strong>You have <?= $invitationCount ?> pending duet poll invitation<?= $invitationCount > 1 ? 's' : '' ?>!</strong>
        <a href="duet_polls.php" class="alert-link">View invitations</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Polls</h3>
      <div>
        <a href="create_duet_poll.php" class="btn btn-outline-primary me-2">
          <span class="badge duet-badge me-1">DUET</span>
          Create Duet Poll
        </a>
        <a href="create_poll.php" class="btn btn-primary">+ Create New Poll</a>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Question</th>
                <th>Type</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Options</th>
                <th>Trends</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($polls)): ?>
                <tr>
                  <td colspan="8" class="text-center p-3">No polls created yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($polls as $poll): ?>
                  <?php
                  $optCount = $pollModel->countOptions((int)$poll['id']);
                  $trendSummary = $trendAnalyzer->getPollTrendSummary((int)$poll['id']);
                  $totalTrends = count($trendSummary['rising_options']) + count($trendSummary['falling_options']) + count($trendSummary['spike_alerts']) + count($trendSummary['decay_alerts']);

                  // Check if it's a duet poll
                  $isDuet = isset($poll['is_duet']) && $poll['is_duet'];
                  $duetInfo = null;
                  if ($isDuet) {
                    $duetInfo = $pollModel->getDuetPollInfo((int)$poll['id']);
                  }
                  ?>
                  <tr>
                    <td><?= (int)$poll['id'] ?></td>
                    <td>
                      <?= htmlspecialchars($poll['question']) ?>
                      <?php if ($isDuet && $duetInfo): ?>
                        <br>
                        <span class="badge duet-badge mt-1">
                          <i class="bi bi-people-fill"></i> DUET
                        </span>
                        <small class="text-muted d-block mt-1">
                          <?= htmlspecialchars($duetInfo['creator1_username']) ?> &
                          <?= htmlspecialchars($duetInfo['creator2_username']) ?>
                        </small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($poll['allow_multiple']): ?>
                        <span class="badge bg-info">Multiple</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Single</span>
                      <?php endif; ?>
                      <?php if (!empty($poll['is_emoji_only'])): ?>
                        <span class="badge bg-warning text-dark ms-1">Emoji</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($poll['is_active']): ?>
                        <span class="badge bg-success">Active</span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($poll['created_at']) ?></td>
                    <td><?= $optCount ?></td>
                    <td>
                      <div class="d-flex flex-wrap gap-1">
                        <?php if (!empty($trendSummary['rising_options'])): ?>
                          <span class="badge bg-success" title="Rising options">📈 <?= count($trendSummary['rising_options']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($trendSummary['falling_options'])): ?>
                          <span class="badge bg-danger" title="Falling options">📉 <?= count($trendSummary['falling_options']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($trendSummary['spike_alerts'])): ?>
                          <span class="badge bg-warning text-dark" title="Vote spikes">⚡ <?= count($trendSummary['spike_alerts']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($trendSummary['decay_alerts'])): ?>
                          <span class="badge bg-info text-dark" title="Vote decay">⬇️ <?= count($trendSummary['decay_alerts']) ?></span>
                        <?php endif; ?>
                        <?php if ($totalTrends === 0): ?>
                          <span class="badge bg-secondary">No trends</span>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td>
                      <div class="btn-group btn-group-sm" role="group">
                        <a href="?toggle=1&id=<?= (int)$poll['id'] ?>" class="btn btn-outline-primary">
                          <?= $poll['is_active'] ? 'Deactivate' : 'Activate' ?>
                        </a>
                        <a href="../results.php?poll_id=<?= (int)$poll['id'] ?>" class="btn btn-outline-info" target="_blank">
                          Results
                        </a>
                        <a href="?delete=1&id=<?= (int)$poll['id'] ?>" class="btn btn-outline-danger"
                          onclick="return confirm('Delete this poll?');">
                          Delete
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>