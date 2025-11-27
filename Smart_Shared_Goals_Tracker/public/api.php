<?php
require_once __DIR__ . '/../src/bootstrap.php';

// Very small API dispatcher for initial development
header('Content-Type: application/json; charset=utf-8');

// start session early for auth routes
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// normalize base path if served from subfolder
$base = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$path = preg_replace('#^' . preg_quote($base) . '#', '', $path);
$path = trim($path, '/');

// normalize requests that come in as api.php/... (e.g. fetch('api.php/register'))
if (strpos($path, 'api.php/') === 0) {
    $path = substr($path, strlen('api.php/'));
}
// also handle if path equals 'api.php'
if ($path === 'api.php') {
    $path = '';
}

// simple helpers
// ------------------
// Chat / Messages
// POST /api/threads  { group_id, goal_id, parent_id?, body }
// GET  /api/threads?group_id=...&goal_id=...&since=YYYY-mm-dd HH:MM:SS
// ------------------
if ($method === 'POST' && $path === 'threads') {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $group_id = isset($data['group_id']) ? (int)$data['group_id'] : null;
    $goal_id = isset($data['goal_id']) ? (int)$data['goal_id'] : null;
    $parent_id = isset($data['parent_id']) ? (int)$data['parent_id'] : null;
    $body = trim($data['body'] ?? '');
    if ($body === '') {
        jsonErr('Empty message', 422);
        exit;
    }

    // permission checks
    if ($group_id) {
        $stmt = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$group_id, $uid]);
        if (!$stmt->fetchColumn()) {
            jsonErr('Not a member of group', 403);
            exit;
        }
    }
    if ($goal_id) {
        $stmt = $pdo->prepare('SELECT g.id FROM goals g LEFT JOIN group_members gm ON gm.group_id = g.group_id WHERE g.id = ? AND (g.created_by = ? OR gm.user_id = ?)');
        $stmt->execute([$goal_id, $uid, $uid]);
        if (!$stmt->fetchColumn()) {
            jsonErr('Not allowed on this goal', 403);
            exit;
        }
    }

    $stmt = $pdo->prepare('INSERT INTO messages (group_id, goal_id, user_id, parent_id, body) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$group_id, $goal_id, $uid, $parent_id, $body]);
    $msg_id = (int)$pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT m.*, u.name as user_name, u.email as user_email FROM messages m JOIN users u ON u.id = m.user_id WHERE m.id = ?');
    $stmt->execute([$msg_id]);
    $msg = $stmt->fetch(PDO::FETCH_ASSOC);
    jsonOk(['message' => $msg]);
    exit;
}

if ($method === 'GET' && $path === 'threads') {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
    $goal_id = isset($_GET['goal_id']) ? (int)$_GET['goal_id'] : null;
    $since = isset($_GET['since']) ? $_GET['since'] : null;

    $params = [];
    $sql = 'SELECT m.*, u.name as user_name, u.email as user_email FROM messages m JOIN users u ON u.id = m.user_id WHERE 1=1 ';
    if ($group_id) {
        $stmt = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$group_id, $uid]);
        if (!$stmt->fetchColumn()) {
            jsonErr('Not a member of group', 403);
            exit;
        }
        $sql .= ' AND m.group_id = ?';
        $params[] = $group_id;
    }
    if ($goal_id) {
        $sql .= ' AND m.goal_id = ?';
        $params[] = $goal_id;
    }
    if ($since) {
        $sql .= ' AND m.created_at > ?';
        $params[] = $since;
    }
    $sql .= ' ORDER BY m.created_at ASC LIMIT 100';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonOk(['messages' => $rows]);
    exit;
}
function jsonOk($data = [])
{
    echo json_encode(array_merge(['status' => 'ok'], $data));
}
function jsonErr($msg, $code = 400)
{
    http_response_code($code);
    echo json_encode(['error' => $msg]);
}

if ($path === '' || $path === 'api.php') {
    jsonOk(['message' => 'Smart Shared Goals API']);
    exit;
}

// Health check
if ($method === 'GET' && $path === 'health') {
    jsonOk(['time' => date(DATE_ATOM)]);
    exit;
}

// POST /api/register -> register user
if ($method === 'POST' && preg_match('#^register$#', $path)) {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($data['email']) || empty($data['password']) || empty($data['name'])) {
        jsonErr('Missing fields', 422);
        exit;
    }
    // check existing
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        jsonErr('Email already exists', 409);
        exit;
    }
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    $stmt->execute([$data['name'], $data['email'], $hash]);
    $id = $pdo->lastInsertId();
    // After creating a user, check for any pending invites to groups and accept them automatically
    try {
        $stmtInv = $pdo->prepare('SELECT id, group_id, role FROM group_invites WHERE email = ? AND accepted_by IS NULL');
        $stmtInv->execute([$data['email']]);
        $invites = $stmtInv->fetchAll(PDO::FETCH_ASSOC);
        foreach ($invites as $inv) {
            // add membership if not already present
            $stmtChk = $pdo->prepare('SELECT id FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmtChk->execute([$inv['group_id'], $id]);
            if (!$stmtChk->fetchColumn()) {
                $stmtIns = $pdo->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
                $stmtIns->execute([$inv['group_id'], $id, $inv['role']]);
            }
            // mark invite accepted
            $stmtUpd = $pdo->prepare('UPDATE group_invites SET accepted_by = ?, accepted_at = NOW() WHERE id = ?');
            $stmtUpd->execute([$id, $inv['id']]);
            // log activity
            try {
                $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
                $stmtAct->execute([$id, $inv['group_id'], 'invite_accepted', json_encode(['invite_id' => (int)$inv['id']])]);
            } catch (Exception $e) {
                // ignore activity logging errors
            }
        }
    } catch (Exception $e) {
        // ignore invite processing errors for now
    }

    jsonOk(['user_id' => (int)$id]);
    exit;
}

// POST /api/login -> simple session-based login (for demo only)
if ($method === 'POST' && preg_match('#^login$#', $path)) {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($data['email']) || empty($data['password'])) {
        jsonErr('Missing fields', 422);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ?');
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $ok = false;
    if ($user && !empty($user['password'])) {
        $ok = password_verify($data['password'], $user['password']);
    }

    if (!$ok) {
        jsonErr('Invalid credentials', 401);
        exit;
    }
    // set session
    $_SESSION['user_id'] = (int)$user['id'];
    jsonOk(['user_id' => (int)$user['id']]);
    exit;
}

