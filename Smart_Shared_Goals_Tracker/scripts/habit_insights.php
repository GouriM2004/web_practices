<?php

/**
 * scripts/habit_insights.php
 *
 * Compute per-user habit insights and store them in `user_habit_insights`.
 * Run from CLI (php scripts/habit_insights.php) or schedule via cron/Task Scheduler.
 */

require_once __DIR__ . '/../src/bootstrap.php';

// Simple configuration/tuning
$WINDOW_DAYS = 30; // how many days of history to analyze
$WEEKLY_WINDOW = 7; // for weekly summaries
$TOP_HOURS = 3; // suggest up to this many hours
$VERBOSE = true;

function insert_insight($pdo, $user_id, $goal_id, $type, $payload)
{
    $stmt = $pdo->prepare('INSERT INTO user_habit_insights (user_id, goal_id, insight_type, payload, generated_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([$user_id, $goal_id, $type, json_encode($payload)]);
}

echo "Starting habit insights run...\n";

// fetch users
$stmtUsers = $pdo->prepare('SELECT id, email, name FROM users');
$stmtUsers->execute();
$users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    $uid = (int)$u['id'];
    if ($VERBOSE) echo "Processing user {$uid} ({$u['email']})\n";

    // find goals relevant to this user: goals they've created or have checkins for
    $stmtGoals = $pdo->prepare('SELECT DISTINCT g.* FROM goals g LEFT JOIN checkins c ON c.goal_id = g.id WHERE g.created_by = ? OR c.user_id = ?');
    $stmtGoals->execute([$uid, $uid]);
    $goals = $stmtGoals->fetchAll(PDO::FETCH_ASSOC);

    foreach ($goals as $g) {
        $goalId = (int)$g['id'];
        if ($VERBOSE) echo "  Goal {$goalId}: {$g['title']}\n";

        // fetch checkins for this user/goal in the window
        $stmt = $pdo->prepare('SELECT id, value, note, created_at, date FROM checkins WHERE goal_id = ? AND user_id = ? AND date >= ? ORDER BY date ASC');
        $from = date('Y-m-d', strtotime("-" . $WINDOW_DAYS . " days"));
        $stmt->execute([$goalId, $uid, $from]);
        $checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If no checkins, skip detailed analysis but still store a weekly summary of zeros
        $dates = array_column($checkins, 'date');

        // Weekly insights: last 7 days counts and days active
        $weekStart = date('Y-m-d', strtotime('-' . ($WEEKLY_WINDOW - 1) . ' days'));
        $stmtWeek = $pdo->prepare('SELECT COUNT(*) AS cnt, COUNT(DISTINCT date) AS days_active FROM checkins WHERE goal_id = ? AND user_id = ? AND date BETWEEN ? AND ?');
        $stmtWeek->execute([$goalId, $uid, $weekStart, date('Y-m-d')]);
        $rowWeek = $stmtWeek->fetch(PDO::FETCH_ASSOC);
        $weeklySummary = [
            'goal_id' => $goalId,
            'checkins_last_7' => (int)$rowWeek['cnt'],
            'days_active_last_7' => (int)$rowWeek['days_active'],
            'window_days' => $WINDOW_DAYS,
        ];
        insert_insight($pdo, $uid, $goalId, 'weekly_summary', $weeklySummary);

        if (empty($checkins)) {
            if ($VERBOSE) echo "    no checkins in window, wrote weekly_summary\n";
            continue;
        }

        // Failure pattern: weekday distribution (0=Sun .. 6=Sat)
        $weekdayCounts = array_fill(0, 7, 0);
        $hourCounts = [];
        foreach ($checkins as $c) {
            $d = $c['date'];
            $ts = strtotime($c['created_at']);
            $w = (int)date('w', strtotime($d));
            $h = (int)date('G', $ts); // 0-23
            $weekdayCounts[$w]++;
            if (!isset($hourCounts[$h])) $hourCounts[$h] = 0;
            $hourCounts[$h]++;
        }

        // compute failure patterns: compare weekend vs weekday activity
        $weekdaysTotal = array_sum(array_slice($weekdayCounts, 1, 5)); // Mon-Fri
        $weekendTotal = $weekdayCounts[0] + $weekdayCounts[6]; // Sun + Sat
        $failurePattern = null;
        if ($weekendTotal < max(1, $weekdaysTotal * 0.5)) {
            $failurePattern = 'misses_on_weekends';
        } elseif ($weekdaysTotal < max(1, $weekendTotal * 0.5)) {
            $failurePattern = 'misses_on_weekdays';
        }
        $payloadFailure = ['weekday_counts' => $weekdayCounts, 'failure_pattern' => $failurePattern];
        insert_insight($pdo, $uid, $goalId, 'failure_pattern', $payloadFailure);

        // Suggested times: pick top hours
        arsort($hourCounts);
        $top = array_slice($hourCounts, 0, $TOP_HOURS, true);
        $suggested = [];
        foreach ($top as $hour => $cnt) {
            $suggested[] = ['hour' => (int)$hour, 'count' => (int)$cnt];
        }
        insert_insight($pdo, $uid, $goalId, 'suggested_times', ['top_hours' => $suggested]);

        // Predicted streak break alerts: use goal_user_meta if available
        $stmtMeta = $pdo->prepare('SELECT current_streak, longest_streak, last_checkin FROM goal_user_meta WHERE user_id = ? AND goal_id = ?');
        $stmtMeta->execute([$uid, $goalId]);
        $meta = $stmtMeta->fetch(PDO::FETCH_ASSOC);
        $lastCheckin = $meta['last_checkin'] ?? end($dates);
        $daysSince = (int)floor((time() - strtotime($lastCheckin)) / 86400);
        $predictedBreak = false;
        $reason = null;
        // simple heuristics: daily cadence expects checkin every day; weekly expects within 7 days
        if ($g['cadence'] === 'daily' && $daysSince >= 2) {
            $predictedBreak = true;
            $reason = "No check-in for {$daysSince} days (daily cadence)";
        } elseif ($g['cadence'] === 'weekly' && $daysSince >= 10) {
            $predictedBreak = true;
            $reason = "No check-in for {$daysSince} days (weekly cadence)";
        } elseif ($g['cadence'] === 'monthly' && $daysSince >= 40) {
            $predictedBreak = true;
            $reason = "No check-in for {$daysSince} days (monthly cadence)";
        }
        if ($predictedBreak) {
            insert_insight($pdo, $uid, $goalId, 'predicted_streak_break', ['days_since_last' => $daysSince, 'reason' => $reason]);
        }
        if ($VERBOSE) echo "    wrote failure_pattern, suggested_times" . ($predictedBreak ? ", predicted_streak_break" : "") . "\n";
    }

    // additionally compute cross-goal signals (global for user)
    // Example: busiest check-in hour across all goals
    $stmtAll = $pdo->prepare('SELECT created_at FROM checkins WHERE user_id = ? AND date >= ?');
    $stmtAll->execute([$uid, date('Y-m-d', strtotime('-' . $WINDOW_DAYS . ' days'))]);
    $all = $stmtAll->fetchAll(PDO::FETCH_COLUMN);
    if ($all) {
        $hourCounts = [];
        foreach ($all as $cAt) {
            $h = (int)date('G', strtotime($cAt));
            if (!isset($hourCounts[$h])) $hourCounts[$h] = 0;
            $hourCounts[$h]++;
        }
        arsort($hourCounts);
        $top = array_slice($hourCounts, 0, $TOP_HOURS, true);
        $suggested = [];
        foreach ($top as $hour => $cnt) $suggested[] = ['hour' => (int)$hour, 'count' => (int)$cnt];
        insert_insight($pdo, $uid, null, 'suggested_times_global', ['top_hours' => $suggested]);
    }
}

echo "Habit insights run complete.\n";

// End
