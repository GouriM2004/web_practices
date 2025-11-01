<?php
// includes/Models/Log.php
require_once __DIR__ . '/../Database.php';

class Log
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function add($user_id, $group_id, $action)
    {
        $stmt = $this->db->prepare('INSERT INTO logs (user_id, group_id, action) VALUES (?, ?, ?)');
        if (!$stmt) return false;
        $stmt->bind_param('iis', $user_id, $group_id, $action);
        $res = $stmt->execute();
        $stmt->close();
        return $res ? $this->db->insert_id : false;
    }

    public function getGroupLogs($group_id, $limit = 20)
    {
        $stmt = $this->db->prepare('SELECT l.*, u.name as user_name FROM logs l LEFT JOIN users u ON l.user_id = u.id WHERE l.group_id = ? ORDER BY l.created_at DESC LIMIT ?');
        if (!$stmt) return [];
        $stmt->bind_param('ii', $group_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }
}
