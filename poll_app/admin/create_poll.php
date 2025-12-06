<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_guard.php';

$pollModel = new Poll();
$error = '';
$success = '';
$question = '';
$cleanOptions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $options = $_POST['options'] ?? [];

    $cleanOptions = [];
    foreach ($options as $opt) {
        $opt = trim($opt);
        if ($opt !== '') $cleanOptions[] = $opt;
    }

    if ($question === '' || count($cleanOptions) < 2) {
        $error = "Question is required and at least 2 options.";
    } else {
        $poll_id = $pollModel->createPoll($question, $cleanOptions);
        if ($poll_id) {
            $success = "Poll created successfully.";
            $question = '';
            $cleanOptions = [];
        } else {
            $error = "Failed to create poll.";
        }
    }
}

$existingOptions = $cleanOptions ?: ['', '', ''];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Create Poll - Admin</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
  <script>
    function addOptionField() {
      const container = document.getElementById('options-container');
      const div = document.createElement('div');
      div.className = 'input-group mb-2';
      div.innerHTML = `
        <input type="text" name="options[]" class="form-control" placeholder="Option text">
        <button class="btn btn-outline-danger" type="button" onclick="this.closest('.input-group').remove()">Remove</button>
      `;
      container.appendChild(div);
    }
  </script>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="dashboard.php">Poll Admin</a>
    <div class="d-flex">
      <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
      <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-8">

      <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h4 class="mb-0">Create New Poll</h4>
          <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">&laquo; Back</a>
        </div>
        <div class="card-body">
          <?php if($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
          <?php endif; ?>

          <form method="post">
            <div class="mb-3">
              <label class="form-label">Question</label>
              <input type="text" name="question" class="form-control"
                     value="<?= htmlspecialchars($question) ?>" required>
            </div>

            <div class="mb-2 d-flex justify-content-between align-items-center">
              <label class="form-label mb-0">Options</label>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addOptionField()">+ Add Option</button>
            </div>

            <div id="options-container">
              <?php foreach ($existingOptions as $optVal): ?>
                <div class="input-group mb-2">
                  <input type="text" name="options[]" class="form-control" placeholder="Option text"
                         value="<?= htmlspecialchars($optVal) ?>">
                  <button class="btn btn-outline-danger" type="button"
                          onclick="this.closest('.input-group').remove()">Remove</button>
                </div>
              <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-success mt-3">Create Poll</button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
