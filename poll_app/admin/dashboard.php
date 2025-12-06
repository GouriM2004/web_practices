<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_guard.php';

$pollModel = new Poll();

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
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">Poll Admin</a>
    <div class="d-flex">
      <span class="navbar-text me-3">
        Logged in as <?= htmlspecialchars($_SESSION['admin_username'] ?? '') ?>
      </span>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Polls</h3>
    <a href="create_poll.php" class="btn btn-primary">+ Create New Poll</a>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Question</th>
              <th>Status</th>
              <th>Created At</th>
              <th>Options</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($polls)): ?>
            <tr><td colspan="6" class="text-center p-3">No polls created yet.</td></tr>
          <?php else: ?>
            <?php foreach ($polls as $poll): ?>
              <?php $optCount = $pollModel->countOptions((int)$poll['id']); ?>
              <tr>
                <td><?= (int)$poll['id'] ?></td>
                <td><?= htmlspecialchars($poll['question']) ?></td>
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
