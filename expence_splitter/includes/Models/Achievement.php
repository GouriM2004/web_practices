<?php
// includes/Models/Achievement.php
require_once __DIR__ . '/../Database.php';

class Achievement
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureSeeded();
    }

    // seed a few basic achievements if not present
    private function ensureSeeded()
    {
        $seed = [
            ['code' => 'first_expense', 'name' => 'First Expense', 'description' => 'Recorded your first expense in a group', 'points' => 10],
            ['code' => 'first_settle', 'name' => 'First to Settle', 'description' => 'Recorded your first payment/settlement', 'points' => 15],
            ['code' => 'budget_keeper', 'name' => 'Budget Keeper', 'description' => 'Kept monthly spending under budget (heuristic)', 'points' => 20],
        ];
        foreach ($seed as $s) {
            $stmt = $this->db->prepare('SELECT id FROM achievements WHERE code = ? LIMIT 1');
            $stmt->bind_param('s', $s['code']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$res) {
                $ins = $this->db->prepare('INSERT INTO achievements (code, name, description, points) VALUES (?, ?, ?, ?)');
                $ins->bind_param('sssi', $s['code'], $s['name'], $s['description'], $s['points']);
                $ins->execute();
                $ins->close();
            }
        }
    }

    public function getByCode($code)
    {
        $stmt = $this->db->prepare('SELECT * FROM achievements WHERE code = ? LIMIT 1');
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    public function hasAchievement($code, $user_id, $group_id = null)
    {
        $ach = $this->getByCode($code);
        if (!$ach) return false;
        $stmt = $this->db->prepare('SELECT id FROM user_achievements WHERE achievement_id = ? AND user_id = ? AND (group_id = ? OR group_id IS NULL) LIMIT 1');
        $stmt->bind_param('iii', $ach['id'], $user_id, $group_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (bool)$res;
    }

    public function award($code, $user_id, $group_id = null, $meta = null)
    {
        $ach = $this->getByCode($code);
        if (!$ach) return false;
        // prevent duplicate
        if ($this->hasAchievement($code, $user_id, $group_id)) return false;
        $metaJson = $meta ? json_encode($meta) : null;
        $stmt = $this->db->prepare('INSERT INTO user_achievements (achievement_id, user_id, group_id, meta) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iiis', $ach['id'], $user_id, $group_id, $metaJson);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? $this->db->insert_id : false;
    }

    public function getUserAchievements($group_id, $user_id)
    {
        $stmt = $this->db->prepare('SELECT ua.*, a.code, a.name, a.description, a.points FROM user_achievements ua JOIN achievements a ON ua.achievement_id = a.id WHERE ua.user_id = ? AND (ua.group_id = ? OR ua.group_id IS NULL) ORDER BY ua.awarded_at DESC');
        $stmt->bind_param('ii', $user_id, $group_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    // Leaderboard: top payers by total paid
    public function getTopPayers($group_id, $limit = 5)
    {
        $stmt = $this->db->prepare('SELECT e.paid_by as user_id, u.name, COUNT(*) as tx_count, SUM(e.amount) as total_paid FROM expenses e JOIN users u ON e.paid_by = u.id WHERE e.group_id = ? GROUP BY e.paid_by ORDER BY total_paid DESC LIMIT ?');
        $stmt->bind_param('ii', $group_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    // Most active: most expenses created
    public function getMostActive($group_id, $limit = 5)
    {
        $stmt = $this->db->prepare('SELECT e.paid_by as user_id, u.name, COUNT(*) as expense_count FROM expenses e JOIN users u ON e.paid_by = u.id WHERE e.group_id = ? GROUP BY e.paid_by ORDER BY expense_count DESC LIMIT ?');
        $stmt->bind_param('ii', $group_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }
}
