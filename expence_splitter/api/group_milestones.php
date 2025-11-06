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

// Find expenses that happened exactly one year ago (same date last year)
$db = Database::getConnection();
$stmt = $db->prepare('SELECT e.*, u.name as paid_by_name FROM expenses e LEFT JOIN users u ON e.paid_by = u.id WHERE e.group_id = ? AND DATE(e.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 YEAR)');
$stmt->bind_param('i', $group_id);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Also return any expenses on the same month-day in previous years
$stmt2 = $db->prepare("SELECT e.*, u.name as paid_by_name FROM expenses e LEFT JOIN users u ON e.paid_by = u.id WHERE e.group_id = ? AND DATE_FORMAT(e.created_at, '%m-%d') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 YEAR), '%m-%d') ORDER BY e.created_at DESC LIMIT 10");
$stmt2->bind_param('i', $group_id);
$stmt2->execute();
$rows2 = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

echo json_encode(['success' => true, 'one_year_ago' => $rows, 'same_day_previous_years' => $rows2]);
