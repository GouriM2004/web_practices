<?php
// includes/Models/Group.php
require_once __DIR__ . '/../Database.php';

class Group {
    private $db;
    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function create($name, $created_by) {
        $stmt = $this->db->prepare('INSERT INTO groups_tbl (name, created_by) VALUES (?, ?)');
        $stmt->bind_param('si', $name, $created_by);
        if ($stmt->execute()) {
            $gid = $stmt->insert_id;
            $stmt->close();
            // add creator as member
            $stmt2 = $this->db->prepare('INSERT INTO group_members (group_id, user_id) VALUES (?, ?)');
            $stmt2->bind_param('ii', $gid, $created_by);
            $stmt2->execute();
            $stmt2->close();
            return $gid;
        }
        $stmt->close();
        return false;
    }

    public function addMember($group_id, $user_id) {
        $stmt = $this->db->prepare('INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $group_id, $user_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function getUserGroups($user_id) {
        $stmt = $this->db->prepare('SELECT g.id, g.name, g.created_at FROM groups_tbl g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    public function getGroup($group_id, $user_id = null) {
        if ($user_id) {
            $stmt = $this->db->prepare('SELECT g.* FROM groups_tbl g JOIN group_members gm ON g.id = gm.group_id WHERE g.id = ? AND gm.user_id = ?');
            $stmt->bind_param('ii', $group_id, $user_id);
        } else {
            $stmt = $this->db->prepare('SELECT * FROM groups_tbl WHERE id = ?');
            $stmt->bind_param('i', $group_id);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    public function getMembers($group_id) {
        $stmt = $this->db->prepare('SELECT u.id, u.name, u.email FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?');
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }
}
