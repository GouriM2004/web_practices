<?php
$page_title = 'Accept Invite';
$page_scripts = ['assets/js/group.js'];
include __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../src/bootstrap.php';

$token = $_GET['token'] ?? ($_POST['token'] ?? null);
if (!$token) {
    echo '<div class="container mt-4"><div class="alert alert-danger">Missing invite token.</div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// find invite
$stmt = $pdo->prepare('SELECT gi.*, g.name as group_name FROM group_invites gi LEFT JOIN groups_tbl g ON g.id = gi.group_id WHERE gi.token = ?');
$stmt->execute([$token]);
$invite = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$invite) {
    echo '<div class="container mt-4"><div class="alert alert-danger">Invalid or expired invite token.</div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$userId = $_SESSION['user_id'] ?? null;

// If user already logged in, accept invite immediately
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'accept' && $userId) {
    try {
        // ensure membership
        $stmtChk = $pdo->prepare('SELECT id FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtChk->execute([$invite['group_id'], $userId]);
        if (!$stmtChk->fetchColumn()) {
            $stmtIns = $pdo->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
            $stmtIns->execute([$invite['group_id'], $userId, $invite['role']]);
        }
        // mark invite accepted
        $stmtUpd = $pdo->prepare('UPDATE group_invites SET accepted_by = ?, accepted_at = NOW() WHERE id = ?');
        $stmtUpd->execute([$userId, $invite['id']]);
        // log
        $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
        $stmtAct->execute([$userId, $invite['group_id'], 'invite_accepted', json_encode(['invite_id' => (int)$invite['id']])]);
        header('Location: group.php?id=' . (int)$invite['group_id']);
        exit;
    } catch (Exception $e) {
        echo '<div class="container mt-4"><div class="alert alert-danger">Error accepting invite: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
        include __DIR__ . '/includes/footer.php';
        exit;
    }
}

// If user not logged in and submitted quick-join, create a temporary account and accept
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'quick_join' && ! $userId) {
    $email = $invite['email'];
    // create a name from email local part
    $name = explode('@', $email)[0];
    $randomPassword = bin2hex(random_bytes(6));
    $hash = password_hash($randomPassword, PASSWORD_DEFAULT);
    try {
        $pdo->beginTransaction();
        $stmtInsU = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmtInsU->execute([$name, $email, $hash]);
        $newId = (int)$pdo->lastInsertId();
        // add membership
        $stmtIns = $pdo->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
        $stmtIns->execute([$invite['group_id'], $newId, $invite['role']]);
        // mark invite accepted
        $stmtUpd = $pdo->prepare('UPDATE group_invites SET accepted_by = ?, accepted_at = NOW() WHERE id = ?');
        $stmtUpd->execute([$newId, $invite['id']]);
        // log
        $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
        $stmtAct->execute([$newId, $invite['group_id'], 'invite_accepted', json_encode(['invite_id' => (int)$invite['id']])]);
        $pdo->commit();
        // set session and show credentials
        $_SESSION['user_id'] = $newId;
        echo '<div class="container mt-4"><div class="alert alert-success">Invite accepted. An account was created for ' . htmlspecialchars($email) . '.</div>';
        echo '<div class="card"><div class="card-body"><h5>Temporary credentials</h5><p><strong>Email:</strong> ' . htmlspecialchars($email) . '<br><strong>Password:</strong> ' . htmlspecialchars($randomPassword) . '</p><p>Please change your password in your profile.</p>';
        echo '<p><a class="btn btn-primary" href="group.php?id=' . (int)$invite['group_id'] . '">Open group</a></p></div></div></div>';
        include __DIR__ . '/includes/footer.php';
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo '<div class="container mt-4"><div class="alert alert-danger">Error creating account: ' . htmlspecialchars($e->getMessage()) . '</div></div>';
        include __DIR__ . '/includes/footer.php';
        exit;
    }
}

// Render invite page
?>
<div class="container mt-4">
    <div class="card">
        <div class="card-body">
            <h3 class="card-title">You're invited to join "<?= htmlspecialchars($invite['group_name'] ?? 'this group') ?>"</h3>
            <p class="text-muted">Invited email: <strong><?= htmlspecialchars($invite['email']) ?></strong></p>
            <p>Role: <strong><?= htmlspecialchars($invite['role']) ?></strong></p>
            <?php if (!empty($invite['accepted_at'])): ?>
                <div class="alert alert-info">This invite was already accepted on <?= htmlspecialchars($invite['accepted_at']) ?></div>
            <?php else: ?>
                <?php if (! $userId): ?>
                    <p>You are not signed in. You can either create an account (recommended) or quickly join now; a temporary account will be created and credentials shown to you.</p>
                    <form method="post">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <button name="action" value="quick_join" class="btn btn-primary">Quick join (create account and accept)</button>
                    </form>
                    <hr>
                    <p>Or <a href="register.php">register</a> with the same email to accept the invite automatically.</p>
                <?php else: ?>
                    <p>You are signed in. Click below to accept the invite and join the group.</p>
                    <form method="post">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <button name="action" value="accept" class="btn btn-success">Accept invite and join</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php';
