<?php
// includes/Models/PollBattle.php
require_once __DIR__ . '/../Database.php';

class PollBattle
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Create a new battle between two polls
     */
    public function createBattle($poll_a_id, $poll_b_id)
    {
        // Ensure different polls
        if ($poll_a_id === $poll_b_id) {
            return false;
        }

        // Check if both polls exist
        $stmt = $this->db->prepare("SELECT id FROM polls WHERE id IN (?, ?)");
        $stmt->bind_param("ii", $poll_a_id, $poll_b_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows !== 2) {
            $stmt->close();
            return false;
        }
        $stmt->close();

        // Create battle record
        $stmt = $this->db->prepare("
            INSERT INTO poll_battles (poll_a_id, poll_b_id, winner_id, loser_id, votes_a, votes_b, margin_of_victory)
            VALUES (?, ?, 0, 0, 0, 0, 0)
        ");
        $stmt->bind_param("ii", $poll_a_id, $poll_b_id);
        if ($stmt->execute()) {
            $battle_id = $this->db->insert_id;
            $stmt->close();
            return $battle_id;
        }
        $stmt->close();
        return false;
    }

    /**
     * Get an active battle or create one if none exists
     */
    public function getActiveBattle()
    {
        // Check if there's an incomplete battle (winner_id = 0)
        $sql = "
            SELECT pb.*, 
                   pa.question as poll_a_question, pa.votes as poll_a_total_votes,
                   pb_table.question as poll_b_question, pb_table.votes as poll_b_total_votes
            FROM poll_battles pb
            LEFT JOIN polls pa ON pb.poll_a_id = pa.id
            LEFT JOIN polls pb_table ON pb.poll_b_id = pb_table.id
            WHERE pb.winner_id = 0
            ORDER BY pb.created_at DESC
            LIMIT 1
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_assoc();
        }

        // No active battle, create a new one
        $activePollsRes = $this->db->query("
            SELECT id FROM polls WHERE is_active = 1 ORDER BY RAND() LIMIT 2
        ");

        if ($activePollsRes && $activePollsRes->num_rows === 2) {
            $polls = [];
            while ($row = $activePollsRes->fetch_assoc()) {
                $polls[] = $row['id'];
            }
            $battleId = $this->createBattle($polls[0], $polls[1]);
            return $this->getBattleById($battleId);
        }

        return null;
    }

    /**
     * Get battle by ID
     */
    public function getBattleById($battle_id)
    {
        $stmt = $this->db->prepare("
            SELECT pb.*, 
                   pa.question as poll_a_question, 
                   pb_table.question as poll_b_question
            FROM poll_battles pb
            LEFT JOIN polls pa ON pb.poll_a_id = pa.id
            LEFT JOIN polls pb_table ON pb.poll_b_id = pb_table.id
            WHERE pb.id = ?
        ");
        $stmt->bind_param("i", $battle_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    /**
     * Record a vote in battle
     */
    public function recordBattleVote($battle_id, $voted_for_id, $voter_ip, $voter_id = null)
    {
        // Check if voter already voted in this battle
        $checkStmt = $this->db->prepare("
            SELECT id FROM battle_votes 
            WHERE battle_id = ? AND voter_ip = ? AND (voter_id = ? OR (voter_id IS NULL AND ? IS NULL))
        ");
        $checkStmt->bind_param("isii", $battle_id, $voter_ip, $voter_id, $voter_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $checkStmt->close();
            return false; // Already voted
        }
        $checkStmt->close();

        // Record the vote
        $stmt = $this->db->prepare("
            INSERT INTO battle_votes (battle_id, voter_ip, voter_id, voted_for_id)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("isii", $battle_id, $voter_ip, $voter_id, $voted_for_id);
        if ($stmt->execute()) {
            $stmt->close();

            // Update battle vote counts
            $this->updateBattleVotes($battle_id);
            return true;
        }
        $stmt->close();
        return false;
    }

    /**
     * Update battle vote counts
     */
    private function updateBattleVotes($battle_id)
    {
        $battle = $this->getBattleById($battle_id);
        if (!$battle) return;

        // Count votes for each poll in this battle
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM battle_votes WHERE battle_id = ? AND voted_for_id = ?");

        $stmt->bind_param("ii", $battle_id, $battle['poll_a_id']);
        $stmt->execute();
        $votes_a = $stmt->get_result()->fetch_assoc()['count'];

        $stmt->bind_param("ii", $battle_id, $battle['poll_b_id']);
        $stmt->execute();
        $votes_b = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        // Determine winner
        $winner_id = 0;
        $loser_id = 0;
        $margin = 0;

        if ($votes_a > $votes_b) {
            $winner_id = $battle['poll_a_id'];
            $loser_id = $battle['poll_b_id'];
            $margin = $votes_a - $votes_b;
        } elseif ($votes_b > $votes_a) {
            $winner_id = $battle['poll_b_id'];
            $loser_id = $battle['poll_a_id'];
            $margin = $votes_b - $votes_a;
        }

        // Update battle record
        $updateStmt = $this->db->prepare("
            UPDATE poll_battles 
            SET votes_a = ?, votes_b = ?, winner_id = ?, loser_id = ?, margin_of_victory = ?
            WHERE id = ?
        ");
        $updateStmt->bind_param("iiiii", $votes_a, $votes_b, $winner_id, $loser_id, $margin, $battle_id);
        $updateStmt->execute();
        $updateStmt->close();

        // Update poll battle stats if there's a winner
        if ($winner_id > 0) {
            $this->updatePollBattleStats($winner_id, $loser_id);
        }
    }

    /**
     * Update poll battle statistics
     */
    private function updatePollBattleStats($winner_id, $loser_id)
    {
        // Increment winner wins
        $this->db->query("UPDATE polls SET battle_wins = battle_wins + 1 WHERE id = $winner_id");

        // Increment loser losses
        $this->db->query("UPDATE polls SET battle_losses = battle_losses + 1 WHERE id = $loser_id");

        // Update win rate for both
        $this->db->query("
            UPDATE polls 
            SET battle_win_rate = CASE 
                WHEN (battle_wins + battle_losses) > 0 THEN ROUND((battle_wins / (battle_wins + battle_losses)) * 100, 2)
                ELSE 0
            END
            WHERE id IN ($winner_id, $loser_id)
        ");
    }

    /**
     * Get trending polls (ranked by battle wins)
     */
    public function getTrendingPolls($limit = 10)
    {
        $stmt = $this->db->prepare("
            SELECT p.*, 
                   (p.battle_wins + p.battle_losses) as total_battles
            FROM polls p
            WHERE is_active = 1 AND (battle_wins > 0 OR battle_losses > 0)
            ORDER BY battle_win_rate DESC, battle_wins DESC, created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res ?: [];
    }

    /**
     * Get battle history
     */
    public function getBattleHistory($limit = 20)
    {
        $stmt = $this->db->prepare("
            SELECT pb.*,
                   pa.question as poll_a_question,
                   pb_table.question as poll_b_question,
                   pw.question as winner_question
            FROM poll_battles pb
            LEFT JOIN polls pa ON pb.poll_a_id = pa.id
            LEFT JOIN polls pb_table ON pb.poll_b_id = pb_table.id
            LEFT JOIN polls pw ON pb.winner_id = pw.id
            WHERE pb.winner_id > 0
            ORDER BY pb.created_at DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res ?: [];
    }

    /**
     * Get poll's battle record
     */
    public function getPollBattleRecord($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                battle_wins, 
                battle_losses, 
                battle_win_rate,
                (battle_wins + battle_losses) as total_battles
            FROM polls
            WHERE id = ?
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    /**
     * Get battle stats for leaderboard
     */
    public function getBattleLeaderboard($limit = 20)
    {
        $stmt = $this->db->prepare("
            SELECT p.id, p.question, p.battle_wins, p.battle_losses, p.battle_win_rate,
                   (p.battle_wins + p.battle_losses) as total_battles
            FROM polls p
            WHERE (p.battle_wins > 0 OR p.battle_losses > 0)
            ORDER BY p.battle_win_rate DESC, p.battle_wins DESC
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res ?: [];
    }
}
