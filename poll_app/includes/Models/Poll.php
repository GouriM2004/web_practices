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

    private function normalizeVoterType($voterType)
    {
        $voterType = strtolower((string)$voterType);
        $allowed = ['expert', 'student', 'public'];
        if (!in_array($voterType, $allowed, true)) {
            return 'public';
        }
        return $voterType;
    }

    private function getVoterTypeWeights()
    {
        $default = ['expert' => 2.0, 'student' => 1.5, 'public' => 1.0];
        $configured = \Config::VOTER_TYPE_WEIGHTS ?? $default;
        if (!is_array($configured)) {
            return $default;
        }

        // Merge configured weights but keep only known types
        $weights = $default;
        foreach ($default as $type => $fallbackWeight) {
            if (isset($configured[$type]) && is_numeric($configured[$type])) {
                $weights[$type] = (float)$configured[$type];
            }
        }

        return $weights;
    }

    public function recordVote($poll_id, $option_ids, $ip, $voterId = null, $voterName = null, $isPublic = 0, $location = null, $confidence_level = 'somewhat_sure', $voter_type = 'public')
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
        $voterType = $this->normalizeVoterType($voter_type);

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

                    $stmt = $this->db->prepare("INSERT INTO poll_votes (poll_id, option_id, voter_ip, voter_id, voter_name, is_public, confidence_level, location, voter_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisisisss", $poll_id, $option_id, $ip, $voterIdParam, $voterNameParam, $isPublicFlag, $confidence_level, $locationParam, $voterType);
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

                $stmt = $this->db->prepare("INSERT INTO poll_votes (poll_id, option_id, voter_ip, voter_id, voter_name, is_public, confidence_level, location, voter_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisisisss", $poll_id, $option_id, $ip, $voterIdParam, $voterNameParam, $isPublicFlag, $confidence_level, $locationParam, $voterType);
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

    // Weighted and segmented results by voter type (expert/student/public)
    public function getVoterTypeLayeredResults($poll_id)
    {
        $weights = $this->getVoterTypeWeights();
        $typeOrder = array_keys($weights);

        // Totals by type
        $totalsByType = array_fill_keys($typeOrder, 0);
        $stmt = $this->db->prepare(
            "SELECT COALESCE(voter_type, 'public') AS voter_type, COUNT(*) AS count
             FROM poll_votes
             WHERE poll_id = ?
             GROUP BY COALESCE(voter_type, 'public')"
        );
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($res as $row) {
            $type = $this->normalizeVoterType($row['voter_type']);
            $totalsByType[$type] = (int)$row['count'];
        }

        $weightedByType = [];
        foreach ($totalsByType as $type => $count) {
            $weightedByType[$type] = $count * ($weights[$type] ?? 1.0);
        }

        $rawTotal = array_sum($totalsByType);
        $weightedTotal = array_sum($weightedByType);

        // Per-option counts by type
        $options = [];
        $stmt = $this->db->prepare(
            "SELECT 
                po.id AS option_id,
                po.option_text,
                COALESCE(pv.voter_type, 'public') AS voter_type,
                COUNT(*) AS count
             FROM poll_votes pv
             JOIN poll_options po ON pv.option_id = po.id
             WHERE pv.poll_id = ?
             GROUP BY po.id, po.option_text, COALESCE(pv.voter_type, 'public')
             ORDER BY po.id"
        );
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($rows as $row) {
            $optId = (int)$row['option_id'];
            $type = $this->normalizeVoterType($row['voter_type']);
            if (!isset($options[$optId])) {
                $options[$optId] = [
                    'id' => $optId,
                    'text' => $row['option_text'],
                    'raw_votes' => 0,
                    'weighted_votes' => 0.0,
                    'by_type' => array_fill_keys($typeOrder, ['count' => 0, 'weighted' => 0.0]),
                ];
            }
            $count = (int)$row['count'];
            $options[$optId]['by_type'][$type]['count'] = $count;
            $options[$optId]['by_type'][$type]['weighted'] = $count * ($weights[$type] ?? 1.0);
        }

        // Aggregate raw and weighted totals per option
        $stmt = $this->db->prepare(
            "SELECT 
                po.id AS option_id,
                po.option_text,
                COUNT(*) AS raw_votes,
                SUM(CASE COALESCE(pv.voter_type, 'public')
                        WHEN 'expert' THEN ?
                        WHEN 'student' THEN ?
                        ELSE ?
                    END) AS weighted_votes
             FROM poll_votes pv
             JOIN poll_options po ON pv.option_id = po.id
             WHERE pv.poll_id = ?
             GROUP BY po.id, po.option_text
             ORDER BY po.id"
        );
        $stmt->bind_param("dddi", $weights['expert'], $weights['student'], $weights['public'], $poll_id);
        $stmt->execute();
        $aggRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($aggRows as $row) {
            $optId = (int)$row['option_id'];
            if (!isset($options[$optId])) {
                $options[$optId] = [
                    'id' => $optId,
                    'text' => $row['option_text'],
                    'raw_votes' => 0,
                    'weighted_votes' => 0.0,
                    'by_type' => array_fill_keys($typeOrder, ['count' => 0, 'weighted' => 0.0]),
                ];
            }
            $options[$optId]['raw_votes'] = (int)$row['raw_votes'];
            $options[$optId]['weighted_votes'] = round((float)$row['weighted_votes'], 2);
        }

        // Ensure options with zero votes still appear
        $allPollOptions = $this->getOptions($poll_id);
        foreach ($allPollOptions as $opt) {
            $optId = (int)$opt['id'];
            if (!isset($options[$optId])) {
                $options[$optId] = [
                    'id' => $optId,
                    'text' => $opt['option_text'],
                    'raw_votes' => 0,
                    'weighted_votes' => 0.0,
                    'by_type' => array_fill_keys($typeOrder, ['count' => 0, 'weighted' => 0.0]),
                ];
            }
        }

        // Finalize percentages
        foreach ($options as $optId => $data) {
            $options[$optId]['weighted_percentage'] = $weightedTotal > 0
                ? round(($data['weighted_votes'] / $weightedTotal) * 100, 1)
                : 0.0;
        }

        // Ensure deterministic ordering
        ksort($options);

        return [
            'weights' => $weights,
            'totals' => [
                'raw_total' => $rawTotal,
                'weighted_total' => round($weightedTotal, 2),
                'by_type' => $totalsByType,
                'weighted_by_type' => array_map(function ($val) {
                    return round($val, 2);
                }, $weightedByType),
            ],
            'options' => array_values($options),
        ];
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

    /**
     * Get historical snapshots of poll results over time
     * Returns an array of snapshots showing how votes evolved
     * 
     * @param int $poll_id The poll ID
     * @param int $numSnapshots Number of time points to generate (default 20)
     * @return array Array with 'snapshots' containing time-based data
     */
    public function getHistoricalSnapshots($poll_id, $numSnapshots = 20)
    {
        // Get poll info and options
        $poll = $this->getPollById($poll_id);
        if (!$poll) {
            return ['error' => 'Poll not found'];
        }

        $options = $this->getOptions($poll_id);

        // Get earliest and latest vote times
        $stmt = $this->db->prepare("
            SELECT 
                MIN(voted_at) as first_vote,
                MAX(voted_at) as last_vote,
                COUNT(*) as total_votes
            FROM poll_votes 
            WHERE poll_id = ?
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $timeRange = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$timeRange || !$timeRange['first_vote'] || $timeRange['total_votes'] == 0) {
            return [
                'poll' => [
                    'id' => $poll['id'],
                    'question' => $poll['question']
                ],
                'options' => array_map(function ($opt) {
                    return [
                        'id' => $opt['id'],
                        'text' => $opt['option_text']
                    ];
                }, $options),
                'snapshots' => [],
                'message' => 'No votes yet'
            ];
        }

        $firstVote = strtotime($timeRange['first_vote']);
        $lastVote = strtotime($timeRange['last_vote']);

        // If all votes happened at same time, create single snapshot
        if ($firstVote == $lastVote) {
            $numSnapshots = 1;
        }

        $snapshots = [];
        $timeInterval = ($lastVote - $firstVote) / max(($numSnapshots - 1), 1);

        // Generate snapshots
        for ($i = 0; $i < $numSnapshots; $i++) {
            $snapshotTime = $firstVote + ($timeInterval * $i);
            $snapshotDate = date('Y-m-d H:i:s', $snapshotTime);

            // Get votes up to this point in time
            $stmt = $this->db->prepare("
                SELECT 
                    po.id as option_id,
                    po.option_text,
                    COUNT(*) as votes
                FROM poll_votes pv
                JOIN poll_options po ON pv.option_id = po.id
                WHERE pv.poll_id = ? AND pv.voted_at <= ?
                GROUP BY po.id, po.option_text
                ORDER BY po.id
            ");
            $stmt->bind_param("is", $poll_id, $snapshotDate);
            $stmt->execute();
            $votesAtTime = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Create a map of votes by option
            $voteMap = [];
            $totalVotes = 0;
            foreach ($votesAtTime as $vote) {
                $voteMap[$vote['option_id']] = (int)$vote['votes'];
                $totalVotes += (int)$vote['votes'];
            }

            // Build snapshot with all options (including those with 0 votes)
            $snapshotOptions = [];
            foreach ($options as $opt) {
                $votes = $voteMap[$opt['id']] ?? 0;
                $percentage = $totalVotes > 0 ? round(($votes / $totalVotes) * 100, 1) : 0;

                $snapshotOptions[] = [
                    'id' => $opt['id'],
                    'text' => $opt['option_text'],
                    'votes' => $votes,
                    'percentage' => $percentage
                ];
            }

            $snapshots[] = [
                'timestamp' => $snapshotTime,
                'datetime' => $snapshotDate,
                'formatted_time' => date('M j, Y g:i A', $snapshotTime),
                'total_votes' => $totalVotes,
                'options' => $snapshotOptions
            ];
        }

        return [
            'poll' => [
                'id' => $poll['id'],
                'question' => $poll['question'],
                'allow_multiple' => (bool)$poll['allow_multiple']
            ],
            'time_range' => [
                'start' => date('Y-m-d H:i:s', $firstVote),
                'end' => date('Y-m-d H:i:s', $lastVote),
                'duration_seconds' => $lastVote - $firstVote
            ],
            'snapshots' => $snapshots
        ];
    }

    // ========== Duet Polls Methods ==========

    /**
     * Create a duet poll with collaboration invitation
     * @param string $question Poll question
     * @param array $options Array of option texts
     * @param int $creator1_id First creator/admin ID
     * @param int $creator2_id Second creator/admin ID (collaborator)
     * @param bool $allow_multiple Allow multiple choice
     * @param string $category Poll category
     * @param string|null $location_tag Location tag
     * @param string|null $collaboration_notes Optional notes about collaboration
     * @return int|false Poll ID on success, false on failure
     */
    public function createDuetPoll($question, $options, $creator1_id, $creator2_id, $allow_multiple = 0, $category = 'General', $location_tag = null, $collaboration_notes = null)
    {
        // Validate that creator IDs are different
        if ($creator1_id === $creator2_id) {
            return false;
        }

        $this->db->begin_transaction();
        try {
            // Create the poll
            $is_duet = 1;
            $stmt = $this->db->prepare("INSERT INTO polls (question, is_active, allow_multiple, is_duet, category, location_tag) VALUES (?, 1, ?, ?, ?, ?)");
            $stmt->bind_param("siiss", $question, $allow_multiple, $is_duet, $category, $location_tag);
            $stmt->execute();
            $poll_id = $this->db->insert_id;
            $stmt->close();

            // Insert options
            foreach ($options as $opt_text) {
                $stmt = $this->db->prepare("INSERT INTO poll_options (poll_id, option_text, votes) VALUES (?, ?, 0)");
                $stmt->bind_param("is", $poll_id, $opt_text);
                $stmt->execute();
                $stmt->close();
            }

            // Create duet poll record
            $stmt = $this->db->prepare("INSERT INTO duet_polls (poll_id, creator1_id, creator2_id, invitation_status, collaboration_notes) VALUES (?, ?, ?, 'pending', ?)");
            $stmt->bind_param("iiis", $poll_id, $creator1_id, $creator2_id, $collaboration_notes);
            $stmt->execute();
            $duet_poll_id = $this->db->insert_id;
            $stmt->close();

            // Log activity
            $activity_desc = "Duet poll created and invitation sent to collaborator";
            $stmt = $this->db->prepare("INSERT INTO duet_poll_activity (duet_poll_id, admin_id, activity_type, activity_description) VALUES (?, ?, 'created', ?)");
            $stmt->bind_param("iis", $duet_poll_id, $creator1_id, $activity_desc);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return $poll_id;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Get duet poll information by poll ID
     */
    public function getDuetPollInfo($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT dp.*, 
                   a1.username as creator1_username,
                   a2.username as creator2_username,
                   p.question, p.is_active, p.category
            FROM duet_polls dp
            JOIN admins a1 ON dp.creator1_id = a1.id
            JOIN admins a2 ON dp.creator2_id = a2.id
            JOIN polls p ON dp.poll_id = p.id
            WHERE dp.poll_id = ?
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result ?: null;
    }

    /**
     * Accept duet poll invitation
     */
    public function acceptDuetInvitation($poll_id, $admin_id)
    {
        $this->db->begin_transaction();
        try {
            // Update invitation status
            $stmt = $this->db->prepare("
                UPDATE duet_polls 
                SET invitation_status = 'accepted', invitation_responded_at = NOW() 
                WHERE poll_id = ? AND creator2_id = ?
            ");
            $stmt->bind_param("ii", $poll_id, $admin_id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected === 0) {
                $this->db->rollback();
                return false;
            }

            // Get duet poll ID for activity logging
            $stmt = $this->db->prepare("SELECT id FROM duet_polls WHERE poll_id = ?");
            $stmt->bind_param("i", $poll_id);
            $stmt->execute();
            $duet_poll_id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
            $stmt->close();

            if ($duet_poll_id) {
                $activity_desc = "Collaboration invitation accepted";
                $stmt = $this->db->prepare("INSERT INTO duet_poll_activity (duet_poll_id, admin_id, activity_type, activity_description) VALUES (?, ?, 'accepted', ?)");
                $stmt->bind_param("iis", $duet_poll_id, $admin_id, $activity_desc);
                $stmt->execute();
                $stmt->close();
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Decline duet poll invitation
     */
    public function declineDuetInvitation($poll_id, $admin_id)
    {
        $stmt = $this->db->prepare("
            UPDATE duet_polls 
            SET invitation_status = 'declined', invitation_responded_at = NOW() 
            WHERE poll_id = ? AND creator2_id = ?
        ");
        $stmt->bind_param("ii", $poll_id, $admin_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $affected > 0;
    }

    /**
     * Check if admin has access to duet poll (is either creator)
     */
    public function adminHasDuetAccess($poll_id, $admin_id)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM duet_polls 
            WHERE poll_id = ? AND (creator1_id = ? OR creator2_id = ?)
        ");
        $stmt->bind_param("iii", $poll_id, $admin_id, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ($result['count'] ?? 0) > 0;
    }

    /**
     * Get all duet polls for a specific admin (either as creator1 or creator2)
     */
    public function getDuetPollsForAdmin($admin_id)
    {
        $stmt = $this->db->prepare("
            SELECT dp.*, 
                   p.question, p.is_active, p.category, p.created_at as poll_created_at,
                   a1.username as creator1_username,
                   a2.username as creator2_username
            FROM duet_polls dp
            JOIN polls p ON dp.poll_id = p.id
            JOIN admins a1 ON dp.creator1_id = a1.id
            JOIN admins a2 ON dp.creator2_id = a2.id
            WHERE dp.creator1_id = ? OR dp.creator2_id = ?
            ORDER BY dp.created_at DESC
        ");
        $stmt->bind_param("ii", $admin_id, $admin_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result ?: [];
    }

    /**
     * Get pending duet poll invitations for an admin
     */
    public function getPendingDuetInvitations($admin_id)
    {
        $stmt = $this->db->prepare("
            SELECT dp.*, 
                   p.question, p.category, p.created_at as poll_created_at,
                   a1.username as creator1_username
            FROM duet_polls dp
            JOIN polls p ON dp.poll_id = p.id
            JOIN admins a1 ON dp.creator1_id = a1.id
            WHERE dp.creator2_id = ? AND dp.invitation_status = 'pending'
            ORDER BY dp.invitation_sent_at DESC
        ");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result ?: [];
    }

    /**
     * Get activity log for a duet poll
     */
    public function getDuetPollActivity($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT dpa.*, a.username
            FROM duet_poll_activity dpa
            JOIN admins a ON dpa.admin_id = a.id
            WHERE dpa.duet_poll_id = (SELECT id FROM duet_polls WHERE poll_id = ?)
            ORDER BY dpa.created_at DESC
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result ?: [];
    }

    /**
     * Get all admins (for selecting collaborators)
     */
    public function getAllAdmins()
    {
        $sql = "SELECT id, username, created_at FROM admins ORDER BY username ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Log activity for a duet poll
     */
    public function logDuetActivity($poll_id, $admin_id, $activity_type, $activity_description = null)
    {
        $stmt = $this->db->prepare("SELECT id FROM duet_polls WHERE poll_id = ?");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $duet_poll_id = $stmt->get_result()->fetch_assoc()['id'] ?? null;
        $stmt->close();

        if (!$duet_poll_id) {
            return false;
        }

        $stmt = $this->db->prepare("INSERT INTO duet_poll_activity (duet_poll_id, admin_id, activity_type, activity_description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $duet_poll_id, $admin_id, $activity_type, $activity_description);
        $stmt->execute();
        $success = $stmt->affected_rows > 0;
        $stmt->close();
        return $success;
    }
}
