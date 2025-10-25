<?php
// includes/Models/Group.php
require_once __DIR__ . '/../Database.php';

class Group
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create($name, $created_by)
    {
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

    public function addMember($group_id, $user_id)
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO group_members (group_id, user_id) VALUES (?, ?)');
        $stmt->bind_param('ii', $group_id, $user_id);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    public function getUserGroups($user_id)
    {
        $stmt = $this->db->prepare('SELECT g.id, g.name, g.created_at FROM groups_tbl g JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    public function getGroup($group_id, $user_id = null)
    {
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

    public function getMembers($group_id)
    {
        $stmt = $this->db->prepare('SELECT u.id, u.name, u.email FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?');
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    /**
     * Delete a group. Only the group creator (created_by) can delete the group.
     * Returns true on success (group deleted), false otherwise.
     */
    public function delete($group_id, $user_id)
    {
        // Use a prepared statement that ensures only the creator can delete
        $stmt = $this->db->prepare('DELETE FROM groups_tbl WHERE id = ? AND created_by = ?');
        if (!$stmt) return false;
        $stmt->bind_param('ii', $group_id, $user_id);
        $res = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return ($res && $affected > 0);
    }

    /**
     * Remove a member from a group.
     * The requester ($requester_id) can remove a member if they are the group's creator.
     * A user can also remove themselves (leave the group) unless they are the group's creator.
     * Returns true on success, false otherwise.
     */
    public function removeMember($group_id, $target_user_id, $requester_id)
    {
        // fetch group to check creator
        $group = $this->getGroup($group_id);
        if (!$group) return false;
        $creator = $group['created_by'] ?? null;

        // cannot remove the creator from the group
        if ($target_user_id == $creator) {
            return false;
        }

        // allow if requester is creator (kick) or requester is the target user (leave)
        if ($requester_id != $creator && $requester_id != $target_user_id) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM group_members WHERE group_id = ? AND user_id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('ii', $group_id, $target_user_id);
        $res = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return ($res && $affected > 0);
    }
}
