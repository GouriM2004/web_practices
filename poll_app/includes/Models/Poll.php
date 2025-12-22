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

    public function getActivePolls($limit = 10)
    {
        $stmt = $this->db->prepare("SELECT * FROM polls WHERE is_active = 1 ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res ?: [];
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

    public function recordVote($poll_id, $option_ids, $ip, $voterId = null, $voterName = null, $isPublic = 0, $location = null, $confidence_level = 'somewhat_sure')
    {
        // normalize to array
        if (!is_array($option_ids)) {
            $option_ids = [$option_ids];
        }
        $option_ids = array_values(array_filter(array_map('intval', $option_ids), function ($id) {
            return $id > 0;
        }));

        $voterIdParam = $voterId ?? null;
        $voterNameParam = $voterName ?? null;
        $isPublicFlag = $isPublic ? 1 : 0;
        $locationParam = $location ?? null;

        // Fetch any existing votes by this voter or IP for the poll
        $existing = [];
        if ($voterId !== null) {
            $stmt = $this->db->prepare("SELECT id, option_id, voted_at FROM poll_votes WHERE poll_id = ? AND voter_id = ?");
            $stmt->bind_param("ii", $poll_id, $voterId);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
            $stmt->close();
        } else {
            $stmt = $this->db->prepare("SELECT id, option_id, voted_at FROM poll_votes WHERE poll_id = ? AND voter_ip = ?");
            $stmt->bind_param("is", $poll_id, $ip);
            $stmt->execute();
            $existing = $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
            $stmt->close();
        }

        // If no previous vote: fresh insert
        if (empty($existing)) {
            $this->db->begin_transaction();
            try {
                foreach ($option_ids as $option_id) {
                    $stmt = $this->db->prepare("UPDATE poll_options SET votes = votes + 1 WHERE id = ? AND poll_id = ?");
                    $stmt->bind_param("ii", $option_id, $poll_id);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $this->db->prepare("INSERT INTO poll_votes (poll_id, option_id, voter_ip, voter_id, voter_name, is_public, confidence_level, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisisiss", $poll_id, $option_id, $ip, $voterIdParam, $voterNameParam, $isPublicFlag, $confidence_level, $locationParam);
                    $stmt->execute();
                    $stmt->close();
                }
                $this->db->commit();
                return ['ok' => true, 'updated' => false];
            } catch (\Throwable $e) {
                $this->db->rollback();
                return ['ok' => false, 'reason' => 'error'];
            }
        }

        // Previous vote exists: check change window
        $windowMinutes = \Config::VOTE_CHANGE_WINDOW_MINUTES ?? 0;
        if ($windowMinutes <= 0) {
            return ['ok' => false, 'reason' => 'locked'];
        }

        $latestVotedAt = null;
        foreach ($existing as $row) {
            $ts = strtotime($row['voted_at']);
            if ($ts && ($latestVotedAt === null || $ts > $latestVotedAt)) {
                $latestVotedAt = $ts;
            }
        }
        if ($latestVotedAt === null) {
            return ['ok' => false, 'reason' => 'locked'];
        }

        $deadline = $latestVotedAt + ($windowMinutes * 60);
        if (time() > $deadline) {
            return ['ok' => false, 'reason' => 'locked'];
        }

        // Within window: replace previous choices with new ones
        $this->db->begin_transaction();
        try {
            // Decrement counts for previous options
            foreach ($existing as $row) {
                $prevOptionId = (int)$row['option_id'];
                $stmt = $this->db->prepare("UPDATE poll_options SET votes = GREATEST(votes - 1, 0) WHERE id = ? AND poll_id = ?");
                $stmt->bind_param("ii", $prevOptionId, $poll_id);
                $stmt->execute();
                $stmt->close();
            }

            // Delete previous vote records
            if ($voterId !== null) {
                $stmt = $this->db->prepare("DELETE FROM poll_votes WHERE poll_id = ? AND voter_id = ?");
                $stmt->bind_param("ii", $poll_id, $voterId);
            } else {
                $stmt = $this->db->prepare("DELETE FROM poll_votes WHERE poll_id = ? AND voter_ip = ?");
                $stmt->bind_param("is", $poll_id, $ip);
            }
            $stmt->execute();
            $stmt->close();

            // Insert new selections
            foreach ($option_ids as $option_id) {
                $stmt = $this->db->prepare("UPDATE poll_options SET votes = votes + 1 WHERE id = ? AND poll_id = ?");
                $stmt->bind_param("ii", $option_id, $poll_id);
                $stmt->execute();
                $stmt->close();

                $stmt = $this->db->prepare("INSERT INTO poll_votes (poll_id, option_id, voter_ip, voter_id, voter_name, is_public, confidence_level, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisisiss", $poll_id, $option_id, $ip, $voterIdParam, $voterNameParam, $isPublicFlag, $confidence_level, $locationParam);
                $stmt->execute();
                $stmt->close();
            }

            $this->db->commit();
            return ['ok' => true, 'updated' => true, 'deadline' => $deadline];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'reason' => 'error'];
        }
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

    public function createPoll($question, array $options, $allow_multiple = 0, $category = 'General', $location_tag = null)
    {
        $stmt = $this->db->prepare("INSERT INTO polls (question, is_active, allow_multiple, category, location_tag) VALUES (?, 1, ?, ?, ?)");
        $stmt->bind_param("siss", $question, $allow_multiple, $category, $location_tag);
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

    // Get last known location for a voter
    public function getLastVoterLocation($voterId)
    {
        $stmt = $this->db->prepare("SELECT location FROM poll_votes WHERE voter_id = ? AND location IS NOT NULL AND location != '' ORDER BY voted_at DESC LIMIT 1");
        $stmt->bind_param("i", $voterId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row['location'] ?? null;
    }

    // Recommend polls based on past votes, categories, and location
    public function getRecommendedPolls($voterId = null, $location = null, $limit = 5)
    {
        // If no voter, just return recent active polls
        if ($voterId === null) {
            return $this->getActivePolls($limit);
        }

        // Preferred categories from past votes
        $preferredCategories = [];
        $stmt = $this->db->prepare(
            "SELECT p.category, COUNT(*) as c
             FROM poll_votes pv
             JOIN polls p ON pv.poll_id = p.id
             WHERE pv.voter_id = ? AND p.category IS NOT NULL
             GROUP BY p.category
             ORDER BY c DESC
             LIMIT 5"
        );
        $stmt->bind_param("i", $voterId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        foreach ($res as $row) {
            $preferredCategories[] = $row['category'];
        }

        // Fallback location
        if (!$location) {
            $location = $this->getLastVoterLocation($voterId);
        }

        // Fetch candidate polls: active, not already voted by this user
        $stmt = $this->db->prepare(
            "SELECT p.*
             FROM polls p
             WHERE p.is_active = 1
             AND NOT EXISTS (SELECT 1 FROM poll_votes pv WHERE pv.poll_id = p.id AND pv.voter_id = ?)
             ORDER BY p.created_at DESC
             LIMIT 50"
        );
        $stmt->bind_param("i", $voterId);
        $stmt->execute();
        $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Score candidates
        $scored = [];
        foreach ($candidates as $poll) {
            $score = 0;
            if (!empty($preferredCategories) && in_array($poll['category'], $preferredCategories, true)) {
                $score += 3;
            }
            if ($location && !empty($poll['location_tag']) && strcasecmp($location, $poll['location_tag']) === 0) {
                $score += 2;
            }
            $created = strtotime($poll['created_at']);
            if ($created >= strtotime('-7 days')) {
                $score += 1; // freshness boost
            }
            $scored[] = ['poll' => $poll, 'score' => $score];
        }

        // Sort by score desc, then created_at desc
        usort($scored, function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return strcmp($b['poll']['created_at'], $a['poll']['created_at']);
            }
            return $b['score'] <=> $a['score'];
        });

        $recommendations = array_slice(array_map(function ($item) {
            return $item['poll'];
        }, $scored), 0, $limit);

        // Fallback if empty
        if (empty($recommendations)) {
            return $this->getActivePolls($limit);
        }
        return $recommendations;
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

    // Get confidence level statistics for a poll
    public function getConfidenceStats($poll_id)
    {
        // Overall confidence breakdown
        $stmt = $this->db->prepare("
            SELECT 
                confidence_level,
                COUNT(*) as count,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM poll_votes WHERE poll_id = ?), 1) as percentage
            FROM poll_votes
            WHERE poll_id = ?
            GROUP BY confidence_level
            ORDER BY FIELD(confidence_level, 'very_sure', 'somewhat_sure', 'just_guessing')
        ");
        $stmt->bind_param("ii", $poll_id, $poll_id);
        $stmt->execute();
        $overall = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Confidence breakdown by option
        $stmt = $this->db->prepare("
            SELECT 
                po.id as option_id,
                po.option_text,
                pv.confidence_level,
                COUNT(*) as count
            FROM poll_votes pv
            JOIN poll_options po ON pv.option_id = po.id
            WHERE pv.poll_id = ?
            GROUP BY po.id, po.option_text, pv.confidence_level
            ORDER BY po.id, FIELD(pv.confidence_level, 'very_sure', 'somewhat_sure', 'just_guessing')
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $byOption = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'overall' => $overall,
            'by_option' => $byOption
        ];
    }
}
