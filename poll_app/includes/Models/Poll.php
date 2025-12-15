<?php
// includes/Models/Poll.php
require_once __DIR__ . '/../Database.php';

class Poll
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getActivePoll()
    {
        $sql = "SELECT * FROM polls WHERE is_active = 1 ORDER BY created_at DESC LIMIT 1";
        $res = $this->db->query($sql);
        return $res && $res->num_rows ? $res->fetch_assoc() : null;
    }

    public function getPollById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM polls WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    public function getOptions($poll_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM poll_options WHERE poll_id = ?");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    public function recordVote($poll_id, $option_ids, $ip, $voterId = null, $voterName = null, $isPublic = 0, $location = null)
    {
        // block duplicate votes by voter account or IP
        $dupByUser = false;
        if ($voterId !== null) {
            $stmt = $this->db->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND voter_id = ? LIMIT 1");
            $stmt->bind_param("ii", $poll_id, $voterId);
            $stmt->execute();
            $dupByUser = $stmt->get_result()->num_rows > 0;
            $stmt->close();
        }

        $stmt = $this->db->prepare("SELECT id FROM poll_votes WHERE poll_id = ? AND voter_ip = ? LIMIT 1");
        $stmt->bind_param("is", $poll_id, $ip);
        $stmt->execute();
        $dupByIp = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($dupByUser || $dupByIp) return false;

        // normalize to array
        if (!is_array($option_ids)) {
            $option_ids = [$option_ids];
        }

        $voterIdParam = $voterId ?? null;
        $voterNameParam = $voterName ?? null;
        $isPublicFlag = $isPublic ? 1 : 0;
        $locationParam = $location ?? null;

        // process each selected option
        foreach ($option_ids as $option_id) {
            $option_id = (int)$option_id;
            if ($option_id <= 0) continue;

            // increase vote count
            $stmt = $this->db->prepare("UPDATE poll_options SET votes = votes + 1 WHERE id = ? AND poll_id = ?");
            $stmt->bind_param("ii", $option_id, $poll_id);
            $stmt->execute();
            $stmt->close();

            // insert vote record
            $stmt = $this->db->prepare("INSERT INTO poll_votes (poll_id, option_id, voter_ip, voter_id, voter_name, is_public, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisisis", $poll_id, $option_id, $ip, $voterIdParam, $voterNameParam, $isPublicFlag, $locationParam);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    }

    public function getAllPolls()
    {
        $sql = "SELECT * FROM polls ORDER BY created_at DESC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function toggleActive($id)
    {
        $stmt = $this->db->prepare("SELECT is_active FROM polls WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) return false;
        $newVal = $row['is_active'] ? 0 : 1;
        $stmt = $this->db->prepare("UPDATE polls SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $newVal, $id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    public function deletePoll($id)
    {
        $stmt = $this->db->prepare("DELETE FROM polls WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function createPoll($question, array $options, $allow_multiple = 0)
    {
        $stmt = $this->db->prepare("INSERT INTO polls (question, is_active, allow_multiple) VALUES (?, 1, ?)");
        $stmt->bind_param("si", $question, $allow_multiple);
        if (!$stmt->execute()) {
            $stmt->close();
            return false;
        }
        $poll_id = $stmt->insert_id;
        $stmt->close();

        $stmtOpt = $this->db->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
        foreach ($options as $opt) {
            $stmtOpt->bind_param("is", $poll_id, $opt);
            $stmtOpt->execute();
        }
        $stmtOpt->close();
        return $poll_id;
    }

    public function countOptions($poll_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS c FROM poll_options WHERE poll_id = ?");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (int)$row['c'] : 0;
    }

    // List distinct public voters for a poll (for transparency display)
    public function getPublicVoters($poll_id)
    {
        $stmt = $this->db->prepare("SELECT DISTINCT voter_name FROM poll_votes WHERE poll_id = ? AND is_public = 1 AND voter_name IS NOT NULL ORDER BY voter_name ASC");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res ?: [];
    }

    // Get geographical voting breakdown for a poll
    public function getGeographicalBreakdown($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                pv.location,
                po.option_text,
                COUNT(*) as vote_count
            FROM poll_votes pv
            JOIN poll_options po ON pv.option_id = po.id
            WHERE pv.poll_id = ? AND pv.location IS NOT NULL AND pv.location != ''
            GROUP BY pv.location, po.option_text
            ORDER BY pv.location, vote_count DESC
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res ?: [];
    }
}
