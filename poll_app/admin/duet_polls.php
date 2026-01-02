<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_guard.php';

$pollModel = new Poll();
$currentAdminId = $_SESSION['admin_id'];
$message = '';
$messageType = '';

// Handle invitation responses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $poll_id = intval($_POST['poll_id'] ?? 0);

    if ($poll_id > 0) {
        if ($action === 'accept') {
            if ($pollModel->acceptDuetInvitation($poll_id, $currentAdminId)) {
                $message = "Invitation accepted! You can now collaborate on this poll.";
                $messageType = 'success';
            } else {
                $message = "Failed to accept invitation.";
                $messageType = 'danger';
            }
        } elseif ($action === 'decline') {
            if ($pollModel->declineDuetInvitation($poll_id, $currentAdminId)) {
                $message = "Invitation declined.";
                $messageType = 'info';
            } else {
                $message = "Failed to decline invitation.";
                $messageType = 'danger';
            }
        }
    }
}

// Get pending invitations
$pendingInvitations = $pollModel->getPendingDuetInvitations($currentAdminId);

// Get all duet polls for this admin
$myDuetPolls = $pollModel->getDuetPollsForAdmin($currentAdminId);

// Separate into categories
$activeCollaborations = [];
$pendingMyInvites = [];
$declinedPolls = [];

foreach ($myDuetPolls as $duetPoll) {
    if ($duetPoll['invitation_status'] === 'accepted') {
        $activeCollaborations[] = $duetPoll;
    } elseif ($duetPoll['invitation_status'] === 'pending' && $duetPoll['creator1_id'] == $currentAdminId) {
        // Invitations I sent that are pending
        $pendingMyInvites[] = $duetPoll;
    } elseif ($duetPoll['invitation_status'] === 'declined') {
        $declinedPolls[] = $duetPoll;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>My Duet Polls - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .duet-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }

        .status-pending {
            border-left: 4px solid #ffc107;
        }

        .status-accepted {
            border-left: 4px solid #28a745;
        }

        .status-declined {
            border-left: 4px solid #dc3545;
        }

        .collab-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 0.875rem;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Poll Admin</a>
            <div class="d-flex">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
                <a href="create_duet_poll.php" class="btn btn-success btn-sm me-2">+ New Duet Poll</a>
                <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <span class="badge duet-badge me-2">DUET</span>
                My Collaborative Polls
            </h2>
            <a href="create_duet_poll.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Duet Poll
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Invitations Received -->
        <?php if (count($pendingInvitations) > 0): ?>
            <div class="card shadow-sm mb-4 status-pending">
                <div class="card-header bg-warning bg-opacity-10">
                    <h5 class="mb-0">
                        <i class="bi bi-envelope-fill text-warning"></i>
                        Pending Invitations (<?= count($pendingInvitations) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($pendingInvitations as $invite): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="card-title">
                                            <?= htmlspecialchars($invite['question']) ?>
                                        </h6>
                                        <p class="card-text mb-2">
                                            <span class="badge bg-secondary"><?= htmlspecialchars($invite['category']) ?></span>
                                            <span class="text-muted small ms-2">
                                                <i class="bi bi-person-fill"></i>
                                                Invited by: <strong><?= htmlspecialchars($invite['creator1_username']) ?></strong>
                                            </span>
                                        </p>
                                        <?php if ($invite['collaboration_notes']): ?>
                                            <p class="small text-muted mb-2">
                                                <i class="bi bi-chat-quote"></i>
                                                <?= htmlspecialchars($invite['collaboration_notes']) ?>
                                            </p>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            Invited <?= date('M j, Y g:i A', strtotime($invite['invitation_sent_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="poll_id" value="<?= $invite['poll_id'] ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-success btn-sm me-1">
                                                <i class="bi bi-check-circle"></i> Accept
                                            </button>
                                        </form>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="poll_id" value="<?= $invite['poll_id'] ?>">
                                            <input type="hidden" name="action" value="decline">
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="bi bi-x-circle"></i> Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Active Collaborations -->
        <div class="card shadow-sm mb-4 status-accepted">
            <div class="card-header bg-success bg-opacity-10">
                <h5 class="mb-0">
                    <i class="bi bi-people-fill text-success"></i>
                    Active Collaborations (<?= count($activeCollaborations) ?>)
                </h5>
            </div>
            <div class="card-body">
                <?php if (count($activeCollaborations) === 0): ?>
                    <p class="text-muted mb-0">No active collaborations yet. Create a duet poll or accept an invitation!</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Poll Question</th>
                                    <th>Category</th>
                                    <th>Collaborators</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeCollaborations as $duet): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($duet['question']) ?></strong>
                                            <?php if (!$duet['is_active']): ?>
                                                <span class="badge bg-secondary ms-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($duet['category']) ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="collab-avatar me-1" title="<?= htmlspecialchars($duet['creator1_username']) ?>">
                                                    <?= strtoupper(substr($duet['creator1_username'], 0, 1)) ?>
                                                </div>
                                                <div class="collab-avatar" title="<?= htmlspecialchars($duet['creator2_username']) ?>">
                                                    <?= strtoupper(substr($duet['creator2_username'], 0, 1)) ?>
                                                </div>
                                                <small class="ms-2 text-muted">
                                                    <?= htmlspecialchars($duet['creator1_username']) ?> &
                                                    <?= htmlspecialchars($duet['creator2_username']) ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle-fill"></i> Active
                                            </span>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y', strtotime($duet['poll_created_at'])) ?></small>
                                        </td>
                                        <td>
                                            <a href="../results.php?poll_id=<?= $duet['poll_id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="bi bi-bar-chart-fill"></i> Results
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pending Invitations I Sent -->
        <?php if (count($pendingMyInvites) > 0): ?>
            <div class="card shadow-sm mb-4 status-pending">
                <div class="card-header bg-info bg-opacity-10">
                    <h5 class="mb-0">
                        <i class="bi bi-hourglass-split text-info"></i>
                        Awaiting Response (<?= count($pendingMyInvites) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Poll Question</th>
                                    <th>Invited Collaborator</th>
                                    <th>Sent</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingMyInvites as $pending): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($pending['question']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($pending['creator2_username']) ?></strong>
                                        </td>
                                        <td>
                                            <small><?= date('M j, Y', strtotime($pending['invitation_sent_at'])) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">Pending Response</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Declined Polls -->
        <?php if (count($declinedPolls) > 0): ?>
            <div class="card shadow-sm mb-4 status-declined">
                <div class="card-header bg-danger bg-opacity-10">
                    <h5 class="mb-0">
                        <i class="bi bi-x-circle text-danger"></i>
                        Declined (<?= count($declinedPolls) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Poll Question</th>
                                    <th>Collaborators</th>
                                    <th>Declined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($declinedPolls as $declined): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($declined['question']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($declined['creator1_username']) ?> &
                                            <?= htmlspecialchars($declined['creator2_username']) ?>
                                        </td>
                                        <td>
                                            <small><?= $declined['invitation_responded_at'] ? date('M j, Y', strtotime($declined['invitation_responded_at'])) : 'N/A' ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>