// POST /api/goals/{id}/checkins -> single checkin (supports client_idempotency_key)
if ($method === 'POST' && preg_match('#^goals/(\d+)/checkins$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $goalId = (int)$m[1];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $clientKey = $data['client_idempotency_key'] ?? null;
    $date = $data['date'] ?? date('Y-m-d');
    $value = $data['value'] ?? null;
    $note = $data['note'] ?? null;
    if (!$clientKey) {
        jsonErr('Missing idempotency key', 422);
        exit;
    }

    try {
        $pdo->beginTransaction();
        // check key
        $stmtCheckKey = $pdo->prepare('SELECT checkin_id FROM idempotency_keys WHERE client_key = ?');
        $stmtCheckKey->execute([$clientKey]);
        $existingKey = $stmtCheckKey->fetchColumn();
        if ($existingKey) {
            $pdo->commit();
            jsonOk(['checkin_id' => (int)$existingKey]);
            exit;
        }

        // check existing same-day
        $stmtCheckExisting = $pdo->prepare('SELECT id FROM checkins WHERE goal_id = ? AND user_id = ? AND date = ?');
        $stmtCheckExisting->execute([$goalId, $_SESSION['user_id'], $date]);
        $existing = $stmtCheckExisting->fetchColumn();
        if ($existing) {
            // map key
            $stmtInsertKey = $pdo->prepare('INSERT INTO idempotency_keys (client_key, checkin_id) VALUES (?, ?)');
            try {
                $stmtInsertKey->execute([$clientKey, $existing]);
            } catch (\Exception $e) {
            }
            $pdo->commit();
            jsonErr('Check-in already exists', 409);
            exit;
        }

        // dependency checks: ensure any 'requires' dependencies are satisfied before inserting
        $stmtDeps = $pdo->prepare('SELECT d.id, d.object_goal_id, d.relation_type, g.title AS object_title FROM goal_dependencies d JOIN goals g ON g.id = d.object_goal_id WHERE d.subject_goal_id = ? AND d.relation_type = ?');
        $stmtDeps->execute([$goalId, 'requires']);
        $requires = $stmtDeps->fetchAll(PDO::FETCH_ASSOC);
        foreach ($requires as $req) {
            // check for a checkin for the required goal on the same date created before now
            $stmtReq = $pdo->prepare('SELECT id, created_at FROM checkins WHERE goal_id = ? AND user_id = ? AND date = ? ORDER BY created_at DESC LIMIT 1');
            $stmtReq->execute([$req['object_goal_id'], $_SESSION['user_id'], $date]);
            $r = $stmtReq->fetch(PDO::FETCH_ASSOC);
            if (!$r) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                jsonErr('Dependency not satisfied: please complete "' . ($req['object_title'] ?? 'required goal') . '" before this goal', 409);
                exit;
            }
            // ensure it was created before now (sanity)
            if (strtotime($r['created_at']) > time()) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                jsonErr('Dependency check failed: required goal check-in is in the future', 409);
                exit;
            }
        }

        // insert
        $stmtInsert = $pdo->prepare('INSERT INTO checkins (goal_id, user_id, value, note, date) VALUES (?, ?, ?, ?, ?)');
        $stmtInsert->execute([$goalId, $_SESSION['user_id'], $value, $note, $date]);
        $cid = (int)$pdo->lastInsertId();
        $stmtInsertKey = $pdo->prepare('INSERT INTO idempotency_keys (client_key, checkin_id) VALUES (?, ?)');
        $stmtInsertKey->execute([$clientKey, $cid]);

        // triggers: if this goal triggers other goals, create a lightweight checkin for the target goal (if missing)
        $stmtTrig = $pdo->prepare('SELECT d.id, d.object_goal_id, g.title AS object_title FROM goal_dependencies d JOIN goals g ON g.id = d.object_goal_id WHERE d.subject_goal_id = ? AND d.relation_type = ?');
        $stmtTrig->execute([$goalId, 'triggers']);
        $triggers = $stmtTrig->fetchAll(PDO::FETCH_ASSOC);
        foreach ($triggers as $t) {
            // skip if already exists for same date
            $stmtCheckTrig = $pdo->prepare('SELECT id FROM checkins WHERE goal_id = ? AND user_id = ? AND date = ?');
            $stmtCheckTrig->execute([$t['object_goal_id'], $_SESSION['user_id'], $date]);
            if ($stmtCheckTrig->fetchColumn()) continue;
            try {
                $stmtInsTrig = $pdo->prepare('INSERT INTO checkins (goal_id, user_id, value, note, date) VALUES (?, ?, ?, ?, ?)');
                $noteTrig = 'Auto-triggered by goal ' . $goalId;
                $stmtInsTrig->execute([$t['object_goal_id'], $_SESSION['user_id'], null, $noteTrig, $date]);
            } catch (Exception $e) {
                // ignore trigger insert errors
            }
        }

        // post-processing: streaks & badges
        $streakSvc = new \Services\StreakService($pdo);
        $badgeSvc = new \Services\BadgeService($pdo);
        $streak = $streakSvc->updateStreak($_SESSION['user_id'], $goalId, $date);
        $badges = $badgeSvc->evaluate($_SESSION['user_id'], $goalId);

        // Award XP for a successful check-in
        try {
            $xpAward = 10; // points per check-in (tunable)
            $stmtXp = $pdo->prepare('INSERT INTO user_xp (user_id, xp_total, level, updated_at) VALUES (?, ?, 0, NOW()) ON DUPLICATE KEY UPDATE xp_total = xp_total + ?, updated_at = NOW()');
            $stmtXp->execute([$_SESSION['user_id'], $xpAward, $xpAward]);
            // recompute level: simple rule: 1 level per 100 XP
            $stmtGetXp = $pdo->prepare('SELECT xp_total FROM user_xp WHERE user_id = ?');
            $stmtGetXp->execute([$_SESSION['user_id']]);
            $xpTotal = (int)$stmtGetXp->fetchColumn();
            $newLevel = (int)floor($xpTotal / 100);
            $stmtUpdLevel = $pdo->prepare('UPDATE user_xp SET level = ? WHERE user_id = ?');
            $stmtUpdLevel->execute([$newLevel, $_SESSION['user_id']]);
        } catch (Exception $e) {
            // don't block on XP awarding
        }

        // log activity: created check-in
        try {
            $stmtGoalGroup = $pdo->prepare('SELECT group_id FROM goals WHERE id = ?');
            $stmtGoalGroup->execute([$goalId]);
            $groupForGoal = $stmtGoalGroup->fetchColumn();
            $meta = json_encode(['value' => $value, 'note' => $note, 'date' => $date]);
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, goal_id, action, meta) VALUES (?, ?, ?, ?, ?)');
            $stmtAct->execute([$_SESSION['user_id'], $groupForGoal ?: null, $goalId, 'checkin_created', $meta]);
        } catch (Exception $e) {
            // don't block on activity logging
        }

        $pdo->commit();
        jsonOk(['checkin_id' => $cid, 'updated_streak' => $streak, 'awarded_badges' => $badges, 'awarded_xp' => $xpAward ?? 0, 'new_level' => $newLevel ?? null]);
        exit;
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonErr('Error creating checkin: ' . $e->getMessage(), 500);
        exit;
    }
}

// ----------------------------
// RESTful Goals resource
// - GET  /api/goals
// - POST /api/goals
// - GET  /api/goals/{id}
// - PATCH/PUT /api/goals/{id}
// - DELETE /api/goals/{id}
// ----------------------------

