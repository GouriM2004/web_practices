<?php
// includes/Models/Notification.php
require_once __DIR__ . '/../Database.php';

class Notification
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // Add a notification for a single user
    public function addNotification($user_id, $message, $group_id = null)
    {
        $stmt = $this->db->prepare('INSERT INTO notifications (user_id, group_id, message) VALUES (?, ?, ?)');
        if (!$stmt) return false;
        if ($group_id === null) $stmt->bind_param('iss', $user_id, $group_id, $message);
        else $stmt->bind_param('iis', $user_id, $group_id, $message);
        $res = $stmt->execute();
        $stmt->close();
        return $res ? $this->db->insert_id : false;
    }

    // Add notification to multiple users
    public function addNotifications($user_ids, $message, $group_id = null)
    {
        $ok = true;
        foreach ($user_ids as $uid) {
            $r = $this->addNotification($uid, $message, $group_id);
            if (!$r) $ok = false;
        }
        return $ok;
    }

    public function getUserNotifications($user_id, $limit = 20)
    {
        $stmt = $this->db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
        if (!$stmt) return [];
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    public function markRead($notification_id)
    {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $notification_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
}
