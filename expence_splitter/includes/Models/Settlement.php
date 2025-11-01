<?php
// includes/Models/Settlement.php
require_once __DIR__ . '/../Database.php';

class Settlement
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getConnection();
    }


    public function addSettlement($group_id, $payer_id, $receiver_id, $amount)
    {
        $stmt = $this->db->prepare('INSERT INTO settlements (group_id, payer_id, receiver_id, amount) VALUES (?, ?, ?, ?)');
        if (!$stmt) return false;
        $stmt->bind_param('iiid', $group_id, $payer_id, $receiver_id, $amount);
        $res = $stmt->execute();
        $stmt->close();
        $id = $res ? $this->db->insert_id : false;
        if ($id) {
            // log activity
            $log = new Log();
            $log->add($payer_id, $group_id, sprintf('Settled ₹%s to user %d', number_format($amount, 2), $receiver_id));
        }
        return $id;
    }

    public function getGroupSettlements($group_id)
    {
        $stmt = $this->db->prepare('SELECT s.*, p.name as payer_name, r.name as receiver_name FROM settlements s LEFT JOIN users p ON s.payer_id = p.id LEFT JOIN users r ON s.receiver_id = r.id WHERE s.group_id = ? ORDER BY s.created_at DESC');
        if (!$stmt) return [];
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }
}
