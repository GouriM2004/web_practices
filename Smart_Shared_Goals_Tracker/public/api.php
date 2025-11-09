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
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ?');
    $stmt->execute([$q]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        jsonErr('User not found', 404);
        exit;
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

// DELETE /api/goals/{id} -> delete a goal (creator or group owner/admin)
if ($method === 'DELETE' && preg_match('#^goals/(\d+)$#', $path, $m)) {
    if (empty($_SESSION['user_id'])) {
        jsonErr('Not authenticated', 401);
        exit;
    }
    $goalId = (int)$m[1];
    try {
        $stmt = $pdo->prepare('SELECT id, created_by, group_id FROM goals WHERE id = ?');
        $stmt->execute([$goalId]);
        $g = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$g) {
            jsonErr('Goal not found', 404);
            exit;
        }
        $uid = $_SESSION['user_id'];
        $allowed = false;
        if ((int)$g['created_by'] === (int)$uid) {
            $allowed = true;
        } elseif (!empty($g['group_id'])) {
            // check group role
            $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
            $stmtRole->execute([$g['group_id'], $uid]);
            $myRole = $stmtRole->fetchColumn();
            if (in_array($myRole, ['owner', 'admin'])) $allowed = true;
        }
        if (!$allowed) {
            jsonErr('Forbidden', 403);
            exit;
        }

        // delete goal (cascade will remove related checkins/meta)
        $stmtDel = $pdo->prepare('DELETE FROM goals WHERE id = ?');
        $stmtDel->execute([$goalId]);

        // activity log
        try {
            $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, goal_id, action, meta) VALUES (?, ?, ?, ?, ?)');
            $stmtAct->execute([$uid, $g['group_id'] ?: null, $goalId, 'goal_deleted', json_encode([])]);
        } catch (Exception $e) {
        }

        jsonOk(['deleted' => (int)$goalId]);
        exit;
    } catch (Exception $e) {
        jsonErr('Error deleting goal: ' . $e->getMessage(), 500);
        exit;
    }
}
