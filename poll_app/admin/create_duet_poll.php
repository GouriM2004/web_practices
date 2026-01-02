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
$collaboratorId = '';
$collaborationNotes = '';
$cleanOptions = [];

// Get list of all admins for collaboration selection
$allAdmins = $pollModel->getAllAdmins();
$currentAdminId = $_SESSION['admin_id'];

// Filter out current admin from collaborator options
$availableCollaborators = array_filter($allAdmins, function ($admin) use ($currentAdminId) {
    return $admin['id'] != $currentAdminId;
});

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = trim($_POST['question'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $locationTag = trim($_POST['location_tag'] ?? '');
    $collaboratorId = intval($_POST['collaborator_id'] ?? 0);
    $collaborationNotes = trim($_POST['collaboration_notes'] ?? '');
    $options = $_POST['options'] ?? [];
    $allow_multiple = isset($_POST['allow_multiple']) ? 1 : 0;

    $cleanOptions = [];
    foreach ($options as $opt) {
        $opt = trim($opt);
        if ($opt !== '') $cleanOptions[] = $opt;
    }

    if ($question === '' || count($cleanOptions) < 2) {
        $error = "Question is required and at least 2 options.";
    } elseif ($collaboratorId === 0) {
        $error = "Please select a collaborator for this duet poll.";
    } else {
        $useCategory = $category !== '' ? $category : 'General';
        $useLocation = $locationTag !== '' ? $locationTag : null;
        $useNotes = $collaborationNotes !== '' ? $collaborationNotes : null;

        $poll_id = $pollModel->createDuetPoll(
            $question,
            $cleanOptions,
            $currentAdminId,
            $collaboratorId,
            $allow_multiple,
            $useCategory,
            $useLocation,
            $useNotes
        );

        if ($poll_id) {
            $success = "Duet poll created successfully! Invitation sent to your collaborator.";
            $question = '';
            $category = '';
            $locationTag = '';
            $collaboratorId = '';
            $collaborationNotes = '';
            $cleanOptions = [];
        } else {
            $error = "Failed to create duet poll.";
        }
    }
}

$existingOptions = $cleanOptions ?: ['', '', ''];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Create Duet Poll - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .collab-card {
            border-left: 4px solid #6f42c1;
        }

        .duet-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
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
                <a href="duet_polls.php" class="btn btn-outline-light btn-sm me-2">My Duet Polls</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-9">

                <div class="card shadow-sm collab-card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">
                                <span class="badge duet-badge me-2">DUET</span>
                                Create Collaborative Poll
                            </h4>
                            <small class="text-muted">Invite another creator to collaborate on this poll</small>
                        </div>
                        <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">&laquo; Back</a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>

                        <form method="post">

                            <!-- Collaborator Selection -->
                            <div class="mb-4 p-3 bg-light rounded">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-people-fill"></i> Select Your Collaborator *
                                </label>
                                <select name="collaborator_id" class="form-select" required>
                                    <option value="">-- Choose a collaborator --</option>
                                    <?php foreach ($availableCollaborators as $admin): ?>
                                        <option value="<?= $admin['id'] ?>" <?= $collaboratorId == $admin['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($admin['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    This person will receive an invitation to collaborate and will have equal editing rights.
                                </small>
                            </div>

                            <!-- Collaboration Notes -->
                            <div class="mb-3">
                                <label class="form-label">Collaboration Notes (Optional)</label>
                                <textarea name="collaboration_notes" class="form-control" rows="2" placeholder="Add any notes about the collaboration purpose or goals..."><?= htmlspecialchars($collaborationNotes) ?></textarea>
                            </div>

                            <hr>

                            <!-- Poll Question -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Poll Question *</label>
                                <input type="text" name="question" class="form-control" value="<?= htmlspecialchars($question) ?>" placeholder="e.g. What feature should we prioritize next?" required>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($category) ?>" placeholder="e.g. Product Development, Business Strategy">
                                <small class="form-text text-muted">Leave blank for 'General'</small>
                            </div>

                            <!-- Location Tag -->
                            <div class="mb-3">
                                <label class="form-label">Location Tag</label>
                                <input type="text" name="location_tag" class="form-control" value="<?= htmlspecialchars($locationTag) ?>" placeholder="e.g. New York, Global">
                            </div>

                            <!-- Allow Multiple Choices -->
                            <div class="mb-3 form-check">
                                <input type="checkbox" name="allow_multiple" class="form-check-input" id="allowMultiple" <?= isset($_POST['allow_multiple']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="allowMultiple">
                                    Allow multiple choice selection
                                </label>
                            </div>

                            <hr>

                            <!-- Options -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Poll Options *</label>
                                <div id="options-container">
                                    <?php foreach ($existingOptions as $opt): ?>
                                        <div class="input-group mb-2">
                                            <input type="text" name="options[]" class="form-control" placeholder="Option text" value="<?= htmlspecialchars($opt) ?>">
                                            <button class="btn btn-outline-danger" type="button" onclick="this.closest('.input-group').remove()">Remove</button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="addOptionField()">+ Add Option</button>
                                <small class="form-text text-muted d-block mt-2">At least 2 options required</small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <span class="badge duet-badge me-1">DUET</span>
                                    Create & Send Invitation
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info Box -->
                <div class="card mt-3 border-info">
                    <div class="card-body">
                        <h6 class="card-title text-info">
                            <i class="bi bi-info-circle-fill"></i> How Duet Polls Work
                        </h6>
                        <ul class="mb-0 small">
                            <li>Your selected collaborator will receive an invitation to collaborate on this poll</li>
                            <li>Both creators will have equal rights to edit and manage the poll</li>
                            <li>The poll will show both creators' names when displayed</li>
                            <li>All activity and edits are tracked for transparency</li>
                            <li>Collaborator must accept the invitation before full collaboration begins</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>