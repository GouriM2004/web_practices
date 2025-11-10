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

// pending invites (visible to owners/admins)
$stmt = $pdo->prepare('SELECT gi.id, gi.email, gi.role, gi.invited_by, gi.accepted_by, gi.created_at, gi.accepted_at, u.name AS invited_by_name, a.name AS accepted_by_name FROM group_invites gi LEFT JOIN users u ON gi.invited_by = u.id LEFT JOIN users a ON gi.accepted_by = a.id WHERE gi.group_id = ? ORDER BY gi.created_at DESC');
$stmt->execute([$gid]);
$invites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// small helper to render human-friendly times
function time_ago($datetime)
{
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    if ($ts === false) return htmlspecialchars($datetime);
    $diff = time() - $ts;
    if ($diff < 60) return $diff . 's ago';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('Y-m-d H:i', $ts);
}

// group goals
$stmt = $pdo->prepare('SELECT id, title, cadence, unit, start_date, end_date, created_by FROM goals WHERE group_id = ? AND active = 1 ORDER BY created_at DESC');
$stmt->execute([$gid]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div id="groupPage" data-group-id="<?= $gid ?>">
    <div class="card">
        <div class="card-body">
            <h2 class="card-title"><?= htmlspecialchars($group['name']) ?></h2>
            <p class="text-muted"><?= nl2br(htmlspecialchars($group['description'])) ?></p>

            <?php if ($membership): ?>
                <div class="mb-3 d-flex align-items-center">
                    <div class="me-3"><strong>Your role:</strong> <?= htmlspecialchars($membership) ?></div>
                    <?php
                    // determine whether this user may leave the group safely
                    $can_leave = true;
                    if ($membership === 'owner') {
                        // ensure there is at least one other owner before allowing leave
                        $stmtOtherOwner = $pdo->prepare('SELECT COUNT(*) FROM group_members WHERE group_id = ? AND role = ? AND user_id != ?');
                        $stmtOtherOwner->execute([$gid, 'owner', $userId]);
                        $otherOwners = (int)$stmtOtherOwner->fetchColumn();
                        if ($otherOwners <= 0) {
                            $can_leave = false;
                        }
                    }
                    ?>
                    <?php if ($can_leave): ?>
                        <button id="leaveGroupBtn" class="btn btn-sm btn-outline-danger">Leave group</button>
                    <?php else: ?>
                        <small class="text-muted">Transfer ownership before leaving.</small>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="mb-3">You are not a member of this group. <button id="joinBtn" class="btn btn-sm btn-primary">Join group</button></div>
            <?php endif; ?>

            <?php if ($membership && in_array($membership, ['owner', 'admin'])): ?>
                <hr>
                <h4>Add member</h4>
                <form id="addMemberForm" class="mb-3">
                    <div class="mb-2">
                        <label class="form-label">User email</label>
                        <input name="email" type="email" class="form-control" required placeholder="user@example.com">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="member">Member</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button class="btn btn-success" type="button" id="addMemberBtn">Add member</button>
                    <div id="addMemberMsg" class="mt-2"></div>
                </form>
            <?php endif; ?>

            <h4>Members</h4>
            <ul id="membersList" class="list-group mb-3">
                <?php foreach ($members as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-member-id="<?= (int)$m['id'] ?>">
                        <div><?= htmlspecialchars($m['name']) ?> <div class="small text-muted"><?= htmlspecialchars($m['email']) ?></div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="small text-muted me-3"><?= htmlspecialchars($m['role']) ?></div>
                            <?php
                            // show remove button if current user is owner (can remove anyone except self)
                            // or if current user is admin (can remove plain members only)
                            $canRemove = false;
                            if ($membership === 'owner' && (int)$m['id'] !== (int)$userId) {
                                $canRemove = true;
                            } elseif ($membership === 'admin' && $m['role'] === 'member' && (int)$m['id'] !== (int)$userId) {
                                $canRemove = true;
                            }
                            ?>
                            <?php if ($canRemove): ?>
                                <button class="btn btn-sm btn-outline-danger remove-member-btn" data-member-id="<?= (int)$m['id'] ?>">Remove</button>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($membership && in_array($membership, ['owner', 'admin'])): ?>
                <h4>Pending invites</h4>
                <?php if (empty($invites)): ?>
                    <p id="noInvitesMsg">No pending invites.</p>
                <?php else: ?>
                    <ul id="invitesList" class="list-group mb-3">
                        <?php foreach ($invites as $inv): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center" data-invite-id="<?= (int)$inv['id'] ?>">
                                <div>
                                    <?= htmlspecialchars($inv['email']) ?>
                                    <div class="small text-muted">Invited as <?= htmlspecialchars($inv['role']) ?> by <?= htmlspecialchars($inv['invited_by_name'] ?? 'System') ?> · <?= htmlspecialchars(time_ago($inv['created_at'])) ?></div>
                                </div>
                                <div>
                                    <?php if ($inv['accepted_at']): ?>
                                        <a href="user.php?id=<?= (int)$inv['accepted_by'] ?>"><?= htmlspecialchars($inv['accepted_by_name'] ?? 'Member') ?></a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary resend-invite" data-invite-id="<?= (int)$inv['id'] ?>">Resend</button>
                                        <button class="btn btn-sm btn-outline-danger cancel-invite" data-invite-id="<?= (int)$inv['id'] ?>">Cancel</button>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endif; ?>

            <h4>Group goals</h4>
            <?php if (!$goals): ?>
                <p>No group goals yet.</p>
            <?php else: ?>
                <ul class="list-group mb-3">
                    <?php foreach ($goals as $g): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold d-flex align-items-center justify-content-between">
                                    <div>
                                        <span class="badge bg-info text-white me-2">Group</span>
                                        <a href="goal.php?id=<?= (int)$g['id'] ?>"><?= htmlspecialchars($g['title']) ?></a>
                                    </div>
                                    <div>
                                        <?php
                                        $canDelete = false;
                                        if ((int)$g['created_by'] === (int)$userId) $canDelete = true;
                                        if ($membership && in_array($membership, ['owner', 'admin'])) $canDelete = true;
                                        ?>
                                        <?php if ($canDelete): ?>
                                            <button class="btn btn-sm btn-outline-danger delete-goal" data-goal-id="<?= (int)$g['id'] ?>">Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
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

    <script>
        // Server-side hint so we can confirm the gid value even if group.js doesn't load
        console.info('group.php server gid', <?= json_encode($gid) ?>);
        // Debug: print fetched group goals so we can see what the server returned
        console.info('group.php goals', <?= json_encode($goals) ?>);
    </script>

    <?php include __DIR__ . '/includes/footer.php'; ?>