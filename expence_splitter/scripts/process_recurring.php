<?php
// scripts/process_recurring.php
// Intended to be run from CLI via cron to create instances of recurring expenses.
require_once __DIR__ . '/../includes/autoload.php';
date_default_timezone_set('UTC');
$db = Database::getConnection();

$stmt = $db->prepare('SELECT * FROM expenses WHERE is_recurring = 1 AND next_run IS NOT NULL AND next_run <= CURDATE()');
if (!$stmt) {
    echo "No recurring expenses or DB error\n";
    exit(0);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($rows as $r) {
    $origId = intval($r['id']);
    $group_id = intval($r['group_id']);
    $title = $r['title'];
    $category = $r['category'];
    $amount = (float)$r['amount'];
    $paid_by = intval($r['paid_by']);
    $next_run = $r['next_run'];
    $interval = $r['recurring_interval'] ?: 'monthly';
    $recurring_until = $r['recurring_until'];

    // insert new expense instance
    $ins = $db->prepare('INSERT INTO expenses (group_id, title, category, amount, paid_by, created_at, recurring_source_id) VALUES (?, ?, ?, ?, ?, NOW(), ?)');
    if (!$ins) continue;
    $ins->bind_param('issdis', $group_id, $title, $category, $amount, $paid_by, $origId);
    $ok = $ins->execute();
    if (!$ok) {
        $ins->close();
        continue;
    }
    $newId = $ins->insert_id;
    $ins->close();

    // copy shares from original
    $s = $db->prepare('SELECT user_id, share_amount FROM expense_shares WHERE expense_id = ?');
    if ($s) {
        $s->bind_param('i', $origId);
        $s->execute();
        $shares = $s->get_result()->fetch_all(MYSQLI_ASSOC);
        $s->close();
        foreach ($shares as $sh) {
            $stmt2 = $db->prepare('INSERT INTO expense_shares (expense_id, user_id, share_amount) VALUES (?, ?, ?)');
            if (!$stmt2) continue;
            $stmt2->bind_param('iid', $newId, $sh['user_id'], $sh['share_amount']);
            $stmt2->execute();
            $stmt2->close();
        }
    }

    // Update next_run on the original template
    try {
        $dt = new DateTime($next_run);
        if ($interval === 'monthly') {
            $dt->modify('+1 month');
        } elseif ($interval === 'weekly') {
            $dt->modify('+7 days');
        } else {
            // default monthly
            $dt->modify('+1 month');
        }
        $newNext = $dt->format('Y-m-d');
        // if recurring_until set and passed, disable recurrence
        if (!empty($recurring_until) && $newNext > $recurring_until) {
            $u2 = $db->prepare('UPDATE expenses SET is_recurring = 0, next_run = NULL WHERE id = ?');
            $u2->bind_param('i', $origId);
            $u2->execute();
            $u2->close();
        } else {
            $u2 = $db->prepare('UPDATE expenses SET next_run = ? WHERE id = ?');
            $u2->bind_param('si', $newNext, $origId);
            $u2->execute();
            $u2->close();
        }
    } catch (Exception $ex) {
        // ignore and continue
    }

    echo "Created recurring expense $newId from template $origId\n";
}

echo "Processed " . count($rows) . " recurring templates\n";
