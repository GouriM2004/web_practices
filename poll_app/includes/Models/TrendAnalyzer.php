<?php
// includes/Models/TrendAnalyzer.php
require_once __DIR__ . '/../Database.php';

class TrendAnalyzer
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Analyze trends for all options in a poll
     * Returns array with trend data for each option
     */
    public function analyzePollTrends($poll_id)
    {
        $options = $this->getOptionVoteHistory($poll_id);
        $trends = [];

        foreach ($options as $option_id => $voteHistory) {
            $trends[$option_id] = $this->analyzeOptionTrend($voteHistory);
        }

        return $trends;
    }

    /**
     * Get vote history for all options in a poll
     * Returns array keyed by option_id with timestamps
     */
    private function getOptionVoteHistory($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                option_id, 
                voted_at,
                COUNT(*) as votes_at_time
            FROM poll_votes
            WHERE poll_id = ?
            GROUP BY option_id, HOUR(voted_at)
            ORDER BY option_id, voted_at ASC
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $history = [];
        foreach ($res as $row) {
            if (!isset($history[$row['option_id']])) {
                $history[$row['option_id']] = [];
            }
            $history[$row['option_id']][] = [
                'timestamp' => strtotime($row['voted_at']),
                'votes' => (int)$row['votes_at_time']
            ];
        }

        return $history;
    }

    /**
     * Analyze single option trend
     */
    private function analyzeOptionTrend($voteHistory)
    {
        if (empty($voteHistory)) {
            return [
                'trend' => 'stable',
                'direction' => '→',
                'change_percent' => 0,
                'status' => 'none',
                'spike_detected' => false,
                'decay_detected' => false,
                'momentum' => 0
            ];
        }

        // Sort by timestamp
        usort($voteHistory, function ($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        $trend = $this->detectTrend($voteHistory);
        $spike = $this->detectSpike($voteHistory);
        $decay = $this->detectDecay($voteHistory);
        $momentum = $this->calculateMomentum($voteHistory);
        $changePercent = $this->calculateChangePercent($voteHistory);

        return [
            'trend' => $trend,
            'direction' => $this->getTrendArrow($trend),
            'change_percent' => $changePercent,
            'status' => $this->getStatus($trend, $spike, $decay),
            'spike_detected' => $spike,
            'decay_detected' => $decay,
            'momentum' => $momentum,
            'history' => $voteHistory
        ];
    }

    /**
     * Detect if option is rising, falling, or stable
     */
    private function detectTrend($voteHistory)
    {
        if (count($voteHistory) < 2) {
            return 'stable';
        }

        $count = count($voteHistory);
        $midpoint = floor($count / 2);

        // Compare first half to second half
        $firstHalf = array_slice($voteHistory, 0, $midpoint);
        $secondHalf = array_slice($voteHistory, $midpoint);

        $firstHalfTotal = array_sum(array_column($firstHalf, 'votes'));
        $secondHalfTotal = array_sum(array_column($secondHalf, 'votes'));

        // Need at least 1 vote in first half to calculate
        if ($firstHalfTotal === 0) {
            return $secondHalfTotal > 0 ? 'rising' : 'stable';
        }

        $change = (($secondHalfTotal - $firstHalfTotal) / $firstHalfTotal) * 100;

        if ($change > 20) {
            return 'rising';
        } elseif ($change < -20) {
            return 'falling';
        } else {
            return 'stable';
        }
    }

    /**
     * Detect sudden vote spikes
     * A spike is when votes in one period exceed average by 2x or more
     */
    private function detectSpike($voteHistory)
    {
        if (count($voteHistory) < 3) {
            return false;
        }

        $votes = array_column($voteHistory, 'votes');
        $average = array_sum($votes) / count($votes);
        $maxVotes = max($votes);

        // Spike detected if any period has 2x or more than average votes
        return $maxVotes > ($average * 2);
    }

    /**
     * Detect vote decay (losing momentum)
     */
    private function detectDecay($voteHistory)
    {
        if (count($voteHistory) < 3) {
            return false;
        }

        $count = count($voteHistory);

        // Get last 3 periods
        $recentPeriods = array_slice($voteHistory, max(0, $count - 3));
        $recentVotes = array_column($recentPeriods, 'votes');

        // Check if voting activity is declining
        if (count($recentVotes) >= 2) {
            $lastVotes = $recentVotes[count($recentVotes) - 1];
            $secondLastVotes = $recentVotes[count($recentVotes) - 2];

            // Decay if last period has significantly fewer votes
            return $lastVotes < ($secondLastVotes * 0.5);
        }

        return false;
    }

    /**
     * Calculate momentum (rate of change)
     * -1 to 1 scale
     */
    private function calculateMomentum($voteHistory)
    {
        if (count($voteHistory) < 2) {
            return 0;
        }

        $count = count($voteHistory);
        $weights = [];

        // Recent votes weighted more heavily
        for ($i = 0; $i < $count; $i++) {
            $weights[$i] = ($i + 1) / $count;
        }

        $weightedSum = 0;
        $totalWeight = 0;
        $maxVote = max(array_column($voteHistory, 'votes'));

        if ($maxVote === 0) return 0;

        for ($i = 0; $i < $count; $i++) {
            $normalized = $voteHistory[$i]['votes'] / $maxVote;
            $weightedSum += $normalized * $weights[$i];
            $totalWeight += $weights[$i];
        }

        $momentum = ($weightedSum / $totalWeight) - 0.5;
        return min(1, max(-1, $momentum * 2));
    }

    /**
     * Calculate percentage change from first to last period
     */
    private function calculateChangePercent($voteHistory)
    {
        if (count($voteHistory) < 2) {
            return 0;
        }

        $first = $voteHistory[0]['votes'];
        $last = $voteHistory[count($voteHistory) - 1]['votes'];

        if ($first === 0) {
            return $last > 0 ? 100 : 0;
        }

        return round((($last - $first) / $first) * 100);
    }

    /**
     * Get trend arrow emoji
     */
    private function getTrendArrow($trend)
    {
        switch ($trend) {
            case 'rising':
                return '📈';
            case 'falling':
                return '📉';
            case 'stable':
            default:
                return '→';
        }
    }

    /**
     * Get status badge text
     */
    private function getStatus($trend, $spike, $decay)
    {
        if ($spike) {
            return 'spike';
        } elseif ($decay) {
            return 'decay';
        } elseif ($trend === 'rising') {
            return 'rising';
        } elseif ($trend === 'falling') {
            return 'falling';
        } else {
            return 'stable';
        }
    }

    /**
     * Get trend summary for entire poll
     */
    public function getPollTrendSummary($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT po.id, po.option_text, COUNT(pv.id) as total_votes
            FROM poll_options po
            LEFT JOIN poll_votes pv ON po.id = pv.option_id
            WHERE po.poll_id = ?
            GROUP BY po.id
            ORDER BY total_votes DESC
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $options = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $trends = $this->analyzePollTrends($poll_id);

        $summary = [
            'rising_options' => [],
            'falling_options' => [],
            'spike_alerts' => [],
            'decay_alerts' => [],
            'total_options' => count($options)
        ];

        foreach ($options as $opt) {
            $optionId = $opt['id'];
            $trendData = $trends[$optionId] ?? null;

            if (!$trendData) continue;

            $item = [
                'option_id' => $optionId,
                'option_text' => $opt['option_text'],
                'votes' => $opt['total_votes'],
                'trend_data' => $trendData
            ];

            if ($trendData['trend'] === 'rising') {
                $summary['rising_options'][] = $item;
            } elseif ($trendData['trend'] === 'falling') {
                $summary['falling_options'][] = $item;
            }

            if ($trendData['spike_detected']) {
                $summary['spike_alerts'][] = $item;
            }

            if ($trendData['decay_detected']) {
                $summary['decay_alerts'][] = $item;
            }
        }

        return $summary;
    }

    /**
     * Get trend data for a specific option
     */
    public function getOptionTrendData($option_id)
    {
        $stmt = $this->db->prepare("
            SELECT poll_id FROM poll_options WHERE id = ?
        ");
        $stmt->bind_param("i", $option_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$res) {
            return null;
        }

        $poll_id = $res['poll_id'];
        $history = $this->getOptionVoteHistory($poll_id);

        return $history[$option_id] ?? null;
    }
}