// GET /api/goals -> list goals visible to the user (own, group, public)
if ($method === 'GET' && preg_match('#^goals$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    // user groups
    $stmt = $pdo->prepare('SELECT group_id FROM group_members WHERE user_id = ?');
    $stmt->execute([$uid]);
    $grows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $placeholders = '';
    $params = [];
    if ($grows) {
        $placeholders = implode(',', array_fill(0, count($grows), '?'));
        $params = $grows;
    }
    // build query: own goals OR group goals in user's groups OR public goals
    $sql = 'SELECT * FROM goals WHERE active = 1 AND (created_by = ? OR visibility = "public"';
    $qparams = [$uid];
    if ($placeholders) {
        $sql .= ' OR group_id IN (' . $placeholders . ')';
        $qparams = array_merge($qparams, $params);
    }
    $sql .= ') ORDER BY created_at DESC LIMIT 200';
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute($qparams);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    jsonOk(['goals' => $rows]);
    exit;
}

// POST /api/goals -> create a goal
if ($method === 'POST' && preg_match('#^goals$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($data['title'])) {
        jsonErr('Missing title', 422);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $title = $data['title'];
    $desc = $data['description'] ?? null;
    $groupId = isset($data['group_id']) ? (int)$data['group_id'] : null;
    $cadence = in_array($data['cadence'] ?? 'daily', ['daily', 'weekly', 'monthly']) ? $data['cadence'] : 'daily';
    $target = isset($data['target_value']) ? (int)$data['target_value'] : null;
    $unit = $data['unit'] ?? null;
    $start = $data['start_date'] ?? null;
    $end = $data['end_date'] ?? null;
    $visibility = in_array($data['visibility'] ?? 'private', ['public', 'private']) ? $data['visibility'] : 'private';
    try {
        if ($groupId) {
            $stmt = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmt->execute([$groupId, $uid]);
            if (!$stmt->fetchColumn()) {
                jsonErr('Not a member of target group', 403);
                exit;
            }
        }
        $stmt = $pdo->prepare('INSERT INTO goals (title, description, created_by, group_id, cadence, target_value, unit, start_date, end_date, visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $desc, $uid, $groupId, $cadence, $target, $unit, $start, $end, $visibility]);
        $gid = (int)$pdo->lastInsertId();
        jsonOk(['goal_id' => $gid]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error creating goal: ' . $e->getMessage(), 500);
        exit;
    }
}

// GET /api/goals/{id} -> get goal details (respect visibility)
if ($method === 'GET' && preg_match('#^goals/(\d+)$#', $path, $m)) {
    $goalId = (int)$m[1];
    $stmt = $pdo->prepare('SELECT * FROM goals WHERE id = ? AND active = 1');
    $stmt->execute([$goalId]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$g) {
        jsonErr('Goal not found', 404);
        exit;
    }
    // visibility check
    $uid = $_SESSION['user_id'] ?? null;
    if ($g['visibility'] === 'private') {
        if (empty($uid) || ((int)$g['created_by'] !== (int)$uid)) {
            // check group admin
            if (empty($g['group_id']) || empty($uid)) {
                jsonErr('Not allowed', 403);
                exit;
            }
            $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmtRole->execute([$g['group_id'], $uid]);
            $role = $stmtRole->fetchColumn();
            if (!in_array($role, ['owner', 'admin'])) {
                jsonErr('Not allowed', 403);
                exit;
            }
        }
    }
    // attach per-user difficulty/multiplier if viewer is authenticated
    $viewer = $_SESSION['user_id'] ?? null;
    if ($viewer) {
        try {
            $stmtD = $pdo->prepare('SELECT difficulty, multiplier, last_adjusted, note FROM goal_user_difficulty WHERE goal_id = ? AND user_id = ?');
            $stmtD->execute([$goalId, $viewer]);
            $ud = $stmtD->fetch(PDO::FETCH_ASSOC);
            if ($ud) {
                $g['user_difficulty'] = [
                    'difficulty' => $ud['difficulty'],
                    'multiplier' => (float)$ud['multiplier'],
                    'last_adjusted' => $ud['last_adjusted'],
                    'note' => $ud['note']
                ];
            } else {
                // expose default goal difficulty
                $g['user_difficulty'] = ['difficulty' => $g['difficulty'] ?? 'medium', 'multiplier' => 1.0, 'last_adjusted' => null, 'note' => null];
            }
        } catch (Exception $e) {
            // ignore difficulty fetch errors
            $g['user_difficulty'] = ['difficulty' => $g['difficulty'] ?? 'medium', 'multiplier' => 1.0, 'last_adjusted' => null, 'note' => null];
        }
    }

    jsonOk(['goal' => $g]);
    exit;
}

// PATCH /api/goals/{id} -> update goal
if (($method === 'PATCH' || $method === 'PUT') && preg_match('#^goals/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $goalId = (int)$m[1];
    $stmt = $pdo->prepare('SELECT * FROM goals WHERE id = ?');
    $stmt->execute([$goalId]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$g) {
        jsonErr('Goal not found', 404);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $allowed = false;
    if ((int)$g['created_by'] === $uid) $allowed = true;
    if (!$allowed && $g['group_id']) {
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$g['group_id'], $uid]);
        $role = $stmtRole->fetchColumn();
        if (in_array($role, ['owner', 'admin'])) $allowed = true;
    }
    if (!$allowed) {
        jsonErr('Not allowed', 403);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $fields = [];
    $values = [];
    $allowedFields = ['title', 'description', 'cadence', 'target_value', 'unit', 'start_date', 'end_date', 'visibility', 'active', 'group_id'];
    foreach ($allowedFields as $f) {
        if (array_key_exists($f, $data)) {
            $fields[] = "$f = ?";
            $values[] = $data[$f];
        }
    }
    if (empty($fields)) {
        jsonErr('No updatable fields provided', 422);
        exit;
    }
    $values[] = $goalId;
    $sql = 'UPDATE goals SET ' . implode(', ', $fields) . ' , updated_at = NOW() WHERE id = ?';
    try {
        $stmtUpd = $pdo->prepare($sql);
        $stmtUpd->execute($values);
        jsonOk(['goal_id' => $goalId]);
        exit;
    } catch (Exception $e) {
        jsonErr('Update failed: ' . $e->getMessage(), 500);
        exit;
    }
}

// DELETE /api/goals/{id}
if ($method === 'DELETE' && preg_match('#^goals/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $goalId = (int)$m[1];
    $stmt = $pdo->prepare('SELECT * FROM goals WHERE id = ?');
    $stmt->execute([$goalId]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$g) {
        jsonErr('Goal not found', 404);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $allowed = false;
    if ((int)$g['created_by'] === $uid) $allowed = true;
    if (!$allowed && $g['group_id']) {
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$g['group_id'], $uid]);
        $role = $stmtRole->fetchColumn();
        if (in_array($role, ['owner', 'admin'])) $allowed = true;
    }
    if (!$allowed) {
        jsonErr('Not allowed', 403);
        exit;
    }
    try {
        // soft-delete by marking active = 0
        $stmtDel = $pdo->prepare('UPDATE goals SET active = 0, updated_at = NOW() WHERE id = ?');
        $stmtDel->execute([$goalId]);
        jsonOk(['deleted' => true]);
        exit;
    } catch (Exception $e) {
        jsonErr('Delete failed: ' . $e->getMessage(), 500);
        exit;
    }
}

// ----------------------------
// ----------------------------
// Goal dependencies endpoints
// - GET  /api/goals/{id}/dependencies  (list incoming + outgoing)
// - POST /api/goals/{id}/dependencies  (create dependency)
// - DELETE /api/goals/{id}/dependencies/{dep_id}
// ----------------------------

// GET /api/goals/{id}/dependencies -> list dependencies for a goal
if ($method === 'GET' && preg_match('#^goals/(\d+)/dependencies$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $goalId = (int)$m[1];
    try {
        $stmtOut = $pdo->prepare('SELECT d.id, d.object_goal_id, d.relation_type, g.title AS object_title FROM goal_dependencies d JOIN goals g ON g.id = d.object_goal_id WHERE d.subject_goal_id = ?');
        $stmtOut->execute([$goalId]);
        $out = $stmtOut->fetchAll(PDO::FETCH_ASSOC);
        $stmtIn = $pdo->prepare('SELECT d.id, d.subject_goal_id, d.relation_type, g.title AS subject_title FROM goal_dependencies d JOIN goals g ON g.id = d.subject_goal_id WHERE d.object_goal_id = ?');
        $stmtIn->execute([$goalId]);
        $in = $stmtIn->fetchAll(PDO::FETCH_ASSOC);
        jsonOk(['outgoing' => $out, 'incoming' => $in]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error fetching dependencies: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/goals/{id}/dependencies -> add dependency (subject = {id})
if ($method === 'POST' && preg_match('#^goals/(\d+)/dependencies$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $subjectId = (int)$m[1];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $objectId = isset($data['object_goal_id']) ? (int)$data['object_goal_id'] : null;
    $rel = $data['relation_type'] ?? 'requires';
    if (!$objectId || !in_array($rel, ['requires', 'triggers'])) {
        jsonErr('Missing or invalid fields', 422);
        exit;
    }
    if ($objectId === $subjectId) {
        jsonErr('A goal cannot depend on itself', 422);
        exit;
    }
    // permission: only goal owner or group admin
    $stmt = $pdo->prepare('SELECT * FROM goals WHERE id = ?');
    $stmt->execute([$subjectId]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$g) {
        jsonErr('Goal not found', 404);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $allowed = ((int)$g['created_by'] === $uid);
    if (!$allowed && $g['group_id']) {
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$g['group_id'], $uid]);
        $role = $stmtRole->fetchColumn();
        if (in_array($role, ['owner', 'admin'])) $allowed = true;
    }
    if (!$allowed) {
        jsonErr('Not allowed', 403);
        exit;
    }
    // ensure object exists
    $stmtObj = $pdo->prepare('SELECT id FROM goals WHERE id = ?');
    $stmtObj->execute([$objectId]);
    if (!$stmtObj->fetchColumn()) {
        jsonErr('Target goal not found', 404);
        exit;
    }
    try {
        $stmtIns = $pdo->prepare('INSERT INTO goal_dependencies (subject_goal_id, object_goal_id, relation_type) VALUES (?, ?, ?)');
        $stmtIns->execute([$subjectId, $objectId, $rel]);
        $depId = (int)$pdo->lastInsertId();
        jsonOk(['dependency_id' => $depId]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error creating dependency: ' . $e->getMessage(), 500);
        exit;
    }
}

// DELETE /api/goals/{id}/dependencies/{dep_id}
if ($method === 'DELETE' && preg_match('#^goals/(\d+)/dependencies/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $subjectId = (int)$m[1];
    $depId = (int)$m[2];
    // load dependency
    $stmt = $pdo->prepare('SELECT * FROM goal_dependencies WHERE id = ? AND subject_goal_id = ?');
    $stmt->execute([$depId, $subjectId]);
    $d = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$d) {
        jsonErr('Dependency not found', 404);
        exit;
    }
    // permission: same as creating
    $stmtG = $pdo->prepare('SELECT * FROM goals WHERE id = ?');
    $stmtG->execute([$subjectId]);
    $g = $stmtG->fetch(PDO::FETCH_ASSOC);
    if (!$g) {
        jsonErr('Goal not found', 404);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $allowed = ((int)$g['created_by'] === $uid);
    if (!$allowed && $g['group_id']) {
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$g['group_id'], $uid]);
        $role = $stmtRole->fetchColumn();
        if (in_array($role, ['owner', 'admin'])) $allowed = true;
    }
    if (!$allowed) {
        jsonErr('Not allowed', 403);
        exit;
    }
    try {
        $stmtDel = $pdo->prepare('DELETE FROM goal_dependencies WHERE id = ?');
        $stmtDel->execute([$depId]);
        jsonOk(['deleted' => $depId]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error deleting dependency: ' . $e->getMessage(), 500);
        exit;
    }
}

// ----------------------------
// Checkins resource helpers
// - GET  /api/goals/{id}/checkins  (list)
// - GET  /api/checkins/{id}       (get)
// - PATCH /api/checkins/{id}
// - DELETE /api/checkins/{id}
// ----------------------------

// GET /api/goals/{id}/checkins -> list checkins for goal
if ($method === 'GET' && preg_match('#^goals/(\d+)/checkins$#', $path, $m)) {
    $goalId = (int)$m[1];
    $stmt = $pdo->prepare('SELECT * FROM goals WHERE id = ? AND active = 1');
    $stmt->execute([$goalId]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$g) {
        jsonErr('Goal not found', 404);
        exit;
    }
    // permission: public or owner/group member/admin
    $uid = $_SESSION['user_id'] ?? null;
    if ($g['visibility'] === 'private') {
        if (empty($uid) || ((int)$g['created_by'] !== (int)$uid)) {
            if (empty($g['group_id']) || empty($uid)) {
                jsonErr('Not allowed', 403);
                exit;
            }
            $stmtRole = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmtRole->execute([$g['group_id'], $uid]);
            if (!$stmtRole->fetchColumn()) {
                jsonErr('Not allowed', 403);
                exit;
            }
        }
    }
    $userFilter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    if ($userFilter) {
        $stmt = $pdo->prepare('SELECT * FROM checkins WHERE goal_id = ? AND user_id = ? ORDER BY date DESC');
        $stmt->execute([$goalId, $userFilter]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM checkins WHERE goal_id = ? ORDER BY date DESC LIMIT 500');
        $stmt->execute([$goalId]);
    }
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonOk(['checkins' => $rows]);
    exit;
}

// GET /api/checkins/{id}
if ($method === 'GET' && preg_match('#^checkins/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $cid = (int)$m[1];
    $stmt = $pdo->prepare('SELECT * FROM checkins WHERE id = ?');
    $stmt->execute([$cid]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) {
        jsonErr('Checkin not found', 404);
        exit;
    }
    // owner or group admin
    $uid = (int)$_SESSION['user_id'];
    if ((int)$c['user_id'] !== $uid) {
        // check goal's group admin
        $stmtG = $pdo->prepare('SELECT group_id FROM goals WHERE id = ?');
        $stmtG->execute([$c['goal_id']]);
        $gid = $stmtG->fetchColumn();
        if (!$gid) {
            jsonErr('Not allowed', 403);
            exit;
        }
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $uid]);
        $role = $stmtRole->fetchColumn();
        if (!in_array($role, ['owner', 'admin'])) {
            jsonErr('Not allowed', 403);
            exit;
        }
    }
    jsonOk(['checkin' => $c]);
    exit;
}

// PATCH /api/checkins/{id}
if (($method === 'PATCH' || $method === 'PUT') && preg_match('#^checkins/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $cid = (int)$m[1];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $stmt = $pdo->prepare('SELECT * FROM checkins WHERE id = ?');
    $stmt->execute([$cid]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) {
        jsonErr('Checkin not found', 404);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $allowed = ((int)$c['user_id'] === $uid);
    if (!$allowed) {
        $stmtG = $pdo->prepare('SELECT group_id FROM goals WHERE id = ?');
        $stmtG->execute([$c['goal_id']]);
        $gid = $stmtG->fetchColumn();
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $uid]);
        $role = $stmtRole->fetchColumn();
        if (in_array($role, ['owner', 'admin'])) $allowed = true;
    }
    if (!$allowed) {
        jsonErr('Not allowed', 403);
        exit;
    }
    $fields = [];
    $values = [];
    foreach (['value', 'note', 'date'] as $f) {
        if (array_key_exists($f, $data)) {
            $fields[] = "$f = ?";
            $values[] = $data[$f];
        }
    }
    if (empty($fields)) {
        jsonErr('No updatable fields', 422);
        exit;
    }
    $values[] = $cid;
    $sql = 'UPDATE checkins SET ' . implode(', ', $fields) . ' WHERE id = ?';
    try {
        $stmtUpd = $pdo->prepare($sql);
        $stmtUpd->execute($values);
        jsonOk(['checkin_id' => $cid]);
        exit;
    } catch (Exception $e) {
        jsonErr('Update failed: ' . $e->getMessage(), 500);
        exit;
    }
}

// DELETE /api/checkins/{id}
if ($method === 'DELETE' && preg_match('#^checkins/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $cid = (int)$m[1];
    $stmt = $pdo->prepare('SELECT * FROM checkins WHERE id = ?');
    $stmt->execute([$cid]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$c) {
        jsonErr('Checkin not found', 404);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $allowed = ((int)$c['user_id'] === $uid);
    if (!$allowed) {
        $stmtG = $pdo->prepare('SELECT group_id FROM goals WHERE id = ?');
        $stmtG->execute([$c['goal_id']]);
        $gid = $stmtG->fetchColumn();
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $uid]);
        $role = $stmtRole->fetchColumn();
        if (in_array($role, ['owner', 'admin'])) $allowed = true;
    }
    if (!$allowed) {
        jsonErr('Not allowed', 403);
        exit;
    }
    try {
        $stmtDel = $pdo->prepare('DELETE FROM checkins WHERE id = ?');
        $stmtDel->execute([$cid]);
        jsonOk(['deleted' => true]);
        exit;
    } catch (Exception $e) {
        jsonErr('Delete failed: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/sync -> accept outbox items from PWA (requires session)
if ($method === 'POST' && preg_match('#^sync$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($data['items']) || !is_array($data['items'])) {
        jsonErr('Invalid payload', 422);
        exit;
    }

    $accepted = [];
    $conflicts = [];
    try {
        $pdo->beginTransaction();
        $stmtCheckKey = $pdo->prepare('SELECT checkin_id FROM idempotency_keys WHERE client_key = ?');
        $stmtCheckExisting = $pdo->prepare('SELECT id FROM checkins WHERE goal_id = ? AND user_id = ? AND date = ?');
        $stmtInsertCheckin = $pdo->prepare('INSERT INTO checkins (goal_id, user_id, value, note, date) VALUES (?, ?, ?, ?, ?)');
        $stmtInsertKey = $pdo->prepare('INSERT INTO idempotency_keys (client_key, checkin_id) VALUES (?, ?)');

        // prepare services for post-processing
        $streakSvc = new \Services\StreakService($pdo);
        $badgeSvc = new \Services\BadgeService($pdo);

        foreach ($data['items'] as $it) {
            $clientKey = $it['client_idempotency_key'] ?? null;
            $goalId = isset($it['goal_id']) ? (int)$it['goal_id'] : null;
            $date = $it['date'] ?? null;
            $value = isset($it['value']) ? $it['value'] : null;
            $note = $it['note'] ?? null;
            if (!$clientKey || !$goalId || !$date) {
                $conflicts[] = ['client_idempotency_key' => $clientKey, 'error' => 'missing_fields'];
                continue;
            }

            // already processed by client key?
            $stmtCheckKey->execute([$clientKey]);
            $found = $stmtCheckKey->fetchColumn();
            if ($found) {
                $accepted[] = ['client_idempotency_key' => $clientKey, 'checkin_id' => (int)$found];
                continue;
            }

            // any existing checkin for same goal/user/date?
            $stmtCheckExisting->execute([$goalId, $_SESSION['user_id'], $date]);
            $existing = $stmtCheckExisting->fetchColumn();
            if ($existing) {
                // map key to existing checkin to avoid future duplicates
                try {
                    $stmtInsertKey->execute([$clientKey, $existing]);
                } catch (
                    Exception $e
                ) {
                }
                $conflicts[] = ['client_idempotency_key' => $clientKey, 'status' => 'exists', 'checkin_id' => (int)$existing];
                continue;
            }

            // dependency checks for 'requires'
            $stmtDeps = $pdo->prepare('SELECT d.id, d.object_goal_id, d.relation_type, g.title AS object_title FROM goal_dependencies d JOIN goals g ON g.id = d.object_goal_id WHERE d.subject_goal_id = ? AND d.relation_type = ?');
            $stmtDeps->execute([$goalId, 'requires']);
            $requires = $stmtDeps->fetchAll(PDO::FETCH_ASSOC);
            $depFailed = false;
            foreach ($requires as $req) {
                $stmtReq = $pdo->prepare('SELECT id, created_at FROM checkins WHERE goal_id = ? AND user_id = ? AND date = ? ORDER BY created_at DESC LIMIT 1');
                $stmtReq->execute([$req['object_goal_id'], $_SESSION['user_id'], $date]);
                $r = $stmtReq->fetch(PDO::FETCH_ASSOC);
                if (!$r) {
                    $conflicts[] = ['client_idempotency_key' => $clientKey, 'error' => 'dependency_unsatisfied', 'missing_goal_id' => (int)$req['object_goal_id'], 'missing_goal_title' => $req['object_title'] ?? null];
                    $depFailed = true;
                    break;
                }
                if (strtotime($r['created_at']) > time()) {
                    $conflicts[] = ['client_idempotency_key' => $clientKey, 'error' => 'dependency_time_conflict', 'missing_goal_id' => (int)$req['object_goal_id']];
                    $depFailed = true;
                    break;
                }
            }
            if ($depFailed) continue;

            // insert
            $stmtInsertCheckin->execute([$goalId, $_SESSION['user_id'], $value, $note, $date]);
            $cid = (int)$pdo->lastInsertId();
            $stmtInsertKey->execute([$clientKey, $cid]);

            // update streaks and evaluate badges
            $streak = $streakSvc->updateStreak($_SESSION['user_id'], $goalId, $date);
            $badges = $badgeSvc->evaluate($_SESSION['user_id'], $goalId);

            // Award XP for this inserted check-in
            try {
                $xpAward = 10;
                $stmtXp = $pdo->prepare('INSERT INTO user_xp (user_id, xp_total, level, updated_at) VALUES (?, ?, 0, NOW()) ON DUPLICATE KEY UPDATE xp_total = xp_total + ?, updated_at = NOW()');
                $stmtXp->execute([$_SESSION['user_id'], $xpAward, $xpAward]);
                $stmtGetXp = $pdo->prepare('SELECT xp_total FROM user_xp WHERE user_id = ?');
                $stmtGetXp->execute([$_SESSION['user_id']]);
                $xpTotal = (int)$stmtGetXp->fetchColumn();
                $newLevel = (int)floor($xpTotal / 100);
                $stmtUpdLevel = $pdo->prepare('UPDATE user_xp SET level = ? WHERE user_id = ?');
                $stmtUpdLevel->execute([$newLevel, $_SESSION['user_id']]);
            } catch (Exception $e) {
                // ignore XP errors
            }

            // log activity for this inserted check-in
            try {
                $stmtGoalGroup = $pdo->prepare('SELECT group_id FROM goals WHERE id = ?');
                $stmtGoalGroup->execute([$goalId]);
                $groupForGoal = $stmtGoalGroup->fetchColumn();
                $meta = json_encode(['value' => $value, 'note' => $note, 'date' => $date]);
                $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, goal_id, action, meta) VALUES (?, ?, ?, ?, ?)');
                $stmtAct->execute([$_SESSION['user_id'], $groupForGoal ?: null, $goalId, 'checkin_created', $meta]);
            } catch (Exception $e) {
                // ignore activity logging errors
            }

            $accepted[] = ['client_idempotency_key' => $clientKey, 'checkin_id' => $cid, 'awarded_badges' => $badges, 'updated_streak' => $streak, 'awarded_xp' => $xpAward ?? 0, 'new_level' => $newLevel ?? null];

            // triggers: if this inserted goal triggers other goals, create lightweight checkins for them (if missing)
            try {
                $stmtTrig = $pdo->prepare('SELECT d.id, d.object_goal_id FROM goal_dependencies d WHERE d.subject_goal_id = ? AND d.relation_type = ?');
                $stmtTrig->execute([$goalId, 'triggers']);
                $trs = $stmtTrig->fetchAll(PDO::FETCH_ASSOC);
                foreach ($trs as $t) {
                    $stmtCheckTrig = $pdo->prepare('SELECT id FROM checkins WHERE goal_id = ? AND user_id = ? AND date = ?');
                    $stmtCheckTrig->execute([$t['object_goal_id'], $_SESSION['user_id'], $date]);
                    if ($stmtCheckTrig->fetchColumn()) continue;
                    try {
                        $stmtInsertCheckin->execute([$t['object_goal_id'], $_SESSION['user_id'], null, 'Auto-triggered by goal ' . $goalId, $date]);
                        // don't map idempotency keys for auto triggers
                    } catch (Exception $e) {
                        // ignore trigger insert errors
                    }
                }
            } catch (Exception $e) {
                // ignore trigger errors
            }
        }

        $pdo->commit();
        jsonOk(['accepted' => $accepted, 'conflicts' => $conflicts]);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonErr('Sync error: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/goals/{id}/visibility -> change visibility (owner or group admin only)
if ($method === 'POST' && preg_match('#^goals/(\d+)/visibility$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $goalId = (int)$m[1];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $vis = $data['visibility'] ?? null;
    if ($vis !== 'public' && $vis !== 'private') {
        jsonErr('Invalid visibility', 422);
        exit;
    }
    // load goal
    $stmt = $pdo->prepare('SELECT created_by, group_id FROM goals WHERE id = ?');
    $stmt->execute([$goalId]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$g) {
        jsonErr('Goal not found', 404);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $allowed = false;
    if ((int)$g['created_by'] === $uid) $allowed = true;
    if (!$allowed && $g['group_id']) {
        $stmt = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$g['group_id'], $uid]);
        $role = $stmt->fetchColumn();
        if (in_array($role, ['admin', 'owner'])) $allowed = true;
    }
    if (!$allowed) {
        jsonErr('Not allowed', 403);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE goals SET visibility = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$vis, $goalId]);
    jsonOk(['visibility' => $vis]);
    exit;
}

// GET /api/me -> current user (session)
if ($method === 'GET' && preg_match('#^me$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, name, email, avatar, bio, cover_photo, motivational_quote, show_streaks_public, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        // invalid session
        unset($_SESSION['user_id']);
        jsonErr('Not authenticated', 401);
        exit;
    }
    // attach XP info if available
    try {
        $stmtXp = $pdo->prepare('SELECT xp_total, level FROM user_xp WHERE user_id = ?');
        $stmtXp->execute([$_SESSION['user_id']]);
        $xpRow = $stmtXp->fetch(PDO::FETCH_ASSOC);
        if ($xpRow) {
            $user['xp'] = ['xp_total' => (int)$xpRow['xp_total'], 'level' => (int)$xpRow['level']];
        } else {
            $user['xp'] = ['xp_total' => 0, 'level' => 0];
        }
    } catch (Exception $e) {
        $user['xp'] = ['xp_total' => 0, 'level' => 0];
    }
    jsonOk(['user' => $user]);
    exit;
}

// GET /api/me/insights -> recent habit insights for current user
if ($method === 'GET' && preg_match('#^me/insights$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $goalId = isset($_GET['goal_id']) ? (int)$_GET['goal_id'] : null;
    try {
        if ($goalId) {
            $stmt = $pdo->prepare('SELECT id, goal_id, insight_type, payload, generated_at FROM user_habit_insights WHERE user_id = ? AND goal_id = ? ORDER BY generated_at DESC LIMIT 200');
            $stmt->execute([$uid, $goalId]);
        } else {
            $stmt = $pdo->prepare('SELECT id, goal_id, insight_type, payload, generated_at FROM user_habit_insights WHERE user_id = ? ORDER BY generated_at DESC LIMIT 200');
            $stmt->execute([$uid]);
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // decode payload JSON for convenience
        foreach ($rows as &$r) {
            $r['payload'] = json_decode($r['payload'], true);
        }
        unset($r);
        jsonOk(['insights' => $rows]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error fetching insights: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/me/profile -> update profile fields (bio, cover_photo, motivational_quote, show_streaks_public)
if ($method === 'POST' && preg_match('#^me/profile$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $bio = isset($data['bio']) ? $data['bio'] : null;
    $cover = isset($data['cover_photo']) ? $data['cover_photo'] : null;
    $quote = isset($data['motivational_quote']) ? $data['motivational_quote'] : null;
    $show = isset($data['show_streaks_public']) ? (int)(bool)$data['show_streaks_public'] : 0;
    try {
        // Note: some installations may not have `updated_at` column on `users`.
        // Avoid relying on it here to prevent SQL errors. If you want an `updated_at`
        // column, run the migration provided separately.
        $stmt = $pdo->prepare('UPDATE users SET bio = ?, cover_photo = ?, motivational_quote = ?, show_streaks_public = ? WHERE id = ?');
        $stmt->execute([$bio, $cover, $quote, $show, $_SESSION['user_id']]);
        // return updated user (reuse /me logic)
        $stmt2 = $pdo->prepare('SELECT id, name, email, avatar, bio, cover_photo, motivational_quote, show_streaks_public, created_at FROM users WHERE id = ?');
        $stmt2->execute([$_SESSION['user_id']]);
        $u = $stmt2->fetch(PDO::FETCH_ASSOC);
        jsonOk(['user' => $u]);
        exit;
    } catch (Exception $e) {
        jsonErr('Failed to update profile: ' . $e->getMessage(), 500);
        exit;
    }
}

// GET /api/users -> find user by email (query param: email)
if ($method === 'GET' && preg_match('#^users$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $q = $_GET['email'] ?? null;
    if (!$q) {
        jsonErr('Missing email', 422);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, name, email, bio, cover_photo, motivational_quote, show_streaks_public FROM users WHERE email = ?');
    $stmt->execute([$q]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        jsonErr('User not found', 404);
        exit;
    }
    $viewerId = (int)$_SESSION['user_id'];
    $ownerId = (int)$u['id'];
    $canViewProfile = $viewerId === $ownerId || ((int)($u['show_streaks_public'] ?? 0) === 1);
    if (!$canViewProfile) {
        unset($u['bio'], $u['cover_photo'], $u['motivational_quote'], $u['show_streaks_public']);
    }
    jsonOk(['user' => $u]);
    exit;
}

// POST /api/logout -> clear session
if ($method === 'POST' && preg_match('#^logout$#', $path)) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }
    jsonOk(['message' => 'logged out']);
    exit;
}

// ----------------------------
// Groups endpoints
// ----------------------------

// GET /api/groups -> list groups (memberships + public suggestions)
if ($method === 'GET' && preg_match('#^groups$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $uid = $_SESSION['user_id'];
    // groups where user is a member
    $stmt = $pdo->prepare('SELECT g.* FROM groups_tbl g JOIN group_members gm ON gm.group_id = g.id WHERE gm.user_id = ? ORDER BY g.created_at DESC');
    $stmt->execute([$uid]);
    $memberGroups = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // public groups not yet member (suggestions)
    $stmt2 = $pdo->prepare('SELECT g.* FROM groups_tbl g WHERE g.privacy = "public" AND g.id NOT IN (SELECT group_id FROM group_members WHERE user_id = ?) ORDER BY g.created_at DESC');
    $stmt2->execute([$uid]);
    $publicGroups = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    jsonOk(['member_groups' => $memberGroups, 'public_groups' => $publicGroups]);
    exit;
}

// GET /api/groups/{id}/leaderboard?period=weekly|monthly&metric=checkins|days|completed_goals
if ($method === 'GET' && preg_match('#^groups/(\d+)/leaderboard$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $groupId = (int)$m[1];
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $period = $_GET['period'] ?? 'weekly';
    $metric = $_GET['metric'] ?? 'checkins';

    // compute date range
    $end = date('Y-m-d');
    if ($period === 'monthly') {
        $start = date('Y-m-d', strtotime('-30 days'));
    } else { // default weekly
        $start = date('Y-m-d', strtotime('-7 days'));
    }

    // base query: aggregate checkins per user within date range for goals in this group
    // We'll return both 'checkins' (count) and 'days' (distinct active days). Badges list via GROUP_CONCAT.
    $sql = "SELECT u.id AS user_id, u.name, u.email,
            COUNT(c.id) AS checkins,
            COUNT(DISTINCT c.date) AS days_active,
            GROUP_CONCAT(DISTINCT b.slug ORDER BY ub.awarded_at DESC SEPARATOR ',') AS badges
            FROM users u
            JOIN checkins c ON c.user_id = u.id
            JOIN goals g ON g.id = c.goal_id AND g.group_id = ?
            LEFT JOIN user_badges ub ON ub.user_id = u.id
            LEFT JOIN badges b ON b.id = ub.badge_id
            WHERE c.date BETWEEN ? AND ?
              AND (
                  g.visibility = 'public'
                  OR g.created_by = ?
                  OR EXISTS (SELECT 1 FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = ? AND gm.role IN ('owner','admin'))
              )
            GROUP BY u.id
        ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$groupId, $start, $end, $uid, $groupId, $uid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        jsonErr('Error computing leaderboard: ' . $e->getMessage(), 500);
        exit;
    }

    // compute ranking value depending on requested metric
    foreach ($rows as &$r) {
        $r['checkins'] = (int)$r['checkins'];
        $r['days_active'] = (int)$r['days_active'];
        $r['badges'] = $r['badges'] ? explode(',', $r['badges']) : [];
        if ($metric === 'days') $r['score'] = $r['days_active'];
        else $r['score'] = $r['checkins'];
    }
    unset($r);

    // sort by score desc
    usort($rows, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    // attach rank and limit to top 50
    $rows = array_slice($rows, 0, 50);
    $ranked = [];
    $rank = 1;
    foreach ($rows as $r) {
        $r['rank'] = $rank++;
        $ranked[] = $r;
    }

    jsonOk(['period' => $period, 'metric' => $metric, 'start' => $start, 'end' => $end, 'leaders' => $ranked]);
    exit;
}

// ----------------------------
// Challenges
// ----------------------------
// POST /api/challenges -> create a challenge
if ($method === 'POST' && preg_match('#^challenges$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($data['title']) || empty($data['duration_days'])) {
        jsonErr('Missing fields', 422);
        exit;
    }
    $title = $data['title'];
    $desc = $data['description'] ?? null;
    $groupId = isset($data['group_id']) ? (int)$data['group_id'] : null;
    $startDate = $data['start_date'] ?? date('Y-m-d');
    $duration = (int)$data['duration_days'];
    $cadence = in_array($data['cadence'] ?? 'daily', ['daily', 'weekly', 'monthly']) ? $data['cadence'] : 'daily';
    $unit = $data['unit'] ?? null;
    $target = isset($data['target_value']) ? (int)$data['target_value'] : null;

    try {
        // if group specified, ensure user is a member
        if ($groupId) {
            $stmt = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmt->execute([$groupId, $_SESSION['user_id']]);
            if (!$stmt->fetchColumn()) {
                jsonErr('Not a member of target group', 403);
                exit;
            }
        }
        $stmt = $pdo->prepare('INSERT INTO challenges (title, description, group_id, created_by, start_date, duration_days, cadence, unit, target_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $desc, $groupId, $_SESSION['user_id'], $startDate, $duration, $cadence, $unit, $target]);
        $cid = (int)$pdo->lastInsertId();
        jsonOk(['challenge_id' => $cid]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error creating challenge: ' . $e->getMessage(), 500);
        exit;
    }
}

// GET /api/challenges[?group_id=...] -> list challenges
if ($method === 'GET' && preg_match('#^challenges$#', $path)) {
    $groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
    try {
        if ($groupId) {
            $stmt = $pdo->prepare('SELECT * FROM challenges WHERE group_id = ? ORDER BY created_at DESC');
            $stmt->execute([$groupId]);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM challenges ORDER BY created_at DESC');
            $stmt->execute([]);
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonOk(['challenges' => $rows]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error listing challenges: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/challenges/{id}/join -> join a challenge (creates a personal goal for user)
if ($method === 'POST' && preg_match('#^challenges/(\d+)/join$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $challengeId = (int)$m[1];
    try {
        $stmt = $pdo->prepare('SELECT * FROM challenges WHERE id = ?');
        $stmt->execute([$challengeId]);
        $ch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ch) {
            jsonErr('Challenge not found', 404);
            exit;
        }

        // ensure not already participant
        $stmtChk = $pdo->prepare('SELECT id FROM challenge_participants WHERE challenge_id = ? AND user_id = ?');
        $stmtChk->execute([$challengeId, $_SESSION['user_id']]);
        if ($stmtChk->fetchColumn()) {
            jsonErr('Already joined', 409);
            exit;
        }

        // create a goal for this participant to track progress
        $title = 'Challenge: ' . $ch['title'];
        $start = $ch['start_date'] ?: date('Y-m-d');
        $end = date('Y-m-d', strtotime($start . ' + ' . ((int)$ch['duration_days'] - 1) . ' days'));
        $stmtG = $pdo->prepare('INSERT INTO goals (title, description, created_by, group_id, cadence, target_value, unit, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmtG->execute([$title, $ch['description'], $_SESSION['user_id'], $ch['group_id'], $ch['cadence'], $ch['target_value'], $ch['unit'], $start, $end]);
        $goalId = (int)$pdo->lastInsertId();

        // map goal to challenge and participant
        $stmtMap = $pdo->prepare('INSERT INTO challenge_goals (challenge_id, goal_id) VALUES (?, ?)');
        $stmtMap->execute([$challengeId, $goalId]);
        $stmtPart = $pdo->prepare('INSERT INTO challenge_participants (challenge_id, user_id, goal_id) VALUES (?, ?, ?)');
        $stmtPart->execute([$challengeId, $_SESSION['user_id'], $goalId]);

        jsonOk(['challenge_id' => $challengeId, 'goal_id' => $goalId]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error joining challenge: ' . $e->getMessage(), 500);
        exit;
    }
}

// GET /api/challenges/{id}/stats -> participation and success metrics
if ($method === 'GET' && preg_match('#^challenges/(\d+)/stats$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $challengeId = (int)$m[1];
    try {
        $stmt = $pdo->prepare('SELECT * FROM challenges WHERE id = ?');
        $stmt->execute([$challengeId]);
        $ch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ch) {
            jsonErr('Not found', 404);
            exit;
        }

        $duration = (int)$ch['duration_days'];
        $start = $ch['start_date'] ?: null;
        // fetch participants and compute progress by counting distinct checkin days for their goal
        $stmt = $pdo->prepare('SELECT cp.user_id, cp.goal_id, u.name, u.email FROM challenge_participants cp JOIN users u ON u.id = cp.user_id WHERE cp.challenge_id = ?');
        $stmt->execute([$challengeId]);
        $parts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results = [];
        $totalPct = 0;
        $successCount = 0;
        foreach ($parts as $p) {
            if (!$p['goal_id']) {
                $pct = 0;
            } else {
                if ($start) {
                    $stmtCnt = $pdo->prepare('SELECT COUNT(DISTINCT date) FROM checkins WHERE goal_id = ? AND user_id = ? AND date BETWEEN ? AND ?');
                    $stmtCnt->execute([$p['goal_id'], $p['user_id'], $start, date('Y-m-d', strtotime($start . ' + ' . ($duration - 1) . ' days'))]);
                } else {
                    // fallback: count within goal dates
                    $stmtGoal = $pdo->prepare('SELECT start_date, end_date FROM goals WHERE id = ?');
                    $stmtGoal->execute([$p['goal_id']]);
                    $g = $stmtGoal->fetch(PDO::FETCH_ASSOC);
                    if ($g && $g['start_date'] && $g['end_date']) {
                        $stmtCnt = $pdo->prepare('SELECT COUNT(DISTINCT date) FROM checkins WHERE goal_id = ? AND user_id = ? AND date BETWEEN ? AND ?');
                        $stmtCnt->execute([$p['goal_id'], $p['user_id'], $g['start_date'], $g['end_date']]);
                    } else {
                        $stmtCnt = $pdo->prepare('SELECT COUNT(DISTINCT date) FROM checkins WHERE goal_id = ? AND user_id = ?');
                        $stmtCnt->execute([$p['goal_id'], $p['user_id']]);
                    }
                }
                $countDays = (int)$stmtCnt->fetchColumn();
                $pct = $duration > 0 ? round(($countDays / $duration) * 100, 1) : 0;
            }
            $success = $pct >= 80.0; // considered successful if >=80%
            $totalPct += $pct;
            if ($success) $successCount++;
            $results[] = ['user_id' => (int)$p['user_id'], 'name' => $p['name'], 'email' => $p['email'], 'goal_id' => $p['goal_id'], 'days_active' => $countDays ?? 0, 'pct' => $pct, 'success' => $success];
        }
        $avg = count($parts) ? round($totalPct / count($parts), 1) : 0;
        jsonOk(['challenge_id' => $challengeId, 'participants' => $results, 'average_pct' => $avg, 'success_count' => $successCount, 'total_participants' => count($parts)]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error computing stats: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/groups -> create group
if ($method === 'POST' && preg_match('#^groups$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($data['name'])) {
        jsonErr('Missing group name', 422);
        exit;
    }
    $name = $data['name'];
    $description = $data['description'] ?? null;
    $privacy = in_array($data['privacy'] ?? 'private', ['public', 'private']) ? $data['privacy'] : 'private';
    $code = $data['code'] ?? null;
    if (!$code) {
        // simple random code
        $code = substr(strtoupper(bin2hex(random_bytes(3))), 0, 6);
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO groups_tbl (name, description, code, privacy, created_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $description, $code, $privacy, $_SESSION['user_id']]);
        $gid = (int)$pdo->lastInsertId();
        // add membership as owner
        $stmtm = $pdo->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
        $stmtm->execute([$gid, $_SESSION['user_id'], 'owner']);
        // log activity
        $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
        $stmtAct->execute([$_SESSION['user_id'], $gid, 'group_created', json_encode(['name' => $name])]);
        $pdo->commit();
        jsonOk(['group_id' => $gid, 'code' => $code]);
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonErr('Error creating group: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/groups/{id}/join -> join group (public or with code)
if ($method === 'POST' && preg_match('#^groups/(\d+)/join$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $gid = (int)$m[1];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $code = $data['code'] ?? null;
    try {
        $stmt = $pdo->prepare('SELECT id, privacy, code FROM groups_tbl WHERE id = ?');
        $stmt->execute([$gid]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$group) {
            jsonErr('Group not found', 404);
            exit;
        }
        if ($group['privacy'] === 'private' && $group['code'] && $group['code'] !== $code) {
            jsonErr('Invalid group code', 403);
            exit;
        }
        // already member?
        $stmtm = $pdo->prepare('SELECT id FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtm->execute([$gid, $_SESSION['user_id']]);
        if ($stmtm->fetchColumn()) {
            jsonErr('Already a member', 409);
            exit;
        }
        $stmtInsert = $pdo->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
        $stmtInsert->execute([$gid, $_SESSION['user_id'], 'member']);
        // activity
        $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
        $stmtAct->execute([$_SESSION['user_id'], $gid, 'group_joined', json_encode([])]);
        jsonOk(['group_id' => $gid]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error joining group: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/groups/{id}/members -> add member by email (owner/admin only)
if ($method === 'POST' && preg_match('#^groups/(\d+)/members$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $gid = (int)$m[1];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim($data['email'] ?? '');
    $role = in_array($data['role'] ?? 'member', ['member', 'admin']) ? $data['role'] : 'member';
    if (!$email) {
        jsonErr('Missing email', 422);
        exit;
    }

    try {
        // check group exists
        $stmt = $pdo->prepare('SELECT id FROM groups_tbl WHERE id = ?');
        $stmt->execute([$gid]);
        if (!$stmt->fetchColumn()) {
            jsonErr('Group not found', 404);
            exit;
        }

        // ensure current user is owner or admin in group
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $_SESSION['user_id']]);
        $myRole = $stmtRole->fetchColumn();
        if (!in_array($myRole, ['owner', 'admin'])) {
            jsonErr('Forbidden: only owner/admin can add members', 403);
            exit;
        }

        // find user by email
        $stmtU = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmtU->execute([$email]);
        $uid = $stmtU->fetchColumn();
        if (!$uid) {
            jsonErr('User not found', 404);
            exit;
        }

        // check already a member
        $stmtChk = $pdo->prepare('SELECT id FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtChk->execute([$gid, $uid]);
        if ($stmtChk->fetchColumn()) {
            jsonErr('User already a member', 409);
            exit;
        }

        // insert membership
        $stmtIns = $pdo->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
        $stmtIns->execute([$gid, $uid, $role]);

        // activity log
        try {
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
            $stmtAct->execute([$_SESSION['user_id'], $gid, 'group_member_added', json_encode(['added_user_id' => (int)$uid, 'role' => $role])]);
        } catch (Exception $e) {
            // ignore
        }

        // return added user details for client-side update
        $stmtUser = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
        $stmtUser->execute([$uid]);
        $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
        jsonOk(['group_id' => $gid, 'user' => $userRow, 'role' => $role]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error adding member: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/groups/{id}/invites -> invite an email to the group (owner/admin only)
if ($method === 'POST' && preg_match('#^groups/(\d+)/invites$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $gid = (int)$m[1];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $email = trim($data['email'] ?? '');
    $role = in_array($data['role'] ?? 'member', ['member', 'admin']) ? $data['role'] : 'member';
    if (!$email) {
        jsonErr('Missing email', 422);
        exit;
    }

    try {
        // check group exists
        $stmt = $pdo->prepare('SELECT id FROM groups_tbl WHERE id = ?');
        $stmt->execute([$gid]);
        if (!$stmt->fetchColumn()) {
            jsonErr('Group not found', 404);
            exit;
        }

        // ensure current user is owner or admin in group
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $_SESSION['user_id']]);
        $myRole = $stmtRole->fetchColumn();
        if (!in_array($myRole, ['owner', 'admin'])) {
            jsonErr('Forbidden: only owner/admin can invite members', 403);
            exit;
        }

        // check whether the email belongs to an existing user
        $stmtU = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmtU->execute([$email]);
        $uid = $stmtU->fetchColumn();
        if ($uid) {
            // If user exists, add them directly to the group (owner/admin requested invite but user already has account)
            // check already a member
            $stmtChk = $pdo->prepare('SELECT id FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmtChk->execute([$gid, $uid]);
            if ($stmtChk->fetchColumn()) {
                jsonErr('User already a member', 409);
                exit;
            }

            // insert membership
            $stmtIns = $pdo->prepare('INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, ?)');
            $stmtIns->execute([$gid, $uid, $role]);

            // activity log
            try {
                $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
                $stmtAct->execute([$_SESSION['user_id'], $gid, 'group_member_added_via_invite', json_encode(['added_user_id' => (int)$uid, 'role' => $role])]);
            } catch (Exception $e) {
                // ignore
            }

            // return added user details for client-side update
            $stmtUser = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
            $stmtUser->execute([$uid]);
            $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
            jsonOk(['group_id' => $gid, 'user' => $userRow, 'role' => $role, 'added_via_invite' => true]);
            exit;
        }

        // create invite token and insert
        $token = bin2hex(random_bytes(16));
        try {
            $stmtInv = $pdo->prepare('INSERT INTO group_invites (group_id, email, role, invited_by, token) VALUES (?, ?, ?, ?, ?)');
            $stmtInv->execute([$gid, $email, $role, $_SESSION['user_id'], $token]);
            $inviteId = (int)$pdo->lastInsertId();
        } catch (Exception $e) {
            // unique constraint (already invited)
            jsonErr('Invite already exists for this email', 409);
            exit;
        }

        // activity log (record that an invite was created)
        try {
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
            $stmtAct->execute([$_SESSION['user_id'], $gid, 'group_invite_created', json_encode(['invite_id' => $inviteId, 'email' => $email, 'role' => $role])]);
        } catch (Exception $e) {
            // ignore activity logging errors
        }

        // NOTE: sending email is not implemented in this minimal demo.
        jsonOk(['invite_id' => $inviteId, 'email' => $email, 'role' => $role, 'token' => $token]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error creating invite: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/groups/{id}/leave -> current user leaves the group (self-removal)
if ($method === 'POST' && preg_match('#^groups/(\d+)/leave$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $gid = (int)$m[1];
    $uid = $_SESSION['user_id'];
    try {
        // ensure membership exists
        $stmt = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmt->execute([$gid, $uid]);
        $role = $stmt->fetchColumn();
        if (!$role) {
            jsonErr('Not a member of this group', 404);
            exit;
        }

        // if owner, ensure there is another owner before allowing leave
        if ($role === 'owner') {
            $stmtOther = $pdo->prepare('SELECT COUNT(*) FROM group_members WHERE group_id = ? AND role = ? AND user_id != ?');
            $stmtOther->execute([$gid, 'owner', $uid]);
            $otherOwners = (int)$stmtOther->fetchColumn();
            if ($otherOwners <= 0) {
                jsonErr('You are the only owner. Transfer ownership or delete the group before leaving.', 403);
                exit;
            }
        }

        // perform removal
        $stmtDel = $pdo->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtDel->execute([$gid, $uid]);

        // activity log
        try {
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
            $stmtAct->execute([$uid, $gid, 'group_left', json_encode([])]);
        } catch (Exception $e) {
            // ignore
        }

        jsonOk(['left_group' => $gid]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error leaving group: ' . $e->getMessage(), 500);
        exit;
    }
}

// GET /api/groups/{id}/invites -> list pending invites for a group (owner/admin only)
if ($method === 'GET' && preg_match('#^groups/(\d+)/invites$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $gid = (int)$m[1];
    try {
        // check role
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $_SESSION['user_id']]);
        $myRole = $stmtRole->fetchColumn();
        if (!in_array($myRole, ['owner', 'admin'])) {
            jsonErr('Forbidden', 403);
            exit;
        }
        $stmt = $pdo->prepare('SELECT id, email, role, invited_by, created_at FROM group_invites WHERE group_id = ? ORDER BY created_at DESC');
        $stmt->execute([$gid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonOk(['invites' => $rows]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error fetching invites: ' . $e->getMessage(), 500);
        exit;
    }
}

// POST /api/groups/{id}/invites/{invite_id}/resend -> (simulate) resend invite (owner/admin only)
if ($method === 'POST' && preg_match('#^groups/(\d+)/invites/(\d+)/resend$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $gid = (int)$m[1];
    $inviteId = (int)$m[2];
    try {
        // check role
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $_SESSION['user_id']]);
        $myRole = $stmtRole->fetchColumn();
        if (!in_array($myRole, ['owner', 'admin'])) {
            jsonErr('Forbidden', 403);
            exit;
        }
        // fetch invite
        $stmt = $pdo->prepare('SELECT id, email, role, token FROM group_invites WHERE id = ? AND group_id = ?');
        $stmt->execute([$inviteId, $gid]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$inv) {
            jsonErr('Invite not found', 404);
            exit;
        }

        // In a full app we'd re-send the email here. For demo, return the token and mark 'resent' time by updating updated_at
        try {
            $stmtUpd = $pdo->prepare('UPDATE group_invites SET created_at = NOW() WHERE id = ?');
            $stmtUpd->execute([$inviteId]);
        } catch (Exception $e) {
            // ignore update errors
        }

        // log activity
        try {
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
            $stmtAct->execute([$_SESSION['user_id'], $gid, 'invite_resent', json_encode(['invite_id' => $inviteId])]);
        } catch (Exception $e) {
        }

        jsonOk(['invite_id' => $inviteId, 'email' => $inv['email'], 'token' => $inv['token']]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error resending invite: ' . $e->getMessage(), 500);
        exit;
    }
}

// DELETE /api/groups/{id}/invites/{invite_id} -> cancel invite (owner/admin only)
if ($method === 'DELETE' && preg_match('#^groups/(\d+)/invites/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $gid = (int)$m[1];
    $inviteId = (int)$m[2];
    try {
        // check role
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $_SESSION['user_id']]);
        $myRole = $stmtRole->fetchColumn();
        if (!in_array($myRole, ['owner', 'admin'])) {
            jsonErr('Forbidden', 403);
            exit;
        }
        // ensure invite exists
        $stmt = $pdo->prepare('SELECT id, email FROM group_invites WHERE id = ? AND group_id = ?');
        $stmt->execute([$inviteId, $gid]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$inv) {
            jsonErr('Invite not found', 404);
            exit;
        }
        // delete invite
        $stmtDel = $pdo->prepare('DELETE FROM group_invites WHERE id = ?');
        $stmtDel->execute([$inviteId]);
        // activity log
        try {
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
            $stmtAct->execute([$_SESSION['user_id'], $gid, 'invite_cancelled', json_encode(['invite_id' => $inviteId, 'email' => $inv['email']])]);
        } catch (Exception $e) {
        }

        jsonOk(['invite_id' => $inviteId]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error cancelling invite: ' . $e->getMessage(), 500);
        exit;
    }
}

// DELETE /api/groups/{id}/members/{user_id} -> remove a member (owner can remove anyone except self; admin can remove members only)
if ($method === 'DELETE' && preg_match('#^groups/(\d+)/members/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $gid = (int)$m[1];
    $targetUid = (int)$m[2];
    $uid = $_SESSION['user_id'];
    try {
        // ensure group exists
        $stmt = $pdo->prepare('SELECT id FROM groups_tbl WHERE id = ?');
        $stmt->execute([$gid]);
        if (!$stmt->fetchColumn()) {
            jsonErr('Group not found', 404);
            exit;
        }

        // current user's role
        $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtRole->execute([$gid, $uid]);
        $myRole = $stmtRole->fetchColumn();
        if (!in_array($myRole, ['owner', 'admin'])) {
            jsonErr('Forbidden: only owner/admin can remove members', 403);
            exit;
        }

        // target member row
        $stmtTarget = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtTarget->execute([$gid, $targetUid]);
        $targetRole = $stmtTarget->fetchColumn();
        if (!$targetRole) {
            jsonErr('Member not found', 404);
            exit;
        }

        // prevent self-remove via this endpoint (use leave group flow if needed)
        if ((int)$targetUid === (int)$uid) {
            jsonErr('Use the leave group action to remove yourself', 403);
            exit;
        }

        // admin restrictions: admin can remove only plain members
        if ($myRole === 'admin' && $targetRole !== 'member') {
            jsonErr('Forbidden: admin can remove members only', 403);
            exit;
        }

        // owner may remove others (including admins), proceed to delete
        $stmtDel = $pdo->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?');
        $stmtDel->execute([$gid, $targetUid]);

        // activity log
        try {
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, action, meta) VALUES (?, ?, ?, ?)');
            $stmtAct->execute([$uid, $gid, 'group_member_removed', json_encode(['removed_user_id' => (int)$targetUid, 'by' => (int)$uid])]);
        } catch (Exception $e) {
            // ignore
        }

        jsonOk(['removed_user_id' => $targetUid]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error removing member: ' . $e->getMessage(), 500);
        exit;
    }
}

// GET /api/goals/{id}/stats -> team stats / leaderboard for a goal
if ($method === 'GET' && preg_match('#^goals/(\d+)/stats$#', $path, $m)) {
    $goalId = (int)$m[1];
    try {
        // fetch goal
        $stmt = $pdo->prepare('SELECT id, title, cadence, target_value, start_date, end_date, group_id FROM goals WHERE id = ?');
        $stmt->execute([$goalId]);
        $goal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$goal) {
            jsonErr('Goal not found', 404);
            exit;
        }

        $start = $goal['start_date'] ?: null;
        $end = $goal['end_date'] ?: null;
        // default window: last 30 days if no start/end
        if (!$start && !$end) {
            $start = date('Y-m-d', strtotime('-29 days'));
            $end = date('Y-m-d');
        } elseif ($start && !$end) {
            $end = date('Y-m-d');
        } elseif (!$start && $end) {
            $start = date('Y-m-d', strtotime('-29 days'));
        }

        // determine participants: if group goal use group members; else use users who have checkins or the goal creator
        $participants = [];
        if (!empty($goal['group_id'])) {
            $stmtP = $pdo->prepare('SELECT u.id, u.name, u.email FROM users u JOIN group_members gm ON gm.user_id = u.id WHERE gm.group_id = ?');
            $stmtP->execute([$goal['group_id']]);
            $participants = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // users who checked in
            $stmtP = $pdo->prepare('SELECT DISTINCT u.id, u.name, u.email FROM users u JOIN checkins c ON c.user_id = u.id WHERE c.goal_id = ?');
            $stmtP->execute([$goalId]);
            $participants = $stmtP->fetchAll(PDO::FETCH_ASSOC);
            // include creator if no participants
            if (empty($participants)) {
                $stmtC = $pdo->prepare('SELECT u.id, u.name, u.email FROM users u JOIN goals g ON g.created_by = u.id WHERE g.id = ?');
                $stmtC->execute([$goalId]);
                $c = $stmtC->fetch(PDO::FETCH_ASSOC);
                if ($c) $participants[] = $c;
            }
        }

        $stats = [];
        foreach ($participants as $p) {
            // count checkins and sum value in window
            $stmtS = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(value),0) AS total_value FROM checkins WHERE goal_id = ? AND user_id = ? AND date BETWEEN ? AND ?');
            $stmtS->execute([$goalId, $p['id'], $start, $end]);
            $row = $stmtS->fetch(PDO::FETCH_ASSOC);
            $cnt = (int)$row['cnt'];
            $total = (float)$row['total_value'];
            // percentage (if target_value provided, compare total or count depending on cadence)
            $pct = null;
            if (!empty($goal['target_value'])) {
                if (in_array($goal['cadence'], ['daily', 'weekly'])) {
                    $pct = min(100, round(($cnt / (int)$goal['target_value']) * 100));
                } else {
                    $pct = min(100, round(($total / (float)$goal['target_value']) * 100));
                }
            }
            $stats[] = ['user_id' => (int)$p['id'], 'name' => $p['name'], 'email' => $p['email'], 'checkins' => $cnt, 'total_value' => $total, 'pct' => $pct];
        }

        // sort by checkins desc then total_value
        usort($stats, function ($a, $b) {
            if ($b['checkins'] === $a['checkins']) return $b['total_value'] <=> $a['total_value'];
            return $b['checkins'] <=> $a['checkins'];
        });

        jsonOk(['goal' => $goal, 'start' => $start, 'end' => $end, 'stats' => $stats]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error computing stats: ' . $e->getMessage(), 500);
        exit;
    }
}

// ----------------------------
// Activity endpoint
// ----------------------------

// ----------------------------
// Goal templates
// ----------------------------

// GET /api/templates -> list available templates
if ($method === 'GET' && preg_match('#^templates$#', $path)) {
    $stmt = $pdo->prepare('SELECT id, title, description, cadence, unit, start_offset_days, duration_days FROM goal_templates ORDER BY created_at DESC');
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonOk(['templates' => $rows]);
    exit;
}

// GET /api/goals/{id}/periods -> recent period aggregates for a goal (current user by default)
if ($method === 'GET' && preg_match('#^goals/(\d+)/periods$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $goalId = (int)$m[1];
    $viewer = (int)$_SESSION['user_id'];
    $targetUser = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $viewer;

    // fetch goal and check visibility / permissions
    $stmt = $pdo->prepare('SELECT id, created_by, group_id, visibility FROM goals WHERE id = ? AND active = 1');
    $stmt->execute([$goalId]);
    $g = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$g) {
        jsonErr('Goal not found', 404);
        exit;
    }
    // visibility check for viewer
    if ($g['visibility'] === 'private') {
        if ($viewer !== (int)$g['created_by']) {
            if (empty($g['group_id'])) {
                jsonErr('Not allowed', 403);
                exit;
            }
            $stmtRole = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmtRole->execute([$g['group_id'], $viewer]);
            if (!$stmtRole->fetchColumn()) {
                jsonErr('Not allowed', 403);
                exit;
            }
        }
    }

    // if requesting another user's periods, require goal owner or group admin
    if ($targetUser !== $viewer) {
        $allowed = false;
        if ($viewer === (int)$g['created_by']) $allowed = true;
        if (!$allowed && $g['group_id']) {
            $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmtRole->execute([$g['group_id'], $viewer]);
            $role = $stmtRole->fetchColumn();
            if (in_array($role, ['owner', 'admin'])) $allowed = true;
        }
        if (!$allowed) {
            jsonErr('Not allowed to view other users\' periods', 403);
            exit;
        }
    }

    // fetch recent periods for the target user
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
        if ($limit <= 0 || $limit > 52) $limit = 12;
        $stmtP = $pdo->prepare('SELECT id, period_type, period_start, period_end, total_value, checkins_count, created_at FROM goal_periods WHERE goal_id = ? AND user_id = ? ORDER BY period_start DESC LIMIT ?');
        $stmtP->execute([$goalId, $targetUser, $limit]);
        $rows = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        jsonOk(['goal_id' => $goalId, 'user_id' => $targetUser, 'periods' => $rows]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error fetching periods: ' . $e->getMessage(), 500);
        exit;
    }
}

// GET /api/templates/{id} -> get template details
if ($method === 'GET' && preg_match('#^templates/(\d+)$#', $path, $m)) {
    $tid = (int)$m[1];
    $stmt = $pdo->prepare('SELECT id, title, description, cadence, unit, start_offset_days, duration_days FROM goal_templates WHERE id = ?');
    $stmt->execute([$tid]);
    $t = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$t) {
        jsonErr('Template not found', 404);
        exit;
    }
    jsonOk(['template' => $t]);
    exit;
}

// POST /api/templates/{id}/clone -> clone template into a new goal for current user
// optional JSON body: { group_id: int|null, overrides: { title, description, cadence, unit, start_date, end_date } }
if ($method === 'POST' && preg_match('#^templates/(\d+)/clone$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $tid = (int)$m[1];
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    try {
        $stmt = $pdo->prepare('SELECT * FROM goal_templates WHERE id = ?');
        $stmt->execute([$tid]);
        $t = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$t) {
            jsonErr('Template not found', 404);
            exit;
        }

        $groupId = isset($data['group_id']) ? (int)$data['group_id'] : null;
        $over = $data['overrides'] ?? [];

        // determine fields using overrides if provided
        $title = $over['title'] ?? $t['title'];
        $description = $over['description'] ?? $t['description'];
        $cadence = in_array($over['cadence'] ?? $t['cadence'], ['daily', 'weekly', 'monthly']) ? ($over['cadence'] ?? $t['cadence']) : 'daily';
        $unit = $over['unit'] ?? $t['unit'];

        // start_date: if override provided, use it; else use offset days
        if (!empty($over['start_date'])) {
            $start_date = $over['start_date'];
        } else {
            $start = new DateTime();
            if (!empty($t['start_offset_days'])) $start->modify('+' . (int)$t['start_offset_days'] . ' days');
            $start_date = $start->format('Y-m-d');
        }

        // end_date: override or compute from duration_days
        if (!empty($over['end_date'])) {
            $end_date = $over['end_date'];
        } elseif (!empty($t['duration_days'])) {
            $end = new DateTime($start_date);
            $end->modify('+' . ((int)$t['duration_days'] - 1) . ' days');
            $end_date = $end->format('Y-m-d');
        } else {
            $end_date = null;
        }

        // persist into goals
        if (!empty($groupId)) {
            $stmtIns = $pdo->prepare('INSERT INTO goals (title, description, created_by, group_id, cadence, unit, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmtIns->execute([$title, $description, $_SESSION['user_id'], $groupId, $cadence, $unit ?: null, $start_date, $end_date]);
        } else {
            $stmtIns = $pdo->prepare('INSERT INTO goals (title, description, created_by, cadence, unit, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmtIns->execute([$title, $description, $_SESSION['user_id'], $cadence, $unit ?: null, $start_date, $end_date]);
        }
        $gid = (int)$pdo->lastInsertId();
        // activity
        try {
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, goal_id, action, meta) VALUES (?, ?, ?, ?, ?)');
            $stmtAct->execute([$_SESSION['user_id'], $groupId ?: null, $gid, 'goal_created_from_template', json_encode(['template_id' => $tid])]);
        } catch (Exception $e) {
        }

        jsonOk(['goal_id' => $gid]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error cloning template: ' . $e->getMessage(), 500);
        exit;
    }
}

// GET /api/activity -> recent activity relevant to user (their groups + their actions)
if ($method === 'GET' && preg_match('#^activity$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $uid = $_SESSION['user_id'];
    // fetch group ids
    $stmt = $pdo->prepare('SELECT group_id FROM group_members WHERE user_id = ?');
    $stmt->execute([$uid]);
    $groupIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $placeholders = '';
    $params = [];
    if ($groupIds) {
        $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
        $params = $groupIds;
    }

    // build query: activity where user_id = uid OR group_id IN (...) ordered
    if ($placeholders) {
        $sql = "SELECT * FROM activity_log WHERE user_id = ? OR group_id IN ($placeholders) ORDER BY created_at DESC LIMIT 100";
        $params = array_merge([$uid], $params);
    } else {
        $sql = 'SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 100';
        $params = [$uid];
    }
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute($params);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    jsonOk(['activities' => $rows]);
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Not found']);
