<?php
require_once __DIR__ . '/../includes/autoload.php';
require_once __DIR__ . '/../includes/Config.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}
$group_id = intval($_GET['group_id'] ?? 0);
if ($group_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid group']);
    exit;
}

$ach = new Achievement();
$topPayers = $ach->getTopPayers($group_id, 5);
$mostActive = $ach->getMostActive($group_id, 5);

// also include per-user achievement counts for the group
$db = Database::getConnection();
$stmt = $db->prepare('SELECT ua.user_id, COUNT(*) as cnt FROM user_achievements ua WHERE ua.group_id = ? GROUP BY ua.user_id ORDER BY cnt DESC LIMIT 10');
$stmt->bind_param('i', $group_id);
$stmt->execute();
$achCounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'top_payers' => $topPayers, 'most_active' => $mostActive, 'achievement_counts' => $achCounts]);
