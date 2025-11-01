<?php
// includes/Models/Expense.php
require_once __DIR__ . '/../Database.php';

class Expense
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Add an expense and create expense_shares according to split mode.
     * $split_mode: 'equal' (default), 'percentage', 'custom'
     * $split_values for 'percentage' should be associative [user_id => percent]
     * $split_values for 'custom' should be associative [user_id => amount]
     * $shared_with is an indexed array of user ids to include in the split (for 'equal' it's used)
     */
    public function addExpense($group_id, $title, $amount, $paid_by, $shared_with = [], $split_mode = 'equal', $split_values = null, $category = null)
    {
        $stmt = $this->db->prepare('INSERT INTO expenses (group_id, title, category, amount, paid_by) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('issdi', $group_id, $title, $category, $amount, $paid_by);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $expense_id = $stmt->insert_id;
        $stmt->close();

        // build shares: map user_id => share_amount
        $shares = [];
        if ($split_mode === 'equal') {
            $count = count($shared_with);
            if ($count === 0) return $expense_id; // nothing to assign
            $per = round($amount / $count, 2);
            $assigned = 0.0;
            foreach ($shared_with as $i => $uid) {
                $uid = intval($uid);
                if ($i === $count - 1) {
                    $share = round($amount - $assigned, 2);
                } else {
                    $share = $per;
                    $assigned += $share;
                }
                $shares[$uid] = $share;
            }
        } elseif ($split_mode === 'percentage' && is_array($split_values)) {
            // $split_values: [uid => percent]
            $sumPct = 0.0;
            foreach ($split_values as $uid => $pct) $sumPct += floatval($pct);
            if (abs($sumPct - 100.0) > 0.5) {
                // invalid percentages, cleanup and return
                return $expense_id;
            }
            $assigned = 0.0;
            $i = 0;
            $keys = array_keys($split_values);
            foreach ($split_values as $uid => $pct) {
                $uid = intval($uid);
                $i++;
                if ($i === count($split_values)) {
                    $share = round($amount - $assigned, 2);
                } else {
                    $share = round($amount * (floatval($pct) / 100.0), 2);
                    $assigned += $share;
                }
                $shares[$uid] = $share;
            }
        } elseif ($split_mode === 'custom' && is_array($split_values)) {
            // $split_values: [uid => amount]
            $sumAmt = 0.0;
            foreach ($split_values as $uid => $a) $sumAmt += floatval($a);
            if (abs($sumAmt - $amount) > 0.5) {
                // mismatch in sums, do not create shares
                return $expense_id;
            }
            foreach ($split_values as $uid => $a) {
                $shares[intval($uid)] = round(floatval($a), 2);
            }
        }

        // persist shares
        foreach ($shares as $uid => $share) {
            $stmt2 = $this->db->prepare('INSERT INTO expense_shares (expense_id, user_id, share_amount) VALUES (?, ?, ?)');
            $stmt2->bind_param('iid', $expense_id, $uid, $share);
            $stmt2->execute();
            $stmt2->close();
        }

        // Notify group members (in-app) and optionally email
        // fetch members and emails
        $stmt3 = $this->db->prepare('SELECT u.id, u.email, u.name FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?');
        if ($stmt3) {
            $stmt3->bind_param('i', $group_id);
            $stmt3->execute();
            $members = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt3->close();
            $notifUsers = [];
            foreach ($members as $m) {
                if (intval($m['id']) === intval($paid_by)) continue;
                $notifUsers[] = intval($m['id']);
            }
            // build message
            $payerName = '';
            $stmt4 = $this->db->prepare('SELECT name FROM users WHERE id = ?');
            if ($stmt4) {
                $stmt4->bind_param('i', $paid_by);
                $stmt4->execute();
                $r = $stmt4->get_result()->fetch_assoc();
                $payerName = $r['name'] ?? '';
                $stmt4->close();
            }
            $catText = $category ? ' (' . $category . ')' : '';
            $msg = sprintf('New expense "%s"%s of ₹%s added by %s in group %d', $title, $catText, number_format($amount, 2), $payerName, $group_id);
            // add notifications
            if (!empty($notifUsers)) {
                // lazy-load Notification via autoloader
                $notif = new Notification();
                $notif->addNotifications($notifUsers, $msg, $group_id);
                // attempt send email too (best-effort)
                foreach ($members as $m) {
                    if (intval($m['id']) === intval($paid_by)) continue;
                    if (!empty($m['email'])) {
                        $subject = 'New expense in group';
                        $body = "$msg\n\nLogin to view details.";
                        @mail($m['email'], $subject, $body);
                    }
                }
            }
            // add activity log
            $log = new Log();
            $log->add($paid_by, $group_id, sprintf('Added expense "%s"%s of ₹%s', $title, $catText, number_format($amount, 2)));
        }

        return $expense_id;
    }

    public function getGroupExpenses($group_id)
    {
        $stmt = $this->db->prepare('SELECT e.*, u.name as paid_by_name FROM expenses e LEFT JOIN users u ON e.paid_by = u.id WHERE e.group_id = ? ORDER BY e.created_at DESC');
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }
}
