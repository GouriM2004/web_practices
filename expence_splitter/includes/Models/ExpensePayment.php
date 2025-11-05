<?php
// includes/Models/ExpensePayment.php
require_once __DIR__ . '/../Database.php';

class ExpensePayment
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function addPayment($expense_id, $payer_id, $receiver_id, $amount)
    {
        $stmt = $this->db->prepare('INSERT INTO expense_payments (expense_id, payer_id, receiver_id, amount) VALUES (?, ?, ?, ?)');
        if (!$stmt) return false;
        $stmt->bind_param('iiid', $expense_id, $payer_id, $receiver_id, $amount);
        $res = $stmt->execute();
        $stmt->close();
        return $res ? $this->db->insert_id : false;
    }

    public function getPaymentsByExpense($expense_id)
    {
        $stmt = $this->db->prepare('SELECT ep.*, u1.name as payer_name, u2.name as receiver_name FROM expense_payments ep JOIN users u1 ON ep.payer_id = u1.id JOIN users u2 ON ep.receiver_id = u2.id WHERE ep.expense_id = ? ORDER BY ep.created_at DESC');
        $stmt->bind_param('i', $expense_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    public function getPaymentsByGroup($group_id)
    {
        $stmt = $this->db->prepare('SELECT ep.*, ep.created_at, u1.name as payer_name, u2.name as receiver_name, ep.expense_id FROM expense_payments ep JOIN expenses e ON ep.expense_id = e.id JOIN users u1 ON ep.payer_id = u1.id JOIN users u2 ON ep.receiver_id = u2.id WHERE e.group_id = ? ORDER BY ep.created_at DESC');
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }
}
