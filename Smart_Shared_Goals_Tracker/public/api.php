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
    if (!$user || !password_verify($data['password'], $user['password'])) {
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

        // insert
        $stmtInsert = $pdo->prepare('INSERT INTO checkins (goal_id, user_id, value, note, date) VALUES (?, ?, ?, ?, ?)');
        $stmtInsert->execute([$goalId, $_SESSION['user_id'], $value, $note, $date]);
        $cid = (int)$pdo->lastInsertId();
        $stmtInsertKey = $pdo->prepare('INSERT INTO idempotency_keys (client_key, checkin_id) VALUES (?, ?)');
        $stmtInsertKey->execute([$clientKey, $cid]);

        // post-processing: streaks & badges
        $streakSvc = new \Services\StreakService($pdo);
        $badgeSvc = new \Services\BadgeService($pdo);
        $streak = $streakSvc->updateStreak($_SESSION['user_id'], $goalId, $date);
        $badges = $badgeSvc->evaluate($_SESSION['user_id'], $goalId);

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
        jsonOk(['checkin_id' => $cid, 'updated_streak' => $streak, 'awarded_badges' => $badges]);
        exit;
    } catch (\Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        jsonErr('Error creating checkin: ' . $e->getMessage(), 500);
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

            // insert
            $stmtInsertCheckin->execute([$goalId, $_SESSION['user_id'], $value, $note, $date]);
            $cid = (int)$pdo->lastInsertId();
            $stmtInsertKey->execute([$clientKey, $cid]);

            // update streaks and evaluate badges
            $streak = $streakSvc->updateStreak($_SESSION['user_id'], $goalId, $date);
            $badges = $badgeSvc->evaluate($_SESSION['user_id'], $goalId);

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

            $accepted[] = ['client_idempotency_key' => $clientKey, 'checkin_id' => $cid, 'awarded_badges' => $badges, 'updated_streak' => $streak];
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

// GET /api/me -> current user (session)
if ($method === 'GET' && preg_match('#^me$#', $path)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $stmt = $pdo->prepare('SELECT id, name, email, avatar, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        // invalid session
        unset($_SESSION['user_id']);
        jsonErr('Not authenticated', 401);
        exit;
    }
    jsonOk(['user' => $user]);
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

// ----------------------------
// Activity endpoint
// ----------------------------

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
