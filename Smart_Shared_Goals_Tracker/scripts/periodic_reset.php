<?php
// CLI script: compute per-period aggregates for goals and insert into `goal_periods`.
// Run daily from cron / Task Scheduler.

require __DIR__ . '/../src/bootstrap.php';
/** @var \PDO $pdo */

function getPeriodRange(array $goal, \DateTimeInterface $forDate = null)
{
    $dt = $forDate ? new DateTimeImmutable($forDate->format('Y-m-d')) : new DateTimeImmutable('today');
    $cadence = $goal['cadence'] ?? 'daily';
    switch ($cadence) {
        case 'weekly':
            // week starting on reset_day_of_week (0=Sun..6=Sat), default Monday=1
            $weekStart = isset($goal['reset_day_of_week']) ? (int)$goal['reset_day_of_week'] : 1;
            $dow = (int)$dt->format('w'); // 0..6
            $diff = ($dow - $weekStart + 7) % 7;
            $start = $dt->sub(new DateInterval('P' . $diff . 'D'));
            $end = $start->add(new DateInterval('P6D'));
            return [$start->format('Y-m-d'), $end->format('Y-m-d')];
        case 'monthly':
            $start = new DateTimeImmutable($dt->format('Y-m-01'));
            $end = $start->modify('last day of this month');
            return [$start->format('Y-m-d'), $end->format('Y-m-d')];
        case 'quarterly':
            $m = (int)$dt->format('n');
            $q = (int)(floor(($m - 1) / 3)); // 0..3
            $startMonth = $q * 3 + 1;
            $start = new DateTimeImmutable($dt->format('Y-') . str_pad($startMonth, 2, '0', STR_PAD_LEFT) . '-01');
            $end = $start->modify('last day of +2 months');
            return [$start->format('Y-m-d'), $end->format('Y-m-d')];
        case 'seasonal':
            // uses season_start_month and season_end_month (1..12). If season wraps year-end, handle accordingly.
            $ss = $goal['season_start_month'] ? (int)$goal['season_start_month'] : null;
            $se = $goal['season_end_month'] ? (int)$goal['season_end_month'] : null;
            if (!$ss || !$se) {
                // fallback to current month as single-month season
                $start = new DateTimeImmutable($dt->format('Y-m-01'));
                $end = $start->modify('last day of this month');
                return [$start->format('Y-m-d'), $end->format('Y-m-d')];
            }
            $curM = (int)$dt->format('n');
            if ($ss <= $se) {
                // same-year season (e.g., Mar-Jun)
                $year = (int)$dt->format('Y');
                $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $ss));
                $end = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $se)))->modify('last day of this month');
                // if current month not in range, pick the season that contains the date by shifting year
                if ($curM < $ss || $curM > $se) {
                    // find nearest season that contains date -> we'll choose next or previous
                    // to keep logic simple, if date not in this year's season, shift year accordingly
                    if ($curM < $ss) {
                        $start = $start->modify('-1 year');
                        $end = $end->modify('-1 year');
                    } else {
                        $start = $start->modify('+1 year');
                        $end = $end->modify('+1 year');
                    }
                }
                return [$start->format('Y-m-d'), $end->format('Y-m-d')];
            } else {
                // wraps year end (e.g., Nov-Feb)
                $year = (int)$dt->format('Y');
                // if current month >= ss then season started this year
                if ($curM >= $ss) {
                    $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $ss));
                    $end = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year + 1, $se)))->modify('last day of this month');
                } else {
                    $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year - 1, $ss));
                    $end = (new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $se)))->modify('last day of this month');
                }
                return [$start->format('Y-m-d'), $end->format('Y-m-d')];
            }
        default:
            // daily or unknown: use today
            $d = $dt->format('Y-m-d');
            return [$d, $d];
    }
}

// load goals that need period tracking (cadence in weekly/monthly/quarterly/seasonal)
$stmt = $pdo->query("SELECT * FROM goals WHERE cadence IN ('weekly','monthly','quarterly','seasonal') AND active = 1");
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($goals as $goal) {
    echo "Processing goal {$goal['id']} ({$goal['title']}) cadence={$goal['cadence']}\n";
    // find relevant users: any user who has checkins for that goal OR the owner
    $stmtU = $pdo->prepare('SELECT DISTINCT user_id FROM checkins WHERE goal_id = ?');
    $stmtU->execute([$goal['id']]);
    $users = $stmtU->fetchAll(PDO::FETCH_COLUMN);
    // include owner
    if ($goal['created_by']) $users[] = $goal['created_by'];
    $users = array_unique($users);

    foreach ($users as $uid) {
        // compute current period
        $range = getPeriodRange($goal);
        $periodStart = $range[0];
        $periodEnd = $range[1];
        // check if we already have a period row for this goal/user/start
        $stmtC = $pdo->prepare('SELECT id FROM goal_periods WHERE goal_id = ? AND user_id = ? AND period_start = ?');
        $stmtC->execute([$goal['id'], $uid, $periodStart]);
        if ($stmtC->fetchColumn()) {
            // already recorded for current period; optionally update totals
            $stmtSum = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(value),0) AS total FROM checkins WHERE goal_id = ? AND user_id = ? AND date BETWEEN ? AND ?');
            $stmtSum->execute([$goal['id'], $uid, $periodStart, $periodEnd]);
            $s = $stmtSum->fetch(PDO::FETCH_ASSOC);
            $stmtUp = $pdo->prepare('UPDATE goal_periods SET total_value = ?, checkins_count = ?, created_at = NOW() WHERE goal_id = ? AND user_id = ? AND period_start = ?');
            $stmtUp->execute([(int)$s['total'], (int)$s['cnt'], $goal['id'], $uid, $periodStart]);
            continue;
        }
        // compute totals for the period
        $stmtSum = $pdo->prepare('SELECT COUNT(*) AS cnt, COALESCE(SUM(value),0) AS total FROM checkins WHERE goal_id = ? AND user_id = ? AND date BETWEEN ? AND ?');
        $stmtSum->execute([$goal['id'], $uid, $periodStart, $periodEnd]);
        $s = $stmtSum->fetch(PDO::FETCH_ASSOC);
        // insert a row for this period (snapshot)
        $stmtIns = $pdo->prepare('INSERT INTO goal_periods (goal_id, user_id, period_type, period_start, period_end, total_value, checkins_count) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmtIns->execute([$goal['id'], $uid, $goal['cadence'], $periodStart, $periodEnd, (int)$s['total'], (int)$s['cnt']]);
        echo "  -> user {$uid}: {$s['cnt']} checkins, total={$s['total']} (period {$periodStart} to {$periodEnd})\n";
    }
}

echo "Done.\n";
