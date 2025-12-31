<?php
// api_poll_battles.php
header('Content-Type: application/json');

session_start();
require_once __DIR__ . '/includes/bootstrap.php';

$pollBattle = new PollBattle();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'get_battle') {
        // Get active battle
        $battle = $pollBattle->getActiveBattle();
        if (!$battle) {
            http_response_code(404);
            echo json_encode(['error' => 'No active battle']);
            exit;
        }

        // Get vote counts in this battle
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM battle_votes WHERE battle_id = ? AND voted_for_id = ?");

        $stmt->bind_param("ii", $battle['id'], $battle['poll_a_id']);
        $stmt->execute();
        $votes_a = $stmt->get_result()->fetch_assoc()['count'];

        $stmt->bind_param("ii", $battle['id'], $battle['poll_b_id']);
        $stmt->execute();
        $votes_b = $stmt->get_result()->fetch_assoc()['count'];
        $stmt->close();

        echo json_encode([
            'success' => true,
            'battle' => [
                'id' => $battle['id'],
                'poll_a' => [
                    'id' => $battle['poll_a_id'],
                    'question' => $battle['poll_a_question'],
                    'votes' => $votes_a
                ],
                'poll_b' => [
                    'id' => $battle['poll_b_id'],
                    'question' => $battle['poll_b_question'],
                    'votes' => $votes_b
                ],
                'winner_id' => $battle['winner_id'],
                'margin_of_victory' => $battle['margin_of_victory'],
                'created_at' => $battle['created_at']
            ]
        ]);
    } elseif ($action === 'vote') {
        $battle_id = (int)($_POST['battle_id'] ?? 0);
        $poll_id = (int)($_POST['poll_id'] ?? 0);
        $voter_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $voter_id = isset($_SESSION['voter_id']) ? (int)$_SESSION['voter_id'] : null;

        if (!$battle_id || !$poll_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }

        $result = $pollBattle->recordBattleVote($battle_id, $poll_id, $voter_ip, $voter_id);

        if ($result) {
            // Get updated battle
            $battle = $pollBattle->getBattleById($battle_id);

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM battle_votes WHERE battle_id = ? AND voted_for_id = ?");

            $stmt->bind_param("ii", $battle_id, $battle['poll_a_id']);
            $stmt->execute();
            $votes_a = $stmt->get_result()->fetch_assoc()['count'];

            $stmt->bind_param("ii", $battle_id, $battle['poll_b_id']);
            $stmt->execute();
            $votes_b = $stmt->get_result()->fetch_assoc()['count'];
            $stmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Vote recorded',
                'votes_a' => $votes_a,
                'votes_b' => $votes_b,
                'winner_id' => $battle['winner_id'],
                'margin_of_victory' => $battle['margin_of_victory']
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'You have already voted in this battle']);
        }
    } elseif ($action === 'trending') {
        $trending = $pollBattle->getTrendingPolls(10);
        echo json_encode([
            'success' => true,
            'trending' => $trending
        ]);
    } elseif ($action === 'leaderboard') {
        $leaderboard = $pollBattle->getBattleLeaderboard(20);
        echo json_encode([
            'success' => true,
            'leaderboard' => $leaderboard
        ]);
    } elseif ($action === 'battle_history') {
        $history = $pollBattle->getBattleHistory(20);
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
