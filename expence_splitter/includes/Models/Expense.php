<?php
// includes/Models/Expense.php
require_once __DIR__ . '/../Database.php';

class Expense {
    private $db;
    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function addExpense($group_id, $title, $amount, $paid_by, $shared_with = []) {
        $stmt = $this->db->prepare('INSERT INTO expenses (group_id, title, amount, paid_by) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('isdi', $group_id, $title, $amount, $paid_by);
        if ($stmt->execute()) {
            $expense_id = $stmt->insert_id;
            $stmt->close();
            $count = count($shared_with);
            if ($count === 0) return $expense_id;
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
                $stmt2 = $this->db->prepare('INSERT INTO expense_shares (expense_id, user_id, share_amount) VALUES (?, ?, ?)');
                $stmt2->bind_param('iid', $expense_id, $uid, $share);
                $stmt2->execute();
                $stmt2->close();
            }
            return $expense_id;
        }
        $stmt->close();
        return false;
    }

    public function getGroupExpenses($group_id) {
        $stmt = $this->db->prepare('SELECT e.*, u.name as paid_by_name FROM expenses e LEFT JOIN users u ON e.paid_by = u.id WHERE e.group_id = ? ORDER BY e.created_at DESC');
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }
}
