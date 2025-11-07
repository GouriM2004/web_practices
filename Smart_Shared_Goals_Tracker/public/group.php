<?php
$page_title = 'Group';
$page_scripts = ['assets/js/group.js'];
include __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$gid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$gid) {
    echo '<div class="alert alert-danger">Missing group id.</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// fetch group
$stmt = $pdo->prepare('SELECT * FROM groups_tbl WHERE id = ?');
$stmt->execute([$gid]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$group) {
    echo '<div class="alert alert-danger">Group not found.</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// check membership
$stmt = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
$stmt->execute([$gid, $userId]);
$membership = $stmt->fetchColumn();

// members list
$stmt = $pdo->prepare('SELECT u.id, u.name, u.email, gm.role FROM users u JOIN group_members gm ON gm.user_id = u.id WHERE gm.group_id = ? ORDER BY gm.joined_at ASC');
$stmt->execute([$gid]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// group goals
$stmt = $pdo->prepare('SELECT id, title, cadence, unit, start_date, end_date FROM goals WHERE group_id = ? AND active = 1 ORDER BY created_at DESC');
$stmt->execute([$gid]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-body">
        <h2 class="card-title"><?= htmlspecialchars($group['name']) ?></h2>
        <p class="text-muted"><?= nl2br(htmlspecialchars($group['description'])) ?></p>

        <?php if ($membership): ?>
            <div class="mb-3"><strong>Your role:</strong> <?= htmlspecialchars($membership) ?></div>
        <?php else: ?>
            <div class="mb-3">You are not a member of this group. <button id="joinBtn" class="btn btn-sm btn-primary">Join group</button></div>
        <?php endif; ?>

        <h4>Members</h4>
        <ul class="list-group mb-3">
            <?php foreach ($members as $m): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div><?= htmlspecialchars($m['name']) ?> <div class="small text-muted"><?= htmlspecialchars($m['email']) ?></div>
                    </div>
                    <div class="small text-muted"><?= htmlspecialchars($m['role']) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>

        <h4>Group goals</h4>
        <?php if (!$goals): ?>
            <p>No group goals yet.</p>
        <?php else: ?>
            <ul class="list-group mb-3">
                <?php foreach ($goals as $g): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-bold"><a href="goal.php?id=<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['title']) ?></a></div>
                            <div class="small text-muted">Cadence: <?= htmlspecialchars($g['cadence']) ?> • Unit: <?= htmlspecialchars($g['unit'] ?? '') ?></div>
                        </div>
                        <div>
                            <a class="btn btn-sm btn-outline-primary" href="goal.php?id=<?= (int)$g['id'] ?>">Open</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($membership): ?>
            <a class="btn btn-primary" href="create_goal.php?group_id=<?= $gid ?>">Create group goal</a>
        <?php endif; ?>

        <p class="mt-3"><a href="groups.php">Back to groups</a></p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>