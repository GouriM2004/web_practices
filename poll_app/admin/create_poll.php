<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_guard.php';

$pollModel = new Poll();
$error = '';
$success = '';
$question = '';
$category = '';
$locationTag = '';
$isEmojiOnly = 0;
$cleanOptions = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $question = trim($_POST['question'] ?? '');
  $category = trim($_POST['category'] ?? '');
  $locationTag = trim($_POST['location_tag'] ?? '');
  $options = $_POST['options'] ?? [];
  $allow_multiple = isset($_POST['allow_multiple']) ? 1 : 0;
  $isEmojiOnly = isset($_POST['is_emoji_only']) ? 1 : 0;

  $cleanOptions = [];
  foreach ($options as $opt) {
    $opt = trim($opt);
    if ($opt !== '') $cleanOptions[] = $opt;
  }

  if ($question === '' || count($cleanOptions) < 2) {
    $error = "Question is required and at least 2 options.";
  } elseif ($isEmojiOnly) {
    $invalidEmoji = $pollModel->validateEmojiOnlyOptions($cleanOptions);
    if (!empty($invalidEmoji)) {
      $error = "Emoji-only polls need options that contain only emojis (no text or numbers).";
    }
  }

  if ($error === '') {
    $useCategory = $category !== '' ? $category : 'General';
    $useLocation = $locationTag !== '' ? $locationTag : null;
    $poll_id = $pollModel->createPoll($question, $cleanOptions, $allow_multiple, $useCategory, $useLocation, $isEmojiOnly);
    if ($poll_id) {
      $success = "Poll created successfully.";
      $question = '';
      $category = '';
      $locationTag = '';
      $isEmojiOnly = 0;
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

    function isEmojiOnly(value) {
      const trimmed = (value || '').trim();
      if (!trimmed) return false;

      try {
        const emojiPattern = /\p{Extended_Pictographic}/u;
        const nonEmoji = trimmed.replace(/[\p{Extended_Pictographic}\s\u200D\uFE0F]/gu, '');
        return nonEmoji.length === 0 && emojiPattern.test(trimmed);
      } catch (err) {
        const fallback = /[\u{1F000}-\u{1FAFF}\u2600-\u27BF\u{1F1E6}-\u{1F1FF}]/u;
        const nonEmoji = trimmed.replace(fallback, '').replace(/[\s\u200D\uFE0F]/g, '');
        return nonEmoji.length === 0 && fallback.test(trimmed);
      }
    }

    function validateEmojiOnlyPoll(event) {
      const toggle = document.getElementById('emojiOnlyToggle');
      if (!toggle || !toggle.checked) return true;
      const inputs = document.querySelectorAll('input[name="options[]"]');
      for (const input of inputs) {
        if (input && input.value && !isEmojiOnly(input.value)) {
          event.preventDefault();
          alert('Emoji-only polls require every option to be emojis only. Remove text, numbers, or punctuation.');
          input.focus();
          return false;
        }
      }
      return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('createPollForm');
      if (form) {
        form.addEventListener('submit', validateEmojiOnlyPoll);
      }
    });
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
            <?php if ($error): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
              <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" id="createPollForm">
              <div class="mb-3">
                <label class="form-label">Question</label>
                <input type="text" name="question" class="form-control"
                  value="<?= htmlspecialchars($question) ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label">Category (for recommendations)</label>
                <input type="text" name="category" class="form-control" placeholder="e.g., Sports, Tech, Music"
                  value="<?= htmlspecialchars($category) ?>">
              </div>

              <div class="mb-3">
                <label class="form-label">Location Tag (optional)</label>
                <input type="text" name="location_tag" class="form-control" placeholder="e.g., California, UK, Remote"
                  value="<?= htmlspecialchars($locationTag) ?>">
                <small class="text-muted">Helps surface polls to voters from matching regions.</small>
              </div>

              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="allow_multiple" id="allowMultiple" value="1" <?= isset($_POST['allow_multiple']) ? 'checked' : '' ?>>
                  <label class="form-check-label" for="allowMultiple">
                    Allow multiple choice (users can select more than one option)
                  </label>
                </div>
              </div>

              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="is_emoji_only" id="emojiOnlyToggle" value="1" <?= $isEmojiOnly ? 'checked' : '' ?>>
                  <label class="form-check-label" for="emojiOnlyToggle">
                    Emoji-only options (😀🔥💯)
                  </label>
                  <small class="text-muted">Every option must be emoji-only. Perfect for quick, viral polls.</small>
                </div>
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