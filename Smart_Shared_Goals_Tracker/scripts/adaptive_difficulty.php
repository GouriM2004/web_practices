<?php
// CLI: adaptive difficulty evaluator
// Run daily (after periodic_reset) to adjust per-user multipliers based on recent success
require __DIR__ . '/../src/bootstrap.php';
/** @var \PDO $pdo */

// Configuration
$windowDays = 14; // analyze last N days
$increaseStreakDays = 7; // consecutive days to trigger increase
$decreaseMissDays = 3; // missed days threshold to decrease
$increaseFactor = 1.10; // increase multiplier by 10%
$decreaseFactor = 0.90; // decrease by 10%
$minMultiplier = 0.5;
$maxMultiplier = 2.0;

echo "Adaptive difficulty run: " . date('Y-m-d H:i:s') . "\n";

// fetch goals that are active and have a target or cadence where difficulty makes sense
$stmtGoals = $pdo->query("SELECT * FROM goals WHERE active = 1");
$goals = $stmtGoals->fetchAll(PDO::FETCH_ASSOC);

foreach ($goals as $goal) {
    $gid = (int)$goal['id'];
    echo "Processing goal {$gid} ({$goal['title']})\n";

    // determine users to consider: owner + group members + users with recent checkins
    $users = [];
    if (!empty($goal['created_by'])) $users[] = (int)$goal['created_by'];

    // group members
    if (!empty($goal['group_id'])) {
        $stmtGM = $pdo->prepare('SELECT user_id FROM group_members WHERE group_id = ?');
        $stmtGM->execute([$goal['group_id']]);
        $gmu = $stmtGM->fetchAll(PDO::FETCH_COLUMN);
        foreach ($gmu as $u) $users[] = (int)$u;
    }

    // recent checkin authors
    $stmtUC = $pdo->prepare('SELECT DISTINCT user_id FROM checkins WHERE goal_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)');
    $stmtUC->execute([$gid, $windowDays]);
    $recentUsers = $stmtUC->fetchAll(PDO::FETCH_COLUMN);
    foreach ($recentUsers as $u) $users[] = (int)$u;

    $users = array_unique($users);

    foreach ($users as $uid) {
        // fetch checkins for window
        $stmtCI = $pdo->prepare('SELECT date FROM checkins WHERE goal_id = ? AND user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) ORDER BY date ASC');
        $stmtCI->execute([$gid, $uid, $windowDays]);
        $dates = $stmtCI->fetchAll(PDO::FETCH_COLUMN);
        // build set for quick lookup
        $checked = array_flip($dates);

        // compute longest consecutive ending streak within window (count days up to today)
        $today = new DateTimeImmutable('today');
        $streak = 0;
        for ($i = 0; $i < $windowDays; $i++) {
            $d = $today->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
            if (isset($checked[$d])) $streak++;
            else break;
        }

        // compute max consecutive anywhere in window (optional)
        $maxConsec = 0;
        $cur = 0;
        $prev = null;
        foreach ($dates as $d) {
            if (!$prev) {
                $cur = 1;
            } else {
                $prevD = new DateTimeImmutable($prev);
                $curD = new DateTimeImmutable($d);
                $diff = (int)$curD->diff($prevD)->format('%a');
                if ($diff === 1) $cur++;
                else {
                    if ($cur > $maxConsec) $maxConsec = $cur;
                    $cur = 1;
                }
            }
            $prev = $d;
        }
        if ($cur > $maxConsec) $maxConsec = $cur;

        // compute missed days in window
        $missed = 0;
        for ($i = 0; $i < $windowDays; $i++) {
            $d = $today->sub(new DateInterval('P' . $i . 'D'))->format('Y-m-d');
            if (!isset($checked[$d])) $missed++;
        }

        // fetch existing multiplier row
        $stmtGet = $pdo->prepare('SELECT * FROM goal_user_difficulty WHERE goal_id = ? AND user_id = ?');
        $stmtGet->execute([$gid, $uid]);
        $row = $stmtGet->fetch(PDO::FETCH_ASSOC);
        $mult = $row ? (float)$row['multiplier'] : 1.0;
        $oldMult = $mult;
        $oldDiff = $row ? $row['difficulty'] : ($goal['difficulty'] ?? 'medium');
        $note = null;
        $adjusted = false;

        // Rule: if user has streak >= increaseStreakDays -> increase multiplier by factor
        if ($streak >= $increaseStreakDays) {
            $mult = min($maxMultiplier, $mult * $increaseFactor);
            $note = "streak_{$streak}_increase";
            $adjusted = true;
        }
        // Rule: if missed >= decreaseMissDays (i.e., struggling) -> decrease multiplier
        elseif ($missed >= $decreaseMissDays) {
            $mult = max($minMultiplier, $mult * $decreaseFactor);
            $note = "missed_{$missed}_decrease";
            $adjusted = true;
        }

        if ($adjusted) {
            // compute difficulty label based on multiplier
            $newDiff = 'medium';
            if ($mult < 0.85) $newDiff = 'easy';
            elseif ($mult > 1.15) $newDiff = 'hard';

            try {
                if ($row) {
                    $stmtUpd = $pdo->prepare('UPDATE goal_user_difficulty SET multiplier = ?, difficulty = ?, last_adjusted = NOW(), note = ? WHERE id = ?');
                    $stmtUpd->execute([$mult, $newDiff, $note, $row['id']]);
                } else {
                    $stmtIns = $pdo->prepare('INSERT INTO goal_user_difficulty (goal_id, user_id, multiplier, difficulty, note) VALUES (?, ?, ?, ?, ?)');
                    $stmtIns->execute([$gid, $uid, $mult, $newDiff, $note]);
                }

                // activity log entry
                $meta = json_encode(['action' => 'adaptive_adjust', 'from_multiplier' => $oldMult, 'to_multiplier' => $mult, 'from_diff' => $oldDiff, 'to_diff' => $newDiff, 'reason' => $note]);
                $stmtAct = $pdo->prepare('INSERT INTO activity_log (user_id, group_id, goal_id, action, meta) VALUES (?, ?, ?, ?, ?)');
                $stmtAct->execute([$uid, $goal['group_id'] ?? null, $gid, 'adaptive_difficulty_adjusted', $meta]);

                echo "  user {$uid}: adjusted multiplier {$oldMult} -> {$mult} ({$note})\n";
            } catch (Exception $e) {
                echo "  user {$uid}: failed to persist adjustment: " . $e->getMessage() . "\n";
            }
        } else {
            echo "  user {$uid}: no change (streak={$streak}, missed={$missed})\n";
        }
    }
}

echo "Adaptive difficulty run complete.\n";